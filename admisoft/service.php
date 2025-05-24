<?php
    /**
     * Récupération des détails d'un service en utilisant l'identifiant du produit concerné
     */
    require_once "admin/includes/config.php";
    require_once 'Models/Service.php';

    if (!isLoggedIn()) {
        redirect(url: 'login.php');
    }

    if (isset($_GET['id'])) {
        $serviceInfos = Service::get(db: $database, service_id: $_GET['id']);
        if (isNonEmptyArray(arrayData: $serviceInfos)) {
            echo json_encode(value: $serviceInfos);
        } else {
            redirect(url: 'index.php?error=Le service recherché est introuvable');
        }
    } else {
        echo json_encode(value: Service::getAll(db: $database));
    }