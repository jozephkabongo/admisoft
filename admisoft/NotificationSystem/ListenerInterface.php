<?php
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                                                                                                       //
    //   NOTE :                                                                                              //
    //   Cette Interface est un modèle obligatoire de tout Listener (ecouter d'événement) sur ce système.    //
    //   Sauf modifications tout les écouteurs doivent se référer à cette interface                          //
    //                                                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    namespace NotificationSystem;
    /**
     * Modèle d'un `Listener` dans le système
     */
    interface ListenerInterface {
        /**
         * Gèere les événements
         * @param object $event
         * @return void
         */
        public function handle(object $event): void;
    }