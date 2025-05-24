<?php
    use NokySQL\Database; // constructeur des requêtes SQL
    use NokySQL\Exceptions\QueryException;

    /**
     * Classe Catégorie représente une catégorie et toutes les opérations nécessaires à sa gestion
     */
    class Category {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        /**
         * Ajoute une nouvelle catégorie
         * @param string $category_name nom de la nouvelle catégorie 
         * @throws \InvalidArgumentException exception levée si le nom de la catégorie est nul
         * @throws \Exception exception levée si la catégorie existe déjà
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas
         */
        public function add(string $category_name): bool {

            if (empty($category_name)) {
                throw new InvalidArgumentException(message: "Nom de la catégorie manquant");           
            } 

            if ($this->db->select(table: 'tbl_categories')->where(condition: 'cat_name = ?', params: [$category_name])->count() > 0) {
                throw new Exception(message: "Cette catégorie existe déjà");
            }
            
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_categories')->set(data: ['cat_name' => $category_name])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur création catégorie: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne toutes les catégories disponibles
         * @return array retourne un tableau de toutes les catégories disponibles 
         */
        public static function getAll(Database $db): array {
            return $db->select(table: 'tbl_categories')->orderBy(column: 'cat_name')->execute();
        }

        /**
         * Retourne les information d'une catégorie à partir de son identifiant
         * @param int $category_id identifiant de la catégorie à chercher
         * @return array|null tableau des données relatives à la catégorie correspondant à `$category_id` ou `null` si aucune catégorie ne correspond 
         */
        public static function get(Database $db, int $category_id): ?array {
            return $db->select(table: 'tbl_categories')->where(condition: 'cat_id = ?', params: [$category_id])->first();
        }

        /**
         * Modifie une catégorie à partir de son identifiant
         * @param int $category_id identifiant de la catégorie à modifier
         * @param string $new_category_name nouveau nom de la catégorie
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function edit(int $category_id, string  $new_category_name): bool {
            if (empty($category_id) || empty($new_category_name)) {
                throw new InvalidArgumentException(message: "Champ manquant pour la modification de la catégorie: " . $category_id ?? $new_category_name);
            }

            $this->db->beginTransaction();
            try {
                $this->db->update(table: 'tbl_categories')->set(data: ['cat_name' => $new_category_name])->where(condition: 'cat_id = ?', params: [$category_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur mise à jour catégorie: " . $qe->getMessage());
                return false;
            }
        }

        /**
         * Supprime une catégorie à partir de son identifiant 
         * @param int $category_id  identifiant de la catégorie à supprimer
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function delete(int $category_id): bool {
            if (empty($category_id)) {
                throw new InvalidArgumentException(message: "Identifiant de la catégorie requis pour la suppression");
            }

            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_categories')->where(condition: 'cat_id = ?', params: [$category_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur suppression catégorie: ' . $qe->getMessage());
                return false;
            }
            
        }
    }