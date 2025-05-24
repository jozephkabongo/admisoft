<?php
    use NokySQL\Database; // constructeur des requêtes SQL
    use NokySQL\Exceptions\QueryException; // gestion d'exceptions

    /**
     * Classe Product représente un produit et permet la gestion individuelle de chaque produit
     */
    class Product {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        /**
         * Ajoute un nouveau produit dans le catalogue
         * @param array $data données du nouveau produit 
         * @throws \InvalidArgumentException exception levée en cas d'érreur
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function add(array $data): bool {
            $requiredFields = ['cat_id', 'seller_id', 'name', 'price', 'quantity', 'image_path'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException(message: "Champ manquant : $field ");
                }
            }
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_products')->set(data: [
                    'cat_id'        => $data['cat_id'],
                    'seller_id'     => $data['seller_id'],
                    'p_name'        => $data['name'],
                    'p_price'       => $data['price'],
                    'p_qty'         => $data['quantity'],
                    'p_image'       => $data['image_path'],
                    'p_description' => $data['description'] ?? '',
                    'p_feature'     => $data['features'] ?? ''
                ])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur lors de l'ajout du nouveau produit: " . $qe->getMessage());
                return false;
            }
        }
        
        /**
         * Met à jour les données d'un produit
         * @param array $data nouvelles données du produit à modifier, y compris son `identifant `
         * @throws \InvalidArgumentException exception levée en cas d'erreur 
         * @return bool retourne une valeur booléenne `true` si tout se passe bien, ou `false` dans tout les autres cas 
         */
        public function edit(array $data): bool {
            if (!isset($data['prod_id']) OR !isset($data['seller_id'])) {
                throw new InvalidArgumentException(message: "Identifiant du produit et du vendeur requis pour la mise à jour");
            }
            $this->db->beginTransaction();
            try {
                $requiredFields = ['cat_id', 'name', 'price','quantity', 'treshold', 'image_path', 'description', 'feature'];
                foreach ($requiredFields as $field) {
                    if (isset($data[$field])) {
                        switch ($data[$field]) {
                            case 'cat_id':
                                $this->db->update(table: 'tbl_products')->set(data: ['cat_id' => $data['cat_id']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;

                            case 'name':
                                $this->db->update(table: 'tbl_products')->set(data: ['p_name' => $data['name']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;

                            case 'price':
                                $this->db->update(table: 'tbl_products')->set(data: ['p_price' => $data['price']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;

                            case 'quantity':
                                $this->db->update(table: 'tbl_products')->set(data: ['p_qty' => $data['quantity']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;

                            case 'treshold':
                                $this->db->update(table: 'tbl_products')->set(data: ['p_treshold' => $data['treshold']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;

                            case 'image_path':
                                $this->db->update(table: 'tbl_products')->set(data: ['p_image' => $data['image_path']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;

                            case 'description': 
                                $this->db->update(table: 'tbl_products')->set(data: ['p_description' => $data['description']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;
                                
                            case 'feature':
                                $this->db->update(table: 'tbl_products')->set(data: ['p_feature' => $data['feature']])->where(condition: 'p_id = ? AND seller_id = ?', params: [$data['prod_id'], $data['seller_id']])->execute();
                                break;
                        }
                    } 
                }
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur mise à jour du produit: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Supprime un produit à partir de son identifiant
         * @param int $product_id identifiant du produit à supprimer
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas
         */
        public function delete(int $product_id, int $seller_id): bool {
            if (!isset($product_id) OR !isset($seller_id)) {
                throw new InvalidArgumentException(message: "Identifiant du produit et du vender requis pour la suppression");
            }
            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_products')->where(condition: 'p_id = ? AND seller_id = ?', params: [$product_id, $seller_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erruer suppression du produit: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne les informations d'un produit à partir de son identifiant
         * @param int $product_id identifiant du produit à chercher
         * @return array|null un tableau des données asscociées au produit correspondant à `$product_id` ou ``null`` si aucun produit ne correspond 
         */
        public static function get(Database $db, int $product_id, ?int $seller_id = null): ?array {
            if (empty($product_id)) {
                throw new Exception(message: "Identifint requis pour la récupération du produit");
            }

            if ($db->select(table: 'tbl_products')->where(condition: 'p_id = ?', params: [$product_id])->count() === 1) {
                if ($seller_id !== null) {
                    return $db->select(table: 'tbl_products p')->select(columns: [
                        'p.p_id as id',
                        'p.cat_id',
                        'p.seller_id',
                        'p.p_name as name',
                        'p.p_price as price',
                        'p.p_qty as stock',
                        'p_treshold as treshold',
                        'p.p_image as image',
                        'p.p_description as description',
                        'p.p_feature as feature',
                        'p.created_at as created_at',
                        'p.p_last_restock as last_restock',
                        'c.cat_id', 
                        'c.cat_name as category',
                        'AVG(r.rating) as average_rating',
                        'r.product_id',
                        'col.color_name as color',
                        'col.color_id',
                        'pc.product_id',
                        'pc.color_id'
                    ])
                    ->join(table: 'tbl_categories c', condition: 'p.cat_id = c.cat_id')
                    ->join(table: 'tbl_rating r', condition: 'p.p_id = r.product_id', type: 'LEFT')
                    ->join(table: 'tbl_product_color pc', condition: 'p.p_id = pc.product_id', type: 'LEFT')
                    ->join(table: 'tbl_colors col', condition: 'pc.color_id = col.color_id', type: 'LEFT')
                    ->where(condition: 'p_id = ? AND seller_id = ?', params: [$product_id, $seller_id])
                    ->first();
                } else {
                    return $db->select(table: 'tbl_products p')->select(columns: [
                        'p.p_id as id',
                        'p.cat_id',
                        'p.seller_id',
                        'p.p_name as name',
                        'p.p_price as price',
                        'p.p_qty as stock',
                        'p_treshold as treshold',
                        'p.p_image as image',
                        'p.p_description as description',
                        'p.p_feature as feature',
                        'p.created_at as created_at',
                        'p.p_last_restock as last_restock',
                        'c.cat_id', 
                        'c.cat_name as category',
                        'AVG(r.rating) as average_rating',
                        'r.product_id',
                        'col.color_name as color',
                        'col.color_id',
                        'pc.product_id',
                        'pc.color_id'
                    ])
                    ->join(table: 'tbl_categories c', condition: 'p.cat_id = c.cat_id')
                    ->join(table: 'tbl_rating r', condition: 'p.p_id = r.product_id', type: 'LEFT')
                    ->join(table: 'tbl_product_color pc', condition: 'p.p_id = pc.product_id', type: 'LEFT')
                    ->join(table: 'tbl_colors col', condition: 'pc.color_id = col.color_id', type: 'LEFT')
                    ->where(condition: 'p_id = ?', params: [$product_id])
                    ->first();
                }
            } else {
                return null;
            }
        }

        /**
         * Retourne tout les produits enregistrés
         * @return array tableau des tout les produits
         */
        public static function getAll(Database $db, ?int $seller_id = null): array {
            if ($seller_id !== null) {
                $result = $db->select(table: 'tbl_products p')->select(columns: [
                    'p.p_id',
                    'p.seller_id',
                    'p.cat_id',
                    'p.p_name',
                    'p.p_price',
                    'p.p_qty',
                    'p_treshold',
                    'p.p_image',
                    'p.p_description',
                    'p.p_feature',
                    'p.created_at',
                    'p.p_last_restock',
                    'c.cat_id', 
                    'c.cat_name'
                ])
                ->join(table: 'tbl_categories c', condition: 'p.cat_id = c.cat_id')
                ->where(condition: 'seller_id = ?', params: [$seller_id])
                ->orderBy(column: 'p.p_id', direction: 'DESC')
                ->execute();
                return Product::formateData(data: $result);
            } else {
                $result = $db->select(table: 'tbl_products p')->select(columns: [
                    'p.p_id',
                    'p.seller_id',
                    'p.cat_id',
                    'p.p_name',
                    'p.p_price',
                    'p.p_qty',
                    'p_treshold',
                    'p.p_image',
                    'p.p_description',
                    'p.p_feature',
                    'p.created_at',
                    'p.p_last_restock',
                    'c.cat_id', 
                    'c.cat_name'
                ])
                ->join(table: 'tbl_categories c', condition: 'p.cat_id = c.cat_id')
                ->orderBy(column: 'p.p_id', direction: 'DESC')
                ->execute();
                return Product::formateData(data: $result);
            }
        }

        /**
         * Formate les données reçues en les attribuant des nouvelles clés plus explicites
         * @param array $data tableau des données à formater
         * @return array retourne un tableau des données formatées
         */
        private static function formateData(array $data): array {
            $formatted = [];
            foreach ($data as $row) {
                $entry = [
                    'id'           => $row['p_id'],
                    'name'         => $row['p_name'],
                    'description'  => $row['p_description'],
                    'category'     => $row['cat_name'],
                    'price'        => $row['p_price'],
                    'quantity'     => $row['p_qty'],
                    'last_restock' => $row['p_last_restock'],
                    'image'        => $row['p_image'],
                    'treshold'     => $row['p_treshold'],
                    'feature'      => $row['p_feature'],
                    'created_at'   => $row['created_at']
                ];
                $formatted[] = $entry;
            }
            return $formatted;
        }

        /**
         * Récupère les produits selon une catégorie donnée
         * @param int $category_id catégorie des produits à recupérer
         * @throws \InvalidArgumentException
         * @return array|bool retourn le tableau des tout les produits correspondant à la catégorie si elle existe, ou ``false`` dans tout les autres cas
         */
        public static function getByCategory(Database $db, int $category_id): ?array {
            if (empty($category_id)) {
                throw new InvalidArgumentException(message: "Identifiant de la catégorie requis pour recupérer les produits selon leur catégorie");
            }

            return $db->select(table: 'tbl_products p')->select(columns: [
                'p.p_id as id',
                'p.cat_id',
                'p.seller_id',
                'p.p_name as name',
                'p.p_price as price',
                'p.p_qty as quantity',
                'p_treshold as treshold',
                'p.p_image as image',
                'p.p_description as description',
                'p.p_feature as feature',
                'p.created_at',
                'p.p_last_restock',
                'c.cat_id', 
                'c.cat_name as category'
            ])
            ->join(table: 'tbl_categories c', condition: 'p.cat_id = c.cat_id')
            ->where(condition: 'p.cat_id = ?', params: [$category_id])
            ->execute(); 
        }
    }