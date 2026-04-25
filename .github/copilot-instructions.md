# Project Guidelines

## Code Style
- Follow existing project formatting and conventions:
  - PHP style from `.editorconfig` and existing files under `app/`.
  - Vue/JS style from existing files under `app/resources/` and `vite.config.js`.
- Prefer small, targeted changes that preserve current architecture.
- Do not modify generated or third-party directories unless explicitly requested (`vendor/`, `node_modules/`, `coverage/`, `public/build/`).

## Architecture
- Backend is Mako PHP with controllers in `app/http/controllers/`, routes in `app/http/routing/routes.php`, models in `app/models/`, and business logic modules in `app/modules/`.
- Frontend is Vue 3 + Inertia with pages/components/layouts under `app/resources/views/` and entrypoint at `app/resources/js/app.js`.
- Prefer placing domain logic in modules/services instead of controllers.
- Keep shared controller behavior aligned with `app/http/controllers/ControllerBase.php`.
## Build and Test
- Frontend:
  - `docker compose exec frontend npm run dev`
  - `docker compose exec frontend npm run build`
  - `docker compose exec frontend npm test`
  - `docker compose exec frontend npm run test:coverage`
- Backend:
  - `docker compose exec backend composer test`
  - `docker compose exec backend composer test:unit`
  - `docker compose exec backend composer test:coverage`
- Docker-first local development:
  - `docker compose up`

## Database
- Migrations are in `app/database/migrations/`.
- To create a new migration, run `docker compose exec backend php app/reactor migration:create`. If creating more than one migration at a time, wait 1 second between commands to ensure unique timestamps.
- Do not run migrations unless specifically requested. The user should review migration files before applying them.
- To run migrations: `docker compose exec backend php app/reactor migration:up`

## Conventions
- Use hierarchical environment config overrides by loading base config and changing only needed keys (see `app/config/` and `app/config/<env>/`).
- Assume `MAKO_ENV` controls environment-specific config selection.
- In controller flows that redirect after state-changing requests, prefer 303-style redirects via existing helpers.
- Keep tests aligned with current split:
  - PHPUnit unit tests in `tests/Unit/`
  - Vitest tests in `app/resources/js/tests/`

## Environment Pitfalls
- Local Docker setup is optimized for Linux hosts.
- HTTPS and host/domain behavior are tied to Docker + Caddy configuration.
- Ensure project bootstrap/setup has been completed when networking or env-dependent behavior fails.

## Documentation Links
- High-level project info: `README.md`
- Docker details and local networking: `https://github.com/inventor96/mako-vue/wiki/Docker`
- Hierarchical config pattern: `https://github.com/inventor96/mako-vue/wiki/Hierarchical-Environment-Configuration`
- Test workflows and coverage: `https://github.com/inventor96/mako-vue/wiki/Unit-Testing`
- General setup and usage docs: `https://github.com/inventor96/mako-vue/wiki`