document.addEventListener('DOMContentLoaded', function () {
    // Sélectionner tous les boutons "Ajouter au catalogue"
    const buttons = document.querySelectorAll('.add_to_catalog');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            // Récupérer l'ID du film à partir de l'attribut data-id
            const id_tmdb = this.getAttribute('data-id');

            // Vérifier si l'ID existe
            if (!id_tmdb) {
                alert('Erreur : ID du film manquant.');
                return;
            }

            // Créer une instance FormData pour envoyer les données au serveur
            const formData = new FormData();
            formData.append('id_tmdb', id_tmdb);

            // Envoyer la requête AJAX
            fetch('add_to_catalog.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.success);
                    } else {
                        alert(data.error);
                    }
                })
                .catch(error => {
                    alert('Une erreur est survenue lors de l\'ajout du film.');
                    console.error(error);
                });
        });
    });
});
