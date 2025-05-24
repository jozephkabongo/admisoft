<?php
    namespace NotificationSystem;

    use NotificationSystem\ListenerInterface;
    use NotificationSystem\OrderStatusChangedEvent;
    use NotificationSystem\SimpleMailer;
    /**
     * Ecouteur d'événément déclenché par la mise à jour du statut (étatt) de la commande
     */
    class SendOrderStatusEmailListener implements ListenerInterface {
        /**
         * Méthode `obligatoire` venant de l'implémentation de l'interface `ListenerInterface`
         * @param object $event
         * @return void
         */
        public function handle(object $event): void {
            if (! $event instanceof OrderStatusChangedEvent) {
                return;
            }

            $subject = "Mise à jour de votre commande #{$event->orderId}";
            $body = "
                <h1>Commande #{$event->orderId}</h1>
                <p>Votre commande est passée de <strong>{$event->oldStatus}</strong> à <strong>{$event->newStatus}</strong>.</p>
            ";

            $mailer = new Mailer();
            $mailer->send(to: $event->customerEmail, subject: $subject, body: $body);
        }
    }