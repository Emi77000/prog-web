<?php
session_start();
// Inclure la connexion à la base de données
require_once('config.php');

// Récupérer un film aléatoire (ou tu peux récupérer celui avec la popularité la plus haute si tu préfères)
$query = "SELECT * FROM FilmsSeries ORDER BY RAND() LIMIT 10";  // Pour un film aléatoire
// Ou pour un film avec la popularité la plus élevée :
/* $query = "SELECT * FROM FilmsSeries ORDER BY popularite DESC LIMIT 10"; */


$id_utilisateur =1;

// Connexion à la base de données
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = "SELECT f.titre, f.poster, c.statut, c.note, c.commentaire
        FROM Catalogue c
        JOIN FilmsSeries f ON c.id_tmdb = f.id_tmdb
        WHERE c.id_utilisateur = :id_utilisateur";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_utilisateur' => $id_utilisateur]);
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

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
            </ul>
        </nav>
    </header>

    <section class="catalogue">
        <h2>Films et Séries Ajoutés</h2>
        <div class="catalogue-grid">
            <?php if (!empty($films)): ?>
                <?php foreach ($films as $film): ?>
                    <div class="catalogue-item">
                        <img src="<?= htmlspecialchars($film['poster']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                        <h3><?= htmlspecialchars($film['titre']) ?></h3>
                        <p><strong>Statut:</strong> <?= htmlspecialchars($film['statut']) ?></p>
                        <p><strong>Note:</strong> <?= $film['note'] ? "⭐".str_repeat("⭐", floor($film['note'])-1)." (".$film['note']."/5)" : "Non noté" ?></p>
                        <p><strong>Commentaire:</strong> <?= nl2br(htmlspecialchars(isset($film['commentaire']) ? $film['commentaire'] : 'Aucun commentaire')) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun film ou série ajouté pour l'instant.</p>
            <?php endif; ?>

        </div>
    </section>

    <footer>
        <p>&copy; 2025 Suivi Films et Séries</p>
    </footer>
</body>
</html>
