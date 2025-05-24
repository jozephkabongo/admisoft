<?php
    /**
     * Suppression d'un produit du panier 
     */
    include '../admin/includes/config.php';
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $productId = (int)$_GET['id'];
            $cart->removeItem(id: $productId);
        } catch (Exception $e) {
            redirect(url: 'product.php?id=' . $productId . '&error=' . urlencode(string: $e->getMessage()));
        }
        redirect(url: 'cart.php');
    }