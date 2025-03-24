<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Récupérer tous les films de la base de données
$query = "SELECT * FROM FilmsSeries";
$stmt = $pdo->prepare($query);
$stmt->execute();

// Récupérer les résultats
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Catalogue - Suivi Films et Séries</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Mon Catalogue</h1>
        <nav>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <section class="catalogue">
        <h2>Films et Séries du Catalogue</h2>
        <div class="catalogue-grid">
            <?php if (count($films) > 0): ?>
                <?php foreach ($films as $film): ?>
                    <div class="catalogue-item">
                        <!-- Assurez-vous que l'URL de l'image est correcte -->
                        <img src="<?= htmlspecialchars($film['poster']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>" />
                        <h3><?= htmlspecialchars($film['titre']) ?></h3>
                        <p><strong>Année:</strong> <?= htmlspecialchars($film['annee_sortie']) ?></p>
                        <p><strong>Popularité:</strong> <?= htmlspecialchars($film['popularite']) ?></p>
                        <p><strong>Description:</strong> <?= htmlspecialchars($film['description']) ?></p>
                        <!-- Le lien pour ajouter au catalogue -->
                        <a href="add_to_catalogue.php?movie_id=<?= $film['id_tmdb'] ?>">Ajouter au catalogue</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun film disponible dans le catalogue.</p>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 Suivi Films et Séries</p>
    </footer>
</body>
</html>
