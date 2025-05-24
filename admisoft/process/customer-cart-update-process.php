<?php
    /**
     * Mise à jour du panier en case de modification 
     */
    include '../admin/includes/config.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            for ($i = 0; $i < count(value: $_POST['product_id']) && $i < count(value: $_POST['quantity']); $i++) {
                $productId = (int)$_POST['product_id'][$i];
                $quantity  = (int)$_POST['quantity'][$i];
                $stock     = (int)$_POST['item_stock'][$i];
                $cart->updateQuantity(id: $productId, newQuantity: $quantity, maxStock: $stock);
            }
        } catch (Exception $e) {
            redirect(url: 'product.php?id=' . $productId . '&error=' . urlencode(string: $e->getMessage()));
        }
        redirect(url: 'cart.php');
    } else {
        redirect(url: 'index.php');
    }