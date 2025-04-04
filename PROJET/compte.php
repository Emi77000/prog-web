<?php
session_start();
require_once 'db_connection.php'; // Connexion à la base de données

// Vérification que l'utilisateur est bien connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$id_utilisateur = $_SESSION['id_utilisateur'];
$query = "SELECT * FROM utilisateur WHERE id_utilisateur = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_utilisateur]);
$utilisateur = $stmt->fetch();

// Vérifier si l'utilisateur existe
if (!$utilisateur) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Récupérer les films/séries du catalogue de l'utilisateur
$queryCatalogue = "SELECT * FROM catalogue_utilisateur WHERE id_utilisateur = ?";
$stmtCatalogue = $pdo->prepare($queryCatalogue);
$stmtCatalogue->execute([$id_utilisateur]);
$catalogue = $stmtCatalogue->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte - TrackFlix</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="header">
    <nav>
        <ul style="display: flex; align-items: center; margin: 0;">
            <li style="margin-right: auto;">
                <a href="accueil.php" style="font-size: 2em; color: white; text-decoration: none;">TrackFlix</a>
            </li>
            <li><a href="catalogPerso.php">Mon Catalogue</a></li>
            <li><a href="suiviSerie.php">Suivi séries</a></li>
            <li><a href="compte.php">Compte</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<main>
    <div class="account-info">
        <h2>Mon Compte</h2>
        <p><strong>Pseudo:</strong> <?= htmlspecialchars($utilisateur['pseudo']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($utilisateur['email']) ?></p>
        <p><strong>Date d'inscription:</strong> <?= htmlspecialchars($utilisateur['date_inscription']) ?></p>
    </div>

    <div class="user-catalogue">
        <h2>Mon Catalogue</h2>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalogue as $item): 
                    // Récupérer les informations sur l'œuvre (film/série)
                    $queryOeuvre = "SELECT * FROM oeuvre WHERE id_oeuvre = ?";
                    $stmtOeuvre = $pdo->prepare($queryOeuvre);
                    $stmtOeuvre->execute([$item['id_oeuvre']]);
                    $oeuvre = $stmtOeuvre->fetch();
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($oeuvre['titre']) ?></td>
                        <td><?= htmlspecialchars($oeuvre['type']) ?></td>
                        <td><?= htmlspecialchars($item['statut']) ?></td>
                        <td><?= htmlspecialchars($item['note']) ?></td>
                        <td><?= htmlspecialchars($item['commentaire']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
