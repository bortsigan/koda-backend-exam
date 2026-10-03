# Client Project Tracker API

A Laravel REST API for managing client projects. 
Uses Laravel Sanctum for token auth and MySQL for storage. 
The app runs in Docker and can be tested with Postman or PHPUnit.

## Requirements

- Docker Desktop, or Docker Engine with Docker Compose v2
- Postman, if you want to use the included collection

## Setup

1. Create the `.env` file. `.env.example` already has the values Docker needs.

```sh
cp .env.example .env
```

2. Build and start the containers.

```sh
docker compose up -d --build
```

3. Wait for the app to be ready. The first start can take a minute while MySQL initializes.

```sh
docker compose logs -f app
```

Once the server is running, the API will be available at `http://localhost:8000/api`.

On startup the app container generates `APP_KEY` if it is empty, runs the migrations, and seeds the database with test datas.


Test account:

- Email: `koda@example.com`
- Password: `password`

Other commands:

```sh
# Stop the containers
docker compose down

# Stop the containers and delete the database
docker compose down -v

# Reset and reseed the database
docker compose exec app php artisan migrate:fresh --seed
```

If port `8000` or `3306` is already in use, stop whatever is using it or change the port mapping in `docker-compose.yml`.

## Environment Variables

- `APP_KEY` is empty by default and is generated on first start.
- `APP_URL=http://localhost:8000`

- `DB_CONNECTION=mysql`
- `DB_HOST=mysql`
- `DB_PORT=3306`
- `DB_DATABASE=koda_exam`
- `DB_USERNAME=koda`
- `DB_PASSWORD=root`

## Database

MySQL runs in Docker as the `mysql` service.

Open a MySQL shell inside the container:

```sh
docker compose exec mysql mysql -u koda -proot 
OR 
docker compose exec -it mysql mysql -u root -p
```

## API Endpoints

Base URL: `http://localhost:8000/api`

Send `Accept: application/json` with every request. All endpoints except register and login need the token in this header:

```text
Authorization: Bearer <token>

OR 

1. Open the Authorization tab in Postman.
2. Select Bearer Token from the Auth Type dropdown.
3. Paste the token from the Login response into the Token textfield.
```

Endpoints:

- `POST /api/register` creates an account and returns a token
- `POST /api/login` returns a token
- `POST /api/logout` revokes the current token
- `GET /api/projects` lists, searches, and filters projects
- `GET /api/projects/{id}` gets one project
- `POST /api/projects` creates a project
- `PUT /api/projects/{id}` updates a project
- `DELETE /api/projects/{id}` deletes a project


## Postman

1. Import `postman_collection.json` into Postman.
2. Send **Auth > Login** or **Auth > Register**. The collection saves the token and uses it for the other requests. To use the test account, set the login body to `koda@example.com` and `password`.
3. Use **Projects > List projects** to search and filter. Change the query values as needed.
4. To get, update, or delete a project, set the collection variable `projectId`. After creating a project, copy `data.id` from the response.
5. **Auth > Logout** revokes the token. Log in again before sending more requests.

## Testing
Run all tests:
```sh
docker compose exec app php artisan test
```

Run one test:
```sh
docker compose exec app php artisan test --filter=test_create_a_project
```

## Assumptions

- **Shared projects.** Projects are not tied to a user. Any logged in user can see and change every project.
- **Fixed status and priority values.** Status is one of `Planning`, `In Progress`, `On Hold`, `Completed`. Priority is one of `Low`, `Medium`, `High`.
- **Dates.** `dueDate` cannot be earlier than `startDate`.
- **Full updates.** `PUT /api/projects/{id}` replaces the whole project, so every required field must be sent, not just the changed ones.
- **camelCase in the API, snake_case in the database.** Requests use `clientName`, `startDate`, and so on. They are mapped to `client_name`, `start_date` before saving.
- **Search and filters.** `q` searches client name, project name, and description. `status` and `priority` must match exactly. Results are newest first, 10 per page by default (`per_page` max is 100).
- **Database transactions.** Register, create, update, and delete run inside a DB transaction. If something fails halfway, nothing is saved, so the database is never left with partial data.
- **Error handling.**
  - A missing project throws `ProjectNotFoundException` and returns `404`.
  - Invalid input returns `422` with the field errors.
  - Wrong login credentials return `422`.
  - Other failures like DB errors are caught in the service and thrown back with a simple message like `Failed to create project`, keeping the original error attached for logs.
- **Tokens.** Each login or register creates a new Sanctum token. Logout only revokes the token used for that request.
