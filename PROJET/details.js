export function activerBoutonsAjout() {
    console.log("Activation des boutons 'Ajouter au catalogue'");

    const buttons = document.querySelectorAll('.add-to-catalog');

    buttons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const id_tmdb = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');

            if (!id_tmdb || !type) {
                alert('Erreur : données manquantes.');
                return;
            }

            const url = `add_to_catalog.php?id=${id_tmdb}&type=${type}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log("Réponse JSON : ", data);
                    if (data.success) {
                        afficherMessageConfirmation(data.success);
                    } else if (data.info) {
                        afficherMessageConfirmation(data.info);
                    } else if (data.error) {
                        afficherMessageConfirmation("Erreur : " + data.error);
                    }
                })
                .catch(error => {
                    alert("Erreur lors de l'ajout au catalogue.");
                    console.error("Erreur JS : ", error);
                });
        });
    });
}

function afficherMessageConfirmation(message) {
    const msgBox = document.getElementById("confirmation-message");
    msgBox.textContent = message;
    msgBox.style.display = "block";
    msgBox.style.opacity = "1";

    setTimeout(() => {
        msgBox.style.opacity = "0";
        setTimeout(() => {
            msgBox.style.display = "none";
        }, 300);
    }, 2500); // visible 2.5 secondes
}

