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
    $resultatsRecherche = rechercherTMDB($termeRecherche);
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
    </style>
</head>
<body>
<header class="header">
    <nav>
        <ul style="display: flex; align-items: center; margin: 0;">
            <!-- "TrackFlix" ajouté comme un élément de la liste -->
            <li style="margin-right: auto;">
                <a href="accueil.php" style="font-size: 2em; color: white; text-decoration: none;">TrackFlix</a>
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

<script>
    // Récupérer l'élément modale et le bouton de fermeture
    var modal = document.getElementById("modal");
    var span = document.getElementsByClassName("close")[0];

    // Quand l'utilisateur clique sur un élément du carrousel
    document.querySelectorAll('.carrousel-item a').forEach(function(element) {
        element.addEventListener('click', function(event) {
            event.preventDefault(); // Empêche le lien de s'ouvrir normalement
            var id = this.getAttribute('href').split('=')[1]; // Récupérer l'ID du film ou de la série
            var type = this.getAttribute('href').split('=')[2];

            // Charger les détails via AJAX
            fetch('details.php?id=' + id + '&type=' + type)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modal-details').innerHTML = data;
                    modal.style.display = "block"; // Ouvrir la modale
                })
                .catch(error => console.log('Erreur de chargement des détails :', error));
        });
    });

    // Quand l'utilisateur clique sur "X", fermer la modale
    span.onclick = function() {
        modal.style.display = "none";
    }

    // Quand l'utilisateur clique en dehors de la modale, fermer la modale
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
