import { activerBoutonsAjout } from './details.js';

const modal = document.getElementById("modal");
const span = document.getElementsByClassName("close")[0];

document.querySelectorAll('.carrousel-item a, .resultat-item a').forEach(function (element) {
    element.addEventListener('click', function (event) {
        event.preventDefault();

        const urlParams = new URL(this.href).searchParams;
        const id = urlParams.get('id');
        const type = urlParams.get('type');

        fetch('details.php?id=' + id + '&type=' + type)
            .then(response => response.text())
            .then(data => {
                document.getElementById('modal-details').innerHTML = data;
                modal.style.display = "block";
                activerBoutonsAjout();
            })
            .catch(error => console.error('Erreur de chargement des détails :', error));
    });
});

span.onclick = function () {
    modal.style.display = "none";
};
window.onclick = function (event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
};
document.querySelectorAll('.filter a').forEach(function (element) {
    element.addEventListener('click', function (event) {
        event.preventDefault();
        const genreSelect = document.getElementById('genre-select');
        const genre = genreSelect.value;
        window.location.href = this.href + '&genre=' + genre;
    });
});

document.getElementById('genre-select').addEventListener('change', function () {
    const type = new URLSearchParams(window.location.search).get('type') || 'all';
    window.location.href = 'accueil.php?type=' + type + '&genre=' + this.value;
});