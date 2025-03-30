<?php
require_once('db_connection.php');
$api_key = 'f751208ae91021f307bb02f72b63586b';

function get_genres($api_key) {
    $api_url_genres = 'https://api.themoviedb.org/3/genre/movie/list?api_key=' . $api_key . '&language=fr-FR';
    $response = file_get_contents($api_url_genres);
    if ($response === FALSE) {
        die('Erreur lors de la récupération des genres.');
    }
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Erreur lors du décodage JSON : ' . json_last_error_msg());
    }
    return $data['genres'];
}

$genres_list = get_genres($api_key);
$genres_map = [];

// Insertion des genres dans la table Genres
foreach ($genres_list as $genre) {
    $genres_map[$genre['id']] = $genre['name'];
    $query = "INSERT INTO Genres (id_genre, nom) VALUES (:id_genre, :nom) ON DUPLICATE KEY UPDATE nom = :nom";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_genre', $genre['id']);
    $stmt->bindParam(':nom', $genre['name']);
    if (!$stmt->execute()) {
        echo 'Erreur lors de l\'insertion du genre : ' . $genre['name'] . '<br>';
    } else {
        echo 'Genre inséré : ' . $genre['name'] . '<br>';
    }
}

function fetch_and_insert($api_url, $type_oeuvre, $genres_map) {
    global $pdo;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Erreur cURL : ' . curl_error($ch));
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        die("Erreur lors de la récupération des œuvres (Code HTTP: $http_code)");
    }
    curl_close($ch);
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Erreur lors du décodage JSON : ' . json_last_error_msg());
    }
    if (isset($data['results']) && count($data['results']) > 0) {
        foreach ($data['results'] as $oeuvre) {
            $id_tmdb = $oeuvre['id'];
            $titre = isset($oeuvre['title']) ? $oeuvre['title'] : (isset($oeuvre['name']) ? $oeuvre['name'] : 'Inconnu');
            $annee_sortie = isset($oeuvre['release_date']) ? substr($oeuvre['release_date'], 0, 4) : (isset($oeuvre['first_air_date']) ? substr($oeuvre['first_air_date'], 0, 4) : null);
            $poster = isset($oeuvre['poster_path']) ? $oeuvre['poster_path'] : 'default.jpg';
            $description = isset($oeuvre['overview']) ? $oeuvre['overview'] : 'Aucune description disponible';
            $popularite = isset($oeuvre['popularity']) ? $oeuvre['popularity'] : 0;
            $genre_ids = isset($oeuvre['genre_ids']) ? $oeuvre['genre_ids'] : [];
            $genres = [];
            foreach ($genre_ids as $genre_id) {
                if (isset($genres_map[$genre_id])) {
                    $genres[] = $genres_map[$genre_id];
                }
            }
            $genres_str = implode(', ', $genres);
            $query = "INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite, genres)
                      VALUES (:id_tmdb, :titre, :type_oeuvre, :annee_sortie, :poster, :description, :popularite, :genres)
                      ON DUPLICATE KEY UPDATE titre = :titre";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_tmdb', $id_tmdb);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':type_oeuvre', $type_oeuvre);
            $stmt->bindParam(':annee_sortie', $annee_sortie);
            $stmt->bindParam(':poster', $poster);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':popularite', $popularite);
            $stmt->bindParam(':genres', $genres_str);
            if (!$stmt->execute()) {
                echo "Erreur lors de l'insertion du $type_oeuvre : " . $titre . '<br>';
            } else {
                echo "$type_oeuvre inséré : " . $titre . '<br>';
            }
        }
    } else {
        echo "Aucun $type_oeuvre trouvé dans l'API.";
    }
}

$api_url_films = 'https://api.themoviedb.org/3/movie/popular?api_key=' . $api_key . '&language=fr-FR&page=1';
$api_url_series = 'https://api.themoviedb.org/3/tv/popular?api_key=' . $api_key . '&language=fr-FR&page=1';
fetch_and_insert($api_url_films, 'film', $genres_map);
fetch_and_insert($api_url_series, 'serie', $genres_map);

echo "Genres et œuvres insérés avec succès.";
?>