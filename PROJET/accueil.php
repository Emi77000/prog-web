<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

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

$type = $_GET['type'] ?? 'all'; // 'movie', 'tv', ou 'all'

$genres = [];
if ($type === 'tv') {
    $genres = fetchTMDB("genre/tv/list")['genres'] ?? [];
} elseif ($type === 'movie') {
    $genres = fetchTMDB("genre/movie/list")['genres'] ?? [];
} else {
    $genres = array_merge(
        fetchTMDB("genre/movie/list")['genres'] ?? [],
        fetchTMDB("genre/tv/list")['genres'] ?? []
    );
}

$sections = [];
foreach ($genres as $genre) {
    $id = $genre['id'];
    $name = $genre['name'];

    $items = [];
    if ($type === 'tv') {
        $items = fetchTMDB("discover/tv", ['with_genres' => $id])['results'] ?? [];
    } elseif ($type === 'movie') {
        $items = fetchTMDB("discover/movie", ['with_genres' => $id])['results'] ?? [];
    } else {
        $tvItems = fetchTMDB("discover/tv", ['with_genres' => $id])['results'] ?? [];
        $movieItems = fetchTMDB("discover/movie", ['with_genres' => $id])['results'] ?? [];

        // Ajouter type explicitement si mélange
        foreach ($tvItems as &$tv) {
            $tv['media_type'] = 'tv';
        }
        foreach ($movieItems as &$movie) {
            $movie['media_type'] = 'movie';
        }

        $items = array_merge($movieItems, $tvItems);
    }

    if (!empty($items)) {
        $sections[] = [
            'genre' => $name,
            'items' => $items
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Mon Catalogue</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .filter {
            text-align: center;
            margin: 20px 0;
        }
        .filter a {
            margin: 0 10px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            background-color: #e50914;
            border-radius: 5px;
        }
        .carrousel {
            margin: 30px 0;
        }
        .carrousel h2 {
            margin-left: 20px;
            font-size: 1.5em;
        }
        .carrousel-items {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            padding: 10px 20px;
        }
        .carrousel-item {
            flex: 0 0 auto;
            width: 180px;
            margin-right: 15px;
            scroll-snap-align: start;
            background: #333;
            border-radius: 10px;
            overflow: hidden;
        }
        .carrousel-item img {
            width: 100%;
            height: 270px;
            object-fit: cover;
        }
        .carrousel-item h3 {
            font-size: 1em;
            margin: 10px;
            color: white;
            text-align: center;
        }
        .carrousel-item a {
            display: block;
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><a href="accueil.php">Mon Catalogue</a></div>
    <nav>
        <ul>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries </a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<main>
    <div class="filter">
        <a href="accueil.php?type=all">Tout</a>
        <a href="accueil.php?type=movie">Films</a>
        <a href="accueil.php?type=tv">Séries</a>
    </div>

    <?php foreach ($sections as $section): ?>
        <div class="carrousel">
            <h2><?= htmlspecialchars($section['genre']) ?></h2>
            <div class="carrousel-items">
                <?php foreach ($section['items'] as $item):
                    $titre = isset($item['title']) ? $item['title'] : (isset($item['name']) ? $item['name'] : 'Titre inconnu');
                    $poster = $item['poster_path'] ? 'https://image.tmdb.org/t/p/w300' . $item['poster_path'] : '';
                    $mediaType = $item['media_type'] ?? ($type === 'movie' ? 'movie' : 'tv');
                    ?>
                    <div class="carrousel-item">
                        <a href="details.php?id=<?= $item['id'] ?>&type=<?= $mediaType ?>">
                            <?php if ($poster): ?>
                                <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($titre) ?>">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($titre) ?></h3>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Mon Catalogue</p>
</footer>
</body>
</html>