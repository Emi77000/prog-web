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
        .search-bar {
            text-align: center;
            margin: 30px 0;
        }
        .search-bar input[type="text"] {
            padding: 10px;
            width: 300px;
            font-size: 16px;
        }
        .search-bar button {
            padding: 10px 16px;
            font-size: 16px;
            background-color: #e50914;
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 5px;
        }
        .resultats-recherche {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
            padding: 0 30px;
        }

        .resultat-item {
            width: 160px;
            text-align: center;
            background-color: #222;
            border-radius: 8px;
            overflow: hidden;
        }
        .resultat-item img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }
        .resultat-item h4 {
            color: white;
            font-size: 14px;
            padding: 8px;
            margin: 0;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .carrousel-item:hover {
        transform: scale(1.05); /* Agrandit légèrement l'élément au survol */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6); /* Ajoute une ombre portée pour l'effet */
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

        /* Style de la modale */
        .modal {
            display: none; /* Cachée par défaut */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7); /* Fond sombre */
        }

        .modal-content {
            background-color: #222;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 800px;
            color: white;
            border-radius: 10px;
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

        .close:hover,
        .close:focus {
            color: #f40612;
        }

        .filter a {
        margin: 0 15px;
        text-decoration: none;
        font-weight: bold;
        padding: 10px 20px;
        background-color: #e50914; /* Couleur de fond rouge */
        color: white; /* Couleur du texte */
        border-radius: 30px; /* Coins arrondis */
        transition: all 0.3s ease; /* Ajoute une transition douce pour les effets */
        }

        .filter a:hover {
            background-color: white; /* Fond blanc au survol */
            color: #e50914; /* Texte rouge au survol */
            transform: scale(1.1); /* Agrandit légèrement au survol */
        }

        .filter a.active {
            background-color: #ff2a6b; /* Couleur pour le bouton actif */
            color: #fff; /* Couleur du texte */
        }

        header nav ul li a {
        color: white;
        text-decoration: none;
        margin: 0 10px;
        }

        header nav ul li a:hover {
            color: red;  /* Texte devient rouge au survol */
        }
    </style>
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

    <!-- Barre de recherche -->
    <div class="search-bar">
        <form method="GET" action="accueil.php">
            <input type="text" name="recherche" placeholder="Rechercher un film ou une série..." value="<?= htmlspecialchars($termeRecherche ?? '') ?>" required>
            <button type="submit">Rechercher</button>
        </form>
    </div>

    <!-- Filtres par type -->
    <div class="filter">
        <a href="accueil.php?type=all">Tout</a>
        <a href="accueil.php?type=movie">Films</a>
        <a href="accueil.php?type=tv">Séries</a>
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

<script type="module">
    import { activerBoutonsAjout } from './details.js';

    const modal = document.getElementById("modal");
    const span = document.getElementsByClassName("close")[0];

    document.querySelectorAll('.carrousel-item a, .resultat-item a').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();

            const urlParams = new URL(this.href).searchParams;
            const id = urlParams.get('id');
            const type = urlParams.get('type');

            fetch('details.php?id=' + id + '&type=' + type)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modal-details').innerHTML = data;
                    modal.style.display = "block";
                    activerBoutonsAjout(); // 👈 relie les boutons une fois le HTML injecté
                })
                .catch(error => console.error('Erreur de chargement des détails :', error));
        });
    });

    span.onclick = function () {
        modal.style.display = "none";
    };
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
</script>

<div id="confirmation-message" style="
    display: none;
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #28a745;
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    z-index: 1000;
    transition: opacity 0.3s ease;
">
    Ajouté au catalogue !
</div>
</body>
</html>
