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
            transition: all 0.3s ease; /* Transition douce */
        }

        .tab-button:hover {
            background-color: white; /* Fond blanc au survol */
            color: #e50914; /* Texte rouge au survol */
            transform: scale(1.1); /* Agrandissement au survol */
        }

        .tab-button.active {
            background-color: #e50914; /* Fond rouge actif */
            color: white;
        }

        .modal-confirm {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }

        .modal-box {
            background-color: #1f1f1f;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            color: white;
            font-family: 'Segoe UI', sans-serif;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }

        .modal-box p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            justify-content: space-around;
            gap: 15px;
        }

        .modal-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.3s ease;
        }

        #btn-confirm-yes {
            background-color: #e50914;
            color: white;
        }

        #btn-confirm-yes:hover {
            background-color: #f40612;
        }

        #btn-confirm-no {
            background-color: #444;
            color: white;
        }

        #btn-confirm-no:hover {
            background-color: #666;
        }

        .catalogue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            padding: 20px 10px;
        }

        .catalogue-item {
            background-color: #1e1e1e;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
            color: white;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .catalogue-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.4);
        }

        .catalogue-item img {
            max-width: 100%;
            border-radius: 12px;
            height: 300px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .catalogue-item h3 {
            font-size: 18px;
            margin: 10px 0 5px;
            color: #fff;
        }

        .catalogue-item select,
        .catalogue-item textarea {
            width: 100%;
            padding: 8px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            background-color: #2c2c2c;
            color: white;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .catalogue-item textarea {
            resize: vertical;
            min-height: 60px;
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
        <a class="tab-button active" data-filter="all">Tout</a>
        <a class="tab-button" data-filter="film">Films</a>
        <a class="tab-button" data-filter="serie">Séries</a>
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
                            <?php if ($film['type'] === 'tv'): ?>
                                <a href="details_serie.php?id_oeuvre=<?= htmlspecialchars($film['id_oeuvre']) ?>">
                                    <img src="<?= htmlspecialchars($film['affiche']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                                </a>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($film['affiche']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                            <?php endif; ?>

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
        let idASupprimer = null;
        let elementASupprimer = null;

        const modal = document.getElementById("modal-confirm");
        const btnYes = document.getElementById("btn-confirm-yes");
        const btnNo = document.getElementById("btn-confirm-no");

        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function (event) {
                event.stopPropagation();
                idASupprimer = this.dataset.id;
                elementASupprimer = this.closest(".catalogue-item");
                modal.style.display = "flex";
            });
        });

        btnNo.addEventListener("click", function () {
            modal.style.display = "none";
            idASupprimer = null;
            elementASupprimer = null;
        });

        btnYes.addEventListener("click", function () {
            if (!idASupprimer || !elementASupprimer) return;

            fetch("supprimer_catalogue.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_oeuvre=${idASupprimer}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        elementASupprimer.remove();
                        afficherToast("Œuvre supprimée du catalogue.");
                    } else {
                        console.error("Erreur SQL:", data.error);
                    }
                    modal.style.display = "none";
                })
                .catch(error => {
                    console.error("Erreur AJAX:", error);
                    modal.style.display = "none";
                });
        });

    });
</script>

<div id="modal-confirm" class="modal-confirm">
    <div class="modal-box">
        <p>Voulez-vous vraiment supprimer cette œuvre de votre catalogue ?</p>
        <div class="modal-actions">
            <button id="btn-confirm-yes">Oui, supprimer</button>
            <button id="btn-confirm-no">Annuler</button>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2025 Suivi Films et Séries</p>
</footer>
</body>
</html>
