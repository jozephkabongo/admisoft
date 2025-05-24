<?php
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Order.php';
    if (isAdminLoggedIn() === true) {
        $order = new Order(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    /**
                     * Récupération d'une/des commandes avec la requête HTTP GET
                     */
                    if (isset($_GET['id'])) {
                        // Si un identifiant est fourni on récupère la commande correspondante
                        if ($order->getOrderDetails(orderid: $_GET['id']) !== null) {
                            echo json_encode(value:$order->getOrderDetails(orderid: $_GET['id']));
                            // Si l'identifiant fourni est invalide une redirection vers la page index.php sera éffectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                        // Si aucun identifiant n'est fourni on récupère toutes les commandes 
                    } else {
                        echo json_encode(value: Order::getAllOrders(db: $database));
                    }
                    break;
                case 'PATCH':
                    /**
                     * Mise à jour de la comande
                     * Récupération des données contenues dans le corps de la requête HTTP PATCH
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    if (isset($data) AND !empty($data['action'])) {
                        // Prise en compte de différentes actions pouvant faire objet de mise à jour sur une commande
                        switch ($data['action']) {
                            case 'update_status':
                                if ($order->updateStatus(order_id: $data['order_id'], new_status: $data['new_status'], notes: $data['status_note']) === true) {
                                    echo json_encode(value: ['success' => true, 'message' => 'Statut de la commande mis à jour avec succès']);
                                } else {
                                    echo json_encode(value: ['error' => "Erreur lors de la mise à jour du statut de la commande"]);
                                }
                                break;
                            default:
                                echo json_encode(value: ['error' => "Action non autorisée"]);
                        }
                    } else {
                        echo json_encode(value: ['error' => "Données manquantes pour la mise à jour de la commande"]);
                    }
                    break;
                case 'DELETE':
                    /**
                     * Annulation de la commande
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_GET : $data;
                    if (isset($data['id'])) {
                        if ($order->cancel(order_id: $data['id']) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => "Erreur lors de l'annulation de la commande"]);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Id de la commande requis pour l\'annulation']);
                    }
                    break;
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement de la commande: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne:' . $e->getMessage()]);
        }
    } else {
        redirect(url: '../login.php');
    }