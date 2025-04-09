<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

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

    // Synchronisation du statut dans catalogue_utilisateur
    $stmtOeuvre = $pdo->prepare("
        SELECT o.id_oeuvre
        FROM episode e
        JOIN saison s ON e.id_saison = s.id_saison
        JOIN oeuvre o ON s.id_oeuvre = o.id_oeuvre
        WHERE e.id_episode = ?
    ");
    $stmtOeuvre->execute([$id_episode]);
    $id_oeuvre = $stmtOeuvre->fetchColumn();

    if ($id_oeuvre) {
        // Nombre total d’épisodes de la série
        $stmtTotal = $pdo->prepare("
            SELECT COUNT(*) FROM saison s
            JOIN episode e ON e.id_saison = s.id_saison
            WHERE s.id_oeuvre = ?
        ");
        $stmtTotal->execute([$id_oeuvre]);
        $total = $stmtTotal->fetchColumn();

        // Nombre d’épisodes vus
        $stmtVus = $pdo->prepare("
            SELECT COUNT(*) FROM saison s
            JOIN episode e ON e.id_saison = s.id_saison
            JOIN suivi_episode se ON se.id_episode = e.id_episode
            WHERE s.id_oeuvre = ? AND se.id_utilisateur = ? AND se.vu = 1
        ");
        $stmtVus->execute([$id_oeuvre, $id_utilisateur]);
        $vus = $stmtVus->fetchColumn();

        $nouveauStatut = ($vus == 0) ? 'à voir' : (($vus >= $total) ? 'vu' : 'en cours');

        $stmtMajStatut = $pdo->prepare("
            UPDATE catalogue_utilisateur
            SET statut = :statut
            WHERE id_utilisateur = :id_utilisateur AND id_oeuvre = :id_oeuvre
        ");
        $stmtMajStatut->execute([
            'statut' => $nouveauStatut,
            'id_utilisateur' => $id_utilisateur,
            'id_oeuvre' => $id_oeuvre
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
