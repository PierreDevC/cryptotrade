# CryptoTrade 📈

Salut et bienvenue sur CryptoTrade ! C'est une plateforme web de simulation de trading de cryptomonnaies, développée principalement en PHP avec une architecture MVC "maison".

## C'est quoi l'idée ? 🤔

L'objectif, c'est de fournir une interface simple et sympa pour :
*   S'inscrire et se connecter.
*   Consulter un tableau de bord avec son solde (fictif !) et ses actifs crypto.
*   Voir une liste de cryptomonnaies avec des prix simulés qui bougent un peu.
*   Acheter et vendre ces cryptos (toujours en mode simulation).
*   Voir son historique de transactions.
*   Modifier son profil.
*   (Pour l'admin) Gérer les cryptomonnaies disponibles sur la plateforme.

Le tout est rendu dynamique côté client grâce à AJAX et Chart.js pour les graphiques.

## Tech Stack 🛠️

*   **Backend :** PHP 8+ (orienté objet)
*   **Base de Données :** MySQL / MariaDB
*   **Frontend :** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Chart.js, FontAwesome
*   **Architecture :** MVC (Modèle-Vue-Contrôleur) custom, Front Controller Pattern
*   **Serveur :** Apache (avec mod_rewrite pour les URLs sympas via `.htaccess`)

## Installation 🚀

Pour faire tourner ça sur votre machine, jetez un œil au tutoriel détaillé ci-dessous ou dans le fichier `INSTALL.md` (si vous préférez le créer).

## Licence ©️

[MIT License] - 

---

## Tutoriel d'Installation 

**Prérequis : Ce qu'il vous faut avant de commencer**

1.  **Un serveur web local :** Le plus simple, c'est d'utiliser un pack comme XAMPP (Windows/Mac/Linux), WAMP (Windows), ou MAMP (Mac). Ça installe Apache (le serveur web), MySQL/MariaDB (la base de données), et PHP, tout d'un coup.
2.  **Git :** Pour récupérer le code source depuis le dépôt.
3.  **Un navigateur web :** Évidemment ! Chrome, Firefox, Edge...
4.  **Un outil pour gérer la base de données :** phpMyAdmin (souvent inclus avec XAMPP/WAMP/MAMP) est parfait pour ça.

**Étapes d'Installation**

1.  **Récupérer le Code :**
    *   Ouvrez un terminal ou une invite de commande.
    *   Naviguez jusqu'au dossier où votre serveur web sert les fichiers (souvent `htdocs` dans XAMPP, `www` dans WAMP/MAMP).
    *   Clonez le dépôt :
        ```bash
        git clone [URL_DE_VOTRE_DEPOT_GIT] cryptotrade
        ```
        (Remplacez `[URL_DE_VOTRE_DEPOT_GIT]` par l'URL réelle. Le code sera dans un dossier nommé `cryptotrade`).

2.  **Configuration Apache (peut-être) :**
    *   Assurez-vous que le module `mod_rewrite` d'Apache est activé (c'est souvent le cas par défaut).
    *   Vérifiez la configuration de votre serveur Apache pour vous assurer que les fichiers `.htaccess` sont autorisés (directive `AllowOverride All` pour le dossier où vous avez mis le projet). Si vous utilisez XAMPP, c'est souvent déjà configuré.

3.  **Création de la Base de Données :**
    *   Lancez phpMyAdmin (généralement accessible via `http://localhost/phpmyadmin`).
    *   Créez une nouvelle base de données. Nommez-la exactement `cryptotrade_db` (c'est ce qui est dans `config.php` par défaut). Choisissez un encodage comme `utf8mb4_general_ci`.
    *   **Important :** Vérifiez le fichier `config.php` à la racine du projet. Par défaut, il s'attend à se connecter avec l'utilisateur `root` sans mot de passe (`DB_USER = 'root'`, `DB_PASS = ''`). Si votre configuration MySQL est différente (vous avez mis un mot de passe à `root` ou vous utilisez un autre utilisateur), **modifiez ces lignes dans `config.php` !**

4.  **Importation de la Structure et des Données Initiales :**
    *   Dans phpMyAdmin, sélectionnez votre base de données `cryptotrade_db` fraîchement créée.
    *   Allez dans l'onglet "Importer".
    *   Cliquez sur "Choisir un fichier" et sélectionnez le fichier `database/schema.sql` qui se trouve dans le dossier du projet. Ce fichier crée les tables. Cliquez sur "Exécuter" (ou "Importer").
    *   **Ensuite (très important, faites-le après !) :** Refaites la même chose : onglet "Importer", "Choisir un fichier", mais cette fois sélectionnez `database/seed.sql`. Ce fichier insère les cryptomonnaies de base et peut-être un utilisateur admin/test. Cliquez sur "Exécuter".

5.  **Vérification Finale de la Configuration :**
    *   Ouvrez à nouveau le fichier `config.php` à la racine.
    *   Assurez-vous que `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` correspondent bien à votre environnement de base de données.
    *   **Très important :** Vérifiez la ligne `define('BASE_URL', 'http://localhost/cryptotrade');`. Cette URL **doit** correspondre exactement à la manière dont vous allez accéder à l'application dans votre navigateur. Si vous avez mis le dossier `cryptotrade` directement dans `htdocs`, cette URL est probablement correcte. Si vous utilisez un Virtual Host ou un autre chemin, adaptez-la !

6.  **Permissions (rarement nécessaire en local, mais bon à savoir) :**
    *   Le serveur web (Apache) doit pouvoir écrire dans le dossier `storage/logs/` (pour le fichier `audit.log`) et sur le fichier `storage/last_update.txt`. Sous Windows avec XAMPP, c'est généralement OK par défaut. Sous Linux/Mac, un petit `chmod` pourrait être nécessaire si vous rencontrez des erreurs liées à l'écriture de ces fichiers.

7.  **Accéder à l'Application :**
    *   Ouvrez votre navigateur et allez à l'URL définie dans `BASE_URL` (par défaut : `http://localhost/cryptotrade`).
    *   Et voilà ! Vous devriez voir la page d'accueil.

Amusez-vous bien avec CryptoTrade !
