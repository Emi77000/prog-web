<?php
$host = "localhost"; // Hôte de la base de données
$username = "emmataieb";  // Nom d'utilisateur MySQL
$password = "password";      // Mot de passe MySQL
$dbname = "suivi_films_series"; // Nom de la base de données

// Connexion à la base de données
$conn = new mysqli($host, $username, $password, $dbname);

define('DB_DSN', 'mysql:host=localhost;dbname=suivi_films_series;charset=utf8');
define('DB_USER', $username); // Remplacez par votre utilisateur MySQL
define('DB_PASS', $password); // Remplacez par votre mot de passe MySQL

// Vérification de la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>
