# ResourceHub - MVC + REST API

Application PHP MVC pour la gestion des ressources, equipes, projets et taches, avec durcissement de base pour un usage professionnel: configuration par environnement, protections CSRF, durcissement session, audit trail et journalisation.

## Architecture
- `app/Core`: noyau MVC (`Router`, `Controller`, `View`, `Auth`, `Database`)
- `app/Models`: acces donnees (`ResourceModel`, `UserModel`, `TaskModel`)
- `app/Controllers/Web`: controleurs pages web
- `app/Controllers/Api`: controleurs API REST JSON
- `app/Views`: vues HTML

`index.php` est le front-controller web.
`api.php` est le front-controller API.
Les anciens points d'entree (`create.php`, `edit.php`, `planning.php`, etc.) sont gardes comme wrappers de compatibilite.

## Configuration
1. Copier `.env.example` vers `.env`
2. Renseigner les variables de base de donnees et SMTP
3. En production:
- mettre `APP_ENV=production`
- mettre `APP_DEBUG=false`
- mettre `APP_AUTO_MIGRATE=false`
- mettre `APP_SEED_DEMO_DATA=false`
- utiliser un vrai compte SQL applicatif, pas `root`

Secrets a ne jamais versionner:
- `DB_PASSWORD`
- `EMAIL_HOST_PASSWORD`
- toute cle API complementaire

Logs:
- fichier applicatif: `storage/logs/app.log`
- audit metier: table `audit_logs`
- notifications internes: table `notifications`

Email:
- config via `EMAIL_HOST`, `EMAIL_PORT`, `EMAIL_HOST_USER`, `EMAIL_HOST_PASSWORD`, `EMAIL_USE_TLS`, `EMAIL_IMPLICIT_TLS`, `DEFAULT_FROM_EMAIL`
- compatibilite SMTP authentifiee
- pour Gmail: utiliser un mot de passe d application uniquement dans `.env`

## Modules web
- Authentification
- Gestion ressources RH/RM
- Planning des taches
- Gestion utilisateurs (creation par admin/gestionnaire)
- Gestion du compte et changement de mot de passe
- Centre de notifications
- Notifications email SMTP
- Export CSV/PDF

## API REST
Base: `http://localhost/php-crud/api.php`

Auth:
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`

Resources:
- `GET /resources`
- `GET /resources/{id}`
- `POST /resources`
- `PUT|PATCH /resources/{id}`
- `DELETE /resources/{id}`

Tasks:
- `GET /tasks`
- `GET /tasks/{id}`
- `POST /tasks`
- `PUT|PATCH /tasks/{id}`
- `DELETE /tasks/{id}`

Users:
- `GET /users`
- `POST /users`

## Roles
- `admin`
- `gestionnaire` (resource manager)
- `team_leader`
- `team_leader_adjoint`
- `developpeur`

Regle:
- Seuls `admin` et `gestionnaire` creent les utilisateurs.
- `gestionnaire` cree `team_leader`, `team_leader_adjoint` et `developpeur`.
- `admin` cree `gestionnaire`, `team_leader`, `team_leader_adjoint` et `developpeur`.
- Les comptes peuvent etre actives/desactives.
- Les resets de mot de passe forcent le changement au prochain login.

## Securite ajoutee
- configuration centralisee via `.env`
- headers HTTP de securite
- session durcie (`HttpOnly`, `SameSite`, timeout d'inactivite, regeneration d'identifiant)
- protection CSRF sur les formulaires web
- limitation basique des tentatives de connexion
- journalisation applicative
- audit trail des actions sensibles
- notifications internes par utilisateur
- envoi email sur les evenements metier principaux

## Relations SQL
- `users.team_id` -> `teams.id`
- `teams.tl_user_id` -> `users.id`
- `teams.tla_user_id` -> `users.id`
- `projects.team_id` -> `teams.id`
- `projects.tl_user_id` -> `users.id`
- `projects.tla_user_id` -> `users.id`
- `projects.assigned_by` -> `users.id`
- `planning_tasks.assigned_user_id` -> `users.id`
- `planning_tasks.created_by` -> `users.id`
- `planning_tasks.project_id` -> `projects.id`
- `planning_tasks.resource_id` -> `resources.id`
- `audit_logs.user_id` -> `users.id`
- `notifications.user_id` -> `users.id`

## Comptes demo
Disponibles uniquement si `APP_SEED_DEMO_DATA=true`.
- `admin@resourcehub.local` / `Admin123!`
- `manager@resourcehub.local` / `Manager123!`
- `tl@resourcehub.local` / `Tl123456!`
- `tla@resourcehub.local` / `Tla123456!`
- `dev@resourcehub.local` / `Dev123!`

## URLs utiles
- Web login: `http://localhost/php-crud/login.php`
- Mon compte: `http://localhost/php-crud/account.php`
- Notifications: `http://localhost/php-crud/notifications.php`
- Dashboard: `http://localhost/php-crud/`
- API me: `http://localhost/php-crud/api.php/auth/me`
