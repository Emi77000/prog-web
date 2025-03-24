-- Création de la base de données
CREATE DATABASE IF NOT EXISTS suivi_films_series;
USE suivi_films_series;

CREATE TABLE IF NOT EXISTS FilmsSeries (
    id_tmdb INT PRIMARY KEY,  -- ID unique de TMDb (film_id ou tv_id)
    titre VARCHAR(255) NOT NULL,
    type_oeuvre ENUM('film', 'serie') NOT NULL,
    annee_sortie INT,
    poster VARCHAR(255),  -- URL de l'affiche du film/série
    description TEXT,
    popularite FLOAT,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insérer une dizaine de films dans la table FilmsSeries
INSERT INTO FilmsSeries (id_tmdb, titre, type_oeuvre, annee_sortie, poster, description, popularite)
VALUES
    (550, 'Fight Club', 'film', 1999, 'https://image.tmdb.org/t/p/w500/8d6dfl7T4Iu6rghRPt9qYzO2Jlo.jpg', 'A troubled man seeks the help of a self-help guru, but things take a dark turn.', 8.4),
    (680, 'The Dark Knight', 'film', 2008, 'https://image.tmdb.org/t/p/w500/2Xcq8tI0VbhBfWs1yGhX4MvOb5H.jpg', 'Batman faces off against the Joker, a criminal mastermind who seeks to create chaos in Gotham City.', 8.9),
    (157336, 'Interstellar', 'film', 2014, 'https://image.tmdb.org/t/p/w500/8ZFbImxk9zFWeKj5GzD6Zxfz3U6.jpg', 'A team of explorers travels through a wormhole in space in an attempt to ensure humanity\'s survival.', 8.6),
    (500, 'The Shawshank Redemption', 'film', 1994, 'https://image.tmdb.org/t/p/w500/9J4PczmUbNzx39sPrtFlkKqLzry.jpg', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 9.3),
    (120, 'The Godfather', 'film', 1972, 'https://image.tmdb.org/t/p/w500/2lN1bC5MEqOgeUoTjw6aMhxiQUp.jpg', 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.', 9.2),
    (131, 'Pulp Fiction', 'film', 1994, 'https://image.tmdb.org/t/p/w500/i0yaAIZPfl37hO5Mk7Aor5vhtb3.jpg', 'The lives of two mob hitmen, a boxer, a gangster\'s wife, and a pair of diner bandits intertwine in four tales of violence and redemption.', 8.9),
    (670, 'Inception', 'film', 2010, 'https://image.tmdb.org/t/p/w500/sQjczzckgwPxg0txVsjch9HGwFe.jpg', 'A thief who enters the dreams of others to steal secrets from their subconscious is given a chance to have his criminal record erased.', 8.8),
    (337, 'The Matrix', 'film', 1999, 'https://image.tmdb.org/t/p/w500/62E6OwTzqWjtAsX5b5Dp5J1Vu5O.jpg', 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.', 8.7),
    (159, 'The Lord of the Rings: The Fellowship of the Ring', 'film', 2001, 'https://image.tmdb.org/t/p/w500/1iHma8i1doIfZGGAU4u9gg5SqtV.jpg', 'A young hobbit is tasked with carrying the one ring to Mount Doom, accompanied by a fellowship of friends and allies.', 8.8),
    (11, 'Forrest Gump', 'film', 1994, 'https://image.tmdb.org/t/p/w500/jdZmM7eTzzEEnK0GfqftF7mfUp5.jpg', 'The presidencies of Kennedy and Johnson, the Vietnam War, the Watergate scandal and other historical events unfold from the perspective of an Alabama man with an extraordinary brain.', 8.8);


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