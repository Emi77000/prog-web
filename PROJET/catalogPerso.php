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
    <link rel="stylesheet" href="catalogPerso.css">
    <script src="catalogPerso.js" type="module"></script>
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
    <p>&copy; 2025 TrackFlix</p>
</footer>
</body>
</html>
