<?php
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Color.php';
    if (isAdminLoggedIn()) {
        $color = new Color(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    /**
                     * Création d'une nouvelle couleur
                     * Récupération des données contenues dans le corps de la requête HTTP POST
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    if (isset($data)) {
                        if ($color->add(color_name: sanitizeInput(input: $data['col_name'])) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de création de la couleur']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données manquantes pour la création d\'une nouvelle couleur']);
                    }
                    break;
                case 'GET': 
                    /**
                     * Récupération d'une/des couleurs grâce à la requête HTTP GET
                     */
                    if (isset($_GET['id'])) {
                        // Si l'identifiant d'une couleur est fourni, seule la couleur correspondante à l'identifiant sera retournée
                        $color = Color::get(db: $database, color_id: $_GET['id']);
                        if ($color !== null) {
                            echo json_encode(value: $color);
                            // Si l'identifiant est invalide une redirection vers la page index sera effectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        // Si aucun identifiant n'est fourni, toutes les couleurs seront retournées
                        echo json_encode(value: Color::getAll(db: $database));
                    }
                    break;
                case 'PATCH':
                    /**
                     * Mise à jour d'une nouvelle couleur
                     * Récupération des données contenues dans le corps de la requête HTTP PATCH
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    if (!empty($data['col_id']) AND !empty($data['col_name'])) {
                        if ($color->edit(color_id: $data['col_id'], new_color_name: sanitizeInput(input: $data['col_name'])) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de mise à jour e la couleur']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données insuffisantes pour la mise à jour de la couleur']);
                    }
                    break;
                case 'DELETE':
                    /**
                     * Suppresion d'une nouvelle couleur
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_GET : $data;
                    if (isset($data['id'])) {
                        if ($color->delete(color_id: $data['id']) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur lors de la suppression de la couleur']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Identifiant de la couleur requis pour la suppression']);
                    }
                    break;  
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement de la couleur: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne: ' . $e->getMessage()]);
        }
    } else {
        redirect(url: '../login.php');
    }