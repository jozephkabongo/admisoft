<?php
    /**
     * Fichier index.php
     */
    require_once "admin/includes/config.php";
    require_once 'Models/Product.php';
    require_once 'Models/Category.php';
    require_once 'Models/Service.php';
    require_once 'Models/Post.php';

    if (!isLoggedIn()) {
        redirect(url: 'login.php');
    }

    if (isset($_GET['error'])) {
        echo '
        <script>
            alert('.$_GET['error'].');
        </script>
        ';
    }

    $products   = Product::getAll(db: $database);
    $categories = Category::getAll(db: $database);
    $services   = Service::getAll(db: $database);
    $posts      = Post::getAll(db: $database);
    
    $customerView = [
        'products'   => $products,
        'categories' => $categories,
        'services'   => $services,
        'posts'      => $posts
    ];

    echo json_encode(value: $customerView);