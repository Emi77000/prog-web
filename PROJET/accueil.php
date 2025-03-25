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

try {
    // Sélectionner le film ou la série le plus populaire
    $sqlFeatured = "SELECT * FROM FilmsSeries ORDER BY popularite DESC LIMIT 1";
    $stmtFeatured = $pdo->prepare($sqlFeatured);
    $stmtFeatured->execute();
    $featuredFilm = $stmtFeatured->fetch(PDO::FETCH_ASSOC);

    // Récupérer les 10 films les plus populaires
    $sql = "SELECT * FROM FilmsSeries ORDER BY popularite DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de requête : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Films et Séries</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="header">
    <div class="logo">
        <a href="accueil.php">Suivi Films</a>
    </div>
    <nav>
        <ul>
            <li><a href="accueil.php">Accueil</a></li>
            <li><a href="catalogPerso.php">Catalogue</a></li>
            <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($pseudo) ?>)</a></li>
        </ul>
    </nav>
</header>

<section class="featured-film">
    <?php if ($featuredFilm): ?>
        <div class="featured-item">
            <img src="<?= htmlspecialchars($featuredFilm['poster']) ?>"
                 alt="<?= htmlspecialchars($featuredFilm['titre']) ?>"
                 onerror="this.onerror=null;this.src='default.jpg';">
            <h3><?= htmlspecialchars($featuredFilm['titre']) ?> (<?= $featuredFilm['annee_sortie'] ?>)</h3>
            <p><strong>Popularité:</strong> <?= number_format($featuredFilm['popularite'], 1) ?></p>
            <p><?= nl2br(htmlspecialchars($featuredFilm['description'])) ?></p>
            <button class="add-to-catalogue" data-id="<?= $featuredFilm['id_tmdb'] ?>">Ajouter au catalogue</button>
            <p class="status-message"></p>
        </div>
    <?php else: ?>
        <p>Aucun film en vedette.</p>
    <?php endif; ?>
</section>

<section class="catalogue">
    <h2>Films et Séries Populaires</h2>
    <div class="catalogue-grid">
        <?php foreach ($films as $film): ?>
            <div class="catalogue-item">
                <img src="<?= htmlspecialchars($film['poster']) ?>"
                     alt="<?= htmlspecialchars($film['titre']) ?>"
                     onerror="this.onerror=null;this.src='default.jpg';">
                <h3><?= htmlspecialchars($film['titre']) ?> (<?= $film['annee_sortie'] ?>)</h3>
                <p><strong>Popularité:</strong> <?= number_format($film['popularite'], 1) ?></p>
                <p class="description"><?= nl2br(htmlspecialchars($film['description'])) ?></p>
                <button class="add-to-catalogue" data-id="<?= $film['id_tmdb'] ?>">Ajouter au catalogue</button>
                <p class="status-message"></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".add-to-catalogue").forEach(button => {
            button.addEventListener("click", function () {
                let filmId = this.getAttribute("data-id");
                let statusMessage = this.nextElementSibling;

                console.log("ID du film envoyé :", filmId);  // 🔥 Vérification

                let xhr = new XMLHttpRequest();
                xhr.open("POST", "add_to_catalog.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        console.log("Réponse AJAX reçue :", xhr.responseText);  // 🔥 Vérification
                        let response = JSON.parse(xhr.responseText);
                        statusMessage.textContent = response.success ? response.success : response.error;
                    }
                };

                xhr.send("id_tmdb=" + filmId);
            });
        });
    });

</script>

<footer>
    <p>&copy; 2025 Suivi Films et Séries</p>
</footer>

</body>
</html>
