<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: login.php");
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Récupérer les séries du catalogue perso avec les épisodes
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
ORDER BY o.titre;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_utilisateur]);
$series = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi Séries</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        header nav ul {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
            list-style-type: none;
        }
        header nav ul li {
            margin-right: 20px;
        }
        header nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        header nav ul li a:hover {
            color: red;
        }
        .serie-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #1e1e1e;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }
        .serie-poster {
            width: 100px;
            height: auto;
            border-radius: 5px;
        }
        .serie-info {
            flex: 1;
            margin-left: 15px;
            color: white;
        }
        .serie-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .serie-title {
            font-weight: bold;
            font-size: 1.1em;
        }
        .serie-statut {
            background-color: gray;
            color: white;
            font-size: 0.8em;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .episode-meta {
            margin-top: 5px;
        }
        .saison-episode {
            font-size: 0.95em;
            font-weight: bold;
        }
        .titre-episode {
            display: block;
            font-size: 0.85em;
            color: #ccc;
        }
        .check-container {
            margin-left: 15px;
        }
        .vu-checkbox {
            width: 24px;
            height: 24px;
            accent-color: #e50914;
            cursor: pointer;
        }
        .modal {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.75);
        }
        .modal-content {
            background-color: #1e1e1e;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            color: white;
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
    </style>
</head>
<body>

<header class="header">
    <nav>
        <ul>
            <li style="margin-right: auto;"><a href="accueil.php" style="font-size: 2em;">TrackFlix</a></li>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries</a></li>
            <li><a href="compte.php">Compte</a></li>
            <li><a href="logout.php">Déconnexion (<?= htmlspecialchars($_SESSION['pseudo']) ?>)</a></li>
        </ul>
    </nav>
</header>

<main class="catalogue">
    <h2>Mes séries</h2>
    <?php foreach ($series as $serie):
        $poster = $serie['affiche'] ?? 'placeholder.jpg';
        $titre = $serie['titre'];
        $saison = str_pad($serie['numero_saison'], 2, "0", STR_PAD_LEFT);
        $episode = str_pad($serie['numero_episode'], 2, "0", STR_PAD_LEFT);
        $titre_ep = $serie['titre_episode'] ?? "Épisode inconnu";
        $statut = strtoupper($serie['statut']);
        $vu = $serie['vu'] ? 'checked' : '';
        $id_episode = $serie['id_episode'];
        ?>
        <div class="serie-card" id="card-<?= $id_episode ?>">
            <img src="<?= htmlspecialchars($poster) ?>" class="serie-poster" alt="Affiche série">
            <div class="serie-info">
                <div class="serie-header">
                    <span class="serie-title"><?= htmlspecialchars($titre) ?></span>
                    <span class="serie-statut"><?= htmlspecialchars($statut) ?></span>
                </div>
                <div class="episode-meta">
                    <span class="saison-episode">S<?= $saison ?> | E<?= $episode ?></span>
                    <span class="titre-episode">
                    <a href="#" class="open-episode-details" data-id="<?= $id_episode ?>">
                        <?= htmlspecialchars($titre_ep) ?>
                    </a>
                </span>
                </div>
            </div>
            <div class="check-container">
                <input type="checkbox" class="vu-checkbox" data-id="<?= $id_episode ?>" <?= $vu ?>>
            </div>
        </div>
    <?php endforeach; ?>
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
                    modal.dataset.episodeId = id; // <- ajout pour suivi dynamique

                    bindEpisodeEvents();
                });
        });
    });

    function bindEpisodeEvents() {
        // Gestion étoiles
        document.querySelectorAll('.update-episode-star').forEach(star => {
            star.addEventListener('change', function () {
                const id_episode = this.closest('.rating').dataset.episodeId;
                const value = this.value;

                console.log("NOTE →", id_episode, value);

                fetch("modifier_suivi_episode.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: `id_episode=${id_episode}&field=note&value=${value}`
                });
            });
        });

        // Gestion commentaire
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

        // Marquer comme vu
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
                        if (data.success) {
                            if (data.success) {
                                location.reload();
                            }
                        } else {
                            alert('Erreur : ' + data.error);
                        }
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