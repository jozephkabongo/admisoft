<?php
    /**
     * Fichier du traitement du processuss de mise à jour des données utilisateur
     */
    header(header: 'Content-Type: application/json');
    require_once '../admin/includes/config.php'; 
    require_once '../Models/Customer.php';
    if (!isLoggedIn()) {
        redirect(url: 'logout.php');
    }
    $customer = new Customer(database: $database);
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                // Formation du tableau des données de mise à jour
                $dataToUpdate = [
                    'customer_id'  => (int)$_SESSION['customer']['cust_id'],
                    'address_id'   => (int)$_POST['cust_address_id'] ?? null,
                    'address_type' => $_POST['cust_address_type'] ?? null,
                    'firstname'    => strip_tags(string: $_POST['cust_first_name']) ?? null,
                    'lastname'     => strip_tags(string: $_POST['cust_last_name']) ?? null,
                    'email'        => strip_tags(string: $_POST['cust_email']) ?? null,
                    'phone'        => strip_tags(string: $_POST['cust_phone']) ?? null,
                    'plot'         => $_POST['cust_plot_num'] ?? null,
                    'street'       => strip_tags(string: $_POST['cust_street']) ?? null,
                    'quarter'      => strip_tags(string: $_POST['cust_quarter']) ?? null,
                    'commune'      => strip_tags(string: $_POST['cust_commune']) ?? null,
                    'city'         => strip_tags(string: $_POST['cust_city']) ?? null,
                    'country'      => strip_tags(string: $_POST['cust_country']) ?? null
                ];
                if (isset($dataToUpdate['address_id']) AND $dataToUpdate['address_id'] > 0) {
                    // Si l'adresse existe déjà on la met à jour avec les nouvelles informatiions
                    if ($customer->update(data: $dataToUpdate) == true) {
                        $_SESSION['customer']['cust_first_name'] = strip_tags(string: $_POST['cust_first_name']);
                        $_SESSION['customer']['cust_last_name']  = strip_tags(string: $_POST['cust_last_name']);
                        $_SESSION['customer']['cust_email']      = strip_tags(string: $_POST['cust_email']);
                        $_SESSION['customer']['cust_phone']      = strip_tags(string: $_POST['cust_phone']);
                        $_SESSION['customer']['address_id']      = strip_tags(string: $_POST['cust_address_id']);
                        $_SESSION['customer']['address_type']    = strip_tags(string: $_POST['cust_address_type']);
                        $_SESSION['customer']['plot_num']        = strip_tags(string: $_POST['cust_plot_num']);
                        $_SESSION['customer']['street']          = strip_tags(string: $_POST['cust_street']);
                        $_SESSION['customer']['quarter']         = strip_tags(string: $_POST['cust_quarter']);
                        $_SESSION['customer']['commune']         = strip_tags(string: $_POST['cust_commune']);
                        $_SESSION['customer']['city']            = strip_tags(string: $_POST['cust_city']);
                        $_SESSION['customer']['country']         = strip_tags(string: $_POST['cust_country']);
                    } else {
                        throw new Exception(message: 'Erreur de mise à jour adresse utilisateur');
                    }
                } else {
                    // Sinon (si l'adresse n'existe pas encore) on crée une nouvelle adresse pour l'utilisateur 
                    if ($customer->addAdress(data: $dataToUpdate) == true) {
                        $_SESSION['customer']['cust_first_name'] = strip_tags(string: $_POST['cust_first_name']);
                        $_SESSION['customer']['cust_last_name']  = strip_tags(string: $_POST['cust_last_name']);
                        $_SESSION['customer']['cust_email']      = strip_tags(string: $_POST['cust_email']);
                        $_SESSION['customer']['cust_phone']      = strip_tags(string: $_POST['cust_phone']);
                        $_SESSION['customer']['address_id']      = strip_tags(string: $_POST['cust_address_id']);
                        $_SESSION['customer']['address_type']    = strip_tags(string: $_POST['cust_address_type']);
                        $_SESSION['customer']['plot_num']        = strip_tags(string: $_POST['cust_plot_num']);
                        $_SESSION['customer']['street']          = strip_tags(string: $_POST['cust_street']);
                        $_SESSION['customer']['quarter']         = strip_tags(string: $_POST['cust_quarter']);
                        $_SESSION['customer']['commune']         = strip_tags(string: $_POST['cust_commune']);
                        $_SESSION['customer']['city']            = strip_tags(string: $_POST['cust_city']);
                        $_SESSION['customer']['country']         = strip_tags(string: $_POST['cust_country']);
                    } else {
                        throw new Exception(message: 'Erreur de création adresse utilisateur');
                    }
                }
                redirect(url: $_SERVER['HTTP_REFERER']);
                break;
            default:
                http_response_code(response_code: 405);
                echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                break;
        }
    } catch (Exception $e) {
        error_log(message: 'Erreur du traitement de l\'authentification: ' . $e->getMessage());
        http_response_code(response_code: 500);
        echo json_encode(value: ['error' => 'Erreur interne' . $e->getMessage()]);
    }