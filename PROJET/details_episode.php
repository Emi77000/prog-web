<?php
require_once 'db_connection.php';
session_start();

if (!isset($_GET['id_episode']) || !isset($_SESSION['id_utilisateur'])) {
    echo "Erreur de paramètres.";
    exit;
}

$id_episode = (int) $_GET['id_episode'];
$id_utilisateur = $_SESSION['id_utilisateur'];

$sql = "
SELECT e.id_episode, e.numero_episode, e.titre_episode, e.resume, e.date_diffusion,
       s.numero_saison,
       o.titre AS titre_serie, o.affiche, o.id_oeuvre,
       se.note, se.commentaire, se.vu
FROM episode e
JOIN saison s ON e.id_saison = s.id_saison
JOIN oeuvre o ON s.id_oeuvre = o.id_oeuvre
LEFT JOIN suivi_episode se ON se.id_episode = e.id_episode AND se.id_utilisateur = ?
WHERE e.id_episode = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_utilisateur, $id_episode]);
$ep = $stmt->fetch();

if (!$ep) {
    echo "Épisode introuvable.";
    exit;
}
?>
<link rel="stylesheet" href="details.css">
<h2><?= htmlspecialchars($ep['titre_serie']) ?> — S<?= $ep['numero_saison'] ?>E<?= $ep['numero_episode'] ?></h2>
<p><strong>Titre de l’épisode :</strong> <?= htmlspecialchars($ep['titre_episode']) ?></p>
<p><strong>Date :</strong> <?= $ep['date_diffusion'] ?? 'Inconnue' ?></p>
<p><?= nl2br(htmlspecialchars($ep['resume'] ?? 'Aucun résumé.')) ?></p>

<div style="margin-top: 20px;">
    <label>Note :</label>
    <div class="rating" data-episode-id="<?= $ep['id_episode'] ?>">
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="star<?= $i ?>-<?= $ep['id_episode'] ?>" name="note-<?= $ep['id_episode'] ?>" value="<?= $i ?>" class="update-episode-star" <?= ($ep['note'] == $i) ? 'checked' : '' ?>>
            <label for="star<?= $i ?>-<?= $ep['id_episode'] ?>">★</label>
        <?php endfor; ?>
    </div>

    <br><br>
    <label for="commentaire">Commentaire :</label><br>
    <textarea id="episode-commentaire" class="update-episode-field" data-field="commentaire" data-id="<?= $ep['id_episode'] ?>" rows="4" cols="50"><?= htmlspecialchars($ep['commentaire'] ?? '') ?></textarea>

    <br><br>
    <button id="marquer-vu" data-id="<?= $ep['id_episode'] ?>" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">✅ Marquer comme vu</button>
</div>

<script src="details_episode.js" defer></script>
