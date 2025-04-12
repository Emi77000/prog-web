<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'fetch_tmdb.php';

$id = $_GET['id'] ?? null; // ID de l'œuvre
$type = $_GET['type'] ?? null; // Type de l'œuvre (film ou série)

if (!$id || !$type) {
    echo "Paramètres manquants.";
    exit;
}

// Récupérer les détails de l'œuvre via l'API TMDB
$details = getDetailsTMDB($id, $type);

if (!$details) {
    echo "Aucune information trouvée.";
    exit;
}

// Récupération des infos
$titre = $details['title'] ?? $details['name'] ?? 'Titre inconnu';
$description = $details['overview'] ?? 'Aucune description disponible.';
$affiche = $details['poster_path']
    ? 'https://image.tmdb.org/t/p/w300' . $details['poster_path']
    : 'https://via.placeholder.com/300x450?text=Pas+de+visuel';
$date = $details['release_date'] ?? $details['first_air_date'] ?? 'Date inconnue';
$genres = array_map(function ($g) {
    return $g['name'];
}, $details['genres'] ?? []);
$duree = $details['runtime'] ?? ($details['episode_run_time'][0] ?? null);

// Pour affichage HTML
$poster = $affiche;
$title = $titre;
$release_date = $date;
$overview = $description;
$id_tmdb = $id;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails - <?= htmlspecialchars($title) ?> - Mon Catalogue</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="details.css">
</head>
<body>
<main class="details-container">
    <img src="<?= htmlspecialchars($poster) ?>" alt="Poster de <?= htmlspecialchars($title) ?>">
    <div class="details-text">
        <h2><?= htmlspecialchars($title) ?></h2>
        <p><strong>Date de sortie : </strong><?= htmlspecialchars($release_date) ?></p>
        <p><?= htmlspecialchars($overview) ?></p>

        <?php if (!empty($genres)): ?>
            <p><strong>Genres : </strong><?= implode(', ', $genres) ?></p>
        <?php endif; ?>

        <?php if ($duree): ?>
            <p><strong>Durée : </strong><?= htmlspecialchars($duree) ?> minutes</p>
        <?php else: ?>
            <p><strong>Durée : </strong>Non spécifiée</p>
        <?php endif; ?>

        <div class="buttons-container">
            <button class="add-to-catalog" data-id="<?= htmlspecialchars($id_tmdb) ?>" data-type="<?= htmlspecialchars($type) ?>">
                Ajouter au catalogue
            </button>
        </div>
    </div>
</main>

</body>
</html>
