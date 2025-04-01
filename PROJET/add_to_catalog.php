<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connection.php';

// Vérifier que la connexion PDO existe
if (!isset($pdo) || !$pdo instanceof PDO) {
    echo json_encode(['error' => 'Erreur de connexion à la base de données.']);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Vous devez être connecté pour ajouter un film.']);
    exit;
}

if (!isset($_POST['id_tmdb']) || !is_numeric($_POST['id_tmdb'])) {
    echo json_encode(['error' => 'ID TMDB invalide ou manquant.']);
    exit;
}

$id_utilisateur = $_SESSION['user_id'];
$id_tmdb = intval($_POST['id_tmdb']);

try {
    // Vérifie si le film est déjà dans le catalogue de l'utilisateur
    $check = $pdo->prepare("SELECT 1 FROM catalogue WHERE id_utilisateur = ? AND id_tmdb = ?");
    $check->execute([$id_utilisateur, $id_tmdb]);

    if ($check->fetch()) {
        echo json_encode(['error' => 'Ce film est déjà dans votre catalogue.']);
        exit;
    }

    // Insertion dans la table `catalogue`
    $stmt = $pdo->prepare("INSERT INTO catalogue (id_utilisateur, id_tmdb) VALUES (?, ?)");
    $stmt->execute([$id_utilisateur, $id_tmdb]);

    echo json_encode(['success' => 'Film ajouté à votre catalogue.']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur SQL : ' . $e->getMessage()]);
}
?>
