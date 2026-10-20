# Tymetry — Todo Management System

A Laravel-based task manager for creating and organizing todos, tracking priorities and due dates, and archiving/restoring tasks.

## Project status

The core MVP is implemented. The code has received a source-level skim, but the application and its workflows have not been fully verified by an executed test suite. See the known findings and verification checklist in [TESTING.md](TESTING.md).

## Features in the current code

- Login/logout, password-reset and OTP routes.
- Profile details and password updates.
- Todo creation, editing, status changes, and archiving/restoration.
- Bulk mark-done, archive, and restore endpoints.
- Title/status/priority filters and dashboard summaries.
- Due-date labels and task ordering.
- Activity-log records for selected todo actions.

Some listed workflows have known gaps; consult [TESTING.md](TESTING.md) before treating them as verified.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL (configure the database in `.env`)
- Blade, JavaScript, Vite, and Tailwind CSS tooling
- Yajra Laravel DataTables

## Local setup

Requirements: PHP compatible with `composer.json`, Composer, Node.js/npm, and a configured database.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then run:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

See the Composer `setup` script for the bundled setup sequence.

## Project documentation

- [Architecture overview](ARCHITECTURE.md) — request flow, code responsibilities, and data relationships.
- [Testing guide](TESTING.md) — smoke checks, regression priorities, and source-review findings.

## Tests

Run the automated suite with:

```bash
php artisan test
```

Do not treat a feature as verified just because its route or controller exists. Check the assertions and record actual test results.
