<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

$sql = "
SELECT o.id_oeuvre, o.titre, o.affiche,
       s.numero_saison, e.numero_episode, e.titre_episode,
       cu.statut, e.id_episode,
       se.vu
FROM catalogue_utilisateur cu
JOIN oeuvre o ON cu.id_oeuvre = o.id_oeuvre
JOIN saison s ON s.id_oeuvre = o.id_oeuvre
JOIN episode e ON e.id_saison = s.id_saison
LEFT JOIN suivi_episode se ON se.id_episode = e.id_episode AND se.id_utilisateur = cu.id_utilisateur
WHERE cu.id_utilisateur = ?
AND o.type = 'tv'
AND (se.vu = 0 OR se.vu IS NULL)
AND NOT EXISTS (
    SELECT 1
    FROM saison s2
    JOIN episode e2 ON e2.id_saison = s2.id_saison
    LEFT JOIN suivi_episode se2 ON se2.id_episode = e2.id_episode AND se2.id_utilisateur = cu.id_utilisateur
    WHERE s2.id_oeuvre = o.id_oeuvre
    AND (se2.vu = 0 OR se2.vu IS NULL)
    AND (s2.numero_saison < s.numero_saison
         OR (s2.numero_saison = s.numero_saison AND e2.numero_episode < e.numero_episode))
)
ORDER BY o.titre;";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_utilisateur]);
$series = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pasCommence = [];
$enCours = [];

foreach ($series as $row) {
    // Vérifie le nombre total d'épisodes et ceux vus
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM saison s JOIN episode e ON e.id_saison = s.id_saison WHERE s.id_oeuvre = ?");
    $stmtTotal->execute([$row['id_oeuvre']]);
    $totalEpisodes = $stmtTotal->fetchColumn();

    $stmtVus = $pdo->prepare("SELECT COUNT(*) FROM saison s JOIN episode e ON e.id_saison = s.id_saison JOIN suivi_episode se ON se.id_episode = e.id_episode WHERE s.id_oeuvre = ? AND se.id_utilisateur = ? AND se.vu = 1");
    $stmtVus->execute([$row['id_oeuvre'], $id_utilisateur]);
    $vus = $stmtVus->fetchColumn();

    if ($vus > 0 && $vus < $totalEpisodes) {
        $enCours[] = $row;
    } elseif ($vus == 0) {
        $pasCommence[] = $row;
    }
}

// Requête pour les séries terminées
$terminees_sql = "
SELECT DISTINCT o.id_oeuvre, o.titre, o.affiche
FROM oeuvre o
JOIN catalogue_utilisateur cu ON cu.id_oeuvre = o.id_oeuvre
WHERE cu.id_utilisateur = ?
AND o.type = 'tv'
AND NOT EXISTS (
    SELECT 1
    FROM saison s
    JOIN episode e ON e.id_saison = s.id_saison
    LEFT JOIN suivi_episode se ON se.id_episode = e.id_episode AND se.id_utilisateur = cu.id_utilisateur
    WHERE s.id_oeuvre = o.id_oeuvre
    AND (se.vu = 0 OR se.vu IS NULL)
)
ORDER BY o.titre;";

$stmt2 = $pdo->prepare($terminees_sql);
$stmt2->execute([$id_utilisateur]);
$terminees = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des Séries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="suiviSerie.css">
</head>
<body>
<header class="header">
    <nav>
        <ul>
            <li style="margin-right: auto;"><a href="accueil.php" style="font-size: 2em;">TrackFlix</a></li>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries</a></li>
            <li><a href="compte.php">Compte</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>
<main class="catalogue">

    <h2>Séries en cours</h2>
    <?php if (count($enCours) > 0): ?>
        <?php foreach ($enCours as $serie): ?>
            <div class="serie-card">
                <img src="<?= htmlspecialchars($serie['affiche']) ?>" alt="Affiche de la série" class="serie-poster">
                <div class="serie-info">
                    <div class="serie-header">
                        <span class="serie-title"><?= htmlspecialchars($serie['titre']) ?></span>
