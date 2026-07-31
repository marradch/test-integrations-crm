# Contact Form Integration

Base project implementation using:

- Laravel 10
- Vue 3
- Contact form component with Composition API
- Validation powered by yup

## Setup

Start the Docker services:

```bash
docker compose up -d
```

Install PHP dependencies inside the `laravel_app` container:

```bash
docker compose exec laravel_app composer install
```

Then install frontend packages and build assets:

```bash
docker compose exec laravel_app npm install
docker compose exec laravel_app npm run build
```

### Queue and Postgres

This project uses a database-backed queue. The repository includes a `postgres` service in `docker-compose.yml`.

After `docker compose up -d`, run inside the `laravel_app` container:

```bash
# migrate database and queue tables
docker compose exec laravel_app php artisan migrate
docker compose exec laravel_app php artisan queue:table
docker compose exec laravel_app php artisan migrate

# run a queue worker
docker compose exec -d laravel_app php artisan queue:work
```
### Environment variables are in `.env.example`
- SalesDrive API: `SALESDRIVE_URL`, `SALESDRIVE_API_KEY`.
- Dilovod API: `DILOVOD_URL`, `DILOVOD_API_KEY`.
- Telegram API:  `TELEGRAM_BOT_TOKEN`, `TELEGRAM_MANAGER_CHAT_ID`.

