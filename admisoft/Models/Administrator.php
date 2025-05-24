<?php
    use NokySQL\Database; // constructeur des requêtes SQL
    use NokySQL\Exceptions\QueryException; // gestion d'exceptions

    /**
     * ``PASSWORD_ERROR`` est utilisé pour informé que le mot de passe saisi est incorrect
     * @var string
     */
    const PASSWORD_ERROR = 'Mot de passe incorrect';

    /**
     * ``NOT_FOUNDED_ACCOUNT`` est utilisé pour informé qu'aucun compte n'a été trouvé avec les informations renseignées (e-mail, mot de passe)
     * @var string
     */
    const NOT_FOUNDED_ACCOUNT = 'Aucun compte trouvé';

    /**
     * ``UNVERIFIED_ACCOUNT`` est utilisé pour informé qu'un compte n'a pas encore été vérifié ou activé par adresse mail
     * @var string
     */
    const UNVERIFIED_ACCOUNT = 'Adresse mail non verifie';

    /**
     * Classe Administrator représente un utilisateur administrateur dans le système
     */
    class Administrator {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        ///////////////////////////////////////////////////
        //                                               //
        //  Actions basiques des administrateurs simples //
        //                                               //
        ///////////////////////////////////////////////////
        
        /**
         * Vérifie si un utilisateur existe et le connecte à son compte
         * @param string $email adresse mail de l'utilisateur
         * @param string $password mot de passe de l'utilisateur
         * @throws \InvalidArgumentException exception levée en cas d'érreur
         * @return void ne retourne rien
         */
        public function login(string $email, string $password): array {

            if (empty($email) || empty($password)) {
                throw new InvalidArgumentException(message: "Email ou Mot de passe manquant pour la connexion au compte administrateur");           
            } 

            $adminInfos = $this->db->select(table: 'tbl_administrators')->where(condition: 'email = ? AND status = ?', params: [$email, 'active'])->first();
            if (isNonEmptyArray(arrayData: $adminInfos)) {
                if (password_verify(password: $password, hash: $adminInfos['password'])) {
                    return [
                        'adminInfos' => $adminInfos,
                        'success'    => true
                    ];
                } else {
                    // Erreur mot de passe
                    return ['error' => PASSWORD_ERROR];
                }
            } else {
                // Erreur compte non trouvé
                return ['error' => NOT_FOUNDED_ACCOUNT];
            }
        }

        /**
         * Retourne les données de l'administrateur à partir de son identifiant `$admin_id`
         * @param int $admin_id id de l'utilisateur (administrateur)
         * @throws \InvalidArgumentException
         * @return array|null retourne un tableau ou `null` selon que les données soient trouvées ou pas
         */
        public function get(int $admin_id): ?array {
            if (empty($admin_id)) {
                throw new InvalidArgumentException(message: "Identifiant requis");   
            }

            if ($admin_id == 1) {
                throw new InvalidArgumentException(message: "Accès refusé Super-Administrateur");   
            }

            try {
                return $this->db->select(table: 'tbl_administrators')->where(condition: 'id = ?', params: [$admin_id])->first();
            } catch (Exception $e) {
                error_log(message: 'Erreur lors de la récupération des données administrateur: ' . $e->getMessage());
                return null;
            }
        }

        /**
         * Retourne tout les administrateurs actifs sauf le super-administrateur
         * @return array|bool
         */
        public function getAllAdmin(): array {
            return $this->db->select(table: 'tbl_administrators')->where(condition: 'status = ? AND role = ?', params: ['active', 'admin'])->execute();
        } 

        /////////////////////////////////////////////////////////////////////
        //                                                                 //
        //  Gestion des utilisateurs (customers) par les administrateurs   //
        //                                                                 //
        /////////////////////////////////////////////////////////////////////

        /**
         * Permet la création d'un utilisateur (client) par un administrateur
         * @param array $data données du nouvel utilisateur 
         * @throws \InvalidArgumentException
         * @return bool
         */
        public function createCustomer(array $data): bool {
            $requieredFields = ['firstname', 'lastname', 'email', 'phone', 'password', 'address_type', 'plot', 'street', 'quarter', 'commune', 'city', 'country'];
            foreach ($requieredFields as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException(message: "Champ requis << $field >> pour inseérer un nouvel utilisateur");
                }
            }
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_customers')->set(data: [
                    'cust_first_name' => $data['firstname'],
                    'cust_last_name'  => $data['lastname'],
                    'cust_email'      => $data['email'],
                    'cust_phone'      => $data['phone'],
                    'cust_password'   => password_hash(password: $data['password'], algo: PASSWORD_ARGON2ID)
                ])->execute();
                $customer_id = $this->db->lastInsertId();
                $this->db->insert(table: 'tbl_customer_addresses')->set(data: [
                    'customer_id'  => $customer_id,
                    'address_type' => $data['address_type'],
                    'plot_num'     => $data['plot'],
                    'street'       => $data['street'],
                    'quarter'      => $data['quarter'],
                    'commune'      => $data['commune'],
                    'city'         => $data['city'],
                    'country'      => $data['country']
                ])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur lors de la création utilisateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne les données de l'utilisateur à partir de son identifiant `$cust_id`
         * @param int $cust_id id de l'utilisateur (administrateur)
         * @throws \InvalidArgumentException
         * @return array|null retourne un tableau ou `null` selon que les données soient trouvées ou pas
         */
        public static function getCustomer(Database $db, int $cust_id): ?array {
            if (empty($cust_id)) {
                throw new InvalidArgumentException(message: "Identifiant requis");   
            }

            try {
                return $db->select(table: 'tbl_customers c')->select(columns: ['c.*', 'ca.*'])->join(table: 'tbl_customer_addresses ca', condition: 'c.cust_id = ca.customer_id', type: 'LEFT')->where(condition: 'cust_id = ?', params: [$cust_id])->first();
            } catch (QueryException $qe) {
                error_log(message: 'Erreur lors de la récupération des données utilisateur: ' . $qe->getMessage());
                return null;
            }
        }

        /**
         * Permet à un administrateur de supprimer un utilisateur à partir de son identifiant 
         * @param int $cust_id  identifiant de l'utilisateur à supprimer
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function deleteCustomer(int $cust_id): bool {
            if (empty($cust_id)) {
                throw new InvalidArgumentException(message: "Id requis pour la suppression utilisateur");
            }

            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_customers')->where(condition: 'cust_id = ?', params: [$cust_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur suppression utilisateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne tout les clients actifs
         * @return array
         */
        public static function getAllCustomers(Database $db): array {
            return $db->select(table: 'tbl_customers c')->select(columns: ['c.*', 'ca.*'])->join(table: 'tbl_customer_addresses ca', condition: 'c.cust_id = ca.customer_id', type: 'LEFT')->execute();
        } 

        /**
         * Change le statut d'un utilisateur (actif/suspendu)
         * @param int $cust_id identifiant de l'utilisateur 
         * @throws \InvalidArgumentException exception levée en cas d'érreur 
         * @return bool retourne une valeur booléenne selon que la mise à jour aura réussie ou pas 
         */
        public function changeCustomerStatus(int $cust_id): bool {
            if (empty($cust_id)) {
                throw new InvalidArgumentException(message: "Identifiant requis pour changer le statut utilisateur");   
            }
            $this->db->beginTransaction();
            try {
                $customerStatus = $this->db->select(table: 'tbl_customers')->where(condition: 'cust_id = ?', params: [$cust_id])->first()['cust_status'];
                switch ($customerStatus) {
                    case 'active':
                        $this->db->update(table: 'tbl_customers')->set(data: ['status' => 'suspended'])->where(condition: 'cust_id = ?', params: [$cust_id])->execute();
                        break;
                    case 'suspended':
                        $this->db->update(table: 'tbl_customers')->set(data: ['status' => 'active'])->where(condition: 'cust_id = ?', params: [$cust_id])->execute();
                        break;
                    default:
                        throw new Exception(message: "Impossible de changer le statut du compte client/vendeur: Statut invalide");
                }
                $this->db->commit(); 
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur de mise à jour statut utilisateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Permet à un administrateur de mettre à jour les informations d'utilisateur
         * @param array $data données de mise à jour
         * @throws \Exception
         * @throws \InvalidArgumentException
         * @return bool
         */
        public function editCustomer(array $data): bool {
            $requieredFields = ['firstname', 'lastname', 'email', 'phone', 'password', 'status', 'address_type', 'plot', 'street', 'quarter', 'commune', 'city', 'country'];
            if (empty($data['cust_id'])) {
                throw new Exception(message: "Id de l'utilisateur requis pour les modifications");
            }

            if (empty($data['address_id'])) {
                throw new Exception(message: "Id de l'adresse requis pour les modifications");
            }
            $this->db->beginTransaction();
            try {
                foreach ($requieredFields as $field) {
                    if (!empty($data[$field])) {
                        switch ($data[$field]) {
                            // Mise à jour des informations sur l'utilisateur (client)
                            case 'firstname': 
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_first_name' => $data['firstname']])->where(condition: 'cust_id = ?', params: [$data['cust_id']])->execute();
                                break;
                            case 'lastname': 
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_last_name' => $data['lastname']])->where(condition: 'cust_id = ?', params: [$data['cust_id']])->execute();
                                break;
                            case 'email': 
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_email' => $data['email']])->where(condition: 'cust_id = ?', params: [$data['cust_id']])->execute();
                                break;
                            case 'phone': 
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_phone' => $data['phone']])->where(condition: 'cust_id = ?', params: [$data['cust_id']])->execute();
                                break;
                            case 'status':
                                $this->changeCustomerStatus(cust_id: $data['cust_id']);
                                break;
                            case 'password': 
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_password' => $data['password']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            // Mise à jour d'adresse utilisateur (client)
                            case 'adress_type': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['address_type' => $data['address_type']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            case 'plot': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['plot_num' => $data['plot']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            case 'street': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['street' => $data['street']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            case 'quarter': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['quarter' => $data['quarter']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            case 'commune': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['commune' => $data['commune']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            case 'city': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['city' => $data['city']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                            case 'country': 
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['country' => $data['country']])->where(condition: 'address_id = ? AND cust_id = ?', params: [$data['address_id'], $data['cust_id']])->execute();
                                break;
                        }
                    }
                }
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur mise à jour utilisateur: ' . $qe->getMessage());
                return false;
            }
        }
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                                                                                                           //
    //   Classe spéciale pour le super-administrateur                                                            //
    //   Les actions possibles du super-administrateur incluent celles des administrateurs simples par héritage  //
    //                                                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Classe spéciale pour le super-administrateur 
     */
    class SuperAdministrator extends Administrator {
        private Database $db;
        /**
         * Crée un nouvel administrateur dans la base de données
         * @param array $data données du nouvel administrateur à enregistrer 
         * @throws \InvalidArgumentException exception levée en cas d'érreur
         * @return bool retourne une valeur booléenne selon le cas, `true` si tout se passe bien et `false` dans tout les autres cas
         */
        public function create(array $data): bool {
            $requieredFields = ['full_name', 'email', 'phone', 'password'];
            foreach ($requieredFields as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException(message: "Champ requis: $field");
                }
            }
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_administrators')->set(data: [
                    'full_name' => $data['fill_name'],
                    'email'     => $data['email'],
                    'phone'     => $data['phone'],
                    'password'  => password_hash(password: $data['password'], algo: PASSWORD_ARGON2ID)
                ])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur création administrateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Change le statut d'un administrateur (actif/suspendu)
         * @param int $admin_id identifiant de l'administrateur 
         * @throws \InvalidArgumentException exception levée en cas d'érreur 
         * @return bool retourne une valeur booléenne selon que la mise à jour aura réussie ou pas 
         */
        public function changeStatus(int $admin_id): bool {
            if (empty($admin_id)) {
                throw new InvalidArgumentException(message: "Identifiant requis");   
            }
            $this->db->beginTransaction();
            try {
                $adminStatus = $this->db->select(table: 'tbl_administrators')->where(condition: 'id = ?', params: [$admin_id])->first()['status'];
                switch ($adminStatus) {
                    case 'active':
                        $this->db->update(table: 'tbl_administrators')->set(data: ['status' => 'suspended'])->where(condition: 'id = ?', params: [$admin_id])->execute();
                        break;
                    case 'suspended':
                        $this->db->update(table: 'tbl_administrators')->set(data: ['status' => 'active'])->where(condition: 'id = ?', params: [$admin_id])->execute();
                        break;
                    default:
                        throw new Exception(message: "Impossible de changer le statut du compte administrateur: Statut invalide");
                }
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur de mise à jour statut administrateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Supprime un administrateur à partir de son identifiant 
         * @param int $admin_id identifiant de l'administrateur à supprimer
         * @throws \InvalidArgumentException
         * @return bool
         */
        public function delete(int $admin_id): bool {
            if (empty($admin_id)) {
                throw new InvalidArgumentException(message: "Identifiant requis pour suppression administrateur");   
            }

            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_administrators')->where(condition: 'id = ?', params: [$admin_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur de suppression administrateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne tout les administrateurs actifs et non actifs y compris le super-administrateur
         * @return array|bool
         */
        public function getAll(): array {
            return $this->db->select(table: 'tbl_administrators')->execute();
        } 
    }