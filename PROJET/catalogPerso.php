<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la connexion à la base de données
require_once('db_connection.php');

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

// Regrouper les films par statut
$films_par_statut = [];
foreach ($films as $film) {
    $films_par_statut[$film['statut']][] = $film;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Films et Séries</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .rating {
            display: inline-block;
            direction: rtl;
        }

        .rating input[type="radio"] {
            display: none;
        }

        .rating label {
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
        }

        .rating input[type="radio"]:checked ~ label {
            color: #ffcc00;
        }

        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffcc00;
        }
    </style>
</head>
<body>
<header>
    <h1> Mon catalogue </h1>
    <nav>
        <ul>
            <li><a href="accueil.php">Accueil</a></li>
            <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($_SESSION['pseudo']) ?>)</a></li>
        </ul>
    </nav>
</header>

<section class="catalogue">
    <h2>Films et Séries Ajoutés</h2>
    <?php foreach (["à voir", "en cours", "vu"] as $statut): ?>
        <h3><?= ucfirst($statut) ?></h3>
        <div class="catalogue-grid">
            <?php if (!empty($films_par_statut[$statut])): ?>
                <?php foreach ($films_par_statut[$statut] as $film): ?>
                    <div class="catalogue-item" data-id="<?= $film['id_tmdb'] ?>">
                        <img src="<?= htmlspecialchars($film['poster']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                        <h3><?= htmlspecialchars($film['titre']) ?></h3>
                        <label>Statut :</label>
                        <select class="styled-select update-field" data-field="statut">
                            <option value="à voir" <?= $film['statut'] == 'à voir' ? 'selected' : '' ?>>À voir</option>
                            <option value="en cours" <?= $film['statut'] == 'en cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="vu" <?= $film['statut'] == 'vu' ? 'selected' : '' ?>>Vu</option>
                        </select>
                        <label>Note :</label>
                        <div class="rating" data-film-id="<?= $film['id_tmdb'] ?>">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>-<?= $film['id_tmdb'] ?>" name="note-<?= $film['id_tmdb'] ?>" value="<?= $i ?>" class="update-star" <?= ($film['note'] == $i) ? 'checked' : '' ?>>
                                <label for="star<?= $i ?>-<?= $film['id_tmdb'] ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <label>Commentaire :</label>
                        <textarea class="styled-textarea update-field" data-field="commentaire"><?= htmlspecialchars($film['commentaire'] ?? '') ?></textarea>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun film dans cette catégorie.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".update-field, .update-star").forEach(field => {
            field.addEventListener("change", function () {
                let filmId = this.closest(".catalogue-item")?.dataset.id;
                let fieldName = this.dataset.field || "note";
                let fieldValue = this.value;

                console.log("Film ID:", filmId);
                console.log("Champ modifié:", fieldName);
                console.log("Valeur envoyée:", fieldValue);

                if (!filmId || !fieldName) {
                    console.error("Données manquantes!");
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
                            console.log("Mise à jour réussie!");
                        } else {
                            console.error("Erreur SQL:", data.error);
                        }
                    })
                    .catch(error => console.error("Erreur AJAX:", error));
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".update-field, .update-star").forEach(field => {
            field.addEventListener("change", function () {
                let filmId = this.closest(".catalogue-item")?.dataset.id;
                let fieldName = this.dataset.field || "note";
                let fieldValue = this.value;

                console.log("Film ID:", filmId);
                console.log("Champ modifié:", fieldName);
                console.log("Valeur envoyée:", fieldValue);

                if (!filmId || !fieldName) {
                    console.error("Données manquantes!");
                    return;
                }

                // Envoi de la requête AJAX pour mettre à jour le statut dans la base de données
                fetch("modifier_catalogue.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id_tmdb=${filmId}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Mise à jour réussie!");

                            // Déplacer le film dans la nouvelle section en fonction du statut
                            if (fieldName === "statut") {
                                // Trouver l'élément du film
                                let filmElement = document.querySelector(`[data-id="${filmId}"]`);
                                let newStatut = fieldValue;

                                // Supprimer l'élément de sa section actuelle
                                filmElement.remove();

                                // Trouver la nouvelle section pour ce statut
                                let newSection = document.querySelector(`.catalogue-grid[data-statut="${newStatut}"]`);
                                if (newSection) {
                                    // Ajouter l'élément du film à la nouvelle section
                                    newSection.appendChild(filmElement);
                                }
                                location.reload();
                            }

                        } else {
                            console.error("Erreur SQL:", data.error);
                        }
                    })
                    .catch(error => console.error("Erreur AJAX:", error));
            });
        });
    });

</script>

<footer>
    <p>&copy; 2025 Suivi Films et Séries</p>
</footer>
</body>
</html>
