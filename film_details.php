<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Vérifier si l'ID du film est passé dans l'URL
if (isset($_GET['id'])) {
    $filmId = $_GET['id'];

    try {
        // Récupérer les informations détaillées du film
        $sql = "SELECT * FROM FilmsSeries WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $filmId, PDO::PARAM_INT);
        $stmt->execute();
        $filmDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$filmDetail) {
            echo "Film non trouvé.";
            exit;
        }

    } catch (PDOException $e) {
        echo "Erreur de requête : " . $e->getMessage();
    }
} else {
    echo "ID du film non spécifié.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($filmDetail['titre']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header class="header">
        <div class="logo">
            <a href="PROJET/accueil.php">Suivi Films</a>
        </div>
        <nav>
            <ul>
                <li><a href="PROJET/accueil.php">Accueil</a></li>
                <li><a href="catalog.php">Catalogue</a></li>
                <li><a href="login.html">Connexion</a></li>
            </ul>
        </nav>
    </header>

    <!-- Détails du film -->
    <section class="film-detail">
        <div class="film-detail-container">
            <img src="<?= htmlspecialchars($filmDetail['poster']) ?>" alt="<?= htmlspecialchars($filmDetail['titre']) ?>" onerror="this.onerror=null;this.src='default.jpg';">
            <div class="film-info">
                <h2><?= htmlspecialchars($filmDetail['titre']) ?> (<?= $filmDetail['annee_sortie'] ?>)</h2>
                <p><strong>Popularité:</strong> <?= number_format($filmDetail['popularite'], 1) ?></p>
                <p><strong>Description:</strong></p>
                <p><?= nl2br(htmlspecialchars($filmDetail['description'])) ?></p>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 Suivi Films et Séries</p>
    </footer>
</body>
</html>
