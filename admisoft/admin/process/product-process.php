<?php
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Product.php';
    if (isAdminLoggedIn()) {
        $productManager = new ProductManager(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    /**
                     * Création d'un nouveau produit
                     * Si le flux de données contenues dans le corps de la requête n'a pas pu être capturé par json_decode(...) on utilise la globale $_POST
                     */
                    $data == null ? $data = $_POST : $data;
                    $image = imageUploader(file: $_FILES, future_path: "product_photos/");
                    if (isset($data) AND isset($image['path'])) {
                        $productData = [
                            'cat_id'      => $data['cat_id'], 
                            'name'        => sanitizeInput(input: $data['name']), 
                            'price'       => $data['price'], 
                            'quantity'    => $data['quantity'], 
                            'treshold'    => $data['treshold'] ?? null, 
                            'image_path'  => $image['path'], 
                            'description' => sanitizeInput(input: $data['description']) ?? null, 
                            'features'    => sanitizeInput(input: $data['features']) ?? null
                        ];
                        if ($productManager->add(data: $productData) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de la création du produit']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données manquantes pour la création du produit']);
                    }
                    break;
                case 'GET': // Récupération du/des produits
                    if (isset($_GET['id'])) {
                        // Si l'identifiant d'un produit est fourni, seul le produit correspondant à l'identifiant sera retourné
                        $product = ProductManager::get(db: $database, product_id: $_GET['id']);
                        if ($product !== null) {
                            echo json_encode(value: $product);
                            // Si l'identifiant est invalide une redirection vers la page index sera effectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        // Si aucun identifiant n'est fourni, tout les produits seront retournés
                        echo json_encode(value: ProductManager::getAll(db: $database));
                    }
                    break;
                case 'PATCH':
                    /**
                     * Mise à jour du produit
                     * Récupération des données contenues dans le corps de la requête HTTP PATCH
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $image = imageUploader(file: $_FILES, future_path: "product_photos/");
                    if (isset($data) AND isset($image['path'])) {
                        $productData = [
                            'prod_id'     => $data['prod_id'],
                            'cat_id'      => $data['cat_id'], 
                            'name'        => sanitizeInput(input: $data['name']), 
                            'price'       => $data['price'], 
                            'quantity'    => $data['quantity'], 
                            'treshold'    => $data['treshold'] ?? null, 
                            'image_path'  => $image['path'], 
                            'description' => sanitizeInput(input: $data['description']) ?? null, 
                            'features'    => sanitizeInput(input: $data['features']) ?? null
                        ];
                        if ($productManager->edit(data: $productData) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de la mise àjour du produit']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données manquantes pour la mise à jour du produit']);
                    }
                    break;
                case 'DELETE':
                    /**
                     * Suppression d'un produit
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_GET : $data;
                    if (isset($data['id'])) {
                        if ($productManager->delete(product_id: $data['id']) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur lors de la suppression du produit']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Id du produit requis pour la suppression']);
                    }
                    break;
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement du produit: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne']);
        }
    } else {
        redirect(url: '../login.php');
    }