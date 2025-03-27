<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données
require_once('config.php');

$id_utilisateur = $_SESSION['user_id']; // Récupérer l'ID utilisateur depuis la session

// Connexion à la base de données
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Récupérer les films/séries ajoutés par l'utilisateur
    $sql = "SELECT f.id_tmdb, f.titre, f.poster, c.statut, c.note, c.commentaire
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
    <h1> Mon catalogue de films </h1>
    <nav>
        <ul>
            <li><a href="accueil.php">Accueil</a></li>
            <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($_SESSION['pseudo']) ?>)</a></li>
        </ul>
    </nav>
</header>

<section class="catalogue">
    <h2>Films et Séries Ajoutés</h2>
    <div class="catalogue-grid">
        <?php if (!empty($films)): ?>
            <?php foreach ($films as $film): ?>
                <div class="catalogue-item" data-id="<?= $film['id_tmdb'] ?>">
                    <img src="<?= htmlspecialchars($film['poster']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                    <h3><?= htmlspecialchars($film['titre']) ?></h3>

                    <!-- Menu déroulant pour le statut -->
                    <label>Statut :</label>
                    <select class="styled-select update-field" data-field="statut">
                        <option value="à voir" <?= $film['statut'] == 'à voir' ? 'selected' : '' ?>>À voir</option>
                        <option value="en cours" <?= $film['statut'] == 'en cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="vu" <?= $film['statut'] == 'vu' ? 'selected' : '' ?>>Vu</option>
                    </select>

                    <!-- Champ pour la note -->
                    <label>Note :</label>
                    <div class="rating" data-film-id="<?= $film['id_tmdb'] ?>">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>-<?= $film['id_tmdb'] ?>" name="note-<?= $film['id_tmdb'] ?>" value="<?= $i ?>" class="update-star" <?= ($film['note'] == $i) ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>-<?= $film['id_tmdb'] ?>">★</label>
                        <?php endfor; ?>
                    </div>

                    <!-- Zone de texte pour le commentaire -->
                    <label>Commentaire :</label>
                    <textarea class="styled-textarea update-field" data-field="commentaire"><?= htmlspecialchars($film['commentaire'] ?? '') ?></textarea>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun film ou série ajouté pour l'instant.</p>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Gère la mise à jour des statuts et commentaires
        document.querySelectorAll(".update-field").forEach(field => {
            field.addEventListener("change", updateField);
            field.addEventListener("input", updateField);
        });

        // Gère la mise à jour des notes (étoiles)
        document.querySelectorAll(".update-star").forEach(star => {
            star.addEventListener("change", function () {
                let filmId = this.closest(".catalogue-item")?.dataset.id;
                let fieldName = "note";
                let fieldValue = this.value;

                if (!filmId) {
                    console.error("Problème avec l'ID du film !");
                    return;
                }

                fetch("modifier_catalogue.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id_tmdb=${filmId}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Note mise à jour avec succès !");
                        } else {
                            console.error("Erreur SQL :", data.error);
                        }
                    })
                    .catch(error => console.error("Erreur AJAX :", error));
            });
        });

        function updateField(event) {
            let field = event.target;
            let filmId = field.closest(".catalogue-item")?.dataset.id;
            let fieldName = field.dataset.field;
            let fieldValue = field.value;

            if (!filmId || !fieldName) {
                console.error("Problème avec les données envoyées !");
                return;
            }

            fetch("modifier_catalogue.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_tmdb=${filmId}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Mise à jour réussie !");
                    } else {
                        console.error("Erreur SQL :", data.error);
                    }
                })
                .catch(error => console.error("Erreur AJAX :", error));
        }
    });
</script>

<footer>
    <p>&copy; 2025 Suivi Films et Séries</p>
</footer>
</body>
</html>
