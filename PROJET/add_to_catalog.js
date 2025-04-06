document.addEventListener('DOMContentLoaded', function () {
    // Sélectionner tous les boutons "Ajouter au catalogue"
    const buttons = document.querySelectorAll('.add-to-catalog');

    buttons.forEach(button => {
        button.addEventListener('click', function (event) {
            // Prévenir le comportement par défaut (empêcher une redirection, par exemple)
            event.preventDefault();

            // Récupérer l'ID TMDB et le type du film/série
            const id_tmdb = this.getAttribute('data-id');
            const type = this.getAttribute('data-type'); // 'movie' ou 'tv'

            // Vérifier si l'ID ou le type est manquant
            if (!id_tmdb || !type) {
                alert('Erreur : données manquantes.');
                return;
            }

            // Construire l'URL pour l'appel AJAX
            const url = `add_to_catalog.php?id=${id_tmdb}&type=${type}`;

            // Afficher dans la console si le bouton est bien cliqué
            console.log("Bouton cliqué! ID TMDB: " + id_tmdb + " Type: " + type);

            // Effectuer l'appel AJAX via fetch
            fetch(url)
                .then(response => {
                    // Vérifie que le contenu est bien du JSON
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error("Réponse non-JSON :\n", text); // 👈 VOICI LA CLÉ
                            throw new Error("Réponse non-JSON");
                        });
                    }
                })
                .then(data => {
                    console.log("Réponse JSON : ", data);
                    if (data.success) {
                        alert(data.success);
                    } else {
                        alert("Erreur : " + data.error);
                    }
                })
                .catch(error => {
                    alert('Une erreur est survenue lors de l\'ajout au catalogue.');
                    console.error("Erreur JS : ", error);
                });

        });
    });
});