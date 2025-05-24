<?php
    use NokySQL\Database; // constructeur des requêtes SQL
    use NokySQL\Exceptions\QueryException; // gestion d'exceptions

    /**
     * Classe Color représente une couleur et toutes les opérations nécessaires à sa gestion
     */
    class Color {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }

        /**
         * Ajoute une nouvelle couleur
         * @param string $color_name nom de la nouvelle couleur 
         * @throws \InvalidArgumentException exception levée si le nom de la couleur est nul
         * @throws \Exception exception levée si la couleur existe déjà
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas
         */
        public function add(string $color_name): bool {

            if (empty($color_name)) {
                throw new InvalidArgumentException(message: "Nom de la couleur manquant");           
            } 

            if ($this->db->select(table: 'tbl_colors')->where(condition: 'color_name = ?', params: [$color_name])->count() > 0) {
                throw new Exception(message: "Cette couleur existe déjà");
            }
            
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_colors')->set(data: ['color_name' => $color_name])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur création couleur: " . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne toutes les couleurs disponibles
         * @return array retourne un tableau de toutes les couleurs disponibles 
         */
        public static function getAll(Database $db): array {
            return $db->select(table: 'tbl_colors')->orderBy(column: 'color_name')->execute();
        }

        /**
         * Retourne les information d'une couleur à partir de son identifiant
         * @param int $color_id identifiant de la couleur à chercher
         * @return array|null tableau des données relatives à la couleur correspondant à `$color_id` ou `null` si aucune couleur ne correspond 
         */
        public static function get(Database $db, int $color_id): ?array {
            return $db->select(table: 'tbl_colors')->where(condition: 'color_id = ?', params: [$color_id])->first();
        }

        /**
         * Modifie une couleur à partir de son identifiant
         * @param int $color_id identifiant de la couleur à modifier
         * @param string $new_color_name nouveau nom de la couleur
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function edit(int $color_id, string  $new_color_name): bool {
            if (empty($color_id) || empty($new_color_name)) {
                throw new InvalidArgumentException(message: "Champ manquant " . $color_id ?? $new_color_name);
            }

            $this->db->beginTransaction();
            try {
                $this->db->update(table: 'tbl_colors')->set(data: ['color_name' => $new_color_name])->where(condition: 'color_id = ?', params: [$color_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur mise à jour couleur: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Supprime une couleur à partir de son identifiant 
         * @param int $color_id  identifiant de la couleur à supprimer
         * @return bool retourne une valeur booléenne `true` si tout se passe bien ou `false` dans tout les autres cas 
         */
        public function delete(int $color_id): bool {
            if (empty($color_id)) {
                throw new InvalidArgumentException(message: "Identifiant de la couleur requis pour la suppression");
            }

            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_colors')->where(condition: 'color_id = ?', params: [$color_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur suppression couleur: ' . $qe->getMessage());
                return false;
            }
        }
    }