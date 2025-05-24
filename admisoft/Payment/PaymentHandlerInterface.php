<?php
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                                                                                                               //
    //   NOTE :                                                                                                      //
    //   Cette Interface est un modèle obligatoire de tout PaymentHandler (gestionnaire de paiment) sur ce système.  //
    //   Sauf modifications tout les moyens/modes de paiement doivent se référer à cette interface                   //
    //                                                                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    namespace Payment;
    /**
     * Modèle d'un `PaymentHandler` dans le système
     */
    interface PaymentHandlerInterface {
        /**
         * Lance le processus de paiement 
         * @param int $order_id
         * @param float $amount
         * @return string|null URL ou ID de paiement
         */
        public function initializePayment(int $order_id, float $amount): ?string;

        /**
         * Traite le callback après retour du prestataire 
         * @param array  $request Données reçues du prestataire
         * @return array Résultat 
         */
        public function handleCallback(array $request): array;

        /**
         * Récupère les détails d'une transaction
         * @param string $transaction_id 
         * @return array Informations de la transaction
         */
        public function getPaymentDetails(string $transaction_id): array;
    }