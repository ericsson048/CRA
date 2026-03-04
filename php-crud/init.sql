CREATE DATABASE IF NOT EXISTS resource_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resource_manager;

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    tl_user_id INT NULL,
    tla_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'gestionnaire', 'team_leader', 'team_leader_adjoint', 'developpeur') NOT NULL DEFAULT 'developpeur',
    team_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    password_changed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_team_id (team_id),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    categorie VARCHAR(80) NOT NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('Disponible', 'En maintenance', 'Indisponible') NOT NULL DEFAULT 'Disponible',
    localisation VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(140) NOT NULL,
    description TEXT NULL,
    statut ENUM('Planifie', 'En cours', 'En pause', 'Termine') NOT NULL DEFAULT 'Planifie',
    start_date DATE NULL,
    end_date DATE NULL,
    team_id INT NOT NULL,
    tl_user_id INT NOT NULL,
    tla_user_id INT NULL,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_projects_team (team_id),
    INDEX idx_projects_tl_user (tl_user_id),
    INDEX idx_projects_tla_user (tla_user_id),
    INDEX idx_projects_assigned_by (assigned_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planning_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT NULL,
    statut ENUM('A faire', 'En cours', 'Terminee') NOT NULL DEFAULT 'A faire',
    priorite ENUM('Basse', 'Moyenne', 'Haute') NOT NULL DEFAULT 'Moyenne',
    due_date DATE NULL,
    project_id INT NULL,
    resource_id INT NULL,
    assigned_user_id INT NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_planning_project_id (project_id),
    INDEX idx_planning_resource_id (resource_id),
    INDEX idx_planning_assigned_user (assigned_user_id),
    INDEX idx_planning_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    details_json JSON NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_audit_user_id (user_id),
    INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL,
    INDEX idx_login_identifier (identifier, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_notifications_user_id (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE teams
    ADD CONSTRAINT fk_teams_tl_user FOREIGN KEY (tl_user_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_teams_tla_user FOREIGN KEY (tla_user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE users
    ADD CONSTRAINT fk_users_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

ALTER TABLE projects
    ADD CONSTRAINT fk_projects_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_projects_tl_user FOREIGN KEY (tl_user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_projects_tla_user FOREIGN KEY (tla_user_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_projects_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE planning_tasks
    ADD CONSTRAINT fk_planning_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_planning_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_planning_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_planning_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE audit_logs
    ADD CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
