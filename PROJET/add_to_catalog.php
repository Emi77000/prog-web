<?php
session_start();
require_once('db_connection.php');  // Assure-toi que la connexion à la base de données est bien incluse

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Utilisateur non connecté"]);
    exit;
}

// Vérifier si l'ID du film est passé en POST
if (!isset($_POST['id_tmdb']) || !is_numeric($_POST['id_tmdb'])) {
    echo json_encode(["success" => false, "error" => "ID du film manquant ou invalide"]);
    exit;
}

$id_utilisateur = $_SESSION['user_id'];
$id_tmdb = $_POST['id_tmdb'];  // L'ID du film envoyé en POST

try {
    // Utiliser la connexion déjà définie dans db_connection.php
    // Pas besoin de recréer la connexion ici, la variable $pdo doit déjà être définie

    // Vérifier si le film est déjà dans le catalogue de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM Catalogue WHERE id_utilisateur = :id_utilisateur AND id_tmdb = :id_tmdb");
    $stmt->execute(['id_utilisateur' => $id_utilisateur, 'id_tmdb' => $id_tmdb]);

    if ($stmt->rowCount() == 0) {
        // Ajouter le film au catalogue avec statut 'à voir'
        $query = "INSERT INTO Catalogue (id_utilisateur, id_tmdb, statut) VALUES (:id_utilisateur, :id_tmdb, 'à voir')";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id_utilisateur' => $id_utilisateur, 'id_tmdb' => $id_tmdb]);

        // Retourner une réponse JSON indiquant que l'ajout a réussi
        echo json_encode(["success" => true]);
    } else {
        // Le film est déjà dans le catalogue de l'utilisateur
        echo json_encode(["success" => false, "error" => "Film déjà dans le catalogue"]);
    }
} catch (PDOException $e) {
    // En cas d'erreur, afficher le message d'erreur
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
