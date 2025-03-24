<?php
include 'config.php';

// Fonction pour enregistrer un utilisateur
function registerUser($pseudo, $email, $mot_de_passe) {
    global $conn;

    // Hachage du mot de passe
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO Utilisateurs (pseudo, email, mot_de_passe) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $pseudo, $email, $hashed_password);
    $stmt->execute();
    $stmt->close();
}

// Fonction pour connecter un utilisateur
function loginUser($email, $mot_de_passe) {
    global $conn;

    $stmt = $conn->prepare("SELECT id_utilisateur, mot_de_passe FROM Utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id_utilisateur, $hashed_password);
    
    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($mot_de_passe, $hashed_password)) {
            session_start();
            $_SESSION['user_id'] = $id_utilisateur;
            return true;
        }
    }
    return false;
}

// Fonction pour récupérer les détails d'un film/série depuis l'API TMDb
function getDetailsFromAPI($id_tmdb, $type = 'movie') {
    global $api_key;
    $url = "https://api.themoviedb.org/3/$type/$id_tmdb?api_key=$api_key&language=fr";

    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return $data;
}

// Fonction pour ajouter un film/série dans le catalogue de l'utilisateur
function addToCatalogue($id_utilisateur, $id_tmdb, $statut, $note, $commentaire) {
    global $conn;

    // Vérifier si le film/série est déjà dans le catalogue
    $stmt = $conn->prepare("SELECT * FROM Catalogue WHERE id_utilisateur = ? AND id_tmdb = ?");
    $stmt->bind_param("ii", $id_utilisateur, $id_tmdb);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO Catalogue (id_utilisateur, id_tmdb, statut, note, commentaire) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $id_utilisateur, $id_tmdb, $statut, $note, $commentaire);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Ce film/série est déjà dans votre catalogue.";
    }
}
?>
