# Tymetry — Todo Management System

Tymetry is a Laravel-based task manager for creating and organizing todos, tracking status, priority, and due dates, and archiving/restoring tasks.

## Current project checkpoint

**Updated 2026-11-27:** Board M1/M2, TYMX v1 Export, TYMX read-only Import Preview, and TOTP account rate-limit atomicity are merged into main (PRs #51–#55, with #50 planning). Sprint 08 cross-client contract work is not yet an approved implementation baseline. Operational QA items remain tracked in GitHub Issues #36, #39, #40, and #41. See [engineering checkpoint](engineering/session-checkpoint.md) and [issue lifecycle/closure policy](engineering/issue-lifecycle-and-closure.md) for the current handoff and closure rules.


**Sprint 06 — Core Stabilization: Complete.** Data-integrity fixes, authentication hardening, and the scoped UX consistency changes have been merged into `main`. M9 documentation closure was merged in PR #28.

Baseline before the Sprint 07 maintenance PR: `edc29cbfc4ab2ad0a752969b4d08598ea92fd72b` (2026-11-17).

- M6 Data Integrity: implemented and covered by focused regression tests.
- M7 Security Hardening: password-reset endpoints and login are rate-limited; focused password-reset tests are present.
- M8-A Feedback Consistency: merged in PR #26.
- M8-B Form and Accessibility Consistency: merged in PR #27.
- M8-C Responsive Verification: responsive rules are present in source; viewport-specific browser confirmation remains a manual check.
- M9 Documentation Closure: merged in PR #28. Documentation is maintained against the merged implementation and its verification evidence.

**Sprint 07 — Foundation Alignment:** backend/API readiness, web UX, and technical-debt audits have been completed at source level and recorded in the Tymetry Sprint 07 Notion page. The first implementation slice remains subject to contract decisions and browser verification; no API endpoint or mobile client is implied to exist.

Do not treat source presence or a passing CI run as proof of every possible workflow. See [TESTING.md](TESTING.md) for the verification boundary and manual smoke checklist.

## Features in the current code

- Login/logout, email verification, optional two-factor authentication, and password reset.
- Profile details, password, and two-factor preference updates.
- Todo creation, editing, status and priority changes, due-date and due-time fields.
- Todo filtering, smart ordering, dashboard summaries, and activity history.
- Bulk mark-done, archive, and restore operations.
- Accessible application navigation, form labels, validation feedback, and task-table controls.

OTP delivery depends on a working mail configuration and cache backend in the deployed environment.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL for the typical application environment; PHPUnit currently uses in-memory SQLite.
- Node.js 24 LTS for CI frontend dependency installation and build; npm version is supplied by the selected Node.js distribution.
- Blade, JavaScript, Vite, and Tailwind CSS tooling
- Yajra Laravel DataTables

## Local setup

Requirements: PHP compatible with `composer.json`, Composer, Node.js 24, npm, and a configured database.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database and mail/cache settings in `.env`, then run:

```bash
php artisan migrate
npm ci
npm run build
php artisan serve
```

## Project documentation

- [Architecture overview](ARCHITECTURE.md) — request flow, code responsibilities, and data relationships.
- [Testing guide](TESTING.md) — automated checks, manual smoke checks, and verification boundaries.
- [Review findings](REVIEW-FINDINGS.md) — disposition of findings from the 2026-10-20 source review.
- [Issue lifecycle and closure policy](engineering/issue-lifecycle-and-closure.md) — evidence-based GitHub issue closure rules and current dispositions.
- [Engineering session checkpoint](engineering/session-checkpoint.md) — current verified handoff.
