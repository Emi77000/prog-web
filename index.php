<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

try {
    // Sélectionner le film le plus populaire
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
    // Afficher l'erreur si la requête échoue
    echo "Erreur de requête : " . $e->getMessage();
}

// Inclure la structure HTML
include('index.html');
?>

<script>
// Ajouter du contenu dynamique via JavaScript (facultatif si tu préfères faire ça côté PHP)
document.getElementById('featured-film').innerHTML = `<?php if ($featuredFilm): ?>
    <div class="featured-item">
        <img src="<?= htmlspecialchars($featuredFilm['poster']) ?>" alt="<?= htmlspecialchars($featuredFilm['titre']) ?>" onerror="this.onerror=null;this.src='default.jpg';">
        <h3><?= htmlspecialchars($featuredFilm['titre']) ?> (<?= $featuredFilm['annee_sortie'] ?>)</h3>
        <p><strong>Popularité:</strong> <?= number_format($featuredFilm['popularite'], 1) ?></p>
        <p><?= nl2br(htmlspecialchars($featuredFilm['description'])) ?></p>
        <button>Ajouter au catalogue</button>
    </div>
<?php else: ?>
    <p>Aucun film en vedette.</p>
<?php endif; ?>`;

document.getElementById('catalogue-grid').innerHTML = `<?php if (!empty($films)): ?>
    <?php foreach ($films as $film): ?>
        <div class="catalogue-item">
            <img src="<?= htmlspecialchars($film['poster']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>" onerror="this.onerror=null;this.src='default.jpg';">
            <h3><?= htmlspecialchars($film['titre']) ?> (<?= $film['annee_sortie'] ?>)</h3>
            <p><strong>Popularité:</strong> <?= number_format($film['popularite'], 1) ?></p>
            <p class="description"><?= nl2br(htmlspecialchars($film['description'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Aucun film ou série disponible pour l'instant.</p>
<?php endif; ?>`;
</script>
