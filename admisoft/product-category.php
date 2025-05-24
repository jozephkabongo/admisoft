<?php
    /**
     * Récupération des produits correspondant à une catégorie donnée
     */
    require_once "admin/includes/config.php";
    require_once 'Models/Product.php';

    if (!isLoggedIn()) {
        redirect(url: 'login.php');
    }
    
    if (!isset($_GET['id'])) {
        redirect(url: 'index.php');
    } else {
        echo json_encode(value: Product::getByCategory(db: $database, category_id: $_GET['id']));
    }