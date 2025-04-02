<?php
session_start();
require_once('db_connection.php');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_oeuvre = $_POST['id_oeuvre']; // ⬅️ Nouveau nom correct
    $field = $_POST['field']; // 'statut', 'note', 'commentaire'
    $value = $_POST['value'];

    if (!in_array($field, ['statut', 'note', 'commentaire'])) {
        echo json_encode(['success' => false, 'error' => 'Champ invalide']);
        exit;
    }

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Sécurité : empêcher injection SQL dans le nom du champ
        $allowed_fields = ['statut', 'note', 'commentaire'];
        if (!in_array($field, $allowed_fields)) {
            throw new Exception("Champ interdit.");
        }

        // Construire la requête dynamiquement (champ sécurisé)
        $sql = "UPDATE catalogue_utilisateur SET $field = :value WHERE id_oeuvre = :id_oeuvre AND id_utilisateur = :id_utilisateur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':value' => $value,
            ':id_oeuvre' => $id_oeuvre,
            ':id_utilisateur' => $_SESSION['id_utilisateur']
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode invalide']);
}
