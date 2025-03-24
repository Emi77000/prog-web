<?php
session_start();
include 'functions.php';

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    echo "Vous devez être connecté pour ajouter un film/série au catalogue.";
    exit();
}

$id_utilisateur = $_SESSION['user_id'];
$id_tmdb = $_POST['id_tmdb'];
$statut = $_POST['statut'];
$note = $_POST['note'];
$commentaire = $_POST['commentaire'];

// Ajouter le film/série au catalogue
addToCatalogue($id_utilisateur, $id_tmdb, $statut, $note, $commentaire);

echo "Film/Série ajouté au catalogue avec succès !";
?>
