<?php
    require_once dirname(path: __DIR__) . '/Payment/autoload.php';
    require_once dirname(path: __DIR__) . '/NotificationSystem/autoload.php';
    
    use NokySQL\Database; // Connection à la base de données
    use NokySQL\Exceptions\QueryException; // Gestion d'Exceptions
    use NokySQL\QueryBuilder; // Constructeur de requêtes NokySQL
    use NotificationSystem\EventDispatcher;
    use NotificationSystem\OrderStatusChangedEvent;
    use NotificationSystem\Mailer;
    use Payment\PaymentFactory;

    /**
     * Classe représentant une commande et toutes les méthodes nécessaires à sa gestion 
     */
    class Order {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        /**
         * Crée une nouvelle commande à partir des produits du panier
         * @param array $data données de la commanndes 
         * @return array|null tableau des données contenant le numéro de la commande et l'URL du payment en cas de paiment en ligne ou null en cas d'échec de création de la commande
         */
        public function createFromCart(Cart $cart, string $order_number, int $customer_id, string $payment_method): ?array {
            $this->db->beginTransaction();
            try {
                $total = $cart->calculateTotal();
                $this->db->insert(table: 'tbl_orders')->set(data: [
                    'order_num'      => $order_number,
                    'customer_id'    => $customer_id,
                    'total'          => $total,
                    'payment_method' => $payment_method
                ])->execute();
                $orderId = $this->db->lastInsertId();

                foreach ($cart->getContents() as $item) {
                    $this->addOrderItem(order_id: $orderId, item: $item);
                    $this->updateInventory(product_id: $item['id'], quantity: $item['quantity']);
                }

                $paymentUrl = $this->initPayment(order_id: $orderId, amount: $total, method: $payment_method);
                $this->db->commit();
                return [
                    'orderId'    => $this->getOrderDetails(orderid: $orderId)['order_num'],
                    'paymentUrl' => $paymentUrl
                ];
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log(message: 'Erreur création commande: ' . $e->getMessage());
                return null;
            }
        }

        /**
         * Met à jour le status d'une commande selon les cas
         * @param int $order_id identifiant unique de la commande 
         * @param string $new_status nouveau statut de la commande 
         * @param string $notes note accompagnant le changement de statut (facultatif)
         * @throws \InvalidArgumentException
         * @return bool retourne une valeur booléenne `true` ou `false` selon que la mise à jour aura réussi ou pas
         */
        public function updateStatus(int $order_id, string $new_status, string $notes = ''): bool {
            $allowedStatuses = ['processing', 'delivered', 'cancelled'];
            if (!in_array(needle: $new_status, haystack: $allowedStatuses)) {
                throw new InvalidArgumentException(message: "Statut invalide");
            }

            $orderInfos = $this->getOrderDetails(orderid: $order_id)[0];
            
            if ($orderInfos['status'] === 'cancelled') {
                throw new InvalidArgumentException(message: "Commande déjà annulée");
            }

            if ($orderInfos['status'] === 'delivered') {
                throw new InvalidArgumentException(message: "Commande déjà delivrée");
            }

            $this->db->beginTransaction();
            try {
                $previous_status = $this->db->select(table: 'tbl_orders')->select(columns: ['status'])->where(condition: 'id = ?', params: [$order_id])->first()['status'];
                $this->db->update(table: 'tbl_orders')->set(data: [
                    'status'      => $new_status,
                    'status_note' => $notes
                ])->where(condition: 'id = ?', params: [$order_id])->execute();
                $this->logStatusChange(ordernum: $$orderInfos['order_num'], newstatus: $new_status);
                $this->db->commit();
                $orderEmail = $orderInfos['email'];
                $dispatcher = EventDispatcher::getInstance();
                $dispatcher->dispatch(
                    event: new OrderStatusChangedEvent(
                        orderId: $orderInfos['order_num'], 
                        oldStatus: $previous_status, 
                        newStatus: $new_status, 
                        customerEmail: $orderEmail
                    )
                );
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log(message: 'Erreur mise à jour commande: ' . $e->getMessage());
                return false;
            }
        }

        /**
         * Reécupère les détails d'une commande
         * @return array|null détails de la commande si elle existe sinon nul
         */
        public function getOrderDetails(int $orderid): ?array {
            try {
                $order = $this->db->select(table: 'tbl_orders o')->select(columns: [
                    'o.*',
                    'o.status',
                    'COUNT(oi.id) AS items_count',
                    'SUM(oi.quantity) AS total_quantity',
                    'c.cust_email as email',
                    'c.cust_first_name',
                    'c.cust_last_name'
                ])
                ->join(table: 'tbl_customers c', condition: 'o.customer_id = c.cust_id')
                ->join(table: 'tbl_order_items oi', condition: 'o.id = oi.order_id', type: 'LEFT')
                ->where(condition: 'o.id = ?', params: [$orderid])
                ->groupBy(columns: 'o.id')
                ->execute();
                if ($order) {
                    $order['items']         = $this->getOrderItems(orderid: $orderid);
                    $order['timeline']      = $this->getOrderTimeline(orderid: $orderid);
                    $order['payment_infos'] = $this->getPaymentDetails(order_id: $orderid);
                }
                return $order;
            } catch (QueryException $qe) {
                error_log(message: "Erreur récupération de la commande: " . $qe->getDebugInfo()['sql']);
                return null;
            }
        }

        /**
         * Récupère et retourne toutes les commandes d'un utilisateur et les détails des produits de chaque commande
         * @param int $customer_id identifiant du client 
         * @throws \InvalidArgumentException
         * @return array tableau contenant les commandes du client
         */
        public static function getCustomerOrders(Database $db, int $customer_id): array {
            if (empty($customer_id)) {
                throw new InvalidArgumentException(message: "Identifiant manquant pour recuperer les commandes clients");
            }

            $limit = 10;
            $offset = 0;
            // Requête principale pour la récupération des commandes utilisateur
            $subQuery = $db->select(table: 'tbl_orders')->select(columns: ['id'])->where(condition: 'customer_id = ?', params: [$customer_id])->orderBy(column: 'created_at', direction: 'DESC')->limit(limit: $limit)->offset(offset: $offset);
            $orders = $db->select(table: 'tbl_orders o')->select(columns: [
                'o.id as order_id',
                'o.status as order_status',
                'o.total as order_total',
                'o.created_at as created_at',
                'p.p_id as product_id',
                'p.p_name as product_name',
                'oi.quantity',
                'oi.unit_price',
                'pay.id as payment_id',
                'pay.transaction_id as transaction_id',
                'pay.payment_method as payment_method',
                'pay.status as payment_status',
                'pay.amount as amount_paid',
                'pay.created_at as payment_date'
            ]) // Sous-requète pour ajouter les données associées venant d'autres tables
            ->joinSub(subquery: $subQuery, alias: 'sq', first: 'o.id', operator: '=', second: 'sq.id')
            ->join('tbl_order_items oi', 'oi.order_id = o.id')
            ->join('tbl_products p', 'p.p_id = oi.product_id')
            ->join('tbl_payments pay', 'pay.order_id = o.id', 'LEFT')
            ->orderBy('o.created_at', 'DESC')
            ->orderBy('oi.id')->execute();
            $customerOrders = [];
            foreach($orders as $order) {
                $oid = $order['order_id'];
                // Nouvelle commande trouvée
                if (!isset($customerOrders[$oid])) {
                    $customerOrders[$oid] = [
                        'id'             => $oid,
                        'status'         => $order['order_status'],
                        'total'          => $order['order_total'],
                        'date'           => $order['created_at'],
                        'payment_method' => $order['payment_method'],
                        'payment_status' => $order['payment_status'],
                        'amount_paid'    => $order['amount_paid'],
                        'payment_date'   => $order['payment_date'],
                        'payment_id'     => $order['payment_id'],
                        'transaction_id' => $order['transaction_id'],
                        'products'       => []
                    ];
                }
                // Ajout des produits de la commande à la liste des produits de celle-ci 
                $customerOrders[$oid]['products'] = [
                    'id'         => $order['product_id'],
                    'name'       => $order['product_name'],
                    'quantity'   => $order['quantity'],
                    'unit_price' => $order['unit_price']
                ];
            }
            // Ré-indexation numérique des commandes 
            return array_values(array: $customerOrders);
        }

        /**
         * Retourne toute les commandes
         * @return array|bool
         */
        public static function getAllOrders(Database $db): array {
            // Requête principale pour la récupération des commandes utilisateur
            $subQuery = $db->select(table: 'tbl_orders')->select(columns: ['id'])->orderBy(column: 'created_at', direction: 'DESC');
            return $db->select(table: 'tbl_orders o')->select(columns: [
                'o.id as order_id',
                'o.order_num',
                'o.status as order_status',
                'o.total as order_total',
                'o.created_at as created_at',
                'p.p_id as product_id',
                'p.p_name as product_name',
                'oi.quantity',
                'oi.unit_price',
                'pay.id as payment_id',
                'pay.transaction_id as transaction_id',
                'pay.payment_method as payment_method',
                'pay.status as payment_status',
                'pay.amount as amount_paid',
                'pay.created_at as payment_date',
                'c.cust_first_name as firstname',
                'c.cust_last_name as lastname',
                'c.cust_email as email',
                'c.cust_phone as phone'
            ]) // Sous-requète pour ajouter les données associées venant d'autres tables
            ->joinSub(subquery: $subQuery, alias: 'sq', first: 'o.id', operator: '=', second: 'sq.id')
            ->join('tbl_order_items oi', 'oi.order_id = o.id')
            ->join('tbl_products p', 'p.p_id = oi.product_id')
            ->join('tbl_payments pay', 'pay.order_id = o.id', 'LEFT')
            ->join('tbl_customers c', 'c.cust_id = o.customer_id', 'LEFT')
            ->orderBy('o.created_at', 'DESC')
            ->orderBy('oi.id')->execute();
        }

        /**
         * Analyse les commandes avec des filtres avancés
         * @param array $filters les filtres servant d'analyse
         * @return array
         */
        public function analyzeOrders(array $filters = []): array {
            $this->validateFilters(filters: $filters);
            $results = $this->buildAnalysisQuery(filters: $filters)->execute();
            return $this->formatAnalysisResults(data: $results, groupBy: $filters['group_by'] ?? 'day');
        }

        /**
         * Annule une commande et toutes les données associées à celle-ci présentes dans d'autres tables
         * @param int $requested_id identifiant de la commande à supprimer 
         * @throws \InvalidArgumentException
         * @return bool
         */
        public function cancel(int $order_id, string $cancellation_note = ''): bool {
            if (empty($order_id)) {
                throw new Exception(message: 'Identifiant manquant pour supprimer la commande');
            }

            $orderInfos = $this->getOrderDetails(orderid: $order_id);
            if (!isNonEmptyArray(arrayData: $orderInfos) OR ($orderInfos[0]['status'] ===  'cancelled')) {
                throw new Exception(message: "Commande introuvable");
            }

            if (!in_array(needle: $orderInfos[0]['status'], haystack: ['pending', 'processing'])) {
                throw new Exception(message: "Commande non annulable à ce statge");
            }

            $this->db->beginTransaction();

            try {
                foreach($orderInfos['items'] as $item) {
                    $this->restockProduct(product_id: $item['product_id'], quantity: $item['quantity']);
                }

                $this->updateStatus(order_id: $order_id, new_status: 'cancelled', notes: $cancellation_note);

                $this->logStatusChange(ordernum: $$orderInfos[0]['order_num'], newstatus: 'cancelled');

                $paymentInfos = $this->getPaymentDetails(order_id: $order_id);
                if ($paymentInfos && $paymentInfos['status'] === 'paid' && $paymentInfos['payment_method'] !== 'cash') {
                    $this->refundPayment(payment_id: $paymentInfos['transaction_id'], amount: $paymentInfos['amount']);
                }

                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur annulation commande: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Vérifie le paiment d'une commande
         * @param int $order_id identifiant de la commande 
         * @throws \Exception
         * @return array
         */
        public function verifyPayment(int $order_id): array {
            $paymentInfos = $this->getPaymentDetails(order_id: $order_id);
            if (!$paymentInfos) {
                throw new Exception(message: "Pas d'informations pour cette commande");
            }

            $handler = PaymentFactory::createHandler(method: $paymentInfos['payment_method']);
            return $handler->getPaymentDetails(transaction_id: $paymentInfos['payment_reference']);
        }

        /**
         * Restocke le produit après annulation d'une commande ou autre utilisation nécessitant un restockage
         * @param int $product_id identifiant du produit à restocker
         * @param int $quantity quantité à restocker
         * @return void
         */
        private function restockProduct(int $product_id, int $quantity): void {
            $this->db->update(table: 'tbl_products')->increment(column: 'p_qty', value: $quantity)->where(condition: 'p_id = ?', params: [$product_id])->execute();
        }

        /**
         * Rembourse un paiement si possible
         * @param int $payment_id identifiant du paiment
         * @param float $amount montant à rembourser
         * @throws \Exception
         * @return void
         */
        private function refundPayment(int $payment_id, float $amount): void {
            $payment = $this->db->select(table: 'tbl_payments')->where(condition: 'id = ?', params: [$payment_id])->first();
            if (!$payment) {
                throw new Exception(message: 'Paiement introuvable');
            }

            $handler = PaymentFactory::createHandler(method: $payment['payment_method']);
            $handler->refund(transaction_id: $payment['payment_reference'], amount: $amount);
            $this->db->update(table: 'tbl_payments')->set(data: ['status' => 'refunded'])->where(condition: 'id = ?', params: [$payment_id])->execute();
        }

        /**
         * Initialise le processus d'un paiement pour une commande
         * @param int $order_id identifiant de la commande 
         * @param float $amount montant à payer 
         * @param string $method méthode de paiement
         * @return string|null retourne une URL en cas de paiement ou null dans autres cas
         */
        private function initPayment(int $order_id, float $amount, string $method): mixed {
            $paymentHandler = PaymentFactory::createHandler(method: $method);
            return $paymentHandler->initializePayment(order_id: $order_id, amount: $amount);
        }

        /**
         * Ajoute les produits à une commande 
         * @param int $order_id identifiant du produit
         * @param array $item tableau de détails du produit
         * @throws \InvalidArgumentException
         * @return void
         */
        private function addOrderItem(int $order_id, array $item): void {
            if (empty($order_id) || empty($item)) {
                throw new InvalidArgumentException(message: "Identifiant de la commande ou données manquantes");
            }
            try {
                $this->db->insert(table: 'tbl_order_items')->set(data: [
                    'order_id'   => $order_id,
                    'product_id' => $item['id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['price']
                ])->execute();
            } catch (Exception $e) {
                error_log(message: 'Erreur insertion du produit dans la commande: ' . $e->getMessage());
            }
        }

        /**
         * Met à jour le stock du produit 
         * @param int $product_id identifiant du produit à mettre à jour le stock
         * @param mixed $quantity quantité à retrancher du stock
         * @throws \InvalidArgumentException
         * @throws \Exception
         * @return void
         */
        private function updateInventory(int $product_id, $quantity): void {
            if (empty($product_id)) {
                throw new InvalidArgumentException(message: "Identifiant du produit manquant pour la mise à jour stock");
            }
            
            try {
                if ($this->db->select(table: 'tbl_products')->select(columns: ['p_qty'])->where(condition: 'p_id = ?', params: [$product_id])->first()['p_qty'] >= $quantity) {
                    $this->db->update(table: 'tbl_products')->decrement(column: 'p_qty', value: $quantity)->where(condition: 'p_id = ?', params: [$product_id])->execute();
                } else {
                    throw new Exception(message: "Stock insuffisant pour le prouit $product_id");
                }
            } catch (Exception $e) {
                error_log(message: 'Erreur Mise à jour inventaire: ' . $e->getMessage());
            }
        }

        /**
         * Rrtourne les détails d'un paiement éffectué
         * @param int $order_id identifiant de la commande concernée par le paiement 
         * @return array retourne un tableau contenant le resultat
         */
        private function getPaymentDetails(int $order_id): array {
            $payment = $this->db->select(table: 'tbl_orders o')->select(columns: ['o.payment_method', 'pay.transaction_id', 'pay.payment_reference', 'pay.status'])->join(table: 'tbl_payments pay', condition: 'o.id = pay.order_id')->where(condition: 'order_id = ?', params: [$order_id])->first();
            if (!$payment) {
                return [
                    'status'         => 'not_paid',
                    'method'         => null,
                    'transaction_id' => null
                ];
            }

            return [
                'status'         => $payment['status'],
                'payment_method' => $payment['payment_method'],
                'transaction_id' => $payment['transaction_id']
            ];
        }

        /**
         * Frormate les données de l'analyse afin de les rendre plus lisible et facilement manipulable
         * @param array $data les données de l'analyse
         * @param string $groupBy les filtre utilisés pour le regroupement 
         * @return array{data: array, meta: array}
         */
        private function formatAnalysisResults(array $data, string $groupBy): array {
            $formatted = [];
            $totals = [
                'order_count'   => 0,
                'total_revenue' => 0,
                'items_sold'    => 0
            ];
        
            foreach ($data as $row) {
                $periodLabel = $this->formatPeriodLabel(period: $row['period'], groupby: $groupBy);
                $entry = [
                    'period'     => $periodLabel,
                    'orders'     => (int)$row['order_count'],
                    'revenue'    => round(num: $row['total_revenue'], precision: 2),
                    'avg_order'  => round(num: $row['avg_order_value'], precision: 2),
                    'customers'  => (int)$row['unique_customers'],
                    'items_sold' => (int)$row['items_sold']
                ];
                $formatted[] = $entry;
                $this->accumulateTotals(totals: $totals, row: $row);
            }
        
            return [
                'data' => $formatted,
                'meta' => $this->buildMetaData(totals: $totals)
            ];
        }

        /**
         * Accumule les totaux des commandes durant la période séléctionée
         * @param array $totals les totaux des commandes
         * @param array $row les valeurs correspondantes
         * @return void
         */
        private function accumulateTotals(array &$totals, array $row): void {
            $totals['order_count']   += (int)$row['order_count'];
            $totals['total_revenue'] += (float)$row['total_revenue'];
            $totals['items_sold']    += (int)$row['items_sold'];
        }

        /**
         * Construit les métadonnées d'une analyse de commande
         * @param array $totals
         * @return array
         */
        private function buildMetaData(array $totals): array {
            return [
                'total' => $totals,
                'avg_revenue_per_order' => $totals['order_count'] > 0 
                    ? round(num: $totals['total_revenue'] / $totals['order_count'], precision: 2)
                    : 0
            ];
        }

        /**
         * Formate la période d'analyse en fonction des filtres
         * @param string $period période d'analyse
         * @param string $groupby filtres du groupement des données
         * @return string
         */
        private function formatPeriodLabel(string $period, string $groupby): string {
            return match($groupby) {
                'week'  => "Semaine " . substr(string: $period, offset: 5) . " " . substr(string: $period, offset: 0, length: 4),
                'month' => DateTime::createFromFormat(format: 'Y-m', datetime: $period)->format(format: 'F Y'),
                default => $period
            };
        }

        /**
         * Construit la requête de la récupération de données nécessaires à l'analyse de la commande 
         * @param array $filters 
         * @return NokySQL\QueryBuilder retourne un requête du type `QueryBuilder`, pour plus dinfos allez sur https://github.com/jozephkabongo/nokysql
         */
        private function buildAnalysisQuery(array $filters): QueryBuilder {
            $groupBy = $this->getGroupByExpression(groupBy: $filters['group_by'] ?? 'day'); // récupération des filtres utilisés pour regrouper les donées de l'analyse
            $query = $this->db->select(table: 'tbl_orders o')
                ->select(columns: [
                    "$groupBy AS period",
                    'COUNT(o.id) AS order_count',
                    'SUM(o.total) AS total_revenue',
                    'AVG(o.total) AS avg_order_value',
                    'COUNT(DISTINCT o.user_id) AS unique_customers',
                    'SUM(oi.quantity) AS items_sold'
                ])
                ->join(table: 'tbl_order_items oi', condition: 'o.id = oi.order_id', type: 'INNER')
                ->join(table: 'tbl_articles a', condition: 'oi.article_id = a.id', type: 'LEFT')
                ->where(condition: 'o.created_at BETWEEN ? AND ?', params: [
                    $filters['start_date'], 
                    $filters['end_date']
                ])
                ->groupBy(columns: 'period')
                ->orderBy('period');

            if (!empty($filters['min_amount'])) {
                $query->where('o.total >= ?', [(float)$filters['min_amount']]);
            }
        
            if (!empty($filters['category_id'])) {
                $query->where('a.category_id = ?', [(int)$filters['category_id']]);
            }
            return $query;
        }

        /**
         * Retourne l'expression du filtre correspondant à celui demandé
         * @param string $groupBy filtre demandé
         * @return string
         */
        private function getGroupByExpression(string $groupBy): string {
            return match ($groupBy) {
                'week'  => "DATE_FORMAT(o.created_at, '%Y-%u')",
                'month' => "DATE_FORMAT(o.created_at, '%Y-%m')",
                default => "DATE(o.created_at)"
            };
        }

        /**
         * Vérifie qu'un filtre est valide
         * @param array $filters les filtres à vérifier
         * @throws \InvalidArgumentException
         * @return void
         */
        private function validateFilters(array &$filters): void {
            if (empty($filters['start_date'])) { 
                // par défaut 30 derniers jours
                $filters['start_date'] = date(format: 'Y-m-d', timestamp: strtotime(datetime: '-30 days'));
            }
        
            if (empty($filters['end_date'])) { 
                // si la date de fin d'analyse n'est pas précisée on récupère la date courante
                $filters['end_date'] = date(format: 'Y-m-d');
            }
        
            if (!strtotime(datetime: $filters['start_date']) || !strtotime(datetime: $filters['end_date'])) {
                throw new InvalidArgumentException(message: "Format de date invalide");
            }
        }

        /**
         * Retourne les articles (unique) de la commande
         * @return array|bool
         */
        private function getOrderItems(int $orderid): array|bool {
            return $this->db->select(table: "tbl_order_items oi")->select(columns: [
                'oi.*',
                'p.p_id',
                'p.p_name', 
                'p.p_sku'
            ])
            ->join(table: 'tbl_products p', condition: 'oi.product_id = p.p_id')
            ->where(condition: 'order_id = ?', params: [$orderid])
            ->execute();
        }

        /**
         * Enregistre l'historique des commandes
         * @param string $newstatus nouveau statut de la commande 
         * @return void
         */
        private function logStatusChange(string $ordernum, string $newstatus): void {
            try {
                $this->db->insert(table: 'tbl_order_histories')->set(data: [
                    'order_num' => $ordernum,
                    'status'    => $newstatus
                ])->execute();
            } catch (Exception $e) {
                error_log(message: 'Erreur historique de commande: ' . $e->getMessage());
            }
        }

        /**
         * Retourne l'historique d'une commande 
         * @return array|bool
         */
        private function getOrderTimeline(int $orderid): array|bool {
            return $this->db->select(table: 'tbl_order_histories')->where(condition: 'order_id = ?', params: [$orderid])->orderBy(column: 'changed_at', direction: 'DESC')->execute();
        }
    }