document.addEventListener('DOMContentLoaded', () => {
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

    const boutonVu = document.getElementById('marquer-vu');
    if (boutonVu) {
        boutonVu.addEventListener('click', function () {
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
                        alert("L'épisode a été marqué comme vu.");
                        boutonVu.disabled = true;
                        boutonVu.innerText = '✅ Vu';
                    } else {
                        alert('Erreur : ' + data.error);
                    }
                });
        });
    }
});
