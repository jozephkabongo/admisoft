<?php
    /**
     * Fichier du traitement du processuss d'insertion d'un client par un administrateur
     */
    header(header: 'Content-Type: application/json');
    require_once '../includes/config.php'; 
    require_once '../../Models/Administrator.php';
    if (isAdminLoggedIn()) {
        $administrator = new Administrator(database: $database);
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    /**
                     * Création d'un nouvel utilisateur 
                     * Récupération des données contenues dans le corps de la requête HTTP POST
                     */
                    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
                    $requiredFields = ['firstname', 'lastname', 'email', 'phone', 'password'];
                    if (isset($_POST['email']) AND !mailExists(db: $database, email: $_POST['email'])) {
                        foreach ($requiredFields as $field) {
                            if (empty($_POST[$field])) {
                                echo json_encode(value: ['error' => "Champ <<$field>> manquant"]);
                            }
                        }
                    } else {
                        echo json_encode(value: ['error' => "Adresse mail déjà utilisée"]);
                    }
                    $token = generateToken();
                    $customer_data = [
                        'firstname' => sanitizeInput(input: $_POST['firstname']),
                        'lastname'  => sanitizeInput(input: $_POST['lastname']),
                        'email'     => sanitizeInput(input: $_POST['email']),
                        'phone'     => sanitizeInput(input: $_POST['phone']),
                        'token'     => $token,
                        'password'  => sanitizeInput(input: $_POST['password']),
                        'plot'      => sanitizeInput(input: $_POST['cust_plot']),
                        'street'    => sanitizeInput(input: $_POST['cust_street']),
                        'quarter'   => sanitizeInput(input: $_POST['cust_quarter']),
                        'commune'   => sanitizeInput(input: $_POST['cust_commune']),
                        'city'      => sanitizeInput(input: $_POST['cust_city']),
                        'country'   => sanitizeInput(input: $_POST['cust_country']),
                    ];
                    $newUserInfos = $administrator->createCustomer(data: $customer_data);
                    if (isset($newUserInfos) AND $newUserInfos !== false) {
                        echo json_encode(value: ['success' => true]);
                    } else {
                        echo json_encode(value: ['error' => "Erreur lors de l'enregistrement."]);
                    }
                    break;
                case 'GET':
                    /**
                     * Récupération d'un/des utilisateurs grâce à la requête HTTP GET
                     */
                    if (isset($_GET['id'])) {
                        $customer = Administrator::getCustomer(db: $database, cust_id: $_GET['id']);
                        if ($customer !== null) {
                            // Formattage des données brutes
                            $customerInfos = [
                                'id'            => $customer['cust_id'],
                                'firtName'      => $customer['cust_first_name'],
                                'lastName'      => $customer['cust_last_name'],
                                'email'         => $customer['cust_email'],
                                'phone'         => $customer['cust_phone'],
                                'phoneVerified' => (bool)$customer['cust_phone_verified'],
                                'emailVerified' => (bool)$customer['cust_email_verified'],
                                'password'      => $customer['cust_password'],
                                'status'        => $customer['cust_status'],
                                'token'         => $customer['cust_token'],
                                'dateCreation'  => $customer['created_at'],
                                'addressId'     => $customer['address_id'] ?? '',
                                'addressType'   => $customer['address_type'] ?? '',
                                'plot'          => $customer['plot_num'] ?? '',
                                'street'        => $customer['street'] ?? '',
                                'quarter'       => $customer['quarter'] ?? '',
                                'commune'       => $customer['commune'] ?? '',
                                'city'          => $customer['city'] ?? '',
                                'country'       => $customer['country'] ?? ''
                            ];
                            echo json_encode(value: $customerInfos);
                            // Si l'identifiant est invalide une redirection vers la page index sera effectuée
                        } else {
                            redirect(url: '../index.php');
                        }
                    } else {
                        $customers = Administrator::getAllCustomers(db: $database);
                        $customersInfo = [];
                        foreach ($customers as $customer) {
                            // Formattage des données brutes
                            $cust = [
                                'id'            => $customer['cust_id'],
                                'firtName'      => $customer['cust_first_name'],
                                'lastName'      => $customer['cust_last_name'],
                                'email'         => $customer['cust_email'],
                                'phone'         => $customer['cust_phone'],
                                'phoneVerified' => (bool)$customer['cust_phone_verified'],
                                'emailVerified' => (bool)$customer['cust_email_verified'],
                                'password'      => $customer['cust_password'],
                                'status'        => $customer['cust_status'],
                                'token'         => $customer['cust_token'],
                                'dateCreation'  => $customer['created_at'],
                                'addressId'     => $customer['address_id'],
                                'addressType'   => $customer['address_type'],
                                'plot'          => $customer['plot_num'],
                                'street'        => $customer['street'],
                                'quarter'       => $customer['quarter'],
                                'commune'       => $customer['commune'],
                                'city'          => $customer['city'],
                                'country'       => $customer['country']
                            ];
                            $customersInfo[] = $cust;
                        }
                        echo json_encode(value: $customersInfo);
                    }
                    break;
                default:
                    http_response_code(response_code: 405);
                    echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                    break;
            }
        } catch (Exception $e) {
            error_log(message: 'Erreur du traitement de l\'authentification: ' . $e->getMessage());
            http_response_code(response_code: 500);
            echo json_encode(value: ['error' => 'Erreur interne']);
        }
    }