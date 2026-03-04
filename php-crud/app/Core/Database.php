<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    private const ROLE_ENUM = "'admin','gestionnaire','team_leader','team_leader_adjoint','developpeur'";

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = AppConfig::dbHost();
        $port = AppConfig::dbPort();
        $dbName = AppConfig::dbName();
        $username = AppConfig::dbUser();
        $password = AppConfig::dbPassword();

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            if (AppConfig::autoMigrate()) {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");
                self::initializeSchema($pdo, $dbName);
            } else {
                $pdo->exec("USE `{$dbName}`");
            }

            self::$connection = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            Logger::error('Erreur de connexion base de donnees', ['error' => $e->getMessage()]);
            if (AppConfig::debug()) {
                throw $e;
            }
            http_response_code(500);
            exit('Erreur interne de connexion a la base de donnees.');
        }
    }

    private static function initializeSchema(PDO $pdo, string $dbName): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(120) NOT NULL,
            categorie VARCHAR(80) NOT NULL,
            quantite INT UNSIGNED NOT NULL DEFAULT 0,
            statut ENUM('Disponible', 'En maintenance', 'Indisponible') NOT NULL DEFAULT 'Disponible',
            localisation VARCHAR(120) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(120) NOT NULL UNIQUE,
            description TEXT NULL,
            tl_user_id INT NULL,
            tla_user_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(120) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM(" . self::ROLE_ENUM . ") NOT NULL DEFAULT 'developpeur',
            team_id INT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            must_change_password TINYINT(1) NOT NULL DEFAULT 0,
            last_login_at DATETIME NULL,
            password_changed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS planning_tasks (
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT NULL,
            ip_address VARCHAR(45) NOT NULL,
            details_json JSON NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(190) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            attempted_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            action_url VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureMigrations($pdo, $dbName);
        if (AppConfig::seedDemoData()) {
            self::seedUsers($pdo);
            self::seedResources($pdo);
            self::seedTeams($pdo);
            self::seedProjects($pdo);
            self::seedTasks($pdo);
        }
    }

    private static function ensureMigrations(PDO $pdo, string $dbName): void
    {
        // Existing installs: widen role enum and add team support.
        $pdo->exec("ALTER TABLE users MODIFY role ENUM(" . self::ROLE_ENUM . ") NOT NULL DEFAULT 'developpeur'");

        if (!self::columnExists($pdo, $dbName, 'users', 'team_id')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN team_id INT NULL AFTER role');
        }
        if (!self::indexExists($pdo, $dbName, 'users', 'idx_users_team_id')) {
            $pdo->exec('ALTER TABLE users ADD INDEX idx_users_team_id (team_id)');
        }
        if (!self::columnExists($pdo, $dbName, 'users', 'is_active')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER team_id');
        }
        if (!self::columnExists($pdo, $dbName, 'users', 'must_change_password')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
        }
        if (!self::columnExists($pdo, $dbName, 'users', 'last_login_at')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER must_change_password');
        }
        if (!self::columnExists($pdo, $dbName, 'users', 'password_changed_at')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL AFTER last_login_at');
        }
        if (!self::indexExists($pdo, $dbName, 'users', 'idx_users_active')) {
            $pdo->exec('ALTER TABLE users ADD INDEX idx_users_active (is_active)');
        }

        // Teams constraints.
        if (!self::indexExists($pdo, $dbName, 'teams', 'idx_teams_tl_user_id')) {
            $pdo->exec('ALTER TABLE teams ADD INDEX idx_teams_tl_user_id (tl_user_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'teams', 'idx_teams_tla_user_id')) {
            $pdo->exec('ALTER TABLE teams ADD INDEX idx_teams_tla_user_id (tla_user_id)');
        }
        if (!self::constraintExists($pdo, $dbName, 'teams', 'fk_teams_tl_user')) {
            $pdo->exec('ALTER TABLE teams ADD CONSTRAINT fk_teams_tl_user FOREIGN KEY (tl_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
        if (!self::constraintExists($pdo, $dbName, 'teams', 'fk_teams_tla_user')) {
            $pdo->exec('ALTER TABLE teams ADD CONSTRAINT fk_teams_tla_user FOREIGN KEY (tla_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
        if (!self::constraintExists($pdo, $dbName, 'users', 'fk_users_team')) {
            $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL');
        }

        // Projects constraints.
        if (!self::indexExists($pdo, $dbName, 'projects', 'idx_projects_team')) {
            $pdo->exec('ALTER TABLE projects ADD INDEX idx_projects_team (team_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'projects', 'idx_projects_tl_user')) {
            $pdo->exec('ALTER TABLE projects ADD INDEX idx_projects_tl_user (tl_user_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'projects', 'idx_projects_tla_user')) {
            $pdo->exec('ALTER TABLE projects ADD INDEX idx_projects_tla_user (tla_user_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'projects', 'idx_projects_assigned_by')) {
            $pdo->exec('ALTER TABLE projects ADD INDEX idx_projects_assigned_by (assigned_by)');
        }
        if (!self::constraintExists($pdo, $dbName, 'projects', 'fk_projects_team')) {
            $pdo->exec('ALTER TABLE projects ADD CONSTRAINT fk_projects_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE');
        }
        if (!self::constraintExists($pdo, $dbName, 'projects', 'fk_projects_tl_user')) {
            $pdo->exec('ALTER TABLE projects ADD CONSTRAINT fk_projects_tl_user FOREIGN KEY (tl_user_id) REFERENCES users(id) ON DELETE CASCADE');
        }
        if (!self::constraintExists($pdo, $dbName, 'projects', 'fk_projects_tla_user')) {
            $pdo->exec('ALTER TABLE projects ADD CONSTRAINT fk_projects_tla_user FOREIGN KEY (tla_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
        if (!self::constraintExists($pdo, $dbName, 'projects', 'fk_projects_assigned_by')) {
            $pdo->exec('ALTER TABLE projects ADD CONSTRAINT fk_projects_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE');
        }

        // Planning tasks migration.
        if (!self::columnExists($pdo, $dbName, 'planning_tasks', 'resource_id')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD COLUMN resource_id INT NULL AFTER due_date');
        }
        if (!self::columnExists($pdo, $dbName, 'planning_tasks', 'project_id')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD COLUMN project_id INT NULL AFTER due_date');
        }
        if (!self::indexExists($pdo, $dbName, 'planning_tasks', 'idx_planning_project_id')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD INDEX idx_planning_project_id (project_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'planning_tasks', 'idx_planning_resource_id')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD INDEX idx_planning_resource_id (resource_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'planning_tasks', 'idx_planning_assigned_user')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD INDEX idx_planning_assigned_user (assigned_user_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'planning_tasks', 'idx_planning_created_by')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD INDEX idx_planning_created_by (created_by)');
        }
        if (!self::constraintExists($pdo, $dbName, 'planning_tasks', 'fk_planning_project')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD CONSTRAINT fk_planning_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL');
        }
        if (!self::constraintExists($pdo, $dbName, 'planning_tasks', 'fk_planning_resource')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD CONSTRAINT fk_planning_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL');
        }
        if (!self::constraintExists($pdo, $dbName, 'planning_tasks', 'fk_planning_assigned_user')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD CONSTRAINT fk_planning_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE CASCADE');
        }
        if (!self::constraintExists($pdo, $dbName, 'planning_tasks', 'fk_planning_created_by')) {
            $pdo->exec('ALTER TABLE planning_tasks ADD CONSTRAINT fk_planning_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL');
        }

        if (!self::indexExists($pdo, $dbName, 'audit_logs', 'idx_audit_user_id')) {
            $pdo->exec('ALTER TABLE audit_logs ADD INDEX idx_audit_user_id (user_id)');
        }
        if (!self::indexExists($pdo, $dbName, 'audit_logs', 'idx_audit_entity')) {
            $pdo->exec('ALTER TABLE audit_logs ADD INDEX idx_audit_entity (entity_type, entity_id)');
        }
        if (!self::constraintExists($pdo, $dbName, 'audit_logs', 'fk_audit_user')) {
            $pdo->exec('ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        }

        if (!self::indexExists($pdo, $dbName, 'login_attempts', 'idx_login_identifier')) {
            $pdo->exec('ALTER TABLE login_attempts ADD INDEX idx_login_identifier (identifier, ip_address, attempted_at)');
        }

        if (!self::indexExists($pdo, $dbName, 'notifications', 'idx_notifications_user_id')) {
            $pdo->exec('ALTER TABLE notifications ADD INDEX idx_notifications_user_id (user_id, is_read, created_at)');
        }
        if (!self::constraintExists($pdo, $dbName, 'notifications', 'fk_notifications_user')) {
            $pdo->exec('ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        }
    }

    private static function seedUsers(PDO $pdo): void
    {
        $seedUsers = [
            ['nom' => 'Administrateur', 'email' => 'admin@resourcehub.local', 'password' => 'Admin123!', 'role' => 'admin'],
            ['nom' => 'Gestionnaire Demo', 'email' => 'manager@resourcehub.local', 'password' => 'Manager123!', 'role' => 'gestionnaire'],
            ['nom' => 'Team Leader Demo', 'email' => 'tl@resourcehub.local', 'password' => 'Tl123456!', 'role' => 'team_leader'],
            ['nom' => 'Team Leader Adjoint Demo', 'email' => 'tla@resourcehub.local', 'password' => 'Tla123456!', 'role' => 'team_leader_adjoint'],
            ['nom' => 'Developpeur Demo', 'email' => 'dev@resourcehub.local', 'password' => 'Dev123!', 'role' => 'developpeur'],
        ];

        $checkUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $insertUser = $pdo->prepare('
            INSERT INTO users (nom, email, password_hash, role, is_active, must_change_password, password_changed_at)
            VALUES (:nom, :email, :password_hash, :role, 1, 0, NOW())
        ');

        foreach ($seedUsers as $seedUser) {
            $checkUser->execute([':email' => $seedUser['email']]);
            if ($checkUser->fetchColumn()) {
                continue;
            }
            $insertUser->execute([
                ':nom' => $seedUser['nom'],
                ':email' => $seedUser['email'],
                ':password_hash' => password_hash($seedUser['password'], PASSWORD_DEFAULT),
                ':role' => $seedUser['role'],
            ]);
        }
    }

    private static function seedResources(PDO $pdo): void
    {
        $resourceCount = (int)$pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn();
        if ($resourceCount > 0) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO resources (nom, categorie, quantite, statut, localisation) VALUES (:nom, :categorie, :quantite, :statut, :localisation)');
        $rows = [
            ['nom' => 'Equipe Sprint Frontend', 'categorie' => 'RH - Developpeur', 'quantite' => 4, 'statut' => 'Disponible', 'localisation' => 'Planning Sprint A'],
            ['nom' => 'Equipe QA', 'categorie' => 'RH - Support', 'quantite' => 2, 'statut' => 'Disponible', 'localisation' => 'Planning Sprint B'],
            ['nom' => 'Ordinateur Dell Latitude', 'categorie' => 'RM - Informatique', 'quantite' => 15, 'statut' => 'Disponible', 'localisation' => 'Salle IT - Etage 2'],
            ['nom' => 'Projecteur Epson X500', 'categorie' => 'RM - Informatique', 'quantite' => 3, 'statut' => 'En maintenance', 'localisation' => 'Magasin central'],
        ];

        foreach ($rows as $row) {
            $insert->execute([
                ':nom' => $row['nom'],
                ':categorie' => $row['categorie'],
                ':quantite' => $row['quantite'],
                ':statut' => $row['statut'],
                ':localisation' => $row['localisation'],
            ]);
        }
    }

    private static function seedTeams(PDO $pdo): void
    {
        $teamCount = (int)$pdo->query('SELECT COUNT(*) FROM teams')->fetchColumn();

        $tlId = self::findUserIdByRole($pdo, 'team_leader');
        $tlaId = self::findUserIdByRole($pdo, 'team_leader_adjoint');
        $devId = self::findUserIdByRole($pdo, 'developpeur');

        if ($teamCount === 0) {
            $insert = $pdo->prepare('INSERT INTO teams (nom, description, tl_user_id, tla_user_id) VALUES (:nom, :description, :tl_user_id, :tla_user_id)');
            $insert->execute([
                ':nom' => 'Team Produit A',
                ':description' => 'Equipe principale de developpement',
                ':tl_user_id' => $tlId > 0 ? $tlId : null,
                ':tla_user_id' => $tlaId > 0 ? $tlaId : null,
            ]);
        }

        $teamId = (int)$pdo->query('SELECT id FROM teams ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($teamId <= 0) {
            return;
        }

        $setTeam = $pdo->prepare('UPDATE users SET team_id = :team_id WHERE id = :id AND (team_id IS NULL OR team_id = 0)');
        foreach ([$tlId, $tlaId, $devId] as $userId) {
            if ($userId > 0) {
                $setTeam->execute([
                    ':team_id' => $teamId,
                    ':id' => $userId,
                ]);
            }
        }

        // Ensure team leaders are linked on team row.
        $teamUpdate = $pdo->prepare('UPDATE teams SET tl_user_id = COALESCE(tl_user_id, :tl_user_id), tla_user_id = COALESCE(tla_user_id, :tla_user_id) WHERE id = :id');
        $teamUpdate->execute([
            ':tl_user_id' => $tlId > 0 ? $tlId : null,
            ':tla_user_id' => $tlaId > 0 ? $tlaId : null,
            ':id' => $teamId,
        ]);
    }

    private static function seedProjects(PDO $pdo): void
    {
        $projectCount = (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        if ($projectCount > 0) {
            return;
        }

        $teamId = (int)$pdo->query('SELECT id FROM teams ORDER BY id ASC LIMIT 1')->fetchColumn();
        $tlId = self::findUserIdByRole($pdo, 'team_leader');
        $tlaId = self::findUserIdByRole($pdo, 'team_leader_adjoint');
        $managerId = self::findUserIdByRole($pdo, 'gestionnaire');

        if ($teamId <= 0 || $tlId <= 0 || $managerId <= 0) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO projects (nom, description, statut, start_date, end_date, team_id, tl_user_id, tla_user_id, assigned_by) VALUES (:nom, :description, :statut, :start_date, :end_date, :team_id, :tl_user_id, :tla_user_id, :assigned_by)');
        $insert->execute([
            ':nom' => 'Plateforme ResourceHub V2',
            ':description' => 'Projet transverse: ressources, planning et suivi equipe.',
            ':statut' => 'En cours',
            ':start_date' => date('Y-m-d'),
            ':end_date' => date('Y-m-d', strtotime('+45 days')),
            ':team_id' => $teamId,
            ':tl_user_id' => $tlId,
            ':tla_user_id' => $tlaId > 0 ? $tlaId : null,
            ':assigned_by' => $managerId,
        ]);
    }

    private static function seedTasks(PDO $pdo): void
    {
        $taskCount = (int)$pdo->query('SELECT COUNT(*) FROM planning_tasks')->fetchColumn();
        if ($taskCount > 0) {
            return;
        }

        $devId = self::findUserIdByRole($pdo, 'developpeur');
        $tlId = self::findUserIdByRole($pdo, 'team_leader');
        $projectId = (int)$pdo->query('SELECT id FROM projects ORDER BY id ASC LIMIT 1')->fetchColumn();
        $rhResourceId = (int)$pdo->query("SELECT id FROM resources WHERE categorie LIKE 'RH - %' ORDER BY id ASC LIMIT 1")->fetchColumn();
        $rmResourceId = (int)$pdo->query("SELECT id FROM resources WHERE categorie LIKE 'RM - %' ORDER BY id ASC LIMIT 1")->fetchColumn();

        if ($devId <= 0) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO planning_tasks (titre, description, statut, priorite, due_date, project_id, resource_id, assigned_user_id, created_by) VALUES (:titre, :description, :statut, :priorite, :due_date, :project_id, :resource_id, :assigned_user_id, :created_by)');
        $insert->execute([
            ':titre' => 'Implementer la gestion des equipes',
            ':description' => 'Creer les composants MVC pour teams et affectation des developpeurs.',
            ':statut' => 'En cours',
            ':priorite' => 'Haute',
            ':due_date' => date('Y-m-d', strtotime('+3 days')),
            ':project_id' => $projectId > 0 ? $projectId : null,
            ':resource_id' => $rhResourceId > 0 ? $rhResourceId : null,
            ':assigned_user_id' => $devId,
            ':created_by' => $tlId > 0 ? $tlId : null,
        ]);
        $insert->execute([
            ':titre' => 'Verifier les equipements de l equipe',
            ':description' => 'Controler la disponibilite du materiel avant demo sprint.',
            ':statut' => 'A faire',
            ':priorite' => 'Moyenne',
            ':due_date' => date('Y-m-d', strtotime('+5 days')),
            ':project_id' => $projectId > 0 ? $projectId : null,
            ':resource_id' => $rmResourceId > 0 ? $rmResourceId : null,
            ':assigned_user_id' => $devId,
            ':created_by' => $tlId > 0 ? $tlId : null,
        ]);
    }

    private static function findUserIdByRole(PDO $pdo, string $role): int
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE role = :role ORDER BY id ASC LIMIT 1');
        $stmt->execute([':role' => $role]);
        return (int)$stmt->fetchColumn();
    }

    private static function columnExists(PDO $pdo, string $schema, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
        $stmt->execute([
            ':schema' => $schema,
            ':table_name' => $table,
            ':column_name' => $column,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function indexExists(PDO $pdo, string $schema, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name');
        $stmt->execute([
            ':schema' => $schema,
            ':table_name' => $table,
            ':index_name' => $index,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function constraintExists(PDO $pdo, string $schema, string $table, string $constraint): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table_name AND CONSTRAINT_NAME = :constraint_name');
        $stmt->execute([
            ':schema' => $schema,
            ':table_name' => $table,
            ':constraint_name' => $constraint,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
