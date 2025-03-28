<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pseudo = $_SESSION['pseudo'];
$user_id = $_SESSION['user_id'];

// Clé d'API TMDb
$api_key = 'f751208ae91021f307bb02f72b63586b';

// Nombre de films à afficher
$films_par_page = 50;

// URL de base de l'API pour récupérer les films populaires
$base_api_url = 'https://api.themoviedb.org/3/movie/popular?api_key=' . $api_key . '&language=fr-FR&page=';

// Récupérer les films de plusieurs pages (ici 3 pages pour obtenir environ 50 films)
$films = [];
for ($page = 1; $page <= ceil($films_par_page / 20); $page++) {
    $api_url = $base_api_url . $page;
    $response = file_get_contents($api_url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results']) && count($data['results']) > 0) {
            $films = array_merge($films, $data['results']);
        }
    }
}

// Insérer les films dans la base de données
foreach ($films as $film) {
    $id_tmdb = $film['id'];
    $titre = $film['title'];
    $type_oeuvre = 'film';
    $annee_sortie = $film['release_date'] ? substr($film['release_date'], 0, 4) : null;
    $poster_url = $film['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $film['poster_path'] : 'path/to/default_image.jpg';
    $description = $film['overview'];
    $popularite = $film['popularity'];

    // Insérer dans la base de données si le film n'existe pas
    $query = "INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite)
              VALUES (:id_tmdb, :titre, :type_oeuvre, :annee_sortie, :poster, :description, :popularite)
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

    // Exécuter la requête
    $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Films</title>
    <link rel="stylesheet" href="styles.css"> <!-- Lien vers ton fichier CSS -->
</head>
<body>

    <!-- En-tête de ton site -->
    <header>
        <h1>Bienvenue sur notre site de Films</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="films.php">Films</a></li>
                <li><a href="catalogPerso.php">Catalogue</a></li>
                <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($pseudo) ?>)</a></li>
            </ul>
        </nav>
    </header>

    <!-- Section principale : Grille de films -->
    <main>
        <section class="catalogue">
            <h2>Films populaires</h2>
            <div class="catalogue-grid">
                <?php
                if (count($films) > 0) {
                    foreach ($films as $film) {
                        $poster_url = $film['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $film['poster_path'] : 'path/to/default_image.jpg';
                        echo '<div class="catalogue-item">';
                        echo '<img src="' . $poster_url . '" alt="Poster de ' . htmlspecialchars($film['title']) . '">';
                        echo '<h3>' . htmlspecialchars($film['title']) . '</h3>';
                        echo '<p>' . (isset($film['release_date']) ? substr($film['release_date'], 0, 4) : 'N/A') . '</p>';
                        echo '<p>' . htmlspecialchars($film['overview']) . '</p>';
                        echo '<p>Popularité : ' . $film['popularity'] . '</p>';
                        echo '<button class="add-to-catalogue" data-id="' . $film['id'] . '">Ajouter au catalogue</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Aucun film trouvé.</p>';
                }
                ?>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".add-to-catalogue").forEach(button => {
                button.addEventListener("click", function () {
                    let filmId = this.dataset.id;

                    fetch("add_to_catalog.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id_tmdb=${filmId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("Film ajouté au catalogue !");
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
        <p>&copy; 2025 Mon site de films. Tous droits réservés.</p>
    </footer>

</body>
</html>

