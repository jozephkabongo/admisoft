<?php
    use NokySQL\Database; // constructeur des requêtes SQL
    use NokySQL\Exceptions\QueryException;

    /**
     * Classe Service représente une service et toutes les opérations nécessaires à sa gestion
     */
    class Service {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        /**
         * Ajoute un nouveau service dans le catalogie des services 
         * @param array $data données de création du nouveau service
         * @throws \InvalidArgumentException
         * @throws \Exception
         * @return bool
         */
        public function add(array $data): bool {
            $allowedFields = ['title', 'description', 'base_price', 'image_path'];
            foreach($allowedFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception(message: "Champ requis pour la creation d'un service: $field");
                }
            }

            if ($this->db->select(table: 'tbl_services')->where(condition: 'title = ?', params: [$data['title']])->count() > 0) {
                throw new Exception(message: "Ce service existe déjà");
            }
            
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_services')->set(data: [
                    'title'       => $data['title'],
                    'description' => $data['description'],
                    'base_price'  => $data['base_price'],
                    'image'       => $data['image_path']
                ])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur création service: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne touts les services disponibles
         * @return array retourne un tableau de touts les services disponibles 
         */
        public static function getAll(Database $db): array {
            return $db->select(table: 'tbl_services')->orderBy(column: 'title')->execute();
        }

        /**
         * Retourne les information d'un service à partir de son identifiant
         * @param int $service_id identifiant du service à chercher
         * @return array|null tableau des données relatives au service correspondant à `$service_id` ou `null` si aucun service ne correspond 
         */
        public static function get(Database $db, int $service_id): ?array {
            if (empty($service_id)) {
                throw new Exception(message: "Identifiant requis pour la récupération du service");
            }

            if ($db->select(table: 'tbl_services')->where(condition: 'id = ?', params: [$service_id])->count() == 1) {
                return $db->select(table: 'tbl_services')->where(condition: 'id = ?', params: [$service_id])->first();
            } else {
                return null;
            }
        }

        /**
         * Modifie un service à partir d'un ensemble de données envoyées en tableau, le tableau doit contenir l'identifiant du service en plus d'autres informations afin de permettre la mise à jour
         * @param array $data données de mise à jour du service
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function edit(array $data): bool {
            if (empty($data['service_id'])) {
                throw new InvalidArgumentException(message: "Identifiant du service requis pour la modification");
            }
            $allowedFields = ['new_title', 'new_description', 'new_base_price', 'new_service_image'];

            $this->db->beginTransaction();
            try {
                foreach($allowedFields as $field) {
                    if (isset($data[$field])) {
                        switch($data[$field]) {
                            case 'new_title':
                                $this->db->update(table: 'tbl_services')->set(data: ['title' => $data['new_title']])->where(condition: 'id = ?', params: [$data['service_id']])->execute();
                                break;
                            case 'new_description':
                                $this->db->update(table: 'tbl_services')->set(data: ['descritpion' => $data['new_description']])->where(condition: 'id = ?', params: [$data['service_id']])->execute();
                                break;
                            case 'new_base_price':
                                $this->db->update(table: 'tbl_services')->set(data: ['base_price' => $data['new_base_price']])->where(condition: 'id = ?', params: [$data['service_id']])->execute();
                                break;
                            case 'new_service_image':
                                $this->db->update(table: 'tbl_services')->set(data: ['image' => $data['new_service_image']])->where(condition: 'id = ?', params: [$data['service_id']])->execute();
                                break;
                            }
                    }
                }
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur mise à jour service: " . $qe->getMessage());
                return false;
            }
        }

        /**
         * Supprime un service à partir de son identifiant
         * @param int $service_id  identifiant du service à supprimer
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function delete(int $service_id): bool {
            if (empty($service_id)) {
                throw new InvalidArgumentException(message: "Identifiant du service requis pour la suppression");
            }

            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_services')->where(condition: 'id = ?', params: [$service_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur suppression service: ' . $qe->getMessage());
                return false;
            }
        }
    }