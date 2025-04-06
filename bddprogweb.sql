-- Création de la base de données
CREATE DATABASE IF NOT EXISTS suivi_films_series;
USE suivi_films_series;

DROP TABLE IF EXISTS suivi_episode;
DROP TABLE IF EXISTS episode;
DROP TABLE IF EXISTS saison;
DROP TABLE IF EXISTS catalogue_utilisateur;
DROP TABLE IF EXISTS oeuvre;
DROP TABLE IF EXISTS utilisateur;







-- Table des utilisateurs
CREATE TABLE utilisateur (
                             id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
                             pseudo VARCHAR(50) NOT NULL UNIQUE,
                             email VARCHAR(100) NOT NULL UNIQUE,
                             mot_de_passe VARCHAR(255) NOT NULL,
                             date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des œuvres (films et séries)
CREATE TABLE oeuvre (
                        id_oeuvre INT PRIMARY KEY, -- ID de TMDB
                        titre VARCHAR(255) NOT NULL,
                        type varchar(10) NOT NULL,
                        annee_sortie YEAR,
                        genre VARCHAR(100),
                        affiche VARCHAR(255),
                        resume TEXT
);

-- Table du catalogue personnel de chaque utilisateur
CREATE TABLE catalogue_utilisateur (
                                       id_catalogue INT AUTO_INCREMENT PRIMARY KEY,
                                       id_utilisateur INT NOT NULL,
                                       id_oeuvre INT NOT NULL,
                                       statut ENUM('vu','en cours', 'à voir') DEFAULT 'à voir',
                                       note INT, -- sur 5
                                       commentaire TEXT,
                                       date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
                                       type varchar(10) NOT NULL,
                                       FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
                                       UNIQUE KEY  (id_utilisateur, id_oeuvre)
);

-- Table des saisons (pour les séries)
CREATE TABLE saison (
                        id_saison INT AUTO_INCREMENT PRIMARY KEY,
                        id_oeuvre INT NOT NULL,
                        numero_saison INT NOT NULL,
                        titre_saison VARCHAR(255),
                        nb_episodes INT,
                        date YEAR,
                        FOREIGN KEY (id_oeuvre) REFERENCES oeuvre(id_oeuvre) ON DELETE CASCADE,
                        UNIQUE KEY (id_oeuvre, numero_saison)
);

-- Table des épisodes
CREATE TABLE episode (
                         id_episode INT AUTO_INCREMENT PRIMARY KEY,
                         id_saison INT NOT NULL,
                         numero_episode INT NOT NULL,
                         titre_episode VARCHAR(255),
                         resume TEXT,
                         date_diffusion DATE,
                         FOREIGN KEY (id_saison) REFERENCES saison(id_saison) ON DELETE CASCADE,
                         UNIQUE KEY (id_saison, numero_episode)
);

-- Suivi des épisodes vus par utilisateur
CREATE TABLE suivi_episode (
                               id_suivi INT AUTO_INCREMENT PRIMARY KEY,
                               id_utilisateur INT NOT NULL,
                               id_episode INT NOT NULL,
                               vu BOOLEAN DEFAULT FALSE,
                               date_vue DATETIME,
                               FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
                               FOREIGN KEY (id_episode) REFERENCES episode(id_episode) ON DELETE CASCADE,
                               UNIQUE KEY (id_utilisateur, id_episode)
);

