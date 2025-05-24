<?php   
    namespace Payment; // Espace de nom pour les classes de gestion de paiement et les gestionnaires de paiement
    use Exception;
    /**
     * Gestion personnalisée des erreurs d'exception
     */
    class PaymentException extends Exception {}