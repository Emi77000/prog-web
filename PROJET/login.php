<?php
session_start();
require_once 'db_connection.php';

$erreur = '';
$erreur_register = '';
$message = '';
$afficher_formulaire_inscription = false;
$old_pseudo = '';
$old_email = '';

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

    $old_pseudo = $pseudo;
    $old_email = $email;
    $afficher_formulaire_inscription = true;

    if (strlen($pseudo) < 3) {
        $erreur_register = "Le pseudo est trop court.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur_register = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur_register = "Format d'email invalide.";

        
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ? OR pseudo = ?");
        $stmt->execute([$email, $pseudo]);

        if ($stmt->rowCount() > 0) {
            $erreur_register = "Email ou pseudo déjà utilisé.";
        } else {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateur (pseudo, email, mot_de_passe) VALUES (?, ?, ?)");
            $stmt->execute([$pseudo, $email, $hash]);

            $_SESSION['id_utilisateur'] = $pdo->lastInsertId();
            $_SESSION['pseudo'] = $pseudo;
            $_SESSION['email'] = $email;

            header('Location: accueil.php');
            exit;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion / Inscription</title>
    <link rel="stylesheet" href="login.css">
    <script src="login.js" defer></script>
</head>
<body>

<div class="container">
    <!-- Formulaire de connexion -->
    <div class="box" style="<?= $afficher_formulaire_inscription ? 'display:none;' : '' ?>">
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
        <p><a href="javascript:void(0);" onclick="toggleRegisterForm()">Pas encore inscrit ? S'inscrire ici</a></p>
    </div>

    <!-- Formulaire d'inscription -->
    <div class="box register-box" style="<?= $afficher_formulaire_inscription ? 'display:block;' : 'display:none;' ?>">
        <h2>Créer un compte</h2>
        <form method="POST" onsubmit="return validateRegister();">
            <input type="hidden" name="action" value="register">
            <input type="text" name="pseudo" id="reg-username" placeholder="Pseudo" required
                   value="<?= htmlspecialchars($old_pseudo) ?>">
            <input type="email" name="email" id="reg-email" placeholder="Email" required
                   value="<?= htmlspecialchars($old_email) ?>">
            <input type="password" name="mot_de_passe" id="reg-password" placeholder="Mot de passe" required>
            <button type="submit">S'inscrire</button>
            <p id="reg-error" class="message">
                <?php if (!empty($erreur_register)) echo htmlspecialchars($erreur_register); ?>
            </p>
        </form>
        <p><a href="javascript:void(0);" onclick="toggleLoginForm()">Déjà un compte ? Se connecter ici</a></p>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> TrackFlix</p>
</footer>

<script>
    function toggleRegisterForm() {
        document.querySelector('.register-box').style.display = 'block';
        document.querySelector('.box').style.display = 'none';
    }

    function toggleLoginForm() {
        document.querySelector('.box').style.display = 'block';
        document.querySelector('.register-box').style.display = 'none';
    }
</script>

</body>
</html>
