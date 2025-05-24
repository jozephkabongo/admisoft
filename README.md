/////////////////////////////////////////////////////////////////////////////////
//                                                                             //
//              BACKEND DU PROJET ADMISOFT PAR JOSEPH KABONGO                  //
//              Contact: (+243) 850601805                                      //
//              Adresse mail: kabongojozeph@gmail.com                          //
//                                                                             //
/////////////////////////////////////////////////////////////////////////////////

# PROJET ADMISOFT
    Ce dossier contient la logique compléte du traitement en arrière plan du projet AdmiSoft.
    Il contient les répertoires, et les fichiers nécessaires au bon fonctionnement du système selon les règles actuelles

## POUR LE COTE ADMINISTRATEUR 
    Le dossier principale est "admisoft".
    Le dossier contenant toute la logique du côté administrateur est "admin" dans le chemin d'accès "admisoft/admin".
    Le dossier contenant les fichiers de configuration du système et des fonctions utilitaires est "inludes" dans le chemin d'accès "admisoft/admin/inludes".
    Tout les traitements du côté administrateur sont dans le dossier "process" dans le chemin d'accès "admisoft/admin/process".
    Le login administrateur est accessible par le chemin d'accès "admisoft/admin/login.php".
    L'auto-enregistrement administrateur n'est pas prévu afin de garantir que seul le super-administrateur peut créer des administrateurs.
    Le fichier "index.php" accessible par le chemin d'accès "admisoft/admin/index.php" est le rendu du dashboard administrateur, c'est dans ce fichier que seront affichés touts les résumés des informations administratives 
    Le dossier "assets" contient les fichiers téléversés sur le serveur
    Le dossier "Models" contient les classes de gestion du système AdmiSoft
    Le dossier "Payment" contient les classes de gestion du système de paiement et les modes de paiement (pour l'instant le mode de paiement pris en charge est le paiement à la livraison)
    Le dossier "process" contient tout les traitements du côté administrateur
    Le dossier "vendor" contient les bibliothèques externes utilisées dans ce projet (PHPMailer et NokySQL)

## POUR LE COTE CLIENT
    Le côté client inclut les acheteurs et les vendeurs
    Les traitements du client sont accessibles directement dans le dossier "admisoft".
    Le fichier "login.php" gère le traitement de la connexion d'un client à son compte 
    Le fichier "index.php" est la page d'acceuil du site, c'est là que s'effectue le rendu de l'affichage des produis, services, et posts du blog
    Le fichier "cart.php" gère le rendu de l'affichage du contenu du panier du client avec les détails des différents articles du panier ainsi que le prix total
    Le fichier "customer-orders.php" gère le rendu de l'affichage des commandes d'un client précis, en l'occurence le client connecté à son compte
    Le fichier "logout.php" déconnecte un client de son compte et le rédirige vers la page de connexion
    Le fichier "post.php" gère le rendu de l'affichage d'un post du blog
    Le fichier "product-category.php" gère le rendu de l'affichage des produits selon les catégories
    Le fichier "product.php" gère le rendu de l'affichage d'un produit si l'identifiant du produit est fourni, sinon tout les produits
    Le fichier "registartion.php" gère le traitement de l'inscription d'un client
    Le fichier "forgot-password.php" gère le processus d'envoi du code de réinitialisation du mot de passe 
    Le fichier "reset-password.php" gère le traitement de la réinitialisation du mot de passe client
    Le fichier "service.php" gère le rendu de l'affichage d'un service si l'identifiant du service est fourni sinon tout les services
    Le fichier "verify.php" gère le traitement de la vérification du compte client et sa validation 

### CLIENT VENDEUR
    Le client vendeur est l'utilisateur qui crée son compte en tant que vendeur, et pour ce type de d'utilisateur, l'affichage des sera différent.
    L'utilisateur vendeur ne peut voir, récupérer, modifier, supprimier que ses produit, il ne pourra pas accéder aux produits d'autres vendeurs.

### CLIENT ACHETEUR
    Le client acheteur est l'utilisateur qui crée son compte en tant qu'acheteur (c'est dailleur par défaut qu'un utilisateur est client), et pour ce type d'utilisateur, l'affichage sera total pour les produits, les services et les posts du blog, mais il ne poura ni modifier, ni supprimer quoi que ce soit.
    Tout ce qu'il peut faire c'est voir les différentes ressources du système ou annuler une commande dont il est l'auteur.

## NOTE : 
    Pour les deux côtés, les dossiers principaux sont les dossiers "process" (administrateur et client),
    Ces dossiers contiennent tout ce qu'il faut pour créer, récupérer, modifier et supprimer d'une ressource.

    Sauf mofifications, le système gérant ce backend fonctionne correctement, à l'exception des cas particuliers et d'imprévus.