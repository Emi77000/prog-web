<?php
session_start();
require_once 'db_connection.php';

$message = '';
$erreur = '';

// Connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (!empty($email) && !empty($mot_de_passe)) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['pseudo'] = $utilisateur['pseudo'];
            header('Location: accueil.php');
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}

// Inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $pseudo = $_POST['pseudo'] ?? '';
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (strlen($pseudo) < 3) {
        $erreur = "Le pseudo est trop court.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier si l'email ou le pseudo existe déjà
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ? OR pseudo = ?");
        $stmt->execute([$email, $pseudo]);

        if ($stmt->rowCount() > 0) {
            $erreur = "Email ou pseudo déjà utilisé.";
        } else {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateur (pseudo, email, mot_de_passe) VALUES (?, ?, ?)");
            $stmt->execute([$pseudo, $email, $hash]);
            $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion / Inscription</title>
    <script src="login.js" defer></script>
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

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            gap: 40px;
            flex-wrap: wrap; /* Permet l'adaptation mobile */
            padding: 20px;
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

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
            }
        }

        .message {
            color: #d9534f;
            margin-top: 10px;
            font-size: 14px;
        }

        .success {
            color: #28a745;
        }

    </style>
</head>
<body>
<div class="container">
    <div class="box">
        <h2>Créer un compte</h2>
        <form method="POST" onsubmit="return validateRegister();">
            <input type="hidden" name="action" value="register">
            <input type="text" name="pseudo" id="reg-username" placeholder="Pseudo" required>
            <input type="email" name="email" id="reg-email" placeholder="Email" required>
            <input type="password" name="mot_de_passe" id="reg-password" placeholder="Mot de passe" required>
            <button type="submit">S'inscrire</button>
            <p id="reg-error" class="message">
                <?php if (!empty($erreur) && isset($_POST['action']) && $_POST['action'] === 'register') echo htmlspecialchars($erreur); ?>
            </p>
            <p class="success message">
                <?php if (!empty($message)) echo htmlspecialchars($message); ?>
            </p>
        </form>
    </div>
    <div class="box">
        <h2>Connexion</h2>
        <form method="POST" onsubmit="return validateLogin();">
            <input type="hidden" name="action" value="login">
            <input type="email" name="email" id="login-email" placeholder="Email" required>
            <input type="password" name="mot_de_passe" id="login-password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
            <p id="login-error" class="message">
                <?php if (!empty($erreur) && isset($_POST['action']) && $_POST['action'] === 'login') echo htmlspecialchars($erreur); ?>
            </p>
        </form>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Mon Catalogue</p>
</footer>
</body>

</html>
