<?php
    namespace Payment; // Espace de nom pour les classes de gestion de paiement et les gestionnaires de paiement
    use Payment\PaymentHandlerInterface; // Interface modèle de gestionnaires de moyens de paiement
    
    class CashOnDeliveryPaymentHandler implements PaymentHandlerInterface {
        public function initializePayment(int $order_id, float $amount): string|null {
            // Paiement en à la livraison : retour direct
            return "../payment-success.php";
        }

        public function handleCallback(array $request): array {
            // Retour direct car le paiement sera faite à la livraison
            return [
                'status'   => 'pending',
                'order_id' => $request['order_id']
            ];
        }

        public function getPaymentDetails(string $transaction_id): array {
            // Pas de transaction avant livraison
            // Retour direct
            return [
                'transaction_id' => $transaction_id,
                'status'        => 'pending',
                'amount'        => null,
                'currency'      => null
            ];
        }
    }