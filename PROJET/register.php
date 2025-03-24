<?php
// Inclure la connexion à la base de données
require_once('db_connection.php');

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $pseudo = $_POST['pseudo'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Sécuriser les données
    $pseudo = htmlspecialchars($pseudo);
    $email = htmlspecialchars($email);
    $mot_de_passe = htmlspecialchars($mot_de_passe);

    // Hacher le mot de passe avant de l'enregistrer
    $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Vérifier si l'utilisateur existe déjà
    $query = "SELECT * FROM utilisateurs WHERE pseudo = :pseudo";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['pseudo' => $pseudo]);

    if ($stmt->rowCount() > 0) {
        // L'utilisateur existe déjà
        echo "Ce pseudo est déjà pris.";
    } else {
        // Insérer l'utilisateur dans la base de données
        $insertQuery = "INSERT INTO utilisateurs (pseudo, email, mot_de_passe) VALUES (:pseudo, :email, :mot_de_passe)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([
            'pseudo' => $pseudo,
            'email' => $email,
            'mot_de_passe' => $hashedPassword
        ]);

        echo "Compte créé avec succès !";
    }
}
?>
