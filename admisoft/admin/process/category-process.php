<?php
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Category.php';
    if (isAdminLoggedIn()) {
        $category = new Category(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    /**
                     * Création d'une nouvelle catégorie
                     * Récupération des données contenues dans le corps de la requête HTTP POST
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    if (isset($data)) {
                        if ($category->add(category_name: sanitizeInput(input: $data['cat_name'])) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de création de la catégorie']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données manquantes']);
                    }
                    break;
                case 'GET':
                    /**
                     * Récupération d'une/des catégories grâce à la requête HTTP GET
                     */
                    if (isset($_GET['id'])) {
                        $category = Category::get(db: $database, category_id: $_GET['id']);
                        if ($category !== null) {
                            echo json_encode(value: $category);
                            // Si l'identifiant est invalide une redirection vers la page index sera effectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        echo json_encode(value: Category::getAll(db: $database));
                    }
                    break;
                case 'PATCH':
                    /**
                     * Mise à jour de la catégorie
                     * Récupération des données contenues dans le corps de la requête HTTP PATCH
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    if (!empty($data['cat_id']) AND !empty($data['cat_name'])) {
                        if ($category->edit(category_id: $data['cat_id'], new_category_name: sanitizeInput(input: $data['cat_name'])) === true) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur de mise à jour e la catégorie']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Données insuffisantes pour la mise à jour de la catégorie']);
                    }
                    break;
                case 'DELETE':
                    /**
                     * Suppresion d'une catégorie
                     * Récupération des données contenues dans le corps de la requête HTTP DELETE
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $data === null ? $data = $_GET : $data;
                    if (isset($data['id'])) {
                        if ($category->delete(category_id: $data['id'])) {
                            echo json_encode(value: ['success' => true]);
                        } else {
                            echo json_encode(value: ['error' => 'Erreur lors de la suppression de la catégorie']);
                        }
                    } else {
                        http_response_code(response_code: 400);
                        echo json_encode(value: ['error' => 'Id de la catégorie requis']);
                    }
                    break;  
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement de la catégorie: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne: ' . $e->getMessage()]);
        }
    } else {
        redirect(url: '../login.php');
    }