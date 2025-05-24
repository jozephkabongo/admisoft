<?php
    use NokySQL\Database; // Constructeur de requêtes optimisées, plus d'infos : Infos: https://github.com/jozephkabongo/nokysql

    // Constantes pour la gestion d'érreurs du traitement des fichiers (images)
    /**
     * Indique une érreur lors de l'importation du fichier 
     * @var string
     */
    const FILE_UPLOAD_ERROR    = "Erreur d'importation du fichier";
    /**
     * Indique une érreur relative à la taille trop importante du fichier
     * @var string
     */
    const FILE_SIZE_ERROR      = "Erreur liée à la taille du fichier";
    /**
     * Indique une érreur d'extention non prise en charge par le système
     * @var string
     */
    const FILE_EXTENSION_ERROR = "Erreur d'extention";
    /**
     * Indique qu'une érreur a été trouvée lors du traitement fichier, mais qu'elle n'a pas pu être identifiée
     * @var string
     */
    const FILE_ERROR           = "Erreur inconnue lors du traitement fichier";
    /**
     * Indique une érreur de téléversement du fichier depuis le formulaire du client sur le serveur
     * @var string
     */
    const FILE_UPLOAD_FAILED   = "Erreur de téléversement du fichier sur le serveur";

    /**
     * Vérifie si un utilisateur (client) de la session est connécté, 
     * Et si l'état de son compte est actif
     * @return bool retourne `true` s'il est connecté ou `false` dans tout autre cas 
     */
    function isLoggedIn(): bool {
        return (bool) isset($_SESSION['customer']) && ($_SESSION['customer']['cust_id'] > 0) && ($_SESSION['customer']['cust_status'] === 'active');
    }
    
    function isSellerLoggedIn(): bool {
        return (bool) isset($_SESSION['customer']) && ($_SESSION['customer']['cust_id'] > 0) && ($_SESSION['customer']['cust_status'] === 'active') && ($_SESSION['customer']['cust_role'] === 'seller');
    }

    /**
     * Vérifie si un utilisateur (administrateur) de la session est connécté, 
     * @return bool retourne `true` s'il connecté ou `false` dans tout autre cas 
     */
    function isAdminLoggedIn(): bool {
        return (bool) isset($_SESSION['admin']) && $_SESSION['admin']['id'] > 0 && (isAdmin() OR isSupAdmin());
    }

    /**
     * Vérifie si un utilisateur est administrateur ou pas
     * @return bool retourne `true` s'il est administrateur ou `false` dans tout autre cas 
     */
    function isAdmin(): bool {
        return (bool) isset($_SESSION['admin']) && $_SESSION['admin']['role'] === 'admin';
    }

    /**
     * Vérifie si un utilisateur est super-administrateur ou pas
     * @return bool retourne `true` s'il est super-administrateur ou `false` dans tout autre cas 
     */
    function isSupAdmin(): bool {
        return (bool) isset($_SESSION['admin']) && $_SESSION['admin']['role'] === 'super_admin';
    }

    /**
     * Rédirige un utilisateur vers l'url spécifiée
     * @param string $url l'url de redirection
     * @return never ne retourne jamais une valeur
     */
    function redirect(string $url): never {
        try {
            header(header: "Location: $url");
            exit;
        } catch (Exception $e) {
            error_log(message: "Erreur redirection: " . $e->getMessage());
            exit;
        }
    }

    /**
     * Vérifie si une adresse mail existe déjà
     * @param NokySQL\Database $db Objet du type `Database` du query builder ``NokySQL``
     * @param string $email adresse mail à vérifier  
     * @return bool retourne `true` si le mail existe ou `false` dans tout autre cas 
     */
    function mailExists(Database $db, string $email): bool {
        return (bool) $db->select(table: 'tbl_customers')->select(columns: ['cust_email'])->where(condition: 'cust_email = ?', params: [$email])->first();
    }

    /**
     * Vérifie si une valeur venant d'un formulaire est valide
     * @param string $input la valeur à vérifier
     * @return string 
     */
    function sanitizeInput(string $input): string {
        return htmlspecialchars(string: strip_tags(string: $input));
    }

    /**
     * Genère un jeton de 6 caractères alphanumériques (par défaut) aléatoirement choisi pour la vérification d'adresse mail ou de numéro de téléphone
     * @return string
     */
    function generateToken(): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';
        $maxIndex = strlen(string: $characters) - 1;
        for ($i = 0; $i < 6; $i++) {
            $token .= $characters[random_int(min: 0, max: $maxIndex)];
        }
        return $token;
    }

    /**
     * Vérifie qu'un tableau ne soit pas vide et qu'au moin le premier élément du tableau ne soit également pas vide 
     * @param array|null $arrayData
     * @return bool retourne `true` si les deux conditions sont remplies, ou `false` dans tout autre cas
     */
    function isNonEmptyArray(array|null $arrayData): bool {
        if (!is_array(value: $arrayData) || empty($arrayData)) {
            return false;
        }
        return reset(array: $arrayData) !== null;
    }

    /**
     * Envoie un mail à l'adresse passée à `$to`, et le lien de vérification pour activer le compte utilisateur
     * @param string $to mail du destinataire
     * @param string $token le jeton unique servant de code de vérification
     * @return bool retourne `true` si le mail est envoyé ou `false` dans tout autre cas 
     */
    function sendVerifyMail(string $to, string $token): bool {
        $subject = 'Confirmation du compte';
        $verify_link = "verify.php?email=$to&token=" . urlencode(string: $token);
        $message = "<html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; }
                                .button { background-color:rgb(40, 38, 187); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                            </style>
                        </head>
                        <body>
                            <h2>Bonjour $to,</h2>
                            <p>Merci de vous être inscit sur AdmiSoft. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
                            <p><a href='$verify_link' class='button'>confirmer mon compte</a></p>
                            <p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br><code>$verify_link</code></p>
                            <p>Si vous n'êtes pas l'auteur(e) de cette demande, vous pouvez ignorer ce message.</p>
                            <p>Cordialement, <br>L'équipe AdmiSoft</p>
                        </body>
                    </html>";
        $headers = "From: no-reply@admisoft.com\r\n" .
                    "Reply-To: support@admisoft.com\r\n" .
                    "X-Mailer: PHP/" . phpversion() . "\r\n" . 
                    "MIME-Version: 1.0\r\n" . 
                    "Content-Type: text/html; charset=UTF-8\r\n";
        mail(to: $to, subject: $subject, message: $message, additional_headers: $headers);
        return true;
    }

    /**
     * Envoie un mail à l'adresse passée à `$to`, et le lien de réinitialisation du mot de passe
     * @param string $to adresse mail de l'utilisateur demandant une réinitialisation du mot de passe 
     * @param string $token jeton unique servant de code de réinitialisation
     * @return bool retourne `true` si le mail est envoyé ou `false` dans tout autre cas 
     */
    function sendForgotPasswordMail(string $to, string $token): bool {
        $subject = 'Réinitialisation du mot de passe';
        $reset_password = "reset-password.php?email=$to&token=" . urlencode(string: $token);
        $message = "<html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; }
                                .button { background-color:rgb(40, 38, 187); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                            </style>
                        </head>
                        <body>
                            <h2>Bonjour $to,</h2>
                            <p>Une demande de réinitialisation du mot de passe a été faite avec cette adresse mail. Pour réinitialiser votre mot de passe, veuillez cliquer sur le bouton ci-dessous :</p>
                            <p><a href='$reset_password' class='button'>Réinitialiser mon mot de passe</a></p>
                            <p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br><code>$reset_password</code></p>
                            <p>Si vous n'êtes pas l'auteur(e) de cette demande, vous pouvez ignorer ce message.</p>
                            <p>Cordialement, <br>L'équipe AdmiSoft</p>
                        </body>
                    </html>";
        $headers = "From: no-reply@admisoft.com\r\n" .
                    "Reply-To: support@admisoft.com\r\n" .
                    "X-Mailer: PHP/" . phpversion() . "\r\n" . 
                    "MIME-Version: 1.0\r\n" . 
                    "Content-Type: text/html; charset=UTF-8\r\n";
        mail(to: $to, subject: $subject, message: $message, additional_headers: $headers);
        return true;
    }

    /**
     * Effectue l'importation d'un fiechier et retourne son emplacement après importation
     * Touts les fichiers téléversés sur le server sont dans 'assets/uploads/' ensuite il faut juste préciser le sous dossier dans 'uploads/'
     * @param array $file le tableau des données du ficher
     * @param string $future_path l'emplacement dans lequel sera logé le fichier à près importation
     * @return array un tableau contenat l'meplacement du fichir apès importation (si tout se passe bien), sinon le tableau contient une erreur
     */
    function imageUploader(array $file, string $future_path): array {
        if (isset($file['image_path']) AND isset($file['image_path']['error']) AND $file['image_path']['error'] === UPLOAD_ERR_OK)  {
            $fileExtension = strtolower(string: pathinfo(path: $file['image_path']['name'], flags: PATHINFO_EXTENSION));
            // Extensions requises pour une image
            $allowedExtensions = ['jpeg', 'jpg', 'png']; 
            if (in_array(needle: $fileExtension, haystack: $allowedExtensions)) {
                // la taille maximale requise pour une image (5 Mo en octet)
                if ($file['image_path']['size'] <= 50000000) {   
                    // Construction du nom du fichier avec son emplacement
                    $filename = "/assets/uploads/$future_path" . uniqid(prefix: "ADMISOFT-IMG-", more_entropy: true) . basename(path: $file['image_path']['name']);
                    // Récupération dynamique du chemin du répertoire final /assets/uploads/
                    $filepath = dirname(path: __DIR__, levels: 2) . $filename;
                    if (is_uploaded_file(filename: $file['image_path']['tmp_name'])) {
                        if (move_uploaded_file(from: $file['image_path']['tmp_name'], to: $filepath) === true) { // importation de l'image vers son futur emplacement
                            // Retour du futur emplacement sur le serveur avec un préfixe de personnalisation et un identifiant unique afin de sécurisé chaque fichier 
                            return ['path' => $filename];
                        } else {
                            return ['error' => FILE_UPLOAD_ERROR];
                        }
                    } else {
                        return ['error' => FILE_UPLOAD_FAILED];
                    }
                } else {
                    return ['error' => FILE_SIZE_ERROR];
                }
            } else {
                return ['error' => FILE_EXTENSION_ERROR];
            }
        } else {
            return ['error' => FILE_ERROR];
        }
    }