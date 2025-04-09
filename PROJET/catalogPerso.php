<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

require_once('db_connection.php');

$id_utilisateur = $_SESSION['id_utilisateur'];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = "SELECT f.id_oeuvre, f.titre, f.type, f.affiche, c.statut, c.note, c.commentaire
            FROM catalogue_utilisateur c
            JOIN oeuvre f ON c.id_oeuvre = f.id_oeuvre
            WHERE c.id_utilisateur = :id_utilisateur";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_utilisateur' => $id_utilisateur]);
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

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
    <title>Mon Catalogue</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .rating { display: inline-block; direction: rtl; }
        .rating input[type="radio"] { display: none; }
        .rating label { font-size: 24px; color: #ccc; cursor: pointer; }
        .rating input[type="radio"]:checked ~ label { color: #ffcc00; }
        .rating label:hover, .rating label:hover ~ label { color: #ffcc00; }

        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .tab-button {
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            background-color: #e50914; /* Couleur de fond rouge */
            color: white; /* Couleur du texte */
            border-radius: 30px; /* Coins arrondis */
            transition: all 0.3s ease; /* Ajoute une transition douce pour les effets */
        }

        .tab-button.active {
            background-color: #f40612; /* Couleur de fond quand actif */
            color: white; /* Texte blanc quand actif */
        }

        .tab-button:hover {
            background-color: white; /* Fond blanc au survol */
            color: #e50914; /* Texte rouge au survol */

        }

        .poster-container { position: relative; display: inline-block; width: 100%; }
        .delete-btn { position: absolute; top: 5px; right: 5px; background-color: rgba(255, 0, 0, 0.7); color: white; border: none; width: 24px; height: 24px; font-size: 18px; font-weight: bold; text-align: center; cursor: pointer; border-radius: 50%; line-height: 24px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s ease-in-out; }
        .delete-btn:hover { background-color: rgba(255, 0, 0, 1); }
    </style>
</head>
<body>
<header class="header">
    <nav>
        <ul style="display: flex; align-items: center; margin: 0;">
            <li style="margin-right: auto;">
                <a href="accueil.php" style="font-size: 2em;">TrackFlix</a>
            </li>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries</a></li>
            <li><a href="compte.php">Compte</a></li>
            <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($_SESSION['pseudo']) ?>)</a></li>
        </ul>
    </nav>
</header>

<section class="catalogue">
    <h2>Films et Séries Ajoutés</h2>

    <div class="tabs">
        <button class="tab-button active" data-filter="all">Tout</button>
        <button class="tab-button" data-filter="film">Films</button>
        <button class="tab-button" data-filter="serie">Séries</button>
    </div>

    <?php foreach (["à voir", "en cours", "vu"] as $statut): ?>
        <h3><?= ucfirst($statut) ?></h3>
        <div class="catalogue-grid" data-statut="<?= $statut ?>">
            <?php if (!empty($films_par_statut[$statut])): ?>
                <?php foreach ($films_par_statut[$statut] as $film): ?>
                    <?php $type_affiche = ($film['type'] === 'movie') ? 'film' : 'serie'; ?>
                    <div class="catalogue-item" data-id="<?= $film['id_oeuvre'] ?>" data-type="<?= $type_affiche ?>">
                        <div class="poster-container">
                            <button class="delete-btn" data-id="<?= $film['id_oeuvre'] ?>">✖</button>
                            <img src="<?= htmlspecialchars($film['affiche']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                        </div>
                        <h3><?= htmlspecialchars($film['titre']) ?></h3>
                        <label>Statut :</label>
                        <select class="styled-select update-field" data-field="statut">
                            <option value="à voir" <?= $film['statut'] == 'à voir' ? 'selected' : '' ?>>à voir</option>
                            <option value="en cours" <?= $film['statut'] == 'en cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="vu" <?= $film['statut'] == 'vu' ? 'selected' : '' ?>>Vu</option>
                        </select>
                        <?php if ($film["statut"] === "vu"): ?>
                            <label>Note :</label>
                            <div class="rating" data-film-id="<?= $film['id_oeuvre'] ?>">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>-<?= $film['id_oeuvre'] ?>" name="note-<?= $film['id_oeuvre'] ?>" value="<?= $i ?>" class="update-star" <?= ($film['note'] == $i) ? 'checked' : '' ?>>
                                    <label for="star<?= $i ?>-<?= $film['id_oeuvre'] ?>">★</label>
                                <?php endfor; ?>
                            </div>
                            <label>Commentaire :</label>
                            <textarea class="styled-textarea update-field" data-field="commentaire"><?= htmlspecialchars($film['commentaire'] ?? '') ?></textarea>
                        <?php else: ?>
                            <p class="note-disabled"></p>
                        <?php endif; ?>
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
        // Mise à jour note, commentaire, statut
        document.querySelectorAll(".update-field, .update-star").forEach(field => {
            field.addEventListener("change", function () {
                let filmId = this.closest(".catalogue-item")?.dataset.id; // correspond à id_oeuvre
                let fieldName = this.dataset.field || "note";
                let fieldValue = this.value;

                console.log("ID œuvre:", filmId);
                console.log("Champ modifié:", fieldName);
                console.log("Valeur envoyée:", fieldValue);

                if (!filmId || !fieldName) {
                    console.error("Données manquantes!");
                    return;
                }

                fetch("modifier_catalogue.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id_oeuvre=${filmId}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Mise à jour réussie!");

                            if (fieldName === "statut") {
                                let filmElement = document.querySelector(`[data-id="${filmId}"]`);
                                let newStatut = fieldValue;

                                filmElement.remove();
                                let newSection = document.querySelector(`.catalogue-grid[data-statut="${newStatut}"]`);
                                if (newSection) newSection.appendChild(filmElement);

                                location.reload();
                            }
                        } else {
                            console.error("Erreur SQL:", data.error);
                        }
                    })
                    .catch(error => console.error("Erreur AJAX:", error));
            });
        });

        // Filtres (onglets)
        document.querySelectorAll(".tab-button").forEach(button => {
            button.addEventListener("click", function () {
                const filter = this.getAttribute("data-filter");

                document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
                this.classList.add("active");

                document.querySelectorAll(".catalogue-item").forEach(item => {
                    const type = item.getAttribute("data-type");
                    item.style.display = (filter === "all" || type === filter) ? "block" : "none";
                });

                document.querySelectorAll(".catalogue-grid").forEach(grid => {
                    const items = grid.querySelectorAll(".catalogue-item");
                    const visible = Array.from(items).filter(item => item.style.display !== "none");
                    const title = grid.previousElementSibling;
                    grid.style.display = visible.length ? "grid" : "none";
                    if (title) title.style.display = visible.length ? "block" : "none";
                });
            });
        });

        // Suppression d’un élément
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function (event) {
                event.stopPropagation();
                if (!confirm("Voulez-vous vraiment supprimer cet élément ?")) return;

                let filmId = this.dataset.id;
                let filmElement = this.closest(".catalogue-item");

                fetch("supprimer_catalogue.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id_oeuvre=${filmId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            filmElement.remove();
                            console.log("Suppression réussie !");
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
