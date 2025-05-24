<?php
    /**
     * Ajout d'un nouveau produit dans le panier du client
     * Les données requises sont : l'ID du produit, le nom du produit, le prix du produit, la quantité qu produit
     * (quantité commandée), et la quantité en stock.
     */
    include '../admin/includes/config.php';
    require_once '../Models/Product.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['quantity'] < 1) {
            die("La quantité minimale d'un produit pour une commande est de 1");
        }
        $productId = (int)$_POST['product_id'];
        $quantity  = (int)$_POST['quantity'];
        $productInfos = Product::get(db: $database, product_id: $productId);
        try {
            $cart->addItem(id: $productId, name: $productInfos['name'], image: $productInfos['image'], color: $productInfos['color'] ?? 'Couleur non détérminée', price: $productInfos['price'], quantity: $quantity, maxStock: $productInfos['stock']);
        } catch (Exception $e) {
            redirect(url: '../product.php?id=' . $productId . '&error=' . urlencode(string: $e->getMessage()));
        }
        redirect(url: $_SERVER['HTTP_REFERER']);
    } else {
        redirect(url: 'index.php');
    }