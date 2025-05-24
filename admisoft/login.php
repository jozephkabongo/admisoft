<?php
    /**
     * Fichier du traitement du processuss de login (connexion) client
     */
    header(header: 'Content-Type: application/json');
    require_once 'admin/includes/config.php'; 
    require_once 'Models/Customer.php';

    if (isset($_GET['error'])) {
        echo "<script>alert('" . $_GET['error'] . "');</script>";
    }
    
    $customer = new Customer(database: $database);
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $data = json_decode(json: file_get_contents(filename: 'php://input'), associative: true);
                $data === null ? $data = $_POST : $data;
                $loginStatus = $customer->login(email: $data['email'], password: $data['password']);
                switch ($loginStatus) {
                    case $loginStatus['error'] !== null:
                        echo json_encode(value: ['error' => $loginStatus['error']]);  
                        break;
                    case $loginStatus['success'] == true:
                        session_start();
                        $_SESSION['customer'] = $loginStatus['customerInfos'];
                        redirect(url: 'index.php');
                        break;
                    default:
                        echo json_encode(value: ['error' => 'Erreur lors de la connexion au compte']);  
                        break;
                }
                break;
            default:
                http_response_code(response_code: 405);
                echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                break;
        }
    } catch (Exception $e) {
        error_log(message: 'Erreur du traitement d\'authentification client: ' . $e->getMessage());
        http_response_code(response_code: 500);
        echo json_encode(value: ['error' => 'Erreur interne: ' . $e->getMessage()]);
    }