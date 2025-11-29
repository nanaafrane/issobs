<!-- .github/copilot-instructions.md - guidance for AI coding agents working on this repo -->
# Copilot instructions for the ISSOBS Laravel app

This document focuses on the concrete, discoverable patterns and workflows an AI coding agent should know to be immediately productive in this repository.

1. Project overview
- Framework: Laravel (app skeleton in this repo). See `composer.json` (Laravel ^12, PHP ^8.2).
- Structure: standard MVC — `app/Models`, `app/Http/Controllers`, `resources/views`, `routes/web.php`.
- Frontend asset pipeline: Vite + `laravel-vite-plugin`. See `vite.config.js` and `package.json`.

2. Important files / entry points
- Routes: `routes/web.php` — most application routes use resource controllers (e.g. `Route::resource('invoice', InvoiceController::class)`).
- Dashboard & user management: `app/Http/Controllers/HomeController.php` contains many cross-cutting behaviors (dashboards, staff CRUD, image uploads).
- Models: `app/Models/*` — note mixed naming conventions (some files/classes use lowercase names, e.g. `app/Models/employee.php`). Follow the existing file/class names — do not rename without updating usages.
- Migrations: `database/migrations/` define table schemas used across controllers and views.

3. Developer workflows (commands)
- Install PHP deps: `composer install` (composer scripts will copy `.env` from `.env.example` if missing).
- Prepare environment: copy `.env.example` -> `.env`, then `php artisan key:generate` and set DB credentials (XAMPP MySQL or sqlite). The repo's `composer post-create-project-cmd` runs `php artisan migrate` — be cautious on production databases.
- Run dev stack: either run individual commands or the aggregated script defined in `composer.json`:
  - Individual: `php artisan serve` (dev server), `php artisan queue:listen --tries=1` (worker), `npm run dev` (vite dev server).
  - Shortcut: `composer run dev` uses `concurrently` to run server, queue, logs and vite together.
- Build assets: `npm run build` (calls `vite build`).
- Tests: `composer test` or `php artisan test`. `phpunit.xml` config uses sqlite in-memory for CI/local tests (no external DB required).

4. Project-specific conventions and gotchas
- Mixed model naming: Some models are lowercase (e.g. `employee`), others are PascalCase (e.g. `Invoice`). When creating or editing code, match the existing class/file name exactly to avoid autoloading issues.
- Centralized staff logic: Staff/user creation, editing, and password resets are handled in `HomeController` (not a dedicated `UserController`). When adding or altering staff behavior, check `HomeController` first.
- Views: Blade templates live under `resources/views` and mirror controller responsibilities (e.g. `views/users`, `views/sales`). Use existing view names when returning from controllers.
- Storage: file uploads use `Storage::disk('public')` (see `HomeController::staffProfileImage`). Ensure `php artisan storage:link` is run on new setups.
- Aggregations: dashboard uses `whereRelation()` calls on `Invoice`/`Receipt` in `HomeController` — follow this pattern for similar summary queries.

5. Integration points & dependencies
- Data table UI: `yajra/laravel-datatables-html` and `laravel-datatables-vite` are present in `composer.json`/`package.json` — use them for server-side datatables.
- Auth: Laravel default auth scaffolding is in use (`Auth::routes()` in `routes/web.php`).
- Queues: repo expects a queue worker in dev (`php artisan queue:listen`) — many payment/receipt flows may dispatch jobs.

6. How to implement small changes (examples)
- Add a new resource: add `Route::resource('foos', FooController::class)` to `routes/web.php`, create `app/Http/Controllers/FooController.php` (resource methods), create `app/Models/Foo.php` (match model filename/case), add migration `create_foos_table`, run `php artisan migrate`.
- Upload handling: follow `HomeController::staffProfileImage` — validate image, delete old via `Storage::disk('public')->delete()`, store with `$request->file('image')->store('images', 'public')` and save path on model.
- Tests: Write feature tests that use the default sqlite memory DB (no external DB). See `phpunit.xml` for the environment used in tests.

7. Safety & best-effort rules for the agent
- Preserve naming and casing used in the repository; do not refactor class or file names unless the change includes all usage updates and tests pass.
- When adding migrations or running `php artisan migrate`, prefer using a local sqlite database or a clearly isolated test database — do not migrate a production database.
- Avoid touching `HomeController` for unrelated single-purpose changes unless necessary — it centralizes many cross-cutting responsibilities.

8. Where to look next (quick map)
- Routing & overview: `routes/web.php`
- Dashboards & staff flows: `app/Http/Controllers/HomeController.php`
- Resource controllers: `app/Http/Controllers/*Controller.php`
- Models: `app/Models/*`
- Views: `resources/views/*`
- Migrations: `database/migrations/*`
- Scripts & commands: `composer.json`, `package.json`, `phpunit.xml`

If any part of the app's environment (DB choice, expected queue driver, storage location) is missing or ambiguous, tell me which area you want clarified and I'll add short, targeted snippets to the instructions.
