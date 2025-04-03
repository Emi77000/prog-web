<?php
session_start();
require_once 'db_connection.php';
require_once 'fetch_tmdb.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_tmdb = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;
$id_utilisateur = $_SESSION['id_utilisateur'];

if (!$id_tmdb || !$type) {
    echo json_encode(['error' => 'Paramètres manquants.']);
    exit;
}

$details = getDetailsTMDB($id_tmdb, $type);

if (!$details) {
    echo json_encode(['error' => 'Œuvre introuvable.']);
    exit;
}

$titre = $details['title'] ?? $details['name'] ?? 'Titre inconnu';
$annee = substr($details['release_date'] ?? $details['first_air_date'] ?? '', 0, 4);
$poster = $details['poster_path'] ? "https://image.tmdb.org/t/p/w500" . $details['poster_path'] : null;
$resume = $details['overview'] ?? '';
$genre = isset($details['genres'][0]['name']) ? $details['genres'][0]['name'] : null;

// Vérifie si l'œuvre est déjà en base
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM oeuvre WHERE id_oeuvre = ?");
$stmt_check->execute([$id_tmdb]);
$exists = $stmt_check->fetchColumn();

if (!$exists) {
    $stmt_insert = $pdo->prepare("INSERT INTO oeuvre (id_oeuvre, titre, type, annee_sortie, genre, affiche, resume)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->execute([$id_tmdb, $titre, $type, $annee, $genre, $poster, $resume]);
}

// Vérifie si elle est déjà dans le catalogue utilisateur
$stmt_dupli = $pdo->prepare("SELECT COUNT(*) FROM catalogue_utilisateur WHERE id_utilisateur = ? AND id_oeuvre = ?");
$stmt_dupli->execute([$id_utilisateur, $id_tmdb]);
$alreadyInCatalog = $stmt_dupli->fetchColumn();

if (!$alreadyInCatalog) {
    $stmt_catalogue = $pdo->prepare("INSERT INTO catalogue_utilisateur (id_utilisateur, id_oeuvre, type)
                                     VALUES (?, ?, ?)");
    $stmt_catalogue->execute([$id_utilisateur, $id_tmdb, $type]);
}

// Si c'est une série, on ajoute les saisons et épisodes
if ($type === 'tv') {
    $tv_data = fetchTMDB("tv/$id_tmdb");
    $saisons = $tv_data['seasons'] ?? [];

    foreach ($saisons as $saison) {
        $numero_saison = $saison['season_number'];

        // Pour éviter de surcharger la BDD, limite à 3 saisons max
        if ($numero_saison > 5) continue;

        $saison_data = fetchTMDB("tv/$id_tmdb/season/$numero_saison");

        $titre_saison = $saison_data['name'] ?? "Saison $numero_saison";
        $date_sortie = $saison_data['air_date'] ?? null;
        $annee_sortie = $date_sortie ? substr($date_sortie, 0, 4) : null;

        // Insertion saison
        $stmt_s = $pdo->prepare("INSERT INTO saison (id_oeuvre, numero_saison, titre_saison, date)
                                 VALUES (?, ?, ?, ?)");
        $stmt_s->execute([$id_tmdb, $numero_saison, $titre_saison, $annee_sortie]);
        $id_saison = $pdo->lastInsertId();

        // Insertion épisodes
        foreach ($saison_data['episodes'] ?? [] as $ep) {
            $numero_episode = $ep['episode_number'];
            $titre_episode = $ep['name'] ?? '';
            $resume_ep = $ep['overview'] ?? '';
            $date_diffusion = $ep['air_date'] ?? null;

            $stmt_ep = $pdo->prepare("INSERT INTO episode (id_saison, numero_episode, titre_episode, resume, date_diffusion)
                                      VALUES (?, ?, ?, ?, ?)");
            $stmt_ep->execute([$id_saison, $numero_episode, $titre_episode, $resume_ep, $date_diffusion]);
        }
    }
}

echo json_encode(['success' => 'Ajout au catalogue réussi !']);
exit;