<!--                        <span class="serie-statut">En cours</span>-->
                    </div>
                    <div class="episode-meta">
                        <span class="saison-episode">Saison <?= $serie['numero_saison'] ?>, Episode <?= $serie['numero_episode'] ?></span>
                        <span class="titre-episode">
                            <a href="#" class="open-episode-details" data-id="<?= $serie['id_episode'] ?>">
                                <?= htmlspecialchars($serie['titre_episode']) ?>
                            </a>
                        </span>
                    </div>
                    <?php
                    // Calcule la progression
                    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM saison s JOIN episode e ON e.id_saison = s.id_saison WHERE s.id_oeuvre = ?");
                    $stmtTotal->execute([$serie['id_oeuvre']]);
                    $total = $stmtTotal->fetchColumn();

                    $stmtVus = $pdo->prepare("SELECT COUNT(*) FROM saison s JOIN episode e ON e.id_saison = s.id_saison JOIN suivi_episode se ON se.id_episode = e.id_episode WHERE s.id_oeuvre = ? AND se.id_utilisateur = ? AND se.vu = 1");
                    $stmtVus->execute([$serie['id_oeuvre'], $id_utilisateur]);
                    $vus = $stmtVus->fetchColumn();

                    $percent = ($total > 0) ? round(($vus / $total) * 100) : 0;
                    ?>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <p class="progress-text"><?= $vus ?> / <?= $total ?> épisodes vus</p>

                </div>
                <div class="check-container">
                    <input type="checkbox" class="vu-checkbox" data-id="<?= $serie['id_episode'] ?>">
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune série en cours.</p>
    <?php endif; ?>

    <h2>Séries pas encore commencées</h2>
    <?php if (count($pasCommence) > 0): ?>
        <?php foreach ($pasCommence as $serie): ?>
            <div class="serie-card">
                <img src="<?= htmlspecialchars($serie['affiche']) ?>" alt="Affiche de la série" class="serie-poster">
                <div class="serie-info">
                    <div class="serie-header">
                        <span class="serie-title"><?= htmlspecialchars($serie['titre']) ?></span>
<!--                        <span class="serie-statut">Non commencée</span>-->
                    </div>
                    <div class="episode-meta">
                        <span class="saison-episode">Saison <?= $serie['numero_saison'] ?>, Episode <?= $serie['numero_episode'] ?></span>
                        <span class="titre-episode">
                            <a href="#" class="open-episode-details" data-id="<?= $serie['id_episode'] ?>">
                                <?= htmlspecialchars($serie['titre_episode']) ?>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="check-container">
                    <input type="checkbox" class="vu-checkbox" data-id="<?= $serie['id_episode'] ?>">
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune série non commencée.</p>
    <?php endif; ?>

    <h2>Séries terminées</h2>
    <?php if (count($terminees) > 0): ?>
        <?php foreach ($terminees as $serie): ?>
            <div class="serie-card">
                <img src="<?= htmlspecialchars($serie['affiche']) ?>" alt="Affiche de la série" class="serie-poster">
                <div class="serie-info">
                    <div class="serie-header">
                        <span class="serie-title"><?= htmlspecialchars($serie['titre']) ?></span>
<!--                        <span class="serie-statut">Terminée</span>-->
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune série terminée.</p>
    <?php endif; ?>
</main>

<div id="episode-modal" class="modal" style="display: none;">
    <div class="modal-content" id="episode-modal-content"></div>
</div>

<script>
    document.querySelectorAll('.vu-checkbox').forEach(box => {
        box.addEventListener('change', function () {
            const id_episode = this.getAttribute('data-id');
            const vu = this.checked ? 1 : 0;
            const formData = new FormData();
            formData.append('id_episode', id_episode);
            formData.append('vu', vu);

            fetch('update_episode_status.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) alert('Erreur : ' + data.error);
                    else location.reload();
                });
        });
    });

    document.querySelectorAll('.open-episode-details').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            fetch('details_episode.php?id_episode=' + id)
                .then(res => res.text())
                .then(html => {
                    const modal = document.getElementById('episode-modal');
                    const content = document.getElementById('episode-modal-content');
                    content.innerHTML = html;

                    const closeBtn = document.createElement('span');
                    closeBtn.className = 'close-modal';
                    closeBtn.innerHTML = '&times;';
                    closeBtn.onclick = () => modal.style.display = 'none';
                    content.prepend(closeBtn);

                    modal.style.display = 'block';
                    modal.dataset.episodeId = id;

                    bindEpisodeEvents();
                });
        });
    });

    function bindEpisodeEvents() {
        document.querySelectorAll('.update-episode-star').forEach(star => {
            star.addEventListener('change', function () {
                const id_episode = this.closest('.rating').dataset.episodeId;
                const value = this.value;

                fetch("modifier_suivi_episode.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: `id_episode=${id_episode}&field=note&value=${value}`
                });
            });
        });

        document.querySelectorAll('.update-episode-field').forEach(field => {
            field.addEventListener('change', function () {
                const id_episode = this.dataset.id;
                const fieldName = this.dataset.field;
                const fieldValue = this.value;

                fetch("modifier_suivi_episode.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: `id_episode=${id_episode}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
                });
            });
        });

        const boutonVu = document.getElementById('marquer-vu');
        if (boutonVu) {
            boutonVu.addEventListener('click', function () {
                const modal = document.getElementById('episode-modal');
                const id_episode = modal.dataset.episodeId;
                const formData = new FormData();
                formData.append('id_episode', id_episode);
                formData.append('vu', 1);

                fetch('update_episode_status.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) location.reload();
                        else alert('Erreur : ' + data.error);
                    });
            });
        }
    }

    window.addEventListener('click', function (e) {
        const modal = document.getElementById('episode-modal');
        if (e.target === modal) modal.style.display = 'none';
    });
</script>
</body>
</html>
