<?php
require_once('db_connection.php');
session_start();

$message = "";

// Gestion de l'inscription
if (isset($_POST['signup'])) {
    $pseudo = trim($_POST['pseudo']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Vérifier si l'email existe déjà
        $checkSql = "SELECT COUNT(*) FROM Utilisateurs WHERE email = :email";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['email' => $email]);
        $emailExists = $checkStmt->fetchColumn();

        if ($emailExists) {
            $message = "Cet email est déjà utilisé.";
        } else {
            // Insérer le nouvel utilisateur
            $sql = "INSERT INTO Utilisateurs (pseudo, email, mot_de_passe) VALUES (:pseudo, :email, :mot_de_passe)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['pseudo' => $pseudo, 'email' => $email, 'mot_de_passe' => $password]);

            // Récupérer l'ID de l'utilisateur nouvellement inscrit
            $user_id = $pdo->lastInsertId();

            // Créer la session et rediriger
            $_SESSION['user_id'] = $user_id;
            $_SESSION['pseudo'] = $pseudo;
            session_regenerate_id(true); // Sécurité

            header("Location: accueil.php");
            exit;
        }
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// Gestion de la connexion
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM Utilisateurs WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['pseudo'] = $user['pseudo'];
        session_regenerate_id(true); // Sécurité

        header("Location: accueil.php");
        exit;
    } else {
        $message = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion / Inscription</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .container {
            display: flex;
            gap: 20px;
            text-align: center;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        .message {
            text-align: center;
            color: red;
            margin-top: 10px;
        }
    </style>
</head>

<body>
<div class="container">
    <!-- Section Inscription -->
    <div class="box">
        <h2>Créer un compte</h2>
        <form action="" method="POST">
            <input type="text" name="pseudo" placeholder="Pseudo" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" name="signup">S'inscrire</button>
        </form>
    </div>

    <!-- Section Connexion -->
    <div class="box">
        <h2>Se connecter</h2>
        <form action="" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" name="login">Se connecter</button>
        </form>
    </div>
</div>

<p class="message"><?= htmlspecialchars($message) ?></p>
</body>
</html>
