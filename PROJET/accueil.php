<?php
require_once('db_connection.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$pseudo = $_SESSION['pseudo'];
$user_id = $_SESSION['user_id'];
$api_key = 'f751208ae91021f307bb02f72b63586b';
$films_par_page = 50;

function fetch_tmdb_data($url_base, $api_key, $films_par_page) {
    $results = [];
    for ($page = 1; $page <= ceil($films_par_page / 20); $page++) {
        $api_url = $url_base . "?api_key=$api_key&language=fr-FR&page=$page";
        $response = file_get_contents($api_url);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['results'])) {
                $results = array_merge($results, $data['results']);
            }
        }
    }
    return $results;
}

$films = fetch_tmdb_data('https://api.themoviedb.org/3/movie/popular', $api_key, $films_par_page);
$series = fetch_tmdb_data('https://api.themoviedb.org/3/tv/popular', $api_key, $films_par_page);
$all_items = array_merge($films, $series);

foreach ($all_items as $item) {
    $id_tmdb = $item['id'];
    $titre = $item['title'] ?? $item['name'];
    $type_oeuvre = isset($item['title']) ? 'film' : 'serie';
    $annee_sortie = isset($item['release_date']) ? substr($item['release_date'], 0, 4) : (isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : null);
    $poster_url = isset($item['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : null;
    $description = $item['overview'] ?? '';
    $popularite = $item['popularity'] ?? 0;
    $genres = isset($item['genre_ids']) ? implode(',', $item['genre_ids']) : '';
    $query = "INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite, genres)
              VALUES (:id_tmdb, :titre, :type_oeuvre, :annee_sortie, :poster, :description, :popularite, :genres)
              ON DUPLICATE KEY UPDATE 
              titre = VALUES(titre), 
              annee_sortie = VALUES(annee_sortie), 
              poster = VALUES(poster), 
              description = VALUES(description), 
              popularite = VALUES(popularite), 
              genres = VALUES(genres)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':id_tmdb' => $id_tmdb,
        ':titre' => $titre,
        ':type_oeuvre' => $type_oeuvre,
        ':annee_sortie' => $annee_sortie,
        ':poster' => $poster_url,
        ':description' => $description,
        ':popularite' => $popularite,
        ':genres' => $genres
    ]);
}

$query = "SELECT * FROM FilmsSeries ORDER BY popularite DESC";
$stmt = $pdo->query($query);
$all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function get_genre_name_by_id($genre_id) {
    global $pdo;
    if (empty($genre_id)) {
        return 'Inconnu';
    }
    $query = "SELECT nom FROM Genres WHERE id_genre = :genre_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':genre_id', $genre_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nom'] : 'Inconnu';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Films et Séries</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #000;
            color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            display: flex;
        }
        .modal-content img {
            width: 40%;
            margin-right: 20px;
        }
        .modal-content .details {
            width: 60%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<header>
    <h1>Bienvenue sur notre site de Films et Séries</h1>
    <nav>
        <ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="films.php">Films</a></li>
            <li><a href="catalogPerso.php">Catalogue</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>
<main>
    <section class="catalogue">
        <h2>Films et Séries populaires</h2>
        <div class="catalogue-grid">
            <?php
            if (count($all_items) > 0) {
                foreach ($all_items as $item) {
                    $poster_url = $item['poster'] ?: 'path/to/default_image.jpg';
                    echo '<div class="catalogue-item">';
                    echo '<img src="' . htmlspecialchars($poster_url) . '" alt="Affiche de ' . htmlspecialchars($item['titre']) . '" class="poster" data-id="' . $item['id_tmdb'] . '">';
                    echo '<h3>' . htmlspecialchars($item['titre']) . '</h3>';
                    echo '</div>';
                }
            } else {
                echo '<p>Aucun film ou série trouvé.</p>';
            }
            ?>
        </div>
    </section>
</main>

<!-- Modal -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <img id="modal-poster" src="" alt="Affiche">
        <div class="details">
            <h2 id="modal-title"></h2>
            <p id="modal-type"></p>
            <p id="modal-description"></p>
            <p id="modal-genres"></p>
            <p id="modal-annee"></p>
            <p id="modal-popularite"></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById("myModal");
    var span = document.getElementsByClassName("close")[0];

    document.querySelectorAll('.poster').forEach(function(poster) {
        poster.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            fetch('get_details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modal-poster').src = data.poster;
                    document.getElementById('modal-title').textContent = data.titre;
                    document.getElementById('modal-type').textContent = 'Type : ' + data.type_oeuvre;
                    document.getElementById('modal-description').textContent = 'Description : ' + data.description;
                    document.getElementById('modal-genres').textContent = 'Genres : ' + data.genres;
                    document.getElementById('modal-annee').textContent = 'Année de sortie : ' + data.annee_sortie;
                    document.getElementById('modal-popularite').textContent = 'Popularité : ' + data.popularite;
                    modal.style.display = "block";
                });
        });
    });

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});
</script>
</body>
</html>