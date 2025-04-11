<?php
// Configuration de la connexion à la base de données
define('DB_HOST', 'localhost');  // Hôte de la base de données (par défaut, c'est localhost)
define('DB_NAME', 'suivi_films_series');  // Nom de la base de données
define('DB_USER', 'emmataieb');  // Nom d'utilisateur MySQL
define('DB_PASS', 'password');  // Mot de passe MySQL (modifiez-le si nécessaire)
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8');

try {
    // Création d'une instance PDO pour se connecter à la base de données
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Si la connexion est réussie
    // echo "Connexion réussie à la base de données.";
} catch (PDOException $e) {
    // Si la connexion échoue, afficher l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
