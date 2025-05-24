<?php
    /**
     * Fichier du chargerment automatique des fichiers de classe d'écouteurs d'événements et de notifications
     */
    spl_autoload_register(callback: function($className): void {
        $classPath = dirname(path: __DIR__) . '/' . str_replace(search: '\\', replace: '/', subject: $className) . '.php';
        if (file_exists(filename: $classPath)) {
            require_once $classPath;
        } else {
            die("Fichier de classe introuvable : $classPath");
        }
    });