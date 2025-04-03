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
function rechercherTMDB($terme)
{
    return fetchTMDB('search/multi', ['query' => $terme])['results'] ?? [];
}

function getDetailsTMDB($id, $type)
{
    return fetchTMDB("$type/$id");
}
