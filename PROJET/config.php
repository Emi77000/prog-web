<?php
$host = "localhost"; // Hôte de la base de données
$username = "root";  // Nom d'utilisateur MySQL
$password = "123soleil";      // Mot de passe MySQL
$dbname = "suivi_films_series"; // Nom de la base de données

// Connexion à la base de données
$pdo = new mysqli($host, $username, $password, $dbname);

define('DB_DSN', 'mysql:host=localhost;dbname=suivi_films_series;charset=utf8');
define('DB_USER', $username); // Remplacez par votre utilisateur MySQL
define('DB_PASS', $password); // Remplacez par votre mot de passe MySQL

// Vérification de la connexion
if ($pdo->connect_error) {
    die("Connexion échouée : " . $pdo->connect_error);
}
?>
