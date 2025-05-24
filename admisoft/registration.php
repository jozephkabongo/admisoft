<?php
    /**
     * Fichier du traitement du processuss d'inscription
     */
    header(header: 'Content-Type: application/json');
    require_once '.admin/includes/config.php'; 
    require_once '.Models/Customer.php';
    $customer = new Customer(database: $database);
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $data = json_decode(json: file_get_contents(filename: 'php://input'), associative: true);
                $data === null ? $data = $_POST : $data;
                $requiredFields = ['firstname', 'lastname', 'email', 'phone', 'password'];
                if (isset($data['email']) AND !mailExists(db: $database, email: $data['email'])) {
                    $token = generateToken();
                    $customer_data = [
                        'firstname' => sanitizeInput(input: $data['firstname']),
                        'lastname'  => sanitizeInput(input: $data['lastname']),
                        'email'     => sanitizeInput(input: $data['email']),
                        'phone'     => sanitizeInput(input: $data['phone']),
                        'token'     => $token,
                        'password'  => $data['password']
                    ];
                    $newUserInfos = $customer->register(data: $customer_data);
                    if (isNonEmptyArray(arrayData: $newUserInfos)) {
                        if (sendVerifyMail(to: $data['email'], token: $token) === true) {
                            echo json_encode(value: ["success" => true, 'message' => "Un mail a été envoyé dans votre boîte, veuillez suivre les instructions pour activer votre compte."]);
                        } else {
                            echo json_encode(value: ['error' => "Erreur lors de l'envoie du code de confirmation"]);
                        }
                    } else {
                        echo json_encode(value: ['error' => "Erreur lors de l'enregistrement."]);
                    }
                } else {
                    echo json_encode(value: ['error' => "Adresse mail déjà utilisée"]);
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