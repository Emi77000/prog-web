document.addEventListener("DOMContentLoaded", function () {
    // Mise à jour note, commentaire, statut
    document.querySelectorAll(".update-field, .update-star").forEach(field => {
        field.addEventListener("change", function () {
            let filmId = this.closest(".catalogue-item")?.dataset.id; // correspond à id_oeuvre
            let fieldName = this.dataset.field || "note";
            let fieldValue = this.value;

            console.log("ID œuvre:", filmId);
            console.log("Champ modifié:", fieldName);
            console.log("Valeur envoyée:", fieldValue);

            if (!filmId || !fieldName) {
                console.error("Données manquantes!");
                return;
            }

            fetch("modifier_catalogue.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_oeuvre=${filmId}&field=${fieldName}&value=${encodeURIComponent(fieldValue)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Mise à jour réussie!");

                        if (fieldName === "statut") {
                            let filmElement = document.querySelector(`[data-id="${filmId}"]`);
                            let newStatut = fieldValue;

                            filmElement.remove();
                            let newSection = document.querySelector(`.catalogue-grid[data-statut="${newStatut}"]`);
                            if (newSection) newSection.appendChild(filmElement);

                            location.reload();
                        }
                    } else {
                        console.error("Erreur SQL:", data.error);
                    }
                })
                .catch(error => console.error("Erreur AJAX:", error));
        });
    });

    // Filtres (onglets)
    document.querySelectorAll(".tab-button").forEach(button => {
        button.addEventListener("click", function () {
            const filter = this.getAttribute("data-filter");

            document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");

            document.querySelectorAll(".catalogue-item").forEach(item => {
                const type = item.getAttribute("data-type");
                item.style.display = (filter === "all" || type === filter) ? "block" : "none";
            });

            document.querySelectorAll(".catalogue-grid").forEach(grid => {
                const items = grid.querySelectorAll(".catalogue-item");
                const visible = Array.from(items).filter(item => item.style.display !== "none");
                const title = grid.previousElementSibling;
                grid.style.display = visible.length ? "grid" : "none";
                if (title) title.style.display = visible.length ? "block" : "none";
            });
        });
    });

    // Suppression d’un élément
    let idASupprimer = null;
    let elementASupprimer = null;

    const modal = document.getElementById("modal-confirm");
    const btnYes = document.getElementById("btn-confirm-yes");
    const btnNo = document.getElementById("btn-confirm-no");

    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function (event) {
            event.stopPropagation();
            idASupprimer = this.dataset.id;
            elementASupprimer = this.closest(".catalogue-item");
            modal.style.display = "flex";
        });
    });

    btnNo.addEventListener("click", function () {
        modal.style.display = "none";
        idASupprimer = null;
        elementASupprimer = null;
    });

    btnYes.addEventListener("click", function () {
        if (!idASupprimer || !elementASupprimer) return;

        fetch("supprimer_catalogue.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_oeuvre=${idASupprimer}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    elementASupprimer.remove();
                    afficherToast("Œuvre supprimée du catalogue.");
                } else {
                    console.error("Erreur SQL:", data.error);
                }
                modal.style.display = "none";
            })
            .catch(error => {
                console.error("Erreur AJAX:", error);
                modal.style.display = "none";
            });
    });

});