# Tymetry — Personal Task Management Workspace

Tymetry is a Laravel application for managing personal tasks, priorities, due dates, and daily work. Its product direction is to reduce mental overhead through clear task organization and focused workflows.

## Current status

The current project checkpoint is **Sprint 07 — Foundation Alignment**. Backend/API readiness, web UX, and technical-debt audits have been completed at source level. The first implementation slice remains subject to contract decisions and browser verification.

The previous **Sprint 06 — Core Stabilization** work is complete and merged into `main`. Do not treat source presence or a passing CI run as proof of every workflow; see the testing guide for the verification boundary.

## Current capabilities

- Authentication, email verification, optional two-factor authentication, and password reset.
- Profile, password, and two-factor preference management.
- Task creation and editing, with status, priority, due date, and due time.
- Task filtering, smart ordering, dashboard summaries, and activity history.
- Bulk completion, archive, and restore operations.
- Accessible navigation, form labels, validation feedback, and task-table controls.

Email-dependent features require a working mail configuration. Cache-backed rate limiting also depends on the deployed cache configuration.

## Technology

- PHP 8.3+
- Laravel 13
- MySQL for the typical application environment; PHPUnit currently uses in-memory SQLite.
- Node.js 24 LTS and npm for frontend dependency installation and build.
- Blade, JavaScript, Vite, Tailwind CSS, and Yajra Laravel DataTables.

## Local setup

Requirements: PHP compatible with `composer.json`, Composer, Node.js 24, npm, and a configured database.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure database, mail, and cache settings in `.env`, then run:

```bash
php artisan migrate
npm ci
npm run build
php artisan serve
```

## Verification

Run the automated checks:

```bash
php artisan test
npm run build
```

For the complete verification scope, manual smoke checklist, and known verification boundaries, see [docs/TESTING.md](docs/TESTING.md).

## Documentation

- [Project overview and setup](docs/README.md)
- [Architecture overview](docs/ARCHITECTURE.md)
- [Testing guide and regression checklist](docs/TESTING.md)
- [Code review findings](docs/REVIEW-FINDINGS.md)

---

Tymetry is an actively developed portfolio project. Its documentation distinguishes implemented source behavior, automated verification, and manual acceptance rather than presenting them as interchangeable.
