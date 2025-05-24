<?php
    /**
     * Traitement du processus de vérification d'adresse mail 
     * afin de s'assurer que celui qui la possède est vraiment son propriétaire 
     */
    require_once "admin/includes/config.php";

    if (isset($_GET['email']) AND isset($_GET['token'])) {
        $customer = new Customer(database: $database);

        if ($customer->mailVerifiy(email: $_GET['email'], token: $_GET['token']) === true) {
            redirect(url: 'index.php');
        } else {
            redirect(url: "login.php?error=Erreur de vérification de l'adresse mail. Veuillez réessayer");
        }
    } else {
        echo json_encode(value: ['error'=> 'Erreur de vérification de l\'adresse mail.']);
    }