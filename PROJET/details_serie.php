<?php
require_once 'db_connection.php';
session_start();

if (!isset($_GET['id_oeuvre'], $_SESSION['id_utilisateur'])) {
    echo "Paramètres manquants.";
    exit;
}

$id_oeuvre = (int) $_GET['id_oeuvre'];
$id_utilisateur = $_SESSION['id_utilisateur'];

$stmt = $pdo->prepare("SELECT * FROM oeuvre WHERE id_oeuvre = ?");
$stmt->execute([$id_oeuvre]);
$oeuvre = $stmt->fetch();

if (!$oeuvre || $oeuvre['type'] !== 'tv') {
    echo "Cette page n'est disponible que pour les séries.";
    exit;
}

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

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_utilisateur, $id_oeuvre, $id_utilisateur]);
$episodes = $stmt->fetchAll();

$saisons = [];
foreach ($episodes as $ep) {
    $saisons[$ep['numero_saison']][] = $ep;
}

$totalEpisodes = count($episodes);
$notesValides = array_filter($episodes, fn($e) => $e['note'] !== null);
$moyenne = count($notesValides) > 0 ? round(array_sum(array_column($notesValides, 'note')) / count($notesValides), 2) : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la série - <?= htmlspecialchars($oeuvre['titre']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="details.css">
</head>
<body>

<!-- Bouton retour -->
<button class="btn-retour" onclick="history.back()">← Retour</button>

<h2><?= htmlspecialchars($oeuvre['titre']) ?></h2>
<p>
    <strong>Épisodes vus :</strong> <?= $totalEpisodes ?><br>
    <strong>Note moyenne :</strong> <span id="note-moyenne"><?= $moyenne !== null ? $moyenne . ' / 5' : 'Non noté' ?></span>
</p>

<?php foreach ($saisons as $numSaison => $eps): ?>
    <div class="saison" onclick="toggleEpisodes(<?= $numSaison ?>)">Saison <?= $numSaison ?></div>
    <div class="episodes" id="episodes-<?= $numSaison ?>">
        <?php foreach ($eps as $ep): ?>
            <div class="episode">
                <strong>Épisode <?= $ep['numero_episode'] ?> — <?= htmlspecialchars($ep['titre_episode']) ?></strong><br>
                <div class="note-stars" id="stars-<?= $ep['id_episode'] ?>" data-note="<?= (int) $ep['note'] ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button class="star<?= ($ep['note'] >= $i) ? ' active' : '' ?>"
                                onclick="handleStarClick(<?= $ep['id_episode'] ?>, <?= $i ?>)"
                                aria-label="Note <?= $i ?>/5" title="Note <?= $i ?>/5">★</button>
                    <?php endfor; ?>
                </div>
                <span id="feedback-note-<?= $ep['id_episode'] ?>" class="confirmation-message"></span>
                <textarea id="commentaire-input-<?= $ep['id_episode'] ?>" rows="3"><?= htmlspecialchars($ep['commentaire'] ?? '') ?></textarea><br>
                <button onclick="appliquerModifs(<?= $ep['id_episode'] ?>)">Modifier le commentaire</button>
                <span id="feedback-<?= $ep['id_episode'] ?>" class="confirmation-message"></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<script>
    function toggleEpisodes(saisonId) {
        const section = document.getElementById('episodes-' + saisonId);
        section.style.display = section.style.display === 'block' ? 'none' : 'block';
    }

    function handleStarClick(episodeId, starValue) {
        const starsWrapper = document.getElementById('stars-' + episodeId);
        const stars = starsWrapper.querySelectorAll('.star');
        const currentNote = parseInt(starsWrapper.dataset.note || 0);
        const newNote = (currentNote === starValue) ? 0 : starValue;

        starsWrapper.dataset.note = newNote;
        stars.forEach((star, index) => {
            star.classList.toggle('active', index < newNote);
        });

        fetch("modifier_suivi_episode.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_episode=${episodeId}&field=note&value=${newNote}`
        }).then(response => {
            if (!response.ok) throw new Error("Erreur serveur");
            showConfirmation(document.getElementById('feedback-note-' + episodeId), "Note enregistrée ✅");
            updateGlobalStats();
        }).catch(() => {
            showConfirmation(document.getElementById('feedback-note-' + episodeId), "Erreur ❌");
        });
    }

    function appliquerModifs(id) {
        const commentaire = document.getElementById(`commentaire-input-${id}`).value;

        fetch("modifier_suivi_episode.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_episode=${id}&field=commentaire&value=${encodeURIComponent(commentaire)}`
        }).then(() => {
            showConfirmation(document.getElementById('feedback-' + id), "Commentaire enregistré ✅");
        }).catch(() => {
            showConfirmation(document.getElementById('feedback-' + id), "Erreur ❌");
        });
    }

    function showConfirmation(targetElement, message) {
        targetElement.textContent = message;
        targetElement.classList.add('visible');
        targetElement.style.color = message.includes('✅') ? '#8bc34a' : '#ff4747';
        setTimeout(() => {
            targetElement.classList.remove('visible');
        }, 2000);
        setTimeout(() => {
            targetElement.textContent = '';
        }, 2500);
    }

    function updateGlobalStats() {
        const allStars = document.querySelectorAll('.note-stars');
        let total = 0;
        let count = 0;

        allStars.forEach(starGroup => {
            const note = parseInt(starGroup.dataset.note || 0);
            if (note > 0) {
                total += note;
                count++;
            }
        });

        const moyenne = count > 0 ? (total / count).toFixed(2) : null;
        const noteMoyenneSpan = document.getElementById('note-moyenne');
        noteMoyenneSpan.textContent = moyenne ? `${moyenne} / 5` : "Non noté";
    }
</script>

</body>
</html>
