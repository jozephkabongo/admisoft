<?php
    /**
     * Récupération des détails d'un produit en utilisant l'identifiant du produit concerné
     */
    require_once "admin/includes/config.php";
    require_once 'Models/Product.php';

    if (!isLoggedIn()) {
        redirect(url: 'login.php');
    }

    if (isset($_GET['id'])) {
        $productInfos = Product::get(db: $database, product_id: $_GET['id']);
        if (isNonEmptyArray(arrayData: $productInfos)) {
            echo json_encode(value: $productInfos);
        } else {
            redirect(url: 'index.php?error=Le produit recherché est introuvable');
        }
    } else {
        echo json_encode(value: Product::getAll(db: $database));
    }