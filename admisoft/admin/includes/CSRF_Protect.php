<?php
	/**
	 * Classe pour la protection contre les attaques de type Cross-Site Request Forgery (CSRF)
	 */
	class CSRF_Protect {
		private $namespace;
		
		/**
		 * Constructeur de la classe 
		 * @param string $namespace
		 */
		public function __construct(string $namespace = '_csrf') {
			$this->namespace = $namespace;
			if (session_id() === '') {
				session_start();
			}
			$this->setToken();
		}
		
		/**
		 * Récupère le token créé  
		 * @return string
		 */
		public function getToken(): string {
			return $this->readTokenFromStorage();
		}
		
		/**
		 * Vérifi si le token crée est valide.
		 * C'est à dire s'il y'a correspondance entre le token attribué à l'utilisateur et celui de la classe
		 * @param mixed $userToken
		 * @return bool
		 */
		public function isTokenValid($userToken): bool {
			return (bool) $userToken === $this->readTokenFromStorage();
		}
		
		/**
		 * Injecte le token dans les formulaires afin de vérifier toujours que le formulaire est soumis par la bonne personne
		 * @return void
		 */
		public function echoInputField(): void {
			$token = $this->getToken();
			echo "<input type=\"hidden\" name=\"{$this->namespace}\" value=\"{$token}\" />";
		}
		
		/**
		 * Vérifie que le token injecté dans le formulaire correspond bien à celui attribué à l'utilisateur
		 * @return void
		 */
		public function verifyRequest(): void {
			if (!$this->isTokenValid(userToken: $_POST[$this->namespace])) {
				die("Echec de validation CSRF");
			}
		}
		
		/**
		 * Crée un token s'il n'existe pas
		 * @return void
		 */
		private function setToken(): void {
			$storedToken = $this->readTokenFromStorage();
			if ($storedToken === '') {
				$token = md5(string: uniqid(prefix: rand(), more_entropy: TRUE));
				$this->writeTokenToStorage(token: $token);
			}
		}
		
		/**
		 * Récupère le token attribué à l'utilisateur à partir de la variable de SESSION
		 * @return mixed
		 */
		private function readTokenFromStorage(): mixed {
			if (isset($_SESSION[$this->namespace])) {
				return $_SESSION[$this->namespace];
			} else {
				return '';
			}
		}

		/**
		 * Attribue à un utilisateur un token directement dans la variable de SESSION afin d'être accessible à partir de n'importe quelle page
		 * @param mixed $token
		 * @return void
		 */
		private function writeTokenToStorage($token): void {
			$_SESSION[$this->namespace] = $token;
		}
	}
