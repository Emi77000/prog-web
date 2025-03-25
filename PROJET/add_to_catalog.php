<?php
session_start();
require_once('db_connection.php');

// Définir le type de réponse JSON pour AJAX
header("Content-Type: application/json");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Vous devez être connecté pour ajouter un film."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$id_tmdb = isset($_POST['id_tmdb']) ? intval($_POST['id_tmdb']) : null;

// Vérifier si l'ID du film est présent
if (!$id_tmdb) {
    echo json_encode(["error" => "ID du film manquant."]);
    exit;
}

try {
    // Activer le mode exception PDO pour voir les erreurs SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur existe dans la base
    $checkUserQuery = "SELECT id_utilisateur FROM Utilisateurs WHERE id_utilisateur = :id_utilisateur";
    $checkUserStmt = $pdo->prepare($checkUserQuery);
    $checkUserStmt->execute(['id_utilisateur' => $user_id]);

    if ($checkUserStmt->rowCount() === 0) {
        echo json_encode(["error" => "Utilisateur introuvable."]);
        exit;
    }

    // Vérifier si le film existe dans FilmsSeries
    $checkFilmQuery = "SELECT id_tmdb FROM FilmsSeries WHERE id_tmdb = :id_tmdb";
    $checkFilmStmt = $pdo->prepare($checkFilmQuery);
    $checkFilmStmt->execute(['id_tmdb' => $id_tmdb]);

    if ($checkFilmStmt->rowCount() === 0) {
        echo json_encode(["error" => "Film non trouvé dans la base de données."]);
        exit;
    }

    // Vérifier si le film est déjà ajouté au catalogue
    $checkQuery = "SELECT COUNT(*) FROM Catalogue WHERE id_utilisateur = :id_utilisateur AND id_tmdb = :id_tmdb";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute(['id_utilisateur' => $user_id, 'id_tmdb' => $id_tmdb]);
    $filmExists = $checkStmt->fetchColumn();

    if ($filmExists > 0) {
        echo json_encode(["error" => "Ce film est déjà dans votre catalogue."]);
        exit;
    }

    // Insérer le film dans le catalogue
    $insertQuery = "INSERT INTO Catalogue (id_utilisateur, id_tmdb, statut) 
                    VALUES (:id_utilisateur, :id_tmdb, 'à voir')";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute(['id_utilisateur' => $user_id, 'id_tmdb' => $id_tmdb]);

    echo json_encode(["success" => "Film ajouté avec succès !"]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur SQL : " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur : " . $e->getMessage()]);
}
?>
