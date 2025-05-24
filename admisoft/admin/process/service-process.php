<?php
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Service.php';
    if (isAdminLoggedIn()) {
        $service = new Service(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    /**
                     * Création d'un nouveau service
                     * Récupération des données contenues dans le corps de la requête HTTP POST
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $image = imageUploader(file: $_FILES, future_path: "service_photos/");
                    if (isset($data) AND isset($image['path'])) {
                        $serviceData = [
                            'title'       => sanitizeInput(input: $data['service_title']),
                            'description' => sanitizeInput(input: $data['service_description']),
                            'base_price'  => $data['service_base_price'],
                            'image_path'  => $image['path']
                        ];
                        if ($service->add(data: $serviceData) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de création du service']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => "Données manquantes pour la création du nouveau service: " . $image['error']]);
                    }
                    break;
                case 'GET':
                    /**
                     * Récupération d'un/des services grâce à la requête HTTP GET
                     */
                    if (isset($_GET['id'])) {
                        // Si l'identifiant d'un service est fourni, seul le service correspondant à l'identifiant sera retourné
                        if (Service::get(db: $database, service_id: $_GET['id']) !== null) {
                            echo json_encode(value: Service::get(db: $database, service_id: $_GET['id']));
                            // Si l'identifiant est invalide une redirection vers la page index sera effectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        // Si aucun identifiant n'est fourni, tout les services seront retournés
                        echo json_encode(value: Service::getAll(db: $database));
                    }
                    break;
                case 'PATCH': 
                    /**
                     * Mise à jour d'un service
                     * Récupération des données contenues dans le corps de la requête HTTP PATCH
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $image = imageUploader(file: $_FILES, future_path: "service_photos/");
                    if (isset($data) AND isset($image['path'])) {
                        $serviceData = [
                            'service_id'  => $data['service_id'],
                            'title'       => sanitizeInput(input: $data['service_title']),
                            'description' => sanitizeInput(input: $data['service_description']),
                            'base_price'  => $data['service_base_price'],
                            'image_path'  => $data['image_path']
                        ];
                        if ($service->edit(data: $serviceData) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de mise à jour service']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données insuffisantes pour la mise à jour service' . $image['error']]);
                    }
                    break;
                case 'DELETE':
                    /**
                     * Suppression d'un service
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_GET : $data;
                    if (isset($data['id'])) {
                        if ($service->delete(service_id: $data['id']) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur lors de la suppression du service']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Identifiant du service requis pour la suppression']);
                    }
                    break;  
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement du service: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne']);
        }
    } else {
        redirect(url: '../login.php');
    }