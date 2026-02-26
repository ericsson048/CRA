CREATE DATABASE IF NOT EXISTS resource_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resource_manager;

CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    categorie VARCHAR(80) NOT NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('Disponible', 'En maintenance', 'Indisponible') NOT NULL DEFAULT 'Disponible',
    localisation VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'gestionnaire', 'developpeur') NOT NULL DEFAULT 'developpeur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS planning_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT NULL,
    statut ENUM('A faire', 'En cours', 'Terminee') NOT NULL DEFAULT 'A faire',
    priorite ENUM('Basse', 'Moyenne', 'Haute') NOT NULL DEFAULT 'Moyenne',
    due_date DATE NULL,
    resource_id INT NULL,
    assigned_user_id INT NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_planning_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL,
    CONSTRAINT fk_planning_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_planning_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO resources (nom, categorie, quantite, statut, localisation) VALUES
('Equipe Sprint Frontend', 'RH - Developpeur', 4, 'Disponible', 'Planning Sprint A'),
('Equipe QA', 'RH - Support', 2, 'Disponible', 'Planning Sprint B'),
('Ordinateur Dell Latitude', 'RM - Informatique', 15, 'Disponible', 'Salle IT - Etage 2'),
('Projecteur Epson X500', 'RM - Informatique', 3, 'En maintenance', 'Magasin central');
