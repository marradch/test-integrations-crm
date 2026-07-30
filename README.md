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

## Notes

- The form is implemented using Vue 3 Composition API.
- Client-side validation uses `yup`.
- The backend route is configured as an API endpoint.
- The frontend sends `submittedAt` and hidden spam-check fields with each submission.
