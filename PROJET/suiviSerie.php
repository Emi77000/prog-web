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
        /* Style de la barre de navigation (identique à celle de accueil.php) */
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
        <div class="serie-card">
            <img src="<?= htmlspecialchars($poster) ?>" class="serie-poster" alt="Affiche série">

            <div class="serie-info">
                <div class="serie-header">
                    <span class="serie-title"><?= htmlspecialchars($titre) ?></span>
                    <span class="serie-statut"><?= htmlspecialchars($statut) ?></span>
                </div>

                <div class="episode-meta">
                    <span class="saison-episode">S<?= $saison ?> | E<?= $episode ?></span>
                    <span class="titre-episode"><?= htmlspecialchars($titre_ep) ?></span>
                </div>
            </div>

            <div class="check-container">
                <input type="checkbox" class="vu-checkbox" data-id="<?= $id_episode ?>" <?= $vu ?>>
            </div>
        </div>
    <?php endforeach; ?>
</main>
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
                    if (!data.success) {
                        alert('Erreur : ' + data.error);
                    } else {
                        //Recharger la page après succès
                        location.reload();
                    }
                });
        });
    });

</script>

</body>
</html>
