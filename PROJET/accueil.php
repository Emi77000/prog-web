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

// URL de base de l'API pour récupérer les films populaires
$base_api_url_films = 'https://api.themoviedb.org/3/movie/popular?api_key=' . $api_key . '&language=fr-FR&page=';
$base_api_url_series = 'https://api.themoviedb.org/3/tv/popular?api_key=' . $api_key . '&language=fr-FR&page=';

// Récupérer les films populaires (en plusieurs pages si nécessaire)
$films = [];
for ($page = 1; $page <= ceil($films_par_page / 20); $page++) {
    $api_url = $base_api_url_films . $page;
    $response = file_get_contents($api_url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results']) && count($data['results']) > 0) {
            $films = array_merge($films, $data['results']);
        }
    }
}

// Récupérer les séries populaires (en plusieurs pages si nécessaire)
$series = [];
for ($page = 1; $page <= ceil($films_par_page / 20); $page++) {
    $api_url = $base_api_url_series . $page;
    $response = file_get_contents($api_url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results']) && count($data['results']) > 0) {
            $series = array_merge($series, $data['results']);
        }
    }
}

// Fusionner les films et séries dans une seule liste
$all_items = array_merge($films, $series);

// Insérer les films et séries dans la base de données
foreach ($all_items as $item) {
    $id_tmdb = $item['id'];
    $titre = $item['title'] ?? $item['name'];  // Titre (film ou série)
    $type_oeuvre = isset($item['title']) ? 'film' : 'serie';  // Déterminer si c'est un film ou une série
    $annee_sortie = $item['release_date'] ? substr($item['release_date'], 0, 4) : ($item['first_air_date'] ? substr($item['first_air_date'], 0, 4) : null);
    $poster_url = $item['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : 'path/to/default_image.jpg';
    $description = $item['overview'];
    $popularite = $item['popularity'];

    // Récupérer les genres (liste d'ID des genres)
    $genres = isset($item['genre_ids']) ? implode(',', $item['genre_ids']) : '';

    // Insérer dans la base de données si l'œuvre n'existe pas
    $query = "INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite, genres)
              VALUES (:id_tmdb, :titre, :type_oeuvre, :annee_sortie, :poster, :description, :popularite, :genres)
              ON DUPLICATE KEY UPDATE titre = :titre"; // Empêche les doublons

    // Préparer la requête
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_tmdb', $id_tmdb);
    $stmt->bindParam(':titre', $titre);
    $stmt->bindParam(':type_oeuvre', $type_oeuvre);
    $stmt->bindParam(':annee_sortie', $annee_sortie);
    $stmt->bindParam(':poster', $poster_url);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':popularite', $popularite);
    $stmt->bindParam(':genres', $genres);  // Ajouter les genres

    // Exécuter la requête
    $stmt->execute();
}
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
                <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($pseudo) ?>)</a></li>
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
                        $poster_url = $item['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : 'path/to/default_image.jpg';
                        
                        // Récupérer les genres depuis la base de données (ou API si nécessaire)
                        $genres_list = $item['genre_ids']; // On a stocké les IDs dans la base de données
                        $genres_names = [];
                        foreach ($genres_list as $genre_id) {
                            // Appeler une fonction ou requêter la base de données pour récupérer le nom du genre
                            // Cette partie doit être adaptée selon comment les genres sont stockés (par exemple, dans une table 'genres')
                            $genre_name = get_genre_name_by_id($genre_id);  // Remplace cette fonction par une logique appropriée
                            $genres_names[] = $genre_name;
                        }
                        $genres = implode(', ', $genres_names);

                        echo '<div class="catalogue-item">';
                        echo '<img src="' . $poster_url . '" alt="Poster de ' . htmlspecialchars($item['title'] ?? $item['name']) . '">';
                        echo '<h3>' . htmlspecialchars($item['title'] ?? $item['name']) . '</h3>';
                        echo '<p>Genres : ' . htmlspecialchars($genres) . '</p>';  // Afficher les genres
                        echo '<p>' . (isset($item['release_date']) ? substr($item['release_date'], 0, 4) : (isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : 'N/A')) . '</p>';
                        echo '<p>' . htmlspecialchars($item['overview']) . '</p>';
                        echo '<p>Popularité : ' . $item['popularity'] . '</p>';
                        echo '<button class="add-to-catalogue" data-id="' . $item['id'] . '">Ajouter au catalogue</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Aucun film ou série trouvé.</p>';
                }
                ?>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".add-to-catalogue").forEach(button => {
                button.addEventListener("click", function () {
                    let itemId = this.dataset.id;

                    fetch("add_to_catalog.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id_tmdb=${itemId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("Film/Série ajouté au catalogue !");
                            } else {
                                alert("Erreur : " + data.error);
                            }
                        })
                        .catch(error => console.error("Erreur AJAX :", error));
                });
            });
        });
    </script>

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

    // Requête pour obtenir le nom du genre
    $query = "SELECT genre_name FROM genres WHERE genre_id = :genre_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':genre_id', $genre_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['genre_name'] : 'Inconnu';
}
?>
