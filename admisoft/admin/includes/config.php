<?php
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//																													//
	//	Fichier principal des configurations: lancement des sessions, les rapports d'erreurs, le fuseau horaire			//
	//	la connexion à la base de données, les fonctions utilitaires, la protection contre les attaques de type CSRF,	//
	//	l'initialisation du panier, l'initialisation de l'couteur des changement des statuts des commandes				//
	//																													//
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	ob_start();
	session_start();
	ini_set(option: 'error_reporting', value: E_ALL);
	date_default_timezone_set(timezoneId: 'Africa/Kinshasa');
	include "db_connect.php"; 
	include "functions.php"; 
	include "CSRF_Protect.php"; 
	include __DIR__ . "../../../Models/Cart.php"; 
	require_once dirname(path: __DIR__ , levels: 2). '/NotificationSystem/autoload.php';
    use NotificationSystem\EventDispatcher;
	use NotificationSystem\OrderStatusChangedEvent;
	use NotificationSystem\SendOrderStatusEmailListener;
	$csrf = new CSRF_Protect();
	$cart = new Cart();
	$dispatcher = EventDispatcher::getInstance();
	$dispatcher->addListener(
		eventName: OrderStatusChangedEvent::class,
		listener: [new SendOrderStatusEmailListener(), 'handle']
	);