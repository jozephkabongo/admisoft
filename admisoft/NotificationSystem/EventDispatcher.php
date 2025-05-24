<?php
    namespace NotificationSystem;
    /**
     * Déclencheur d'événemnt
     * Gère le déclenchement des événement 
     */
    class EventDispatcher {
        /**
         * Instance unique
         * @var EventDispatcher|null
         */
        private static ?EventDispatcher $instance = null;

        /**
         * Tableau de listeners (écouteurs d'événement)
         */
        private array $listeners = [];

        /**
         * Constructeur privé afin d'empêcher l'instanciation externe
         */
        private function __construct() {}

        /**
         * Récupération de l'instance unique
         */
        public static function getInstance(): EventDispatcher {
            if (self::$instance === null) {
                self::$instance = new EventDispatcher();
            }
            return self::$instance;
        }

        /**
         * Enregistre un listener pour un evenement donné
         */
        public function addListener(string $eventName, callable $listener): void {
            $this->listeners[$eventName][] = $listener;
        }

        /**
         * Déclenche l'événement
         * @param object $event
         * @return void
         */
        public function dispatch(object $event): void {
            $eventName = get_class(object: $event);
            if (!empty($this->listeners[$eventName])) {
                foreach ($this->listeners[$eventName] as $listener) {
                    $listener($event);
                }
            }
        }
    }