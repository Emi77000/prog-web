<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$stmt->execute([$id_utilisateur]);
$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    echo "Utilisateur non trouvé.";
    exit;
}

$message = '';
$erreur = '';

// Vérification de la session utilisateur
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$id_utilisateur = $_SESSION['id_utilisateur'];
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$stmt->execute([$id_utilisateur]);
$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Modification des informations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $nouveau_email = $_POST['nouveau_email'] ?? '';
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'] ?? '';

    // Vérifier les validations
    if (!empty($nouveau_email)) {
        if (!filter_var($nouveau_email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "L'email n'est pas valide.";
        } else {
            // Vérifier si l'email est déjà utilisé
            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$nouveau_email]);
            if ($stmt->rowCount() > 0) {
                $erreur = "L'email est déjà utilisé.";
            } else {
                // Mettre à jour l'email
                $stmt = $pdo->prepare("UPDATE utilisateur SET email = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nouveau_email, $id_utilisateur]);
                $message = "Email modifié avec succès.";
            }
        }
    }

    if (!empty($nouveau_mot_de_passe)) {
        if (strlen($nouveau_mot_de_passe) < 6) {
            $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            // Mettre à jour le mot de passe
            $hash = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?");
            $stmt->execute([$hash, $id_utilisateur]);
            $message = "Mot de passe modifié avec succès.";
        }
    }
}

// Récupérer les œuvres vues
$stmtVus = $pdo->prepare("SELECT cu.*, o.* FROM catalogue_utilisateur cu 
    JOIN oeuvre o ON cu.id_oeuvre = o.id_oeuvre 
    WHERE cu.id_utilisateur = ? AND cu.statut = 'vu'");
$stmtVus->execute([$id_utilisateur]);
$oeuvresVues = $stmtVus->fetchAll();

// Séparer films et séries
$filmsVus = array_filter($oeuvresVues, fn($o) => $o['type'] === 'movie');
$seriesVues = array_filter($oeuvresVues, fn($o) => $o['type'] === 'tv');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte - TrackFlix</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .stats {
            margin: 2em 0;
            display: flex;
            justify-content: center;
            gap: 3em;
        }
        .carousel-container {
            overflow-x: auto;
            white-space: nowrap;
            padding: 1em 0;
        }
        .carousel-item {
            display: inline-block;
            margin-right: 15px;
            width: 150px;
            vertical-align: top;
        }
        .carousel-item img {
            width: 100%;
            border-radius: 10px;
        }
        .carousel-item-title {
            text-align: center;
            margin-top: 0.5em;
            font-weight: bold;
            color: #fff;
        }
        .account-info {
            background-color: #222;
            color: white;
            padding: 2em;
            border-radius: 10px;
            width: 60%;
            margin: 2em auto;
        }
        h2 {
            color: #ff4747;
            border-bottom: 2px solid #ff4747;
            padding-bottom: 5px;
        }
        .form-container {
            background-color: #333;
            padding: 2em;
            border-radius: 10px;
            width: 60%;
            margin: 2em auto;
            color: white;
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
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
        .success {
            color: #28a745;
        }
        .account-info {
            background-color: #222;
            color: white;
            padding: 2em;
            border-radius: 10px;
            width: 60%;
            margin: 2em auto;
        }
        h2 {
            color: #ff4747;
            border-bottom: 2px solid #ff4747;
            padding-bottom: 5px;
        }
        body {
            background-color: #121212;
            font-family: Arial, sans-serif;
            color: white;
        }
        header nav ul li a {
        color: white;
        text-decoration: none;
        margin: 0 10px;
        }

        header nav ul li a:hover {
            color: red;  /* Texte devient rouge au survol */
        }
        header nav ul {
            background-color: #1f1f1f;
            padding: 1em;
        }
    </style>
</head>
<body>
<header class="header">
    <nav>
        <ul style="display: flex; align-items: center; margin: 0;">
            <li style="margin-right: auto;">
                <a href="accueil.php" style="font-size: 2em;">TrackFlix</a>
            </li>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries</a></li>
            <li><a href="compte.php">Compte</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<div class="account-info">
    <h2>Mon Compte</h2>
    <p><strong>Pseudo:</strong> <?= htmlspecialchars($utilisateur['pseudo']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($utilisateur['email']) ?></p>
    <p><strong>Date d'inscription:</strong> <?= htmlspecialchars($utilisateur['date_inscription']) ?></p>
</div>

<div class="form-container">
    <h2>Modifier mes informations</h2>
    <form method="POST">
        <input type="hidden" name="action" value="modifier">
        <input type="email" name="nouveau_email" placeholder="Nouveau email">
        <input type="password" name="nouveau_mot_de_passe" placeholder="Nouveau mot de passe">
        <button type="submit">Modifier</button>
        <p class="message">
            <?php
            if (!empty($erreur)) {
                echo htmlspecialchars($erreur);
            } elseif (!empty($message)) {
                echo "<span class='success'>" . htmlspecialchars($message) . "</span>";
            }
            ?>
        </p>
    </form>
</div>

<div class="stats">
    <div><strong>Films vus :</strong> <?= count($filmsVus) ?></div>
    <div><strong>Séries vues :</strong> <?= count($seriesVues) ?></div>
</div>

<div style="padding: 0 2em;">
    <h2>Films vus</h2>
    <div class="carousel-container">
        <?php foreach ($filmsVus as $film): ?>
            <div class="carousel-item">
                <img src="<?= htmlspecialchars($film['affiche']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>">
                <div class="carousel-item-title"><?= htmlspecialchars($film['titre']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Séries vues</h2>
    <div class="carousel-container">
        <?php foreach ($seriesVues as $serie): ?>
            <div class="carousel-item">
<<<<<<< HEAD
                <img src="<?= htmlspecialchars($serie['affiche']) ?>" alt="<?= htmlspecialchars($serie['titre']) ?>">
=======
                <a href="details_serie.php?id_oeuvre=<?= $serie['id_oeuvre'] ?>">
                    <img src="<?= htmlspecialchars($serie['affiche']) ?>" alt="<?= htmlspecialchars($serie['titre']) ?>">
                </a>
>>>>>>> eb0366f (Page details_serie)
                <div class="carousel-item-title"><?= htmlspecialchars($serie['titre']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
