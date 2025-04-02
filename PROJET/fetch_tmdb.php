<?php
require_once 'db_connection.php';

function importOeuvreDepuisTMDB($id, $type, $pdo)
{
    $apiKey = 'f751208ae91021f307bb02f72b63586b'; // Remplace par ta vraie clé

    // 1. Récupération des données principales
    $data = @file_get_contents("https://api.themoviedb.org/3/$type/$id?api_key=$apiKey&language=fr-FR");
    $data = json_decode($data, true);

    if (!$data || isset($data['status_code'])) {
        return false;
    }

    $titre = $type === 'movie' ? $data['title'] : $data['name'];
    $annee = substr($type === 'movie' ? $data['release_date'] : $data['first_air_date'], 0, 4);
    $genre = $data['genres'][0]['name'] ?? '';
    $affiche = $data['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : '';
    $resume = $data['overview'] ?? '';

    $stmt = $pdo->prepare("REPLACE INTO oeuvre (id_oeuvre, titre, type, annee_sortie, genre, affiche, resume)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $titre, $type === 'movie' ? 'film' : 'serie', $annee, $genre, $affiche, $resume]);

    // Si série, importer les saisons et épisodes
    if ($type === 'tv') {
        $details = @file_get_contents("https://api.themoviedb.org/3/tv/$id?api_key=$apiKey&language=fr-FR&append_to_response=seasons");
        $details = json_decode($details, true);

        if (!isset($details['seasons'])) return true;

        foreach ($details['seasons'] as $saison) {
            $num_saison = $saison['season_number'];
            $titre_saison = $saison['name'];
            $nb_episodes = $saison['episode_count'];

            $stmt = $pdo->prepare("INSERT INTO saison (id_oeuvre, numero_saison, titre_saison, nb_episodes)
                                    VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $num_saison, $titre_saison, $nb_episodes]);
            $id_saison = $pdo->lastInsertId();

            $episodes = @file_get_contents("https://api.themoviedb.org/3/tv/$id/season/$num_saison?api_key=$apiKey&language=fr-FR");
            $episodes = json_decode($episodes, true);

            if (!isset($episodes['episodes'])) continue;

            foreach ($episodes['episodes'] as $episode) {
                $stmt = $pdo->prepare("INSERT INTO episode (id_saison, numero_episode, titre_episode, resume)
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_saison, $episode['episode_number'], $episode['name'], $episode['overview']]);
            }
        }
    }

    return true;
}
