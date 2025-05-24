<?php
    header(header: 'Content-Type: application/json');
    require_once '../admin/includes/config.php'; 
    require_once '../Models/Message.php';
    if (isLoggedIn()) {
        /**
         * Processus du traitement des messages côté client
         */
        $message = new Message(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST': 
                    /**
                     * Création d'un nouveau message
                     * Si le flux de données contenues dans le corps de la requête n'a pas pu être capturé par json_decode(...) on utilise la globale $_POST
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_POST : $data;
                    if (isset($_FILES) AND isset($_FILES['image_path']['name'])) {
                        $attachment = imageUploader(file: $_FILES, future_path: "message_photos/");
                    }
                    if (isset($data['receiver_id']) AND isset($data['message'])) {
                        if ($message->send(senderId: $_SESSION['customer']['cust_id'], receiverId: $data['receiver_id'], content: sanitizeInput(input: $data['message']), attachement: $attachment['path'] ?? null) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur envoi message']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données manquantes pour la création du message']);
                    }
                    break;
                case 'GET':
                    // Récupération des messages (conversations)
                    if (isset($_GET['id'])) {
                        $conversation = Message::getConversation(db: $database,user1: $_SESSION['customer']['cust_id'], user2: $_GET['id']);
                        if ($conversation !== null) {
                            echo json_encode(value: $conversation);
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        redirect(url: '../index.php');
                    }
                    break;
                case 'DELETE':
                    /**
                     * Suppression d'un message
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_GET : $data;
                    if (isset($data['id'])) {
                        if ($message->delete(messageId: $data['id']) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur lors de la suppression du message']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Id du message requis pour la suppression']);
                    }
                    break;
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement du message: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne']);
        }
    } else {
        redirect(url: '../login.php');
    }