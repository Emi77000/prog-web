<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Clé d'API TMDb
$api_key = 'f751208ae91021f307bb02f72b63586b';

// URL de l'API pour récupérer les films populaires
$api_url_films = 'https://api.themoviedb.org/3/movie/popular?api_key=' . $api_key . '&language=fr-FR&page=1';
// URL de l'API pour récupérer les séries populaires
$api_url_series = 'https://api.themoviedb.org/3/tv/popular?api_key=' . $api_key . '&language=fr-FR&page=1';

// Fonction pour récupérer et insérer les films ou séries
function fetch_and_insert($api_url, $type_oeuvre) {
    global $pdo;

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
        echo "Erreur lors de la récupération des œuvres (Code HTTP: $http_code)";
        exit;
    }

    curl_close($ch);

    // Décoder la réponse JSON
    $data = json_decode($response, true);

    // Vérifier si des résultats ont été retournés
    if (isset($data['results']) && count($data['results']) > 0) {
        // Parcourir chaque film ou série
        foreach ($data['results'] as $oeuvre) {
            $id_tmdb = $oeuvre['id'];
            $titre = isset($oeuvre['title']) ? $oeuvre['title'] : (isset($oeuvre['name']) ? $oeuvre['name'] : 'Inconnu');
            $annee_sortie = isset($oeuvre['release_date']) ? substr($oeuvre['release_date'], 0, 4) : (isset($oeuvre['first_air_date']) ? substr($oeuvre['first_air_date'], 0, 4) : null);
            $poster = isset($oeuvre['poster_path']) ? $oeuvre['poster_path'] : 'default.jpg';
            $description = isset($oeuvre['overview']) ? $oeuvre['overview'] : 'Aucune description disponible';
            $popularite = isset($oeuvre['popularity']) ? $oeuvre['popularity'] : 0;
            $genres = isset($oeuvre['genre_ids']) ? implode(',', $oeuvre['genre_ids']) : ''; // Récupérer les genres des films/séries

            // Insérer les données dans la table FilmsSeries
            $query = "INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite, genres)
                      VALUES (:id_tmdb, :titre, :type_oeuvre, :annee_sortie, :poster, :description, :popularite, :genres)
                      ON DUPLICATE KEY UPDATE titre = :titre"; // Empêche les doublons

            // Préparer la requête
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_tmdb', $id_tmdb);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':type_oeuvre', $type_oeuvre);
            $stmt->bindParam(':annee_sortie', $annee_sortie);
            $stmt->bindParam(':poster', $poster);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':popularite', $popularite);
            $stmt->bindParam(':genres', $genres);

            // Exécuter la requête
            if ($stmt->execute()) {
                echo "$type_oeuvre inséré : " . $titre . "<br>";
            } else {
                echo "Erreur lors de l'insertion du $type_oeuvre : " . $titre . "<br>";
            }
        }
    } else {
        echo "Aucun $type_oeuvre trouvé dans l'API.";
    }
}

// Récupérer les films populaires
fetch_and_insert($api_url_films, 'film');

// Récupérer les séries populaires
fetch_and_insert($api_url_series, 'serie');

?>
