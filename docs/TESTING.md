# Testing Guide

Use this guide to verify current Tymetry-web behavior. A checklist is a test plan, not evidence that a test passed.

## Setup

Requirements: PHP 8.3+, Composer, Node.js/npm, a configured database, and a valid application key.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database, mail transport, and cache in `.env`. For a local development database, run:

```bash
php artisan migrate
npm install
npm run build
php artisan test
```

Use a disposable test database for tests that reset or mutate data. Never run destructive test setup against production data.

## Automated test suite

Run:

```bash
php artisan test
npm run build
```

Record the date, commit SHA, PHP/database environment, command, result, and any blocker when reporting a verification run. Passing tests only verify assertions that exist.

The repository's focused feature/unit tests currently cover:

- Authentication verification and two-factor flow behavior.
- Password-reset rate limits.
- Todo request and AJAX enum validation.
- Todo lifecycle, completion timestamp, and activity-log consistency.
- Bulk action validation and user-ownership boundaries.
- Due-time create/edit persistence and validation.
- Profile email uniqueness.
- Smart ordering under the configured test database.
- Dashboard due-date behavior.
- Todo table presentation/accessibility and application-shell navigation.

## Manual smoke checklist

### Authentication and profile

- [ ] Login succeeds with valid credentials and rejects invalid credentials.
- [ ] Repeated login attempts are throttled.
- [ ] Logout invalidates the session.
- [ ] Password reset works with a valid token and rejects invalid/expired tokens.
- [ ] Email verification and optional two-factor flow can send and verify codes using the configured mail transport.
- [ ] OTP resend and verification attempt limits behave as expected.
- [ ] Profile and password updates persist correctly.
- [ ] A duplicate profile email produces a validation message.
- [ ] Two-factor preference cannot be enabled before email verification.

### Todo lifecycle

- [ ] Create and edit todos; required fields and invalid status/priority values are rejected.
- [ ] Due-time value persists after create/edit and remains informational metadata only.
- [ ] Filter by title, status, and priority.
- [ ] Inline status, priority, and due-date controls update the selected todo.
- [ ] Quick-complete and bulk mark-done keep status and completion timestamps consistent.
- [ ] Smart ordering and due-date labels behave correctly for overdue, today, tomorrow, upcoming, and no-date tasks.
- [ ] Archive, restore, and bulk actions work, including empty/malformed selections.
- [ ] A user cannot read or mutate another user's todos, including through bulk endpoints.
- [ ] Activity logs reflect the actions the product intends to record.

### Dashboard and application shell

- [ ] Guests cannot access dashboard, todo, or profile routes.
- [ ] Dashboard counts, due-date lists, and activity are scoped to the signed-in user.
- [ ] Completed todos do not appear in open due-date lists.
- [ ] Empty, loading, and error states render correctly.
- [ ] Success feedback appears once for create/update/archive/restore/profile flows.

### Responsive and accessibility smoke checks

- [ ] At desktop width (for example, 1280px), sidebar, topbar, cards, and tables remain aligned.
- [ ] At tablet width (for example, 768px), navigation changes to the compact horizontal layout without covering the content.
- [ ] At mobile width (for example, 390px), topbar actions remain usable and content does not create unintended page-level horizontal overflow.
- [ ] Sidebar navigation can be horizontally scrolled to reach every destination on narrow screens.
- [ ] Todo and archive tables can be horizontally scrolled within their responsive containers when needed.
- [ ] Keyboard focus remains visible; skip link, navigation, form fields, and archive checkboxes are operable.
- [ ] Validation errors are associated with the relevant fields and remain understandable when navigating by keyboard.

## Verification checkpoint (2026-11-17)

- PR #26 (feedback consistency): GitHub Actions CI completed successfully; the maintainer reported the changed behavior works as expected.
- PR #27 (form/accessibility consistency): GitHub Actions CI completed successfully; the maintainer reported the changed behavior works as expected.
- The main branch checkpoint is `b3d906eb1a9990dd39b23936b832e05f9c207c03`.
- Responsive source inspection found the expected narrow-screen layout and responsive table wrappers. A viewport-specific browser run is not recorded in this checkpoint.
- No claim is made here that the assistant executed the local PHP test suite or a real browser session.

## Safe test-data handling

Use a disposable local/test database for tests that reset or mutate records. Confirm the active connection before running migrations or test commands. Do not use production credentials or production data for local verification.
