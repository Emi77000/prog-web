<?php
require_once 'fetch_tmdb.php';

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;

if (!$id || !$type) {
    echo "Paramètres manquants.";
    exit;
}

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
            margin-top: 30px;
            text-align: center;
        }
        .details-container img {
            width: 300px;
            height: 450px;
            object-fit: cover;
            border-radius: 10px;
        }
        .details-container h2 {
            font-size: 2em;
            margin-top: 20px;
        }
        .details-container p {
            font-size: 1.1em;
            color: #ccc;
            margin-top: 10px;
            width: 60%;
            margin: 0 auto;
        }
        .add-to-catalog {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #e50914;
            color: white;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .add-to-catalog:hover {
            background-color: #f40612;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo"><a href="accueil.php">Mon Catalogue</a></div>
</header>

<main class="details-container">
    <img src="<?= htmlspecialchars($poster) ?>" alt="Poster de <?= htmlspecialchars($title) ?>">
    <h2><?= htmlspecialchars($title) ?></h2>
    <p><strong>Date de sortie : </strong><?= htmlspecialchars($release_date) ?></p>
    <p><?= htmlspecialchars($overview) ?></p>

    <!-- Bouton d'ajout au catalogue -->
    <button class="add-to-catalog" data-id="<?= htmlspecialchars($id_tmdb) ?>" data-type="<?= htmlspecialchars($type) ?>">
        Ajouter au catalogue
    </button>

    <div class="back-button">
        <a href="accueil.php?type=<?= htmlspecialchars($type) ?>">Retour à l'accueil</a>
    </div>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Mon Catalogue</p>
</footer>

<script src="add_to_catalog.js"></script>
</body>
</html>
