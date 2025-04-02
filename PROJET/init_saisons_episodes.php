<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once 'db_connection.php';
    session_start();

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['id_utilisateur'])) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
        exit;
    }

    if (!isset($_POST['id_tmdb'])) {
        echo json_encode(['success' => false, 'error' => 'ID TMDB manquant']);
        exit;
    }

    $id_tmdb = $_POST['id_tmdb'];
    $api_key = 'f751208ae91021f307bb02f72b63586b';

    $tv_info = file_get_contents("https://api.themoviedb.org/3/tv/$id_tmdb?api_key=$api_key&language=fr");
    $data = json_decode($tv_info, true);

    if (!isset($data['seasons']) || empty($data['seasons'])) {
        echo json_encode(['success' => false, 'error' => 'Aucune saison trouvée']);
        exit;
    }

    $nb_saisons_importees = 0;

    foreach ($data['seasons'] as $saison) {
        $numero_saison = $saison['season_number'];

        // Pause de sécurité pour éviter surcharge API
        usleep(300000); // 300ms

        // Détails complets de la saison
        $saison_details = file_get_contents("https://api.themoviedb.org/3/tv/$id_tmdb/season/$numero_saison?api_key=$api_key&language=fr");
        $saison_data = json_decode($saison_details, true);

        if (empty($saison_data['episodes'])) {
            continue; // saison vide
        }

        $titre_saison = $saison_data['name'] ?? "Saison $numero_saison";
        $date_sortie = $saison_data['air_date'] ?? null;
        $annee_sortie = $date_sortie ? substr($date_sortie, 0, 4) : null;

        // Insertion saison
        try {
            $stmt_s = $pdo->prepare("INSERT INTO saison (id_oeuvre, numero_saison, titre_saison, date)
                                     VALUES (?, ?, ?, ?)");
            $stmt_s->execute([$id_tmdb, $numero_saison, $titre_saison, $annee_sortie]);
            $id_saison = $pdo->lastInsertId();
            $nb_saisons_importees++;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur insertion saison ' . $numero_saison . ' : ' . $e->getMessage()]);
            exit;
        }

        // Insertion épisodes
        foreach ($saison_data['episodes'] as $ep) {
            $numero_episode = $ep['episode_number'];
            $titre_episode = $ep['name'] ?? '';
            $resume = $ep['overview'] ?? '';
            $date_diffusion = $ep['air_date'] ?? null;

            try {
                $stmt_ep = $pdo->prepare("
                    INSERT INTO episode (id_saison, numero_episode, titre_episode, resume, date_diffusion)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_ep->execute([$id_saison, $numero_episode, $titre_episode, $resume, $date_diffusion]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => "Erreur épisode S$numero_saison E$numero_episode : " . $e->getMessage()]);
                exit;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "$nb_saisons_importees saisons importées avec succès."
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Exception générale : ' . $e->getMessage()]);
    exit;
}
?>
