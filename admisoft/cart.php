<?php
    /**
     * Fichier du traitement du panier 
     */
    require_once "admin/includes/config.php";

    if (!isLoggedIn()) {
        redirect(url: 'login.php');
    }

    if (empty($_SESSION['cart'])) {
        redirect(url: 'index.php');
    }
    /**
     * Pour chaque article du panier on ajoute toute ses informations, 
     * Puis on ajoute le prix du total des exemplaires d'un même article
     * Ensuite on ajoute le nombre total des articles uniques du paniers (un exemplaire par article)
     * On ajoute le nombre total des articles (le nombre de tout les exemplaires)
     * Enfin on ajoute le prix total du panier 
     */
    $cartItems = [];

    foreach($cart->getContents() as $item) {
        $cartItems[] = $item; 
        $cartItems[]['totalExemplariesPrice'] = $cart->calculateTotalExemplaries(id: $item['id']);
    }
    $cartItems[]['itemCount'] = $cart->countItems();
    $cartItems[]['totalItems'] = $cart->countTotalProducts();
    $cartItems[]['totalPrice'] = $cart->calculateTotal();

    echo json_encode(value: $cartItems);