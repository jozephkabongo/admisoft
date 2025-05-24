<?php
    namespace NotificationSystem;
    /**
     * Gestionnaire d'événement liés aux commandes
     */
    class OrderStatusChangedEvent {
        public function __construct(
            public readonly string $orderId,
            public readonly string $oldStatus,
            public readonly string $newStatus,
            public readonly string $customerEmail
        ) {}
    }