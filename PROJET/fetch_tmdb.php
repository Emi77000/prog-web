<?php

function fetchTMDB($endpoint, $params = [])
{
    $apiKey = 'f751208ae91021f307bb02f72b63586b'; // Clé TMDB
    $url = "https://api.themoviedb.org/3/$endpoint";
    $params['api_key'] = $apiKey;
    $params['language'] = 'fr-FR';
    $url .= '?' . http_build_query($params);

    $response = @file_get_contents($url);
    return json_decode($response, true);
}

// Vérifie si une œuvre contient les infos essentielles
function estOeuvreValide($media)
{
    return isset($media['poster_path'], $media['overview']) &&
           $media['poster_path'] !== null &&
           trim($media['overview']) !== '' &&
           (
               !empty($media['title']) ||
               !empty($media['name'])
           );
}

// Recherche multi (films + séries), avec filtrage des résultats incomplets
function rechercherTMDB($terme)
{
    $resultats = fetchTMDB('search/multi', ['query' => $terme])['results'] ?? [];
    return array_values(array_filter($resultats, 'estOeuvreValide'));
}

// Détails d’une œuvre (film ou série)
function getDetailsTMDB($id, $type)
{
    return fetchTMDB("$type/$id");
}

// Récupère les genres disponibles (films + séries), fusionne et déduplique
function getGenresTMDB()
{
    $genresFilm = fetchTMDB("genre/movie/list")['genres'] ?? [];
    $genresSerie = fetchTMDB("genre/tv/list")['genres'] ?? [];

    $genres = array_merge($genresFilm, $genresSerie);
    $genresUniques = [];

    foreach ($genres as $genre) {
        $genresUniques[$genre['id']] = $genre['name'];
    }

    return $genresUniques;
}
