<?php
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Post.php';
    if (isAdminLoggedIn()) {
        $post = new Post(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    /**
                     * Création d'un nouveau post
                     * Récupération des données contenues dans le corps de la requête HTTP POST
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $image = imageUploader(file: $_FILES, future_path: "post_photos/");
                    if (isset($data) AND isset($image['path'])) {
                        $postData = [
                            'title'      => sanitizeInput(input: $data['post_title']),
                            'subject'    => sanitizeInput(input: $data['post_subject']),
                            'content'    => sanitizeInput(input: $data['post_content']),
                            'image_path' => $image['path']
                        ];
                        if ($post->create(data: $postData) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de création du post']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => "Données manquantes pour la création du post: " . $image['error']]);
                    }
                    break;
                case 'GET':
                    /**
                     * Récupération d'un/des posts grâce à la requête HTTP GET
                     */
                    if (isset($_GET['id'])) {
                        // Si l'identifiant d'un post est fourni, seul le post correspondant à l'identifiant sera retourné
                        if (Post::get(db: $database, post_id: $_GET['id']) !== null) {
                            echo json_encode(value: Post::get(db: $database, post_id: $_GET['id']));
                            // Si l'identifiant est invalide une redirection vers la page index sera effectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        // Si aucun identifiant n'est fourni, tout les posts seront retournés
                        echo json_encode(value: Post::getAll(db: $database));
                    }
                    break;
                case 'PATCH':
                    /**
                     * Mise à) jour d'un post
                     * Récupération des données contenues dans le corps de la requête HTTP PATCH
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $image = imageUploader(file: $_FILES, future_path: "post_photos/");
                    if (isset($data) AND isset($image['path'])) {
                        $postData = [
                            'post_id'    => $data['post_id'],
                            'title'      => sanitizeInput(input: $data['post_title']),
                            'subject'    => sanitizeInput(input: $data['post_subject']),
                            'content'    => sanitizeInput(input: $data['post_content']),
                            'image_path' => $image['path']
                        ];
                        if ($post->edit(data: $postData) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de mise à jour du post']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => "Données manquantes pour la mise à jour du post: " . $image['error']]);
                    }
                    break;
                case 'DELETE':
                    /**
                     * Suppression d'un post
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    if (isset($_GET['id'])) {
                        if ($post->delete(post_id: $_GET['id']) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur lors de la suppression du post']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Identifiant du post requis pour la suppression']);
                    }
                    break;  
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement du post: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur: ' . $e->getMessage()]);
        }
    } else {
        redirect(url: '../login.php');
    }