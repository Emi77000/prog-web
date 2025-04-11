<?php
require_once 'db_connection.php';
session_start();

if (!isset($_GET['id_oeuvre']) || !isset($_SESSION['id_utilisateur'])) {
    echo "Paramètres manquants.";
    exit;
}

$id_oeuvre = (int) $_GET['id_oeuvre'];
$id_utilisateur = $_SESSION['id_utilisateur'];

// Vérifie que c’est une série
$stmt = $pdo->prepare("SELECT * FROM oeuvre WHERE id_oeuvre = ?");
$stmt->execute([$id_oeuvre]);
$oeuvre = $stmt->fetch();

if (!$oeuvre || $oeuvre['type'] !== 'tv') {
    echo "Cette page n'est disponible que pour les séries.";
    exit;
}

// Récupère les saisons et épisodes vus
$sql = "
SELECT s.numero_saison, e.numero_episode, e.titre_episode, e.id_episode,
       se.note, se.commentaire
FROM saison s
JOIN episode e ON s.id_saison = e.id_saison
LEFT JOIN suivi_episode se ON se.id_episode = e.id_episode AND se.id_utilisateur = ?
WHERE s.id_oeuvre = ? AND EXISTS (
    SELECT 1 FROM suivi_episode WHERE id_episode = e.id_episode AND id_utilisateur = ?
)
ORDER BY s.numero_saison, e.numero_episode
";

// Exécution avec les trois paramètres
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_utilisateur, $id_oeuvre, $id_utilisateur]);

$episodes = $stmt->fetchAll();


