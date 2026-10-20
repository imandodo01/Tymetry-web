# Testing Guide

This guide describes a repeatable verification path for Tymetry-web. The checklist is a plan, not evidence that tests have already passed.

## 1. Local prerequisites

- PHP version compatible with `composer.json` (currently PHP 8.3+).
- Composer.
- Node.js and npm.
- A configured database and valid `.env`.
- Application key generated.

Typical setup:

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
```

Use a disposable/test database for tests that reset or mutate data. Never point destructive test setup at production data.

## 2. Automated tests

Run the existing suite:

```bash
php artisan test
```

Record the date, environment, command, pass/fail result, and any known blocker when reporting results. A successful test command only verifies the assertions that exist; it does not prove untested flows work.

## 3. Core manual smoke tests

### Authentication and account

- [ ] Guest can open the login page.
- [ ] Valid credentials sign in and reach the dashboard.
- [ ] Invalid credentials show an error without authenticating.
- [ ] Logout invalidates the session.
- [ ] Password reset and OTP flows work in the configured environment.
- [ ] An authenticated user can update profile details and password.

### Todo management

- [ ] Create a todo with title, description, status, priority, and optional due date.
- [ ] Required-field and invalid-input validation behaves correctly.
- [ ] Edit a todo and confirm changes persist after reload.
- [ ] Filter by title, status, and priority.
- [ ] Change status, priority, and due date using inline controls.
- [ ] Quick-complete toggles a todo between done and not-done states.
- [ ] Smart ordering places overdue and due-today unfinished tasks as intended.
- [ ] Due-date labels correctly show no date, overdue, today, tomorrow, and upcoming.
- [ ] Archive a todo; verify it disappears from the active list and appears in the archive.
- [ ] Restore an archived todo and verify it returns to the active list.
- [ ] Bulk mark-done, bulk archive, and bulk restore work for selected rows.
- [ ] Empty selections and malformed bulk payloads are handled safely.

### Ownership and authorization

- [ ] User A cannot view, edit, change, archive, or restore User B's todos by changing an ID in a request.
- [ ] Bulk endpoints ignore or reject IDs that do not belong to the authenticated user.
- [ ] Guest requests to protected dashboard, todo, and profile routes are redirected or rejected.
- [ ] Activity logs are associated with the correct user and todo.

### Dashboard and regression

- [ ] Counts reflect the current user's todos.
- [ ] Due-today, overdue, and upcoming lists exclude completed tasks where intended.
- [ ] Dashboard activity shows recent actions.
- [ ] Empty states render when there are no todos.
- [ ] The todo table renders correctly on desktop and narrow/mobile screens.
- [ ] Browser console and server logs show no new errors during the smoke test.

## 4. Focused regression tests to add

Prioritize automated feature tests for:

1. Todo CRUD validation and persistence.
2. Ownership boundaries for single-item and bulk actions.
3. Archive/restore lifecycle.
4. Valid and invalid status/priority values.
5. Due-date updates and due-date state boundaries.
6. Consistent `completed_at` behavior for standard status changes, quick-complete, and bulk completion.
7. Activity-log creation for the actions the product intends to audit.
8. Dashboard totals and due-date lists.

## 5. Recording results

For each test run, record:

- Date and commit SHA.
- Environment/PHP version and database type.
- Command or manual scenario.
- Result: Pass / Fail / Blocked / Not run.
- Failure details and follow-up issue.

Do not mark a feature as verified just because its route or controller exists. Use passing automated tests or a recorded manual check as evidence.
