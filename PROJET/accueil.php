<?php
session_start();
require_once 'db_connection.php';
require_once 'fetch_tmdb.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

// --------- GESTION DE LA RECHERCHE -----------
$termeRecherche = $_GET['recherche'] ?? null;
$resultatsRecherche = [];

if (!empty($termeRecherche)) {
    // Normaliser le terme
    $termeRechercheMin = strtolower(trim($termeRecherche));

    // Rechercher par mot-clé (titre)
    $resultatsRecherche = rechercherTMDB($termeRecherche);

    // Rechercher par genre
    $genresMovie = fetchTMDB("genre/movie/list")['genres'] ?? [];
    $genresTv = fetchTMDB("genre/tv/list")['genres'] ?? [];

    // Fusionner tous les genres et éviter les doublons
    $tousGenres = [];
    foreach (array_merge($genresMovie, $genresTv) as $genre) {
        $nomGenreMin = strtolower($genre['name']);
        $tousGenres[$nomGenreMin] = $genre['id'];
    }

    if (array_key_exists($termeRechercheMin, $tousGenres)) {
        $idGenre = $tousGenres[$termeRechercheMin];

        // Chercher des films/séries de ce genre
        $filmsGenre = fetchTMDB("discover/movie", ['with_genres' => $idGenre])['results'] ?? [];
        $seriesGenre = fetchTMDB("discover/tv", ['with_genres' => $idGenre])['results'] ?? [];

        // Ajouter le type pour différencier
        foreach ($filmsGenre as &$film) {
            $film['media_type'] = 'movie';
        }
        foreach ($seriesGenre as &$serie) {
            $serie['media_type'] = 'tv';
        }

        // Fusionner avec les résultats de recherche de titre
        $resultatsRecherche = array_merge($resultatsRecherche, $filmsGenre, $seriesGenre);
    }
}


// --------- DONNÉES CARROUSELS PAR GENRE --------
$type = $_GET['type'] ?? 'all';
$genreId = $_GET['genre'] ?? null;

$sections = [];
$genres = fetchTMDB("genre/tv/list")['genres'] ?? [];
if ($type === 'movie') {
    $genres = fetchTMDB("genre/movie/list")['genres'] ?? [];
} elseif ($type === 'all') {
    $genresMovie = fetchTMDB("genre/movie/list")['genres'] ?? [];
    $genresTv = fetchTMDB("genre/tv/list")['genres'] ?? [];
    $genres = array_merge($genresMovie, $genresTv);
}

