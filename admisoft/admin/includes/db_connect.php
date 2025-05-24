<?php

    // Configuration et connexion à la base de données via le query builder NokySQL. 
    // Infos: https://github.com/jozephkabongo/nokysql

    require __DIR__ . '/../../vendor/autoload.php';

    use NokySQL\Database;
    
    $database = new Database(driver: 'mysql', config: [
        'host'     => 'localhost',
        'database' => 'admisoft_db',
        'user'     => 'root',
        'password' => ''
    ]);