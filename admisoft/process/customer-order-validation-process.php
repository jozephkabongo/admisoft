<?php
	/**
     * Fichier du traitement du processuss de validation de la commande et du pseudo-paiement
     */
	require_once '../admin/includes/config.php';
	require_once '../Models/Order.php';

    if (!isLoggedIn()) {
        redirect(url: '../login.php');
    }

	if (!isset($_SESSION['cart'])) {
		redirect(url: '../index.php?error=Le panier est vide, veuillez y mettre quelque chose avant de passer commande.');
	}

    try {
		$order = new Order(database: $database);
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                if (empty($_SESSION['cart'])) {
                    redirect(url: '../index.php?error=Le panier est vide, veuillez y mettre quelque chose avant de passer commande.');
                }
                $order_number = 'ON-'. time();
				$orderInfos = $order->createFromCart(cart: $cart, order_number: $order_number, customer_id: $_SESSION['customer']['cust_id'], payment_method: $_POST['payment_method'] ?? redirect(url: $_SERVER['HTTP_REFERER'] . "?error=Veuillez séléctionner un mode de paiement"));
				if ($orderInfos !== null) {
					$cart->clear();
					redirect(url: $orderInfos['paymentUrl']);
				} else {
                    redirect(url: "../index.php?error=Erreur lors de la création de la commande");
                }
				break;
            
            default:
                http_response_code(response_code: 405);
                echo json_encode(value: ['error' => 'Méthode non autorisée']);  
                break;
        }
    } catch (Exception $e) {
        error_log(message: 'Erreur validation commande: ' . $e->getMessage());
    }