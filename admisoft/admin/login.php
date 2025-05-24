<?php
    header(header: 'Content-Type: application/json');
    require_once 'includes/db_connect.php'; 
    require_once 'includes/functions.php'; 
    require_once '../Models/Administrator.php';
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $userAdmin = new Administrator(database: $database);
                /**
                 * Récupération des données de connexion, contenues dans le corps de la requête HTTP POST
                 */
                $data = json_decode(json: file_get_contents(filename: 'php://input'), associative: true);
                $data === null ? $data = $_POST : $data;
                $loginStatus = $userAdmin->login(email: sanitizeInput(input: $data['email']), password: sanitizeInput(input: $data['password']));
                switch ($loginStatus) {
                    case isset($loginStatus['error']):
                        echo json_encode(value: ['error' => $loginStatus['error']]);  
                        break;
                    case $loginStatus['success'] === true:
                        session_start();
                        $_SESSION['admin'] = $loginStatus['adminInfos'];
                        redirect(url: 'index.php');
                        break;
                    default:
                        echo json_encode(value: ['error' => 'Erreur lors de la connexion au compte']);  
                        break;
                }
                break;
            default:
                //http_response_code(response_code: 400);
                echo json_encode(value: ['error' => 'Méthode non autorisée pour la connexion admin']);  
                break;
        }
    } catch (Exception $e) {
        error_log(message: 'Erreur du traitement de l\'authentification: ' . $e->getMessage());
        http_response_code(response_code: 500);
        echo json_encode(value: ['error' => 'Erreur serveur: ' . $e->getMessage()]);
    }