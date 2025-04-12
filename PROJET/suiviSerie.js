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