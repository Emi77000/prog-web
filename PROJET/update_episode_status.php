<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Vérifie si les données sont présentes
if (!isset($_POST['id_episode']) || !isset($_POST['vu'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$id_episode = intval($_POST['id_episode']);
$vu = intval($_POST['vu']); // 0 ou 1

try {
    // Vérifie si une entrée existe déjà
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM suivi_episode WHERE id_utilisateur = ? AND id_episode = ?");
    $stmt_check->execute([$id_utilisateur, $id_episode]);
    $exists = $stmt_check->fetchColumn();

    if ($exists) {
        // Mise à jour
        $stmt_update = $pdo->prepare("UPDATE suivi_episode SET vu = ?, date_vue = NOW() WHERE id_utilisateur = ? AND id_episode = ?");
        $stmt_update->execute([$vu, $id_utilisateur, $id_episode]);
    } else {
        // Insertion
        $stmt_insert = $pdo->prepare("INSERT INTO suivi_episode (id_utilisateur, id_episode, vu, date_vue) VALUES (?, ?, ?, NOW())");
        $stmt_insert->execute([$id_utilisateur, $id_episode, $vu]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
