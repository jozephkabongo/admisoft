<?php
    /**
     * Classe pour la gestion du panier
     */
    class Cart {
        private array $items = [];

        public function __construct() {
            $this->loadCart();
        }

        /**
         * Charge/récharge le contenu du panier
         * @return void
         */
        private function loadCart(): void {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            } 
            $this->items = &$_SESSION['cart'];
        }

        /**
         * Enregistre l'état du panier dans la ``SESSION`` utilisateur
         * @return void
         */
        private function saveCart(): void {
            $_SESSION['cart'] = $this->items;
        }

        /**
         * Ajoute un nouvel article dans le panier
         * @param int $id identifiant du produit 
         * @param string $name nom du produit
         * @param float $price prix unitaire du produit
         * @param int $quantity quantité du produit mis en panier
         * @param int $maxStock quantité du stock de l'article
         * @throws \Exception exception en cas d'erreur
         * @return void
         */
        public function addItem(int $id, string $name, string $image, string $color, float $price, int $quantity = 1, int $maxStock = 10): void {
            $id = (int)$id;
            $quantity = max(1, (int)$quantity);
            $price = max(0, floatval(value: $price));

            foreach ($this->items as &$item) {
                if ($item['id'] === $id) {
                    $newQuantity = $item['quatity'] + $quantity;
                    if ($newQuantity > $maxStock) {
                        throw new Exception(message: "Quantité maximale ($maxStock) dépassée");
                    }
                    $item['quantity'] = $newQuantity;
                    $this->saveCart();
                    return;
                }
            }

            $this->items[] = [
                'id'       => $id,
                'name'     => htmlspecialchars(string: $name),
                'image'    => $image,
                'color'    => $color,
                'price'    => $price,
                'quantity' => $quantity,
                'stock'    => $maxStock,
                'added_at' => date(format: 'Y-m-d H:i:s')
            ];
        }

        /**
         * Supprime un article du panier
         * @param int $id identifiant du produit à supprimer
         * @return void
         */
        public function removeItem(int $id): void {
            $this->items = array_filter(array: $this->items, callback: function($item) use ($id): bool {
                return $item['id'] !== $id;
            });
            $this->saveCart();
        }

        /**
         * Met à jour la quantité des produits dans le panier
         * @param int $id identifiant du produit
         * @param int $newQuantity nouvelle quantité à prendre en compte
         * @param mixed $maxStock la quantité maximale du stock
         * @throws \Exception exception encas d'erreur
         * @return void
         */
        public function updateQuantity(int $id, int $newQuantity, int $maxStock): void {
            foreach ($this->items as &$item) {
                if ($item['id'] === $id) {
                    if ($newQuantity > $maxStock) {
                        throw new Exception(message: "Stock insuffisant");
                    }
                    $item['quantity'] = $newQuantity;
                    $this->saveCart();
                    return;
                }
            }
            throw new Exception(message: 'Article non trouvé dans le panier');
        }

        /**
         * Réinitialise le panier à zéro(0)
         * @return void
         */
        public function clear(): void  {
            $this->items = [];
            $this->saveCart();
        }

        /**
         * Calcule le total des prix des articles dans le panier
         * @return mixed 
         */
        public function calculateTotal(): mixed {
            return array_reduce(array: $this->items, callback: function($sum, $item): float|int {
                return $sum + ($item['price'] * $item['quantity']);
            }, initial: 0);
        }

        /**
         * Calcule le prix total des produits à plusieurs exemplaires dans le panier 
         * @param int $id
         * @return float
         */
        public function calculateTotalExemplaries(int $id): float|int {
            $exemplaryData = [];
            foreach ($this->items as $item) {
                if ($item['id'] === $id) {
                    $exemplaryData = $item;
                    break;
                }
            }
            return $exemplaryData['price'] * $exemplaryData['quantity'];
        }
        
        /**
         * Rertourne le nombre d'article unique dans le panier
         * @return int
         */
        public function countItems(): int {
            return count(value: $this->items);
        }

        /** 
         * Retourne le nombre total de produits(somme des quantités)
         * @return int
        */
        public function countTotalProducts(): int {
            return array_reduce(array: $this->items, callback: function($sum, $item): mixed {
                return $sum + $item['quantity'];
            }, initial: 0);
        }

        /**
         * Retourne le contenu complet du panier
         * @return array
         */
        public function getContents(): array {
            return $this->items;
        }
    }