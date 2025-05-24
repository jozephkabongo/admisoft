<?php
    namespace Payment;

    use Payment\PaymentException;
    use Payment\PaymentHandlerInterface;
    /**
     * Classe de la gestion de payment en ligne via Stripe 
     * Cette classe implémente l'interface `PaymentHandlerInterface`
     */
    class StripePaymentHandler implements PaymentHandlerInterface {
        public function initializePayment(int $order_id, float $amount): string|null {
            // Simulation de retour d'URL de session Stripe 
            return "URL de $order_id et $amount" ?? null;
        }

        public function handleCallback($request): array {
            /**
             * Dans cette section on devra faire des vérifications 
             * afin de s'assurer que le paiement a été effectué ou pas
             * par le client
             */

            // Simulation de vérification d'un processus de paiement
            if (isset($request['payment_status']) && $request['payment_status'] === 'paid') {
                return [
                    'status'         => 'success',
                    'transaction_id' => $request['transaction_id'] ?? null
                ];
            }
            return [
                'status'         => 'failed',
                'transaction_id' => $request['transaction_id'] ?? null
            ];
        }

        public function getPaymentDetails($transaction_id): array {
            /**
             * Dans cette section on devra faire faire appelle à l'API 
             * afin de récupérer les informations nécessaires à la suite du tratement
             */

            // Simulation du retour des informations venant de l'API
            return [
                'transaction_id' => $transaction_id,
                'status'         => 'success',
                'amount'         => 100,
                'currency'       => 'USD'
            ];
        }

        /**
         * Permet le remboursement après une annulation de commande
         * @param string $transaction_id
         * @param mixed $amount
         * @return void
         */
        public function refund(string $transaction_id, ?float $amount = null): ?array {
            /**
             * Exemple d'implémentation d'une méthode pour éffectuer le remboursement pour les paiements en ligne
             */

            try {
                return [
                    'refund_id' => null,
                    'status'    => null,
                    'amount'    => null,
                    'currency'  => null
                ];
            } catch (PaymentException $pe) {
                error_log(message: "Erreur lors du remboursement: " . $pe->getMessage());
                return null;
            }
        }
    }