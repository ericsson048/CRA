# ResourceHub - MVC + REST API

## Architecture
- `app/Core`: noyau MVC (`Router`, `Controller`, `View`, `Auth`, `Database`)
- `app/Models`: acces donnees (`ResourceModel`, `UserModel`, `TaskModel`)
- `app/Controllers/Web`: controleurs pages web
- `app/Controllers/Api`: controleurs API REST JSON
- `app/Views`: vues HTML

`index.php` est le front-controller web.
`api.php` est le front-controller API.

Les anciens points d'entree (`create.php`, `edit.php`, `planning.php`, etc.) sont gardes comme wrappers de compatibilite.

## Modules web
- Authentification
- Gestion ressources RH/RM
- Planning des taches
- Gestion utilisateurs (creation par admin/gestionnaire)
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
- `developpeur`

Regle:
- Seuls `admin` et `gestionnaire` creent les utilisateurs.
- `gestionnaire` cree des `developpeur`.
- `admin` cree `developpeur` et `gestionnaire`.

## Relations SQL
- `planning_tasks.assigned_user_id` -> `users.id`
- `planning_tasks.created_by` -> `users.id`
- `planning_tasks.resource_id` -> `resources.id`

## Comptes demo
- `admin@resourcehub.local` / `Admin123!`
- `manager@resourcehub.local` / `Manager123!`
- `dev@resourcehub.local` / `Dev123!`

## URLs utiles
- Web login: `http://localhost/php-crud/login.php`
- Dashboard: `http://localhost/php-crud/`
- API me: `http://localhost/php-crud/api.php/auth/me`
