# Tymetry — Personal Task Management Workspace

Tymetry is a Laravel application for managing personal tasks, priorities, due dates, and daily work. Its product direction is to reduce mental overhead through clear task organization and focused workflows.

## Current status

**Current implementation baseline (2026-11-27):** Sprint 06 core stabilization is complete. Sprint 07 foundation audits are recorded. Board M1/M2, TYMX v1 Export (PR #53), and read-only Import Preview (PR #54) are integrated into main. TOTP account rate-limit atomicity (PR #55) is integrated. Sprint 08 cross-client contracts remain in planning/verification; account-timezone runtime behavior and Android synchronization are not implied to be implemented.

The previous **Sprint 06 — Core Stabilization** work is complete and merged into `main`. Do not treat source presence or a passing CI run as proof of every workflow; see the testing guide for the verification boundary.

## Current capabilities

- Authentication, email verification, optional two-factor authentication, and password reset.
- Profile, password, and two-factor preference management.
- Task creation and editing, with status, priority, due date, and due time.
- Task filtering, smart ordering, dashboard summaries, and activity history.
- Board view with persisted status changes and accessible status controls.
- TYMX v1 Todo export and validated read-only import preview.
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

## Current engineering follow-up

- Open QA/workflow issues: [#36](https://github.com/imandodo01/Tymetry-web/issues/36), [#39](https://github.com/imandodo01/Tymetry-web/issues/39), [#40](https://github.com/imandodo01/Tymetry-web/issues/40), [#41](https://github.com/imandodo01/Tymetry-web/issues/41).
- TYMX M7 Import Apply is not implemented; M6 remains read-only preview.
- Production readiness is not established by merged PRs or CI alone. See the [issue lifecycle and closure policy](docs/engineering/issue-lifecycle-and-closure.md) and [testing guide](docs/TESTING.md).

## Documentation

- [Project overview and setup](docs/README.md)
- [Architecture overview](docs/ARCHITECTURE.md)
- [Testing guide and regression checklist](docs/TESTING.md)
- [Code review findings](docs/REVIEW-FINDINGS.md)
- [Issue lifecycle and closure policy](docs/engineering/issue-lifecycle-and-closure.md)
- [Engineering session checkpoint](docs/engineering/session-checkpoint.md)

---

Tymetry is an actively developed portfolio project. Its documentation distinguishes implemented source behavior, automated verification, and manual acceptance rather than presenting them as interchangeable.
