<?php
session_start();
require_once('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_tmdb'])) {
    $id_tmdb = intval($_POST['id_tmdb']);
    $id_utilisateur = $_SESSION['user_id'];

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sql = "DELETE FROM Catalogue WHERE id_tmdb = :id_tmdb AND id_utilisateur = :id_utilisateur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_tmdb' => $id_tmdb,
            'id_utilisateur' => $id_utilisateur
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Élément non trouvé']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
}
?>
