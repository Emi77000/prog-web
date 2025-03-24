<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Clé d'API TMDb
$api_key = 'f751208ae91021f307bb02f72b63586b';

// URL de l'API pour récupérer les films populaires (par exemple)
$api_url = 'https://api.themoviedb.org/3/movie/popular?api_key=' . $api_key . '&language=fr-FR&page=1';

// Récupérer les données de l'API avec file_get_contents
$response = file_get_contents($api_url);

// Vérifier si la réponse est valide
if ($response) {
    // Décoder la réponse JSON
    $data = json_decode($response, true);

    // Afficher les données pour voir ce que tu obtiens
    echo '<pre>';
    print_r($data);  // Afficher les résultats pour vérifier que tu as bien des films
    echo '</pre>';

    // Vérifier si des résultats ont été retournés
    if (isset($data['results']) && count($data['results']) > 0) {
        // Parcourir chaque film et l'insérer dans la base de données
        foreach ($data['results'] as $film) {
            $titre = $film['title'];
            $type_oeuvre = 'film';
            $annee_sortie = $film['release_date'] ? substr($film['release_date'], 0, 4) : null;
            $poster = $film['poster_path'];
            $description = $film['overview'];
            $popularite = $film['popularity'];

            // Insérer les données dans la table FilmsSeries
            $query = "INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite)
                      VALUES (:id_tmdb, :titre, :type_oeuvre, :annee_sortie, :poster, :description, :popularite)
                      ON DUPLICATE KEY UPDATE titre = :titre"; // Empêche les doublons

            // Préparer la requête
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_tmdb', $film['id']);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':type_oeuvre', $type_oeuvre);
            $stmt->bindParam(':annee_sortie', $annee_sortie);
            $stmt->bindParam(':poster', $poster);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':popularite', $popularite);

            // Exécuter la requête
            if ($stmt->execute()) {
                echo "Film inséré : " . $titre . "<br>";
            } else {
                echo "Erreur lors de l'insertion du film : " . $titre . "<br>";
            }
        }
        echo "Films insérés avec succès!";
    } else {
        echo "Aucun film trouvé dans l'API.";
    }
} else {
    echo "Erreur lors de la récupération des films de l'API.";
}
?>
