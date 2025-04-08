<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$id_episode = $_POST['id_episode'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? null;

if (!$id_episode || !$field || $value === null) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$allowedFields = ['note', 'commentaire'];
if (!in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'error' => 'Champ non autorisé']);
    exit;
}

try {
    // Vérifier si une entrée existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suivi_episode WHERE id_utilisateur = ? AND id_episode = ?");
    $stmt->execute([$id_utilisateur, $id_episode]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Mise à jour
        $sql = "UPDATE suivi_episode SET $field = ?, date_vue = NOW(), vu = 1 WHERE id_utilisateur = ? AND id_episode = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $id_utilisateur, $id_episode]);
    } else {
        // Insertion
        $columns = "id_utilisateur, id_episode, $field, date_vue, vu";
        $placeholders = "?, ?, ?, NOW(), 1";
        $sql = "INSERT INTO suivi_episode ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_utilisateur, $id_episode, $value]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
