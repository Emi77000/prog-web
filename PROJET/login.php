<?php
require_once('db_connection.php');
session_start();

$message = "";

// Gestion de l'inscription
if (isset($_POST['signup'])) {
    $pseudo = trim($_POST['pseudo']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "L'email est invalide.";
    } elseif (strlen($password) < 6) {
        $message = "Le mot de passe doit comporter au moins 6 caractères.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $checkSql = "SELECT COUNT(*) FROM Utilisateurs WHERE email = :email";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute(['email' => $email]);
            $emailExists = $checkStmt->fetchColumn();

            if ($emailExists) {
                $message = "Cet email est déjà utilisé.";
            } else {
                // Hasher le mot de passe
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insérer le nouvel utilisateur
                $sql = "INSERT INTO Utilisateurs (pseudo, email, mot_de_passe) VALUES (:pseudo, :email, :mot_de_passe)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['pseudo' => $pseudo, 'email' => $email, 'mot_de_passe' => $passwordHash]);

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
            $message = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}

// Gestion de la connexion
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "L'email est invalide.";
    } else {
        try {
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
        } catch (PDOException $e) {
            $message = "Erreur lors de la connexion : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion / Inscription</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            gap: 40px;
            flex-wrap: wrap;
        }

        .box {
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            color: red;
            margin-top: 20px;
            font-size: 14px;
        }

        .footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-size: 14px;
            color: #555;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
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
