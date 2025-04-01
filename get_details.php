<?php
require_once('db_connection.php');

$id = $_GET['id'];

$query = "SELECT * FROM FilmsSeries WHERE id_tmdb = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($item) {
    // Récupérer les noms des genres
    $genres_list = explode(',', $item['genres']);
    $genres_names = [];
    foreach ($genres_list as $genre_id) {
        $genre_name = get_genre_name_by_id(trim($genre_id));
        $genres_names[] = $genre_name;
    }
    $item['genres'] = implode(', ', $genres_names);

    echo json_encode($item);
} else {
    echo json_encode(['error' => 'Item not found']);
}

function get_genre_name_by_id($genre_id) {
    global $pdo;
    if (empty($genre_id)) {
        return 'Inconnu';
    }
    $query = "SELECT nom FROM Genres WHERE id_genre = :genre_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':genre_id', $genre_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nom'] : 'Inconnu';
}
?>