$saisons = [];
foreach ($episodes as $ep) {
    $saisons[$ep['numero_saison']][] = $ep;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la série - <?= htmlspecialchars($oeuvre['titre']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background-color: #121212; color: white; font-family: Arial, sans-serif; padding: 2em; }
        h2, h3 { color: #ff4747; }
        .episode { background: #1e1e1e; margin: 1em 0; padding: 1em; border-radius: 10px; }
        .note-stars { color: gold; font-size: 1.2em; }
        textarea { width: 100%; background: #2a2a2a; color: white; border: none; padding: 0.5em; border-radius: 5px; }

        .saison {
            background-color: #1e1e1e;
            color: white;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1.2em;
            text-align: left;
            width: 100%;
            border: none;
        }

        .saison:hover {
            background-color: #ff4747;
        }

        .episodes {
            display: none;
            padding-left: 20px;
            margin-top: 10px;
        }

        .episode {
            background-color: #2a2a2a;
            margin: 0.5em 0;
            padding: 0.5em;
            border-radius: 5px;
        }

        button {
            background-color: #ff4747;
            color: white;
            font-size: 1em;
            padding: 0.5em 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 10px;
        }
        button:hover {
            background-color: #ff2b2b;
            transform: scale(1.05);
        }

        button:active {
            background-color: #ff4747;
            transform: scale(1);
        }

        .note-stars {
            display: inline-flex;
        }

        .note-stars .star {
            font-size: 24px;
            color: #ccc;
            line-height: 1;
        }

        .note-stars .star.active {
            color: #ffcc00;
        }

        .rating {
            display: inline-block;
            direction: rtl;
            margin-bottom: 10px;
        }

        .rating input[type="radio"] {
            display: none;
        }

        .rating label {
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
        }

        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffcc00;
        }

        textarea {
            width: 100%;
            background: #2a2a2a;
            color: white;
            border: 1px solid #444;
            padding: 0.5em;
            border-radius: 5px;
            resize: vertical;
            margin-bottom: 10px;
        }

        textarea:focus {
            border-color: #ff4747;
            outline: none;
        }
    </style>
</head>
<body>

<h2><?= htmlspecialchars($oeuvre['titre']) ?></h2>

<?php foreach ($saisons as $numSaison => $eps): ?>
    <div class="saison" onclick="toggleEpisodes(<?= $numSaison ?>)">
        Saison <?= $numSaison ?>
    </div>

    <div class="episodes" id="episodes-<?= $numSaison ?>">
        <?php foreach ($eps as $ep): ?>
            <div class="episode">
                <strong>Épisode <?= $ep['numero_episode'] ?> — <?= htmlspecialchars($ep['titre_episode']) ?></strong><br>

                <span class="note-stars" id="stars-<?= $ep['id_episode'] ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star<?= ($ep['note'] >= $i) ? ' active' : '' ?>">★</span>
                    <?php endfor; ?>
                </span>

                <strong>Commentaire :</strong><br>
                <div style="margin-top: 5px;" id="commentaire-<?= $ep['id_episode'] ?>">
                    <?= nl2br(htmlspecialchars($ep['commentaire'] ?? 'Aucun commentaire')) ?>
                </div>

                <button onclick="toggleModifier(<?= $ep['id_episode'] ?>)">Modifier</button>

                <div id="modif-zone-<?= $ep['id_episode'] ?>" style="display:none; margin-top: 10px;">
                    <div class="rating" data-episode-id="<?= $ep['id_episode'] ?>">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>-<?= $ep['id_episode'] ?>" name="note-<?= $ep['id_episode'] ?>" value="<?= $i ?>" <?= ($ep['note'] == $i) ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>-<?= $ep['id_episode'] ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <textarea id="commentaire-input-<?= $ep['id_episode'] ?>" rows="3" style="margin-top: 10px;"><?= htmlspecialchars($ep['commentaire'] ?? '') ?></textarea><br>
                    <button onclick="appliquerModifs(<?= $ep['id_episode'] ?>)">Appliquer</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<script>
    function toggleEpisodes(saisonId) {
        const episodesDiv = document.getElementById('episodes-' + saisonId);
        episodesDiv.style.display = episodesDiv.style.display === 'none' ? 'block' : 'none';
    }

    function toggleModifier(id) {
        const zone = document.getElementById('modif-zone-' + id);
        zone.style.display = zone.style.display === 'none' ? 'block' : 'none';
    }

    function appliquerModifs(id) {
        const note = document.querySelector(`input[name="note-${id}"]:checked`);
        const commentaire = document.getElementById(`commentaire-input-${id}`).value;

        // Mise à jour des étoiles en temps réel
        const majStars = (noteValue) => {
            const starsSpan = document.getElementById('stars-' + id);
            starsSpan.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('span');
                star.className = 'star' + (noteValue >= i ? ' active' : '');
                star.textContent = '★';
                starsSpan.appendChild(star);
            }
        };

        if (note) {
            fetch("modifier_suivi_episode.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_episode=${id}&field=note&value=${note.value}`
            }).then(() => majStars(parseInt(note.value)));
        }

        fetch("modifier_suivi_episode.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_episode=${id}&field=commentaire&value=${encodeURIComponent(commentaire)}`
        }).then(() => {
            const commentaireDiv = document.getElementById('commentaire-' + id);
            commentaireDiv.innerHTML = commentaire.trim() !== '' ? commentaire.replace(/\n/g, '<br>') : 'Aucun commentaire';
        });

        document.getElementById('modif-zone-' + id).style.display = 'none';
    }

    // Fonction pour ajuster dynamiquement la hauteur de la zone de commentaire
    function adjustTextArea(id) {
        const textarea = document.getElementById(`commentaire-input-${id}`);
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Fonction pour gérer le clic sur les étoiles et mettre à jour la note
    function handleStarClick(id, starValue) {
        // Mettre à jour la note dans l'input radio correspondant
        const radio = document.querySelector(`input[name="note-${id}"][value="${starValue}"]`);
        radio.checked = true;

        // Mise à jour des étoiles visuellement
        const starsSpan = document.getElementById('stars-' + id);
        const stars = starsSpan.querySelectorAll('.star');
        stars.forEach((star, index) => {
            if (index < starValue) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });

        // Envoyer la note au serveur
        fetch("modifier_suivi_episode.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_episode=${id}&field=note&value=${starValue}`
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', () => {
                const id = textarea.id.split('-')[2]; 
                adjustTextArea(id);
            });
        });

        // Ajouter des gestionnaires de clics sur les étoiles
        const starElements = document.querySelectorAll('.note-stars .star');
        starElements.forEach(star => {
            star.addEventListener('click', () => {
                const episodeId = star.parentNode.id.replace('stars-', '');
                const starValue = Array.from(star.parentNode.children).indexOf(star) + 1;
                handleStarClick(episodeId, starValue);
            });

            // Gestion du survol (pour l'effet de survol avec la souris)
            star.addEventListener('mouseover', () => {
                const episodeId = star.parentNode.id.replace('stars-', '');
                const starValue = Array.from(star.parentNode.children).indexOf(star) + 1;
                const starsSpan = document.getElementById('stars-' + episodeId);
                const stars = starsSpan.querySelectorAll('.star');
                stars.forEach((s, index) => {
                    if (index < starValue) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            // Gestion de la sortie du survol
            star.addEventListener('mouseout', () => {
                const episodeId = star.parentNode.id.replace('stars-', '');
                const starsSpan = document.getElementById('stars-' + episodeId);
                const stars = starsSpan.querySelectorAll('.star');
                const checkedStar = Array.from(stars).find(star => star.classList.contains('active'));
                if (checkedStar) {
                    // Garder la couleur active sur l'étoile sélectionnée
                    const index = Array.from(stars).indexOf(checkedStar) + 1;
                    stars.forEach((s, idx) => {
                        if (idx < index) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                } else {
                    // Aucun star n'est sélectionné, réinitialiser
                    stars.forEach(s => s.classList.remove('active'));
                }
            });
        });
    });
</script>



</body>
</html>
