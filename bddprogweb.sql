-- Création de la base de données
CREATE DATABASE IF NOT EXISTS suivi_films_series;
USE suivi_films_series;

DROP TABLE IF EXISTS SuiviEpisodes;
DROP TABLE IF EXISTS AvisEpisodes;
DROP TABLE IF EXISTS Catalogue;
DROP TABLE IF EXISTS FilmsSeries;
DROP TABLE IF EXISTS Utilisateurs;

CREATE TABLE IF NOT EXISTS FilmsSeries (
    id_tmdb INT PRIMARY KEY,  -- ID unique de TMDb (film_id ou tv_id)
    titre VARCHAR(255) NOT NULL,
    type_oeuvre ENUM('film', 'serie') NOT NULL,
    annee_sortie INT,
    poster VARCHAR(255),  -- URL de l'affiche du film/série
    description TEXT,
    popularite FLOAT,
    genres VARCHAR(255),  -- Liste des genres séparés par des virgules (ex: "Action, Aventure, Drame")
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Genres (
    id_genre INT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL
);


-- Table Utilisateurs
CREATE TABLE IF NOT EXISTS Utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- Table Catalogue personnel (films et séries suivis par chaque utilisateur)
CREATE TABLE IF NOT EXISTS Catalogue (
    id_catalogue INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_tmdb INT NOT NULL,  -- Lié à FilmsSeries
    statut ENUM('vu','en cours','à voir') NOT NULL DEFAULT 'à voir',
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    note FLOAT CHECK (note >= 0 AND note <= 5),
    commentaire TEXT,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_tmdb) REFERENCES FilmsSeries(id_tmdb) ON DELETE CASCADE,
    UNIQUE KEY (id_utilisateur, id_tmdb)  -- Un utilisateur ne peut ajouter un même film/série qu'une fois
);


-- Table SuiviEpisodes (permet de cocher les épisodes vus pour les séries)
CREATE TABLE IF NOT EXISTS SuiviEpisodes (
    id_suivi INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_externe INT NOT NULL,  -- Identifiant TMDb de la série (tv_id)
    saison INT NOT NULL,
    episode INT NOT NULL,
    vu BOOLEAN DEFAULT FALSE,  -- Episode vu ou non
    date_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_suivi_episodes_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id_utilisateur) ON DELETE CASCADE,
    UNIQUE KEY (id_utilisateur, id_externe, saison, episode)  -- Empêche les doublons pour un même épisode suivi
);



CREATE TABLE IF NOT EXISTS AvisEpisodes (
    id_avis INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_externe INT NOT NULL,  -- ID TMDb de la série (tv_id)
    saison INT NOT NULL,
    episode INT NOT NULL,
    note FLOAT CHECK (note >= 0 AND note <= 5),  -- Note de 0 à 5 étoiles
    commentaire TEXT,  -- Commentaire optionnel
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avis_episodes_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id_utilisateur) ON DELETE CASCADE,
    UNIQUE KEY (id_utilisateur, id_externe, saison, episode)  -- Un seul avis par utilisateur/épisode
);