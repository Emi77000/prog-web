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
    <style>
        body {
            background-color: #141414;
            color: white;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #141414;
            padding: 15px 0;
            text-align: center;
        }

        .logo a {
            color: white;
            text-decoration: none;
            font-size: 2em;
        }

        .back-button {
            text-align: center;
            margin-top: 20px;
        }

        .back-button a {
            color: white;
            text-decoration: none;
            background-color: #e50914;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .back-button a:hover {
            background-color: #f40612;
        }

        .details-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding: 0 20px;
        }

        .details-container img {
            width: 300px;
            height: 450px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 40px;
        }

        .details-text {
            background-color : #1e1e1e;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
        }

        .details-text h2 {
            font-size: 1.8em;
            margin-top: 10px;
        }

        .details-text p {
            font-size: 1em;
            line-height: 1.5;
            margin-top: 8px;
        }

        .buttons-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            width: 100%;
        }

        .add-to-catalog, .back-button a {
            padding: 10px 20px;
            background-color: #e50914;
            color: white;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-block;
            flex: 1;
            text-align: center;
        }

        .back-button a {
            background-color: black;
        }

        .add-to-catalog:hover, .back-button a:hover {
            background-color: #f40612;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #222;
            padding: 20px 30px;
            width: 60%;
            max-width: 800px;
            color: white;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
        }


        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 0;
            right: 10px;
            cursor: pointer;
        }

        .close:hover, .close:focus {
            color: #f40612;
        }

        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: transparent;
            color: white;
            font-size: 1em;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .back-to-home:hover {
            color: #e50914;
        }

    </style>
</head>
<body>
<main class="details-container">
    <img src="<?= htmlspecialchars($poster) ?>" alt="Poster de <?= htmlspecialchars($title) ?>">
    <div class="details-text">
        <h2><?= htmlspecialchars($title) ?></h2>
        <p><strong>Date de sortie : </strong><?= htmlspecialchars($release_date) ?></p>
        <p><?= htmlspecialchars($overview) ?></p>

        <!-- Afficher les genres si disponibles -->
        <?php if (!empty($genres)): ?>
            <p><strong>Genres : </strong><?= implode(', ', $genres) ?></p>
        <?php endif; ?>

        <!-- Afficher la durée si disponible -->
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
