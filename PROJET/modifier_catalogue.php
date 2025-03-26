<?php
require_once('config.php');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_utilisateur = $_SESSION['user_id'];
    $id_tmdb = $_POST['id_tmdb'] ?? null;
    $field = $_POST['field'] ?? null;
    $value = $_POST['value'] ?? null;

    // Vérification des données
    if (!$id_tmdb || !$field || $value === null) {
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }

    // Vérifier que le champ est autorisé
    $allowed_fields = ['statut', 'note', 'commentaire'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['success' => false, 'error' => 'Champ non autorisé']);
        exit;
    }

    // Vérification des contraintes
    if ($field === 'statut' && !in_array($value, ['vu', 'en cours', 'à voir'])) {
        echo json_encode(['success' => false, 'error' => 'Statut invalide']);
        exit;
    }

    if ($field === 'note') {
        $value = floatval($value);
        if ($value < 0 || $value > 5) {
            echo json_encode(['success' => false, 'error' => 'Note invalide']);
            exit;
        }
    }

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Vérifier si l'entrée existe
        $checkSql = "SELECT COUNT(*) FROM Catalogue WHERE id_utilisateur = :id_utilisateur AND id_tmdb = :id_tmdb";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([':id_utilisateur' => $id_utilisateur, ':id_tmdb' => $id_tmdb]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            echo json_encode(['success' => false, 'error' => 'Film/Série non trouvé']);
            exit;
        }

        // Mise à jour dynamique
        $sql = "UPDATE Catalogue SET $field = :value WHERE id_utilisateur = :id_utilisateur AND id_tmdb = :id_tmdb";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':value' => $value,
            ':id_utilisateur' => $id_utilisateur,
            ':id_tmdb' => $id_tmdb
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
