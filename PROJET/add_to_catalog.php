<?php
session_start();
require_once('db_connection.php');

// Définir le type de réponse JSON pour AJAX
header("Content-Type: application/json");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Vous devez être connecté pour ajouter un film."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$id_tmdb = isset($_POST['id_tmdb']) ? intval($_POST['id_tmdb']) : null;

// Vérifier si l'ID du film est présent
if (!$id_tmdb) {
    echo json_encode(["error" => "ID du film manquant."]);
    exit;
}

try {
    // Activer le mode exception PDO pour voir les erreurs SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si le film existe dans FilmsSeries
    $checkFilmQuery = "SELECT id_tmdb FROM FilmsSeries WHERE id_tmdb = :id_tmdb";
    $checkFilmStmt = $pdo->prepare($checkFilmQuery);
    $checkFilmStmt->execute(['id_tmdb' => $id_tmdb]);

    if ($checkFilmStmt->rowCount() === 0) {
        echo json_encode(["error" => "Film non trouvé dans la base de données."]);
        exit;
    }

    // Vérifier si le film est déjà ajouté au catalogue
    $checkQuery = "SELECT COUNT(*) FROM Catalogue WHERE id_utilisateur = :id_utilisateur AND id_tmdb = :id_tmdb";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute(['id_utilisateur' => $user_id, 'id_tmdb' => $id_tmdb]);
    $filmExists = $checkStmt->fetchColumn();

    if ($filmExists > 0) {
        echo json_encode(["error" => "Ce film est déjà dans votre catalogue."]);
        exit;
    }

    // Insérer le film dans le catalogue
    $insertQuery = "INSERT INTO Catalogue (id_utilisateur, id_tmdb, statut) 
                    VALUES (:id_utilisateur, :id_tmdb, 'à voir')";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute(['id_utilisateur' => $user_id, 'id_tmdb' => $id_tmdb]);

    echo json_encode(["success" => "Film ajouté avec succès !"]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur SQL : " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur : " . $e->getMessage()]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajout au Catalogue</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Ajouter un film au catalogue</h1>
    </header>

    <section class="catalogue">
        <h2>Films disponibles</h2>
        <div class="films-list">
            <!-- Exemple de film, remplace par une boucle ou du contenu dynamique -->
            <div class="film-item" data-id="12345">
                <img src="https://image.tmdb.org/t/p/w500/abcd1234.jpg" alt="Film 1">
                <h3>Film 1</h3>
                <button class="add-to-catalogue" data-id="12345">Ajouter au catalogue</button>
            </div>
            <!-- Tu peux ajouter d'autres films ici -->
        </div>
    </section>

    <footer>
        <p>&copy; 2025 Suivi Films et Séries</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Gère l'événement pour ajouter le film au catalogue
            document.querySelectorAll('.add-to-catalogue').forEach(button => {
                button.addEventListener('click', function() {
                    let id_tmdb = this.getAttribute('data-id');  // Récupérer l'ID du film

                    // Envoyer la requête AJAX pour ajouter le film au catalogue
                    fetch('add_to_catalog.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id_tmdb=${id_tmdb}`  // Envoyer l'ID du film dans la requête POST
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Film ajouté au catalogue !');  // Afficher un message de succès
                            // Tu pourrais également mettre à jour l'interface pour afficher le film ajouté
                        } else {
                            alert(data.error);  // Afficher l'erreur si ça échoue
                        }
                    })
                    .catch(error => console.error('Erreur AJAX:', error));  // Gérer les erreurs AJAX
                });
            });
        });
    </script>
</body>
</html>
