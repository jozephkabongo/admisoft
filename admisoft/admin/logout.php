<?php 
    /**
     * Destruction de la session utilisateur et redirection vers la page du login
     */
    ob_start();
    session_start();
    $_SESSION = [];
    session_destroy();
    unset($_SESSION['admin']);
    header(header: 'Location:login.php');
    exit();