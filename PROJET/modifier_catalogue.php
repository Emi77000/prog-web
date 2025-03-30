<?php
session_start();
require_once('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données envoyées par AJAX
    $id_tmdb = $_POST['id_tmdb'];
    $field = $_POST['field']; // statut, note ou commentaire
    $value = $_POST['value'];

    if (!in_array($field, ['statut', 'note', 'commentaire'])) {
        echo json_encode(['success' => false, 'error' => 'Champ invalide']);
        exit;
    }

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Mettre à jour le champ demandé dans la base de données
        $sql = "UPDATE Catalogue SET $field = :value WHERE id_tmdb = :id_tmdb AND id_utilisateur = :id_utilisateur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':value' => $value,
            ':id_tmdb' => $id_tmdb,
            ':id_utilisateur' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode invalide']);
}
?>
