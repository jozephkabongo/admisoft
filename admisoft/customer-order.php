<?php
    /**
     * Récupératuin de toutes les commandes d'un client 
     */
    require_once "admin/includes/config.php";
    require_once 'Models/Order.php';
    
    if (isLoggedIn()) {
        $customerOrders = Order::getCustomerOrders(db: $database, customer_id: $_SESSION['customer']['cust_id']);
        echo json_encode(value: $customerOrders);
    } else {
       redirect(url: 'logout.php');
    }