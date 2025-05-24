<?php
    use NokySQL\Database;
    use NokySQL\Exceptions\QueryException;

    class Message {
        private Database $db;
        public function __construct(Database $database) {
            $this->db = $database;
        }

        public function send(int $senderId, int $receiverId, string $content, ?string $attachement = null): bool {
            if ($senderId === $receiverId) {
                throw new Exception(message: "L'expéditeur et le destinataire doivent être différents");
            }
            $this->db->beginTransaction();
            try {
                $this->db->insert(table: 'tbl_messages')->set(data: [
                    'sender_id'   => $senderId,
                    'receiver_id' => $receiverId,
                    'content'     => $content,
                    'attachment'  => $attachement
                ])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur envoi message: " . $qe->getMessage());
                return false;
            }
        }

        public static function getConversation(Database $db, int $user1, int $user2): ?array {
            return $db->select(table: 'tbl_messages')->whereSymmetric(columns: ['sender_id' => $user1, 'receiver_id' => $user2])->orderBy('created_at', 'DESC')->execute();
        }
    
        public function delete(int $messageId): bool {
            if (empty($messageId)) {
                throw new InvalidArgumentException(message: "Identifiant du message manquant pour la suppression");
            }
            $this->db->beginTransaction();
            try {
                $attachedFile = $this->db->select(table: 'tbl_messages')->select(columns: ['attachment'])->where(condition: 'id = ?', params: [$messageId])->first()['attachment'] ?? null;
                if ($attachedFile !== null) {
                    if (unlink(filename: dirname(path: __DIR__) .  $attachedFile) !== true) {
                        throw new InvalidArgumentException(message: "Erreur de suppression du fichier lié au mesage");
                    }
                }
                $this->db->delete(table: 'tbl_messages')->where(condition: 'id = ?', params: [$messageId])->execute();
                $this->db->commit();
                return true;
            } catch (QueryException $qe) {
                $this->db->rollBack();
                error_log(message: "Erreur suppression du message: " . $qe->getMessage());
                return false;
            }
        }
    }