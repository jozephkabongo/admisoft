<?php
    /**
     * Récupération des détails d'un post en utilisant l'identifiant du produit concerné
     */
    require_once "admin/includes/config.php";
    require_once 'Models/Post.php';

    if (!isLoggedIn()) {
        redirect(url: 'login.php');
    }

    if (isset($_GET['id'])) {
        $postInfos = Post::get(db: $database, post_id: $_GET['id']);
        if (isNonEmptyArray(arrayData: $postInfos)) {
            echo json_encode(value: $postInfos);
        } else {
            redirect(url: 'index.php?error=Le post que vous cherchez est introuvable');
        }
    } else {
        echo json_encode(value: Post::getAll(db: $database));
    }