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

<style>
    .rating {
        display: inline-block;
        direction: rtl;
    }
    .rating input[type="radio"] {
        display: none;
    }
    .rating label {
        font-size: 24px;
        color: #ccc;
        cursor: pointer;
    }
    .rating input[type="radio"]:checked ~ label {
        color: #ffcc00;
    }
    .rating label:hover,
    .rating label:hover ~ label {
        color: #ffcc00;
    }
</style>

<script>
    document.querySelectorAll('.update-episode-star').forEach(star => {
        star.addEventListener('change', function () {
            const id_episode = this.closest('.rating').dataset.episodeId;
            const value = this.value;
            fetch("modifier_suivi_episode.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
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
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_episode=${id_episode}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
            });
        });
    });

    document.getElementById('marquer-vu').addEventListener('click', function () {
        const id_episode = this.dataset.id;
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
                    alert("L\'épisode a été marqué comme vu.");
                    document.getElementById('marquer-vu').disabled = true;
                    document.getElementById('marquer-vu').innerText = '✅ Vu';
                } else {
                    alert('Erreur : ' + data.error);
                }
            });
    });
</script>