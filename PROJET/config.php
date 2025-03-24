<?php
$host = "localhost"; // Hôte de la base de données
$username = "root";  // Nom d'utilisateur MySQL
$password = "";      // Mot de passe MySQL
$dbname = "suivi_films_series"; // Nom de la base de données

// Connexion à la base de données
$conn = new mysqli($host, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>
