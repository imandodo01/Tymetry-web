# Testing Guide

Use this guide to verify current Tymetry-web behavior. A checklist is a test plan, not evidence that a test passed.

## Setup

Requirements: PHP 8.3+, Composer, Node.js 24 LTS/npm, a configured database, and a valid application key.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database, mail transport, and cache in `.env`. For a local development database, run:

```bash
php artisan migrate
npm ci
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

Record the date, commit SHA, PHP/database/Node environment, command, result, and any blocker when reporting a verification run. Passing tests only verify assertions that exist.

The repository's focused feature/unit tests currently cover:

- Board data grouping, filtering, ownership, status persistence, completion metadata, and activity logging.
- TYMX v1 export contract/schema assertions, import document validation, public-ID matching, three-way preview classification, and the no-mutation guarantee.
- TOTP enrollment/login/recovery behavior, account-keyed attempt limiting, and deterministic lock-contention rejection. The direct TOTP replay regression and real multi-process MySQL/shared-cache checks remain outstanding in Issue #41.

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

- Main baseline: `edc29cbfc4ab2ad0a752969b4d08598ea92fd72b`.
- GitHub Actions run [#44066718826](https://github.com/imandodo01/Tymetry-web/actions/runs/44066718826) completed successfully for that exact main SHA. The workflow installed PHP dependencies, ran `npm ci`, built frontend assets, and ran `php artisan test`.
- PRs #26 (feedback consistency), #27 (form/accessibility consistency), and #28 (documentation closure) are merged.
- Responsive source inspection found the expected narrow-screen layout and responsive table wrappers. A viewport-specific browser run is not recorded in this checkpoint.
- The assistant inspected the workflow and run metadata; it did not execute the local PHP test suite or a real browser session.
- The Node.js 24 CI change proposed in this Sprint 07 PR is not verified until its own pull-request workflow completes successfully.

## Safe test-data handling

Use a disposable local/test database for tests that reset or mutate records. Confirm the active connection before running migrations or test commands. Do not use production credentials or production data for local verification.


## Board manual acceptance — follow-up checklist

- [ ] List and Board show the same underlying Todo records.
- [ ] Dragging a card persists status; failed requests restore the server state.
- [ ] Keyboard/touch users can change status without drag-and-drop.
- [ ] Filters and due indicators remain consistent across views.
- [ ] Desktop, tablet, and mobile layouts remain usable.

## TYMX manual acceptance — follow-up checklist

- [ ] Export selected Todo records and verify the downloaded UTF-8 JSON against the v1 schema.
- [ ] Export all active Todos and verify no archived or cross-user records are included.
- [ ] Preview a valid .tymx file and inspect identity/conflict classifications.
- [ ] Reject malformed, unsupported, oversized, and invalid documents without mutation.
- [ ] Confirm preview does not change Todo rows or create ActivityLog records.
- [ ] Do not test import execution: M7 Import Apply is not implemented.

## MFA and OTP operational verification — safe environment only

- [ ] Verify email OTP delivery and expiry using the configured non-production mail transport.
- [ ] Exercise concurrent send/verify requests using multiple processes and the intended shared cache/database.
- [ ] Verify TOTP first-use success and same-code replay rejection after the replay regression is added.
- [ ] Verify authenticator enrollment, recovery-code one-time use, and legacy email-MFA compatibility.
- [ ] Record cache driver, database engine/version, mail provider, effective timeout, exact tested SHA, and results.
- [ ] Never use production users, secrets, live OTPs, or production data as test fixtures.
