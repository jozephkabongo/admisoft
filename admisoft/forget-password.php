<?php
    /**
     * Traitement du processus de demande de réinitialisation du mot de utilisateur client
     */
    require_once "admin/includes/config.php";

    if (isset($_POST['email']) AND filter_var(value: $_POST['email'], filter: FILTER_VALIDATE_EMAIL) === true) {
        if ($database->select(table: 'tbl_customers')->where(condition: 'cust_email = ?', params: [$_POST['email']])->count() === 1) {
            $token = generateToken();
            if (sendForgotPasswordMail(to: $_POST['email'], token: $token) === true) {
                $database->update(table: 'tbl_customers')->set(data: ['cust_token'=> $token])->where(condition: 'cust_email = ?', params: [$_POST['email']])->execute();
                echo json_encode(value: ['success' => true, 'message' => 'Un mail a été envoyé dans votre boîte (vérifier également le dossier spam), veuillez suivre les instruction pour réinitialiser votre mot de passe.']);
            } else {
                echo json_encode(value: ['error' => 'Erreur lors de l\'envoi du mail de vérification.']);
            }
        } else {
            echo json_encode(value: ['error' => 'Aucune information pour cette adresse mail.']);
        }
    } else {
        echo json_encode(value: ['error' => 'Adresse mail invalide.']);
    }