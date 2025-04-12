export function toggleEpisodes(saisonId) {
    const section = document.getElementById('episodes-' + saisonId);
    section.style.display = section.style.display === 'block' ? 'none' : 'block';
}

export function handleStarClick(episodeId, starValue) {
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

export function appliquerModifs(id) {
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
