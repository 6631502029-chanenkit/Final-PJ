# Hotel Booking Application

Development setup for the PHP/Apache and MySQL application.

## Requirements

- Docker Desktop with Docker Compose
- Git

## First-time setup

Clone the repository and enter the project directory:

```powershell
git clone <repository-url>
cd project-tester-v1
Copy-Item .env.example .env
```

Edit `.env` and replace the database password placeholders with local development values. Keep `.env` private; it is ignored by Git.

## Run the application

```powershell
docker compose up -d
docker compose ps
```

Open <http://localhost:8080> in a browser. The PHP source directory is bind-mounted from `php/src`, so normal code changes are reflected without rebuilding.

To rebuild after changing the Dockerfile:

```powershell
docker compose up -d --build
```

View recent logs:

```powershell
docker compose logs --tail=100
docker compose logs --tail=100 php-apache-environment
docker compose logs --tail=100 db
```

Stop the development containers without deleting database data:

```powershell
docker compose down
```

## Database

PHP connects to the Compose service named `db` using PDO and the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` variables. MySQL is not published to the host or the Internet. Its data is stored in the named Docker volume `project-tester-v1_mysql_data`, which survives `docker compose down` and container recreation.

Do not run `docker compose down -v` unless you intentionally want to reset the local database and understand that all data in the named volume will be deleted.

The optional phpMyAdmin service is available only with the `admin` profile and is bound to localhost:

```powershell
docker compose --profile admin up -d
```

It is not needed for normal development and must not be exposed publicly.

## Team workflow

Create a branch for each task, make small focused commits, and open a pull request for review. Pull the latest default branch before starting work and resolve conflicts locally. Commit source code, migrations, Docker files, Compose configuration, `.env.example`, and this README. Never commit `.env`, passwords, webhook secrets, generated logs, or local editor files.

## Optional Cloudflare Quick Tunnel

Start the normal application first. Then run a temporary tunnel in a separate terminal:

```powershell
docker run --rm --name hotel-booking-cloudflared cloudflare/cloudflared:latest tunnel --no-autoupdate --url http://host.docker.internal:8080
```

Cloudflare will print a temporary HTTPS URL. Share it only with the intended teammate. This command forwards only the local web application on port 8080; it does not publish MySQL, the Docker daemon, SSH, or phpMyAdmin. Stop it with `Ctrl+C`. The URL is temporary and changes when the tunnel is restarted.

## Important warnings

- Never commit `.env` or share its database and webhook secrets.
- Never run `docker compose down -v` unless intentionally resetting the database.
- Do not publish port 3306 or expose phpMyAdmin through a tunnel.
