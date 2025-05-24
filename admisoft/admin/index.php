<?php
	/**
	 * Fichier du dashboard administrateur, à partir de ce fichier on assure la vue général de létat du ststème 
	 */
	require_once "includes/config.php";
	if (isAdminLoggedIn()) {
		if (isset($_GET['error'])) {
			echo '
			<script>
				alert('.$_GET['error'].');
			</script>
			';
		}
		/**
		 * Récupérations des différents totaux avec une seule exécution en parallèle pour toute les requètes:
		 * Total des catégories
		 * Total des produits
		 * Total des Clients
		 * Total des commandes validées et livrées
		 * Total des commandes en attente de livraison
		 */
		$database->select(table: 'tbl_categories')->queue();
		$database->select(table: 'tbl_products')->queue();
		$database->select(table: 'tbl_customers')->where(condition: 'cust_status = ?', params: ['active'])->queue();
		$database->select(table: 'tbl_administrators')->queue();
		$database->select(table: 'tbl_orders')->where(condition: 'status = ?', params: ['delivered'])->queue();
		$database->select(table: 'tbl_orders')->where(condition: 'status = ?', params: ['pending'])->queue();
		[$totalCategories, $totalProducts, $totalCustomers, $totalAdministrators, $totalCompletedOrders, $totalPendingOrders] = $database->executeParallel();
		// Formatage des totaux
		$adminView = [
			'categories'       => count(value: $totalCategories),
			'products'         => count(value: $totalProducts),
			'customers'        => count(value: $totalCustomers),
			'administrators'   => count(value: $totalAdministrators),
			'completed_orders' => count(value: $totalCompletedOrders),
			'pending_orders'   => count(value: $totalPendingOrders)
		];
		echo json_encode(value: $adminView);
	} else {
		redirect(url: 'logout.php');
	}