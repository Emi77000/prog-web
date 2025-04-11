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

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'modifier') {
    $nouvelEmail = $_POST['nouveau_email'] ?? '';
    $nouveauMDP = $_POST['nouveau_mot_de_passe'] ?? '';

    if (!empty($nouvelEmail)) {
        if (!filter_var($nouvelEmail, FILTER_VALIDATE_EMAIL)) {
            $erreur = "Email invalide.";
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateur SET email = ? WHERE id_utilisateur = ?");
            $stmt->execute([$nouvelEmail, $id_utilisateur]);
            $message = "Email mis à jour.";
            $utilisateur['email'] = $nouvelEmail; // mettre à jour localement
        }
    }

    if (!empty($nouveauMDP)) {
        if (strlen($nouveauMDP) < 6) {
            $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            $hash = password_hash($nouveauMDP, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?");
            $stmt->execute([$hash, $id_utilisateur]);
            $message = "Mot de passe mis à jour.";
        }
    }

    if (empty($nouvelEmail) && empty($nouveauMDP)) {
        $erreur = "Veuillez remplir au moins un champ.";
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

// Récupérer les genres des films
$stmtGenresFilms = $pdo->prepare("SELECT genre, COUNT(*) AS count FROM catalogue_utilisateur cu 
    JOIN oeuvre o ON cu.id_oeuvre = o.id_oeuvre 
    WHERE cu.id_utilisateur = ? AND cu.statut = 'vu' AND o.type = 'movie'
    GROUP BY genre");
$stmtGenresFilms->execute([$id_utilisateur]);
$genresFilms = $stmtGenresFilms->fetchAll();

// Récupérer les genres des séries
$stmtGenresSeries = $pdo->prepare("SELECT genre, COUNT(*) AS count FROM catalogue_utilisateur cu 
    JOIN oeuvre o ON cu.id_oeuvre = o.id_oeuvre 
    WHERE cu.id_utilisateur = ? AND cu.statut = 'vu' AND o.type = 'tv'
    GROUP BY genre");
$stmtGenresSeries->execute([$id_utilisateur]);
$genresSeries = $stmtGenresSeries->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte - TrackFlix</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles CSS (inchangés) */
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
            background-color: #1f1f1f;
            color: white;
            padding: 2em;
            border-radius: 10px;
            width: 60%;
            margin: 2em auto;
        }
        .account-container {
            display: flex;
            justify-content: center;
            gap: 2em;
            margin: 2em auto;
            width: 100%;
            flex-wrap: nowrap;
        }
        .account-info,
        .form-container {
            width: 40%;
            min-width: 300px;
        }
        h2 {
            color: #e50914;
            border-bottom: 2px solid #e50914;
            padding-bottom: 5px;
        }
        .form-container {
            background-color: #1f1f1f;
            padding: 2em;
            border-radius: 10px;
            width: 60%;
            margin: 2em auto;
            color: white;
        }
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
            background-color: #e50914;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color:rgb(247, 55, 64);
        }
        .message {
            color: red;
            margin-top: 20px;
            font-size: 14px;
        }
        .success {
            color: #28a745;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1em;
            margin-top: 2em;
        }

        .stat-card {
            background-color: #1f1f1f;
            color: white;
            width: 250px;
            border-radius: 10px;
            padding: 1.5em;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        }

        .stat-icon {
            font-size: 2em;
            margin-bottom: 1em;
            color: #e50914;
        }

        .stat-info {
            font-size: 1.2em;
            margin-bottom: 1em;
        }

        .stat-info strong {
            display: block;
            font-size: 1.4em;
            margin-bottom: 0.5em;
        }

        .stat-footer {
            font-size: 0.9em;
            color: #777;
        }
        
        .stat-footer span {
            font-style: italic;
        }
        .charts {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 2em;
        }
        .chart {
            background-color: #1f1f1f;
            padding: 1em;
            text-align: center;
            width: 38%;
            min-width: 300px;
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

<div class="account-container">
    <div class="account-info">
        <h2>Mon Compte</h2> <br> <br>
        <p><strong>Pseudo:</strong> <?= htmlspecialchars($utilisateur['pseudo']) ?></p> <br>
        <p><strong>Email:</strong> <?= htmlspecialchars($utilisateur['email']) ?></p> <br>
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
</div>

<div style="padding: 0 2em;">
    <h2>Statistiques</h2>
    <div class="stats">
        <!-- Card for Films Vus -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-film"></i>
            </div>
            <div class="stat-info">
                <strong>Films vus</strong>
                <p><?= count($filmsVus) ?></p>
            </div>
            <div class="stat-footer">
                <span>Films</span>
            </div>
        </div>

        <!-- Card for Heures Estimées -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-clock"></i>
            </div>
            <div class="stat-info">
                <strong>Heures estimées</strong>
                <p><?= count($filmsVus) * 2 ?>h</p>
            </div>
            <div class="stat-footer">
                <span>Temps total</span>
            </div>
        </div>

        <!-- Card for Séries Vues -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-tv"></i>
            </div>
            <div class="stat-info">
                <strong>Séries vues</strong>
                <p><?= count($seriesVues) ?></p>
            </div>
            <div class="stat-footer">
                <span>Séries</span>
            </div>
        </div>

        <!-- Card for Épisodes Vus -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-clipboard-list"></i>
            </div>
            <div class="stat-info">
                <strong>Épisodes vus</strong>
                <p><?= array_sum(array_map(fn($s) => $s['nb_episodes'] ?? 10, $seriesVues)) ?></p>
            </div>
            <div class="stat-footer">
                <span>Épisodes</span>
            </div>
        </div>
    </div>
</div>

<div class="charts">
    <div class="chart">
        <h3>Genres Films</h3> <br>
        <canvas id="filmsChart"></canvas>
    </div>
    <div class="chart">
        <h3>Genres Séries</h3> <br>
        <canvas id="seriesChart"></canvas>
    </div>
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
                <a href="details_serie.php?id_oeuvre=<?= $serie['id_oeuvre'] ?>">
                    <img src="<?= htmlspecialchars($serie['affiche']) ?>" alt="<?= htmlspecialchars($serie['titre']) ?>">
                </a>
                <div class="carousel-item-title"><?= htmlspecialchars($serie['titre']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Récupérer les genres et les comptes des films depuis PHP
    const genresFilms = <?php echo json_encode($genresFilms); ?>;
    const genresSeries = <?php echo json_encode($genresSeries); ?>;

    // Fonction pour générer un diagramme en camembert
    function generatePieChart(ctx, data, labels) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#ff9999','#66b3ff','#99ff99','#ffcc99','#c2c2f0'],
                }]
            }
        });
    }

    // Films Chart
    const genresFilmsLabels = genresFilms.map(item => item.genre);
    const genresFilmsData = genresFilms.map(item => item.count);
    const filmsCtx = document.getElementById('filmsChart').getContext('2d');
    generatePieChart(filmsCtx, genresFilmsData, genresFilmsLabels);

    // Séries Chart
    const genresSeriesLabels = genresSeries.map(item => item.genre);
    const genresSeriesData = genresSeries.map(item => item.count);
    const seriesCtx = document.getElementById('seriesChart').getContext('2d');
    generatePieChart(seriesCtx, genresSeriesData, genresSeriesLabels);

    // Fonction pour générer un diagramme en camembert sans légende
    function generatePieChart(ctx, data, labels) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#ff5201','#ffc901','#00ca03','#00eec2','#005aee'],
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false  // Désactive l'affichage de la légende
                    }
                }
            }
        });
    }

</script>
</body>
</html>
