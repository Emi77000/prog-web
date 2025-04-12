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
    <link rel="stylesheet" href="details_serie.css">
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

<script type="module">
    import { toggleEpisodes, handleStarClick, appliquerModifs } from './details_serie.js';
    window.toggleEpisodes = toggleEpisodes;
    window.handleStarClick = handleStarClick;
    window.appliquerModifs = appliquerModifs;
</script>

</body>
</html>