foreach ($genres as $genre) {
    if ($genreId && $genreId != $genre['id']) {
        continue; // Si un genre est sélectionné, ne traiter que ce genre
    }
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

$type = $_GET['type'] ?? 'all';
$genres = [];

if ($type === 'tv') {
    $genres = fetchTMDB("genre/tv/list")['genres'] ?? [];
} elseif ($type === 'movie') {
    $genres = fetchTMDB("genre/movie/list")['genres'] ?? [];
} else {
    $genresMovie = fetchTMDB("genre/movie/list")['genres'] ?? [];
    $genresTv = fetchTMDB("genre/tv/list")['genres'] ?? [];

    $genres = [];
    $addedNames = [];

    foreach (array_merge($genresMovie, $genresTv) as $genre) {
        $nomGenreMin = strtolower($genre['name']);
        if (!in_array($nomGenreMin, $addedNames)) {
            $genres[] = $genre;
            $addedNames[] = $nomGenreMin;
        }
    }
}

$sections = [];
foreach ($genres as $genre) {
$genreId = $_GET['genre'] ?? null;
    if ($genreId && $genreId != $genre['id']) {
        continue;  // Si un genre est sélectionné, ne traiter que ce genre
    }

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
    <title>Accueil - TrackFlix</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="accueil.css">
    <script type="module" src="accueil.js"></script>
</head>
<body>
<header class="header">
    <nav>
        <ul style="display: flex; align-items: center; margin: 0;">
            <!-- "TrackFlix" ajouté comme un élément de la liste -->
            <li style="margin-right: auto;">
                <a href="accueil.php" style="font-size: 2em;">TrackFlix</a>
            </li>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries</a></li>
            <li><a href="compte.php">Compte</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>


<main>
    <!-- Barre de recherche et liste déroulante regroupées dans un même conteneur -->
<div class="search-container">
    <!-- Barre de recherche -->
    <div class="search-bar">
        <form method="GET" action="accueil.php">
            <input type="text" name="recherche" placeholder="Rechercher un film ou une série..." value="<?= htmlspecialchars($termeRecherche ?? '') ?>" required>
            <button type="submit">Rechercher</button>
        </form>
    </div>

    <div class="filter">
    <a href="accueil.php?type=all&genre=<?= $genreId ?>">Tout</a>
    <a href="accueil.php?type=movie&genre=<?= $genreId ?>">Films</a>
    <a href="accueil.php?type=tv&genre=<?= $genreId ?>">Séries</a>
</div>
<div class="filter-genre">
    <label for="genre-select" style="color: white;">Trier par genre :</label>
    <select id="genre-select" name="genre" onchange="window.location.href='accueil.php?type=<?= $type ?>&genre=' + this.value">
        <option value="" <?= !isset($_GET['genre']) ? 'selected' : '' ?>>Tous les genres</option>
        <?php foreach ($genres as $genre): ?>
            <option value="<?= $genre['id'] ?>" <?= isset($_GET['genre']) && $_GET['genre'] == $genre['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($genre['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


</div>

    <!-- Résultats de recherche -->
    <?php if (!empty($termeRecherche)): ?>
        <h2 style="margin-left: 30px;">Résultats pour "<?= htmlspecialchars($termeRecherche) ?>"</h2>
        <div class="resultats-recherche">
            <?php foreach ($resultatsRecherche as $media):
                $titre = !empty($media['title']) ? $media['title'] : (!empty($media['name']) ? $media['name'] : 'Sans titre');
                $poster = !empty($media['poster_path'])
                    ? 'https://image.tmdb.org/t/p/w200' . $media['poster_path']
                    : 'https://via.placeholder.com/200x300?text=Pas+de+visuel';
                $mediaType = $media['media_type'] ?? 'movie';
                ?>
                <div class="resultat-item">
                    <a href="details.php?id=<?= $media['id'] ?>&type=<?= $mediaType ?>" class="open-modal">
                        <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($titre ?? '') ?>">
                        <h4><?= htmlspecialchars($titre ?? '') ?></h4>
                    </a>
                </div>
            <?php endforeach; ?>
        </div> <!-- fin .resultats-recherche -->
        <?php if (empty($resultatsRecherche)): ?>
            <p style="color: white; margin-left: 30px;">Aucun résultat trouvé pour "<?= htmlspecialchars($termeRecherche) ?>"</p>
        <?php endif; ?>
    <?php endif; ?>


    <?php if (empty($termeRecherche)): ?>
        <!-- Carrousels par genre -->
        <?php foreach ($sections as $section): ?>
            <div class="carrousel">
                <h2><?= htmlspecialchars($section['genre']) ?></h2>
                <div class="carrousel-items">
                    <?php foreach ($section['items'] as $item):
                        $titre = !empty($item['title']) ? $item['title'] : (!empty($item['name']) ? $item['name'] : 'Sans titre');
                        $poster = !empty($item['poster_path']) ? 'https://image.tmdb.org/t/p/w300' . $item['poster_path'] : '';
                        $mediaType = $item['media_type'] ?? ($type === 'movie' ? 'movie' : 'tv');
                        ?>
                        <div class="carrousel-item">
                            <a href="details.php?id=<?= $item['id'] ?>&type=<?= $mediaType ?>" class="open-modal">
                                <?php if ($poster): ?>
                                    <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($titre ?? '') ?>">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($titre ?? '') ?></h3>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


</main>

<!-- Modale pour afficher les détails -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modal-details">
            <!-- Les détails du film/serie seront insérés ici par JavaScript -->
        </div>
    </div>
</div>

<div id="confirmation-message">

</body>
</html>