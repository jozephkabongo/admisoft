<?php
    use NokySQL\Database; // constructeur des requêtes SQL
    use NokySQL\Exceptions\QueryException;

    /**
     * Classe Post représente un post dans le blog, avec tout ce qu'il faut pour sa gestion complète
     */
    class Post {
        private Database $db;

        public function __construct(Database $database) {
            $this->db = $database;
        }
        /**
         * Crée un nouveau post pour le blog 
         * @param array $data données du post
         * @throws \Exception
         * @return bool retourne une valeur boooléenne `true` ou `false` selon les cas 
         */
        public function create(array $data): bool {
            $requiredFields = ['title', 'subject', 'content', 'image_path'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception(message: "Champ manquat pour la création du post: $field");
                }
            }
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_posts')->set(data: [
                    'author_id'    => $_SESSION['user']['id'],
                    'post_title'   => $data['title'],
                    'post_subject' => $data['subject'],
                    'post_content' => $data['content'],
                    'post_image'   => $data['image_path']
                ])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: 'Erreur création post: ' . $qe->getMessage());
                return false;
            }
        }

        /**
         * Retourne tout les posts
         * @param NokySQL\Database $db
         * @return array
         */
        public static function getAll(Database $db): array {
            return $db->select(table: 'tbl_posts')->orderBy(column: 'created_at', direction: 'DESC')->execute();
        }

        /**
         * Retourne un post selon l'identifiant
         * @param NokySQL\Database $db
         * @param int $post_id
         * @throws \Exception
         */
        public static function get(Database $db, int $post_id): ?array {
            if (empty($post_id)) {
                throw new Exception(message: "Identifiant requis pour la récupération du post");
            }

            if ($db->select(table: 'tbl_posts')->where(condition: 'post_id = ?', params: [$post_id])->count() == 1) {
                return $db->select(table: 'tbl_posts')->where(condition: 'post_id = ?', params: [$post_id])->first();
            } else {
                return null;
            }
        }

        /**
         * Met à joour un post
         * @param array $data
         * @throws \Exception
         * @return void
         */
        public function edit(array $data): bool {
            if (empty($data['post_id'])) {
                throw new Exception(message: "Identifiant requi pour modifier le post");
            }
            $requiredFields = ['title', 'subject', 'content', 'image_path'];
            $this->db->beginTransaction();
            try {
                foreach ($requiredFields as $field) {
                    if (isset($data[$field])) {
                        switch ($data[$field]) {
                            case 'title':
                                $this->db->update(table: 'tbl_posts')->set(data: ['post_title' => $data['title']])->where(condition: 'post_id = ?', params: [$data['post_id']])->execute();
                                break;

                            case 'subject':
                                $this->db->update(table: 'tbl_posts')->set(data: ['post_subject' => $data['subject']])->where(condition: 'post_id = ?', params: [$data['post_id']])->execute();
                                break;

                            case 'content':
                                $this->db->update(table: 'tbl_posts')->set(data: ['post_content' => $data['content']])->where(condition: 'post_id = ?', params: [$data['post_id']])->execute();
                                break;

                            case 'image_path':
                                $this->db->update(table: 'tbl_posts')->set(data: ['post_image' => $data['image_path']])->where(condition: 'post_id = ?', params: [$data['post_id']])->execute();
                                break;
                        }
                    }
                }
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur mise à jour post: " . $qe->getMessage());
                return false;
            }
        }

        /**
         * Supprime un post
         * @param int $post_id identifiant du post à supprimer
         * @throws \Exception
         * @return bool
         */
        public function delete(int $post_id): bool {
            if (empty($post_id)) {
                throw new Exception(message: "Identifiant requi pour supprimer le post");
            }
            $this->db->beginTransaction();
            try {
                $this->db->delete(table: 'tbl_posts')->where(condition: 'post_id = ?', params: [$post_id])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur suppression post: " . $qe->getMessage());
                return false;
            }
        }
    }