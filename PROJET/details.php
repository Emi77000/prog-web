<?php
session_start();
require_once 'db_connection.php';

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    echo "Paramètres manquants.";
    exit;
}

$id_tmdb = $_GET['id'];
$type = $_GET['type']; // 'movie' ou 'tv'

function fetchTMDB($endpoint, $params = [])
{
    $apiKey = 'f751208ae91021f307bb02f72b63586b'; // Remplace par ta vraie clé
    $url = "https://api.themoviedb.org/3/$endpoint";
    $params['api_key'] = $apiKey;
    $params['language'] = 'fr-FR';
    $url .= '?' . http_build_query($params);

    $response = @file_get_contents($url);
    return json_decode($response, true);
}

$details = fetchTMDB("$type/$id_tmdb");

if (empty($details)) {
    echo "Erreur lors de la récupération des détails.";
    exit;
}

$title = $details['title'] ?? $details['name'];
$overview = $details['overview'] ?? 'Aucune description disponible';
$poster = 'https://image.tmdb.org/t/p/w500' . ($details['poster_path'] ?? '');
$release_date = $details['release_date'] ?? $details['first_air_date'];
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

    <!-- Ajout du bouton Ajouter au catalogue -->
    <button class="add_to_catalog" data-id="<?= $id_tmdb ?>" data-type="<?= $type ?>">Ajouter au catalogue</button>

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
