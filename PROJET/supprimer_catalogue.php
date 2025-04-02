<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_oeuvre = $_POST['id_oeuvre'] ?? null;

    if (!$id_oeuvre) {
        echo json_encode(['success' => false, 'error' => 'ID de l\'œuvre manquant']);
        exit;
    }

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Suppression de l'œuvre du catalogue de l'utilisateur
        $sql = "DELETE FROM catalogue_utilisateur WHERE id_utilisateur = :id_utilisateur AND id_oeuvre = :id_oeuvre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_utilisateur' => $_SESSION['id_utilisateur'],
            ':id_oeuvre' => $id_oeuvre
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
