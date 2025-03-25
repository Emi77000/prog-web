<?php
// Configuration de la connexion à la base de données
$host = 'localhost';  // Hôte de la base de données (par défaut, c'est localhost)
$dbname = 'suivi_films_series';  // Nom de la base de données
$username = 'root';  // Nom d'utilisateur MySQL
$password = '123soleil';  // Mot de passe MySQL (modifiez-le si nécessaire)

try {
    // Création d'une instance PDO pour se connecter à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optionnel : définir le charset pour éviter les problèmes d'encodage
    $pdo->exec("SET NAMES 'utf8'");
    
    // Si la connexion est réussie
    // echo "Connexion réussie à la base de données.";
} catch (PDOException $e) {
    // Si la connexion échoue, afficher l'erreur
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    die();  // Arrêter l'exécution du script
}
?>
