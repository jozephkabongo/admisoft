<?php
    /**
     * Traitement du processus de réinitialisation du mot de passe utilisateur client
     */
    require_once "admin/includes/config.php";
    
    $isValid = true;
    
    if(!isset($_GET['email']) OR !isset($_GET['token'])) {
        $isValid = false;
        $error = "Adresse mail ou code de réinitialisation manquant";
    }

    if (!isset($_POST['new_password']) OR ($_POST['new_password'] != $_POST['new_password2'])) {
        $isValid = false;
        $error = "Mot de passe manquant ou incorrect";
    }

    if ($database->select(table: 'tbl_customers')->where(condition: 'cust_mail = ? AND cust_token = ?', params: [$_GET['email'], $_GET['token']])->count() != 1) {
        $isValid = false;
        $error = "Aucune information pour cette adresse mail";
    }

    if ($isValid === true) {
        $database->update(table: 'tbl_customers')->set(data: ['cust_password'=> $_POST['new_password'], 'cust_token'=> ''])->where(condition: 'cust_email = ?', params: [$_GET['email']])->execute();
        redirect(url: 'login.php');
    } else {
        echo json_encode(value: ['error'=> $error]);
    }