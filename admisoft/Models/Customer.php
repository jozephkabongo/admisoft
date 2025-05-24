<?php
    use NokySQL\Database;
    use NokySQL\Exceptions\QueryException;

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
     * Classe Customer représente un client sur la plateformer at regroupe toute la logique de son traitement
     */
    class Customer {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        /**
         * Vérifie si un client a un compte et le connecte si vrai, sinon retourne une erreur `NOT_FOUNDED_ACCOUNT`
         * @param string $email adresse mail du client 
         * @param string $password mot de passe du client 
         * @throws \InvalidArgumentException 
         * @return array type de retour selon les cas 
         */
        public function login(string $email, string $password): array {

            if (empty($email) || empty($password)) {
                throw new InvalidArgumentException(message: "Email ou Mot de passe manquant");           
            } 

            $customerInfos = $this->db->select(table: 'tbl_customers c')
            ->select(columns: ['c.*', 'ca.*'])
            ->join(table: 'tbl_customer_addresses ca', condition: 'ca.customer_id = c.cust_id', type: 'LEFT')
            ->where(condition: 'cust_email = ? AND cust_status = ?', params: [$email, 'active'])
            ->first();
            
            if ($this->db->select(table: 'tbl_customers')->where(condition: 'cust_email = ? AND cust_status = ?', params: [$email, 'inactive'])->count() == 1) {
                // Compte non vérifié par adresse mail 
                return ['error' => UNVERIFIED_ACCOUNT];
            } else if (isNonEmptyArray(arrayData: $customerInfos)) {
                if (password_verify(password: $password, hash: $customerInfos['cust_password'])) {
                    return [
                        'customerInfos' => $customerInfos,
                        'success' => true
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
         * Enregistre un nouvel utilisateur 
         * @param array $data données du nouveau utilisateur fournies dans un tableau
         * @throws \InvalidArgumentException
         * @return array|null retourne les informations de l'utilisateur nouvellement enregistré si tout se passe bien, sinon retourne `false`
         */
        public function register(array $data): ?array {
            $requiredFields = ['firstname', 'lastname', 'email', 'token', 'phone', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException(message: "Champ manquant $field");
                }
            }
            
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_customers')->set(data: [
                    'cust_first_name' => $data['firstname'],
                    'cust_last_name'  => $data['lastname'],
                    'cust_email'      => $data['email'],
                    'cust_phone'      => $data['phone'],
                    'cust_token'      => $data['token'],
                    'cust_password'   => password_hash(password: $data['password'], algo: PASSWORD_ARGON2ID)
                ])->execute();
                $this->db->commit();
                return $this->db->lastInsertRow(table: 'tbl_customers', idColumn: 'cust_id');
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur enregistrement utilisateur: ' . $qe->getMessage());
                return null;
            }
        }

        /**
         * Vérifie que l'adresse mail fournie appartient bien à celui qui l'a fourni
         * @param int $email email fournie à vérifier 
         * @param string $token jeton de vérification 
         * @throws \InvalidArgumentException
         * @return bool
         */
        public function mailVerifiy(string $email, string $token): bool {
            if (empty($email) || empty($token)) {
                throw new InvalidArgumentException(message: 'Adresse mail et jeton requis pour la vérification du compte');
            }
            
            $this->db->beginTransaction();
            try {
                $customerToken = $this->db->select(table: 'tbl_customers')->where(condition: 'cust_email = ?', params: [$email])->first()['cust_token'];
            
                if ($token === $customerToken) {
                    $this->db->update(table: 'tbl_customers')->set(data: ['cust_token'=> '', 'cust_status'=> 'active'])->where(condition: 'cust_email = ?', params: [$email])->execute();
                    $this->db->commit();
                    return true;
                } else {
                    return false;
                }
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur vérification mail utilisateur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Met à jour les données utilisateur dans la base de données
         * @param array $data données à mettre à jour  (l'identifiant de l'utilisateur doit être inclut dans ce tableau, et s'il faut mettre à jour une adresse, son l'identifiant de l'adresse doit également être inclut)
         * @throws \InvalidArgumentException
         * @throws \Exception
         * @return bool
         */
        public function update(array $data): bool {
            if (empty($data['customer_id'])) {
                throw new InvalidArgumentException(message: "Identifiant manquant pour la mise à jour utilisateur");
            }
            $this->db->beginTransaction();
            try {
                $allowedFields = ['firstname', 'lastname', 'email', 'password', 'address_type', 'plot_num', 'street', 'quarter', 'commune', 'city', 'country'];
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        switch ($data[$field]) {
                            case 'firstname':
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_first_name'=> $data['firstname']])->where(condition: 'cust_id = ?', params: [$data['customer_id']])->execute();
                                break;
                            case 'lastname':
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_last_name'=> $data['lastname']])->where(condition: 'cust_id = ?', params: [$data['customer_id']])->execute();
                                break;
                            case 'email':
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_email'=> $data['email']])->where(condition: 'cust_id = ?', params: [$data['customer_id']])->execute();
                                break;
                            case 'password':
                                $this->db->update(table: 'tbl_customers')->set(data: ['cust_password'=> $data['password']])->where(condition: 'cust_id', params: [$data['customer_id']])->execute();
                                break;
                            case 'address_type':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['address-type'=> $data['address_type']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
                                break;
                            case 'plot_num':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['plot_num'=> $data['plot_num']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
                                break;
                            case 'street':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['street'=> $data['street']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
                                break;
                            case 'quarter':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['quarter'=> $data['quarter']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
                                break;
                            case 'commune':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['commune'=> $data['commune']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
                                break;
                            case 'city':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['city'=> $data['city']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
                                break;
                            case 'country':
                                if (empty($data['address_id'])) {
                                    throw new Exception(message: 'Identifiant de l\'adresse manquant pour la mise à jour');
                                }
                                $this->db->update(table: 'tbl_customer_addresses')->set(data: ['country'=> $data['country']])->where(condition: 'address_id = ? AND customer_id = ?', params: [$data['address_id'], $data['customer_id']])->execute();
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

        /**
         * Ajoute une adresse utilisateur 
         * @param array $adress_info les informations de l'adresse (dans un tableau)
         * @param int $customer_id l'identifiant de l'utilisateur propriétaire de l'adresse
         * @throws \InvalidArgumentException
         * @return bool
         */
        public function addAdress(array $data): bool {
            $requiredFields = ['customer_id', 'address_type', 'plot', 'street', 'quarter', 'commune', 'city', 'country'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException(message: "Champ manquant $field");
                }
            }

            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_customer_addresses')->set(data: [
                    'customer_id'  => $data['customer_id'],
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
                error_log(message: 'Erreur ajout d\'adresse utilissateur: ' . $qe->getMessage());
                return false;
            }
        }
    }