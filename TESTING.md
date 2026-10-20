# Testing Guide

Use this guide to verify the current Tymetry-web behavior. The checklist is not evidence that a test has passed.

## Setup

Requirements: PHP 8.3+, Composer, Node.js/npm, a configured database, and a valid application key.

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
php artisan test
```

Use a disposable test database for tests that reset or mutate data. Never run destructive test setup against production data.

## Automated tests

Run `php artisan test`. Record the date, commit SHA, PHP/database environment, command, result, and any blocker. Passing tests only verify assertions that exist.

The current visible feature test is a basic homepage response test; meaningful todo/authentication regression coverage is still needed.

## Manual smoke checklist

### Authentication and profile
- [ ] Guest can open login; valid credentials sign in; invalid credentials show an error.
- [ ] Logout invalidates the session.
- [ ] Password reset and OTP flows work in the configured environment.
- [ ] Profile and password updates persist correctly.

### Todo lifecycle
- [ ] Create and edit todos; verify required fields and invalid values are rejected.
- [ ] Filter by title, status, and priority.
- [ ] Update status, priority, and due date from the list.
- [ ] Quick-complete and bulk mark-done keep status and completion timestamps consistent.
- [ ] Smart ordering and due-date labels behave correctly for overdue, today, tomorrow, upcoming, and no-date tasks.
- [ ] Archive, restore, and bulk actions work, including empty or malformed selections.
- [ ] Activity logs reflect the actions the product intends to record.

### Ownership and dashboard
- [ ] User A cannot read or mutate User B's todos, including through bulk endpoints.
- [ ] Guests cannot access dashboard, todo, or profile routes.
- [ ] Dashboard counts, due-date lists, and activity are scoped to the signed-in user.
- [ ] Empty states render correctly; no new browser-console or server-log errors appear.

## Source-review findings to verify/fix

These were identified by reading the current source, not by executing the app:

1. **High priority — validate enum values.** `TodoRequest`, `updateStatus`, and `updatePriority` require values but do not restrict them to the supported status/priority enum values. Invalid values can fail later when Eloquent casts are read.
2. **High priority — keep completion metadata consistent.** `toggleComplete` and `bulkDone` update status without maintaining `completed_at`; bulk completion also bypasses activity logging. Define the expected behavior and test all completion paths.
3. **Medium priority — due-date inline control is not rendered in the active table.** The controller returns `due_action`, but the DataTables column configuration does not display it. The due-date endpoint exists, while the current list appears to offer no inline date input.
4. **Medium priority — due time is currently incomplete.** The migration has `due_time` and `TodoRequest` validates it, but the model's fillable list and the shared form do not expose/persist it. Either implement it end-to-end or remove the unused field/validation.
5. **Medium priority — AJAX error handlers use the wrong option name.** In `todo.js`, several `$.ajax({ ... })` calls specify `fail`; jQuery's AJAX options use `error`. A chained `.fail()` is valid, but the option is not.
6. **Low priority — list UI leftovers.** Both status-related table headers are labelled “Status”; bulk-done JavaScript shows archive wording and duplicate success toasts; the keyboard shortcuts point to old element IDs that are absent from the current view.
7. **Low priority — cleanup.** The root GET route is declared twice, and the old modal-based create/edit handlers reference controls currently commented out or absent.

## Suggested regression tests

Prioritize enum validation, user ownership for single and bulk actions, archive/restore, completion timestamp consistency, due-date updates, due-time persistence (if retained), and dashboard totals. Re-run the automated suite after each fix and record actual results here or in the PR.
