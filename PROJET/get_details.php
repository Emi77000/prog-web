<?php
header('Content-Type: application/json');

// Remplace cette clé par ta vraie clé API TMDB
$api_key = 'f751208ae91021f307bb02f72b63586b';

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$id = htmlspecialchars($_GET['id']);
$type = htmlspecialchars($_GET['type']); // 'movie' ou 'tv'

// Vérification du type
if (!in_array($type, ['movie', 'tv'])) {
    echo json_encode(['error' => 'Type invalide']);
    exit;
}

$url = "https://api.themoviedb.org/3/{$type}/{$id}?api_key={$api_key}&language=fr-FR";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($curl);

if ($response === false) {
    echo json_encode(['error' => 'Erreur cURL : ' . curl_error($curl)]);
    curl_close($curl);
    exit;
}

$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($http_code !== 200) {
    echo json_encode(['error' => "TMDB a renvoyé le code HTTP $http_code"]);
    exit;
}

// Renvoyer les données JSON telles quelles
echo $response;
exit;