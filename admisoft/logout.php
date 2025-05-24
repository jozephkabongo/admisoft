<?php 
    /**
     * Traitement de la déconnexion au compte, 
     * Destruction de la session utilisateur et 
     * Redirection vers la page du login
     */
    ob_start();
    session_start();
    $_SESSION = [];
    session_destroy();
    unset($_SESSION['customer']);
    header(header: 'Location:login.php');
    exit();