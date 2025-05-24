<?php
    namespace Payment; // Espace de nom pour les classes de gestion de paiement et les gestionnaires de paiement
    
    use Payment\PaymentHandlerInterface; 
    use Payment\StripePaymentHandler; // Gestionnaire de paiement Stripe (en ligne)
    use Payment\CashOnDeliveryPaymentHandler; // Gestionnaire de paiement cash (à la livraison)
    use Payment\PaymentException;
    
    /**
     * Classe pour la gestion des différents moyens ou méthodes de paiement
     * Juste un Handler (gestionnaire) à implémenter pour chaque méthode
     */
    class PaymentFactory {
        /**
         * Genère un gestionnaire de mode de paiement selon le mode de paiement choisi par le client
         * @param string $method
         * @return CashOnDeliveryPaymentHandler|StripePaymentHandler
         */
        public static function createHandler(string $method): PaymentHandlerInterface {
            return match (strtolower(string: $method)) {
                // Gestionnaire de paiement Stripe (en ligne)
                'stripe' => new StripePaymentHandler(), 
                // Gestionnaire de paiement cash (à la livraison)
                'cash'   => new CashOnDeliveryPaymentHandler(), 
                // Gestion d'érreur personnalisée via la classe PaymentException
                default  => throw new PaymentException(message: "Méthode de paiement non prise en charge $method")
            };
        }
    }