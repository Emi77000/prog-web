<?php
// Affichage des erreurs pour le debug
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    session_start();
    require_once 'db_connection.php';

    if (!isset($_SESSION['id_utilisateur'])) {
        throw new Exception("Utilisateur non connecté");
    }

    if (!isset($_GET['id']) || !isset($_GET['type'])) {
        throw new Exception("ID TMDB ou type manquant");
    }

    $id_tmdb = $_GET['id'];
    $type = $_GET['type'];
    $id_utilisateur = $_SESSION['id_utilisateur'];

    if (!in_array($type, ['movie', 'tv'])) {
        throw new Exception("Type invalide");
    }

    // --- Fonction API TMDB ---
    function fetchTMDB($endpoint, $params = [])
    {
        $apiKey = 'f751208ae91021f307bb02f72b63586b';
        $url = "https://api.themoviedb.org/3/$endpoint";
        $params['api_key'] = $apiKey;
        $params['language'] = 'fr-FR';
        $url .= '?' . http_build_query($params);

        $response = @file_get_contents($url);
        return json_decode($response, true);
    }

    $details = fetchTMDB("$type/$id_tmdb");

    if (!$details) {
        throw new Exception("Données TMDB introuvables");
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

    // Ajoute dans le catalogue
    $stmt_catalogue = $pdo->prepare("INSERT INTO catalogue_utilisateur (id_utilisateur, id_oeuvre, type)
                                     VALUES (?, ?, ?)");
    $stmt_catalogue->execute([$id_utilisateur, $id_tmdb, $type]);

    // Si c'est une série, ajouter les 3 premières saisons + épisodes
    if ($type === 'tv') {
        $tv_data = fetchTMDB("tv/$id_tmdb");
        $saisons = $tv_data['seasons'] ?? [];

        foreach ($saisons as $saison) {
            $numero_saison = $saison['season_number'];
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
            foreach ($saison_data['episodes'] as $ep) {
                $numero_episode = $ep['episode_number'];
                $titre_episode = $ep['name'] ?? '';
                $resume = $ep['overview'] ?? '';
                $date_diffusion = $ep['air_date'] ?? null;

                $stmt_ep = $pdo->prepare("INSERT INTO episode (id_saison, numero_episode, titre_episode, resume, date_diffusion)
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt_ep->execute([$id_saison, $numero_episode, $titre_episode, $resume, $date_diffusion]);
            }
        }
    }

    echo json_encode(['success' => 'Ajout au catalogue réussi !']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
