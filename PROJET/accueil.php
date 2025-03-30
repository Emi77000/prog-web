<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pseudo = $_SESSION['pseudo'];
$user_id = $_SESSION['user_id'];

// Clé d'API TMDb
$api_key = 'f751208ae91021f307bb02f72b63586b';

// Nombre de films et séries à afficher
$films_par_page = 50;

// Fonction pour récupérer les données TMDb
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

// Récupération des films et séries depuis l'API
$films = fetch_tmdb_data('https://api.themoviedb.org/3/movie/popular', $api_key, $films_par_page);
$series = fetch_tmdb_data('https://api.themoviedb.org/3/tv/popular', $api_key, $films_par_page);

// Fusion des films et séries
$all_items = array_merge($films, $series);

// Insertion dans la base de données
foreach ($all_items as $item) {
    $id_tmdb = $item['id'];
    $titre = $item['title'] ?? $item['name'];
    $type_oeuvre = isset($item['title']) ? 'film' : 'serie';
    $annee_sortie = isset($item['release_date']) ? substr($item['release_date'], 0, 4) :
        (isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : null);
    $poster_url = isset($item['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : null;
    $description = $item['overview'] ?? '';
    $popularite = $item['popularity'] ?? 0;
    $genres = isset($item['genre_ids']) ? implode(',', $item['genre_ids']) : '';

    // Requête SQL avec mise à jour en cas de doublon
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

// Récupération des films et séries stockés en base
$query = "SELECT * FROM FilmsSeries ORDER BY popularite DESC";
$stmt = $pdo->query($query);
$all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Films et Séries</title>
    <link rel="stylesheet" href="styles.css"> <!-- Lien vers ton fichier CSS -->
</head>
<body>

<!-- En-tête de ton site -->
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

<!-- Section principale : Grille de films et séries -->
<main>
    <section class="catalogue">
        <h2>Films et Séries populaires</h2>
        <div class="catalogue-grid">
            <?php
            if (count($all_items) > 0) {
                foreach ($all_items as $item) {
                    $poster_url = $item['poster'] ?: 'path/to/default_image.jpg';

                    // Récupérer les genres depuis la base de données
                    $genres_list = explode(',', $item['genres']); // Convertir en tableau
                    $genres_names = [];
                    foreach ($genres_list as $genre_id) {
                        $genre_name = get_genre_name_by_id(trim($genre_id));
                        $genres_names[] = $genre_name;
                    }
                    $genres = implode(', ', $genres_names);

                    echo '<div class="catalogue-item">';
                    echo '<img src="' . htmlspecialchars($poster_url) . '" alt="Affiche de ' . htmlspecialchars($titre) . '">';
                    echo '<h3>' . htmlspecialchars($titre) . '</h3>';
                    echo '<p>Genres : ' . htmlspecialchars($genres) . '</p>';
                    echo '<p>' . ($annee_sortie ?? 'N/A') . '</p>';
                    echo '<p>' . htmlspecialchars($description) . '</p>';
                    echo '<p>Popularité : ' . $popularite . '</p>';
                    echo '<button class="add-to-catalogue" data-id="' . $id_tmdb . '">Ajouter au catalogue</button>';
                    echo '</div>';
                }
            } else {
                echo '<p>Aucun film ou série trouvé.</p>';
            }
            ?>
        </div>
    </section>
</main>

<!-- Pied de page -->
<footer>
    <p>&copy; 2025 Mon site de films et séries. Tous droits réservés.</p>
</footer>

</body>
</html>

<?php
// Fonction pour récupérer le nom du genre par son ID
function get_genre_name_by_id($genre_id) {
    global $pdo;

    if (empty($genre_id)) {
        return 'Inconnu';
    }

    $query = "SELECT nom FROM genres WHERE id_genre = :genre_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':genre_id', $genre_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nom'] : 'Inconnu';
}
?>
