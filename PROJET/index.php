<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Récupérer un film aléatoire (ou tu peux récupérer celui avec la popularité la plus haute si tu préfères)
$query = "SELECT * FROM FilmsSeries ORDER BY RAND() LIMIT 10";  // Pour un film aléatoire
// Ou pour un film avec la popularité la plus élevée :
/* $query = "SELECT * FROM FilmsSeries ORDER BY popularite DESC LIMIT 10"; */

$stmt = $pdo->prepare($query);
$stmt->execute();

// Récupérer le film
$featuredFilm = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Films et Séries</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Bienvenue sur le site de Suivi de Films et Séries</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="login.html">Connexion</a></li>
                <li><a href="register.html">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <section class="featured">
        <h2>Film en vedette</h2>
        <?php if ($featuredFilm): ?>
            <div class="movie-details">
                <h3><?= htmlspecialchars($featuredFilm['titre']) ?></h3>
                <p><img src="https://image.tmdb.org/t/p/w500/<?= htmlspecialchars($featuredFilm['poster']) ?>" alt="Affiche de <?= htmlspecialchars($featuredFilm['titre']) ?>"></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($featuredFilm['description']) ?></p>
                <p><a href="add_to_catalogue.php?movie_id=<?= $featuredFilm['id_tmdb'] ?>">Ajouter au catalogue</a></p>
            </div>
        <?php else: ?>
            <p>Aucun film en vedette.</p>
        <?php endif; ?>
    </section>

    <footer>
        <p>&copy; 2025 Suivi Films et Séries</p>
    </footer>
</body>
</html>
