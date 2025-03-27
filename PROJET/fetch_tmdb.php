<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Clé d'API TMDb
$api_key = 'f751208ae91021f307bb02f72b63586b';

// URL de l'API pour récupérer les films populaires (par exemple)
$api_url = 'https://api.themoviedb.org/3/movie/popular?api_key=' . $api_key . '&language=fr-FR&page=1';

// Initialiser cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier les erreurs de cURL
if (curl_errno($ch)) {
    echo 'Erreur cURL : ' . curl_error($ch);
    exit;
}

// Vérifier le code HTTP
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code != 200) {
    echo "Erreur lors de la récupération des films (Code HTTP: $http_code)";
    exit;
}

curl_close($ch);

// Décoder la réponse JSON
$data = json_decode($response, true);

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
?>
