# Tymetry Engineering Session Checkpoint

> Updated: 2026-11-24 (Asia/Jakarta)  
> Status: In Progress — current mainline synchronized; remaining PRs under review

## Project

- Web repository: https://github.com/imandodo01/Tymetry-web
- Android repository: https://github.com/imandodo01/Tymetri
- Default branch: `main`
- Verified main HEAD at refresh: `696abc7a7341972256b5eec4d03a7372edd838ae`
- Local checkout / working-tree state: not available in this remote GitHub session. Do not infer local cleanliness.
- This file is a repository handoff checkpoint; Notion page **Tymetry Check List** remains the cross-chat checklist.

## Working rules

- Complete clearly scoped implementation without stopping for routine decisions.
- Keep implementation, independent review, QA, and maintainer acceptance responsibilities distinct.
- Bind CI/test evidence to exact commit SHAs and the tested PR merge tree.
- Do not merge or deploy automatically unless the maintainer has authorized it.
- Do not use production as a substitute for a missing staging environment.
- Record unverified behavior and unresolved product decisions explicitly.

## Verified mainline state

Merged into `main`:

- PR #30 — atomic Todo mutations and ActivityLog writes.
- PR #31 — OTP challenge concurrency protection.
- PR #32 — authenticator-based TOTP MFA, account-keyed failed-attempt limiting, and recovery-code input support.
- PR #33 — CI environment bootstrap.
- PR #34 — Todo ownership authorization regression tests.
- PR #35 — application-timezone-aware Todo smart ordering and discriminating regression coverage.
- PR #46 — UUIDv7 public identities, update immutability, and migration backfill tests.
- PR #48 — ActivityLog rollback regression coverage.

Latest main HEAD: `696abc7a7341972256b5eec4d03a7372edd838ae` (PR #48 squash merge).

## Current change — remove unused sample page

- Branch: `chore/remove-unused-sample-route`
- PR: [#49 — remove unused sample page](https://github.com/imandodo01/Tymetry-web/pull/49)
- Changes:
  - Remove `GET /sample` from `routes/web.php`.
  - Delete `resources/views/sample.blade.php`.
  - Refresh this checkpoint with current mainline and PR status.
- The route update was reconciled against current `main` so the TOTP routes added by PR #32 are preserved.
- The prior project checkpoint dated 2026-11-20 records the maintainer's acceptance that the unused `/sample` route and its Blade view may be removed. That is the authorization basis for this PR.
- Current branch must be synchronized with main at `696abc7a7341972256b5eec4d03a7372edd838ae` and rerun CI before merge.
- No local `route:list`, PHPUnit, frontend build, or browser smoke test was run in this remote session.

## Remaining active pull requests — 2026-11-24

| PR | Scope | Current state | Remaining action |
|---|---|---|---|
| [#45](https://github.com/imandodo01/Tymetry-web/pull/45) | Task input normalization | Draft, base `main` | Remove stale checkpoint from its diff; synchronize and rerun CI; mark ready after review |
| [#47](https://github.com/imandodo01/Tymetry-web/pull/47) | Date/time/timezone contract documentation | Open, base `main` | Standalone documentation proposal; acceptance gap for alternatives/consequences still needs resolution |
| [#49](https://github.com/imandodo01/Tymetry-web/pull/49) | Remove unused sample page | Draft, base `main` | Current main synchronization, CI, then ready/merge |

PR #32, #34, #35, #46, and #48 are merged. PR #30, #31, and #33 are also merged.

## Review findings and operational limitations

1. **TOTP MFA — PR #32:** account-keyed rate limiting now complements the existing per-IP throttle. Five failed TOTP/recovery-code attempts per account are limited per 60 seconds; successful verification clears the account counter. The latest CI run passed, but real authenticator enrollment/recovery and deployed cache-driver behavior remain unverified.
2. **UUIDv7 — PR #46:** new IDs are generated server-side, caller-supplied IDs are overwritten, existing model IDs are immutable, and migration backfill is tested. Integer primary keys remain internal relations.
3. **Timezone — PR #35 / #47:** PR #35 is merged and uses the Laravel application date for smart ordering. PR #47 remains a standalone contract proposal, not shipped account-aware timezone behavior. Its acceptance criterion for alternatives and consequences still needs a systematic mapping or explicit maintainer waiver.
4. **ActivityLog — PR #30 / #48:** atomic Todo mutation writes are merged; rollback coverage now includes create, status, priority, update, archive, restore, completion toggle, and due-date update.
5. **Task normalization — PR #45:** implementation is straightforward; remove its stale checkpoint copy before review.
6. **Staging gap:** no staging environment is available. Real SMTP delivery, manual authenticator-app enrollment/recovery, and production cache-driver behavior remain operationally unverified. Do not use production as a substitute.
7. **Dependency maintenance:** keep the previous dependency-maintenance branch blocked until Composer lockfile compatibility is restored for the declared PHP `^8.3` runtime and CI verifies a clean install.

## Verification evidence

- PR #32: GitHub Actions run #45360675502 passed on exact head `40a19b451e6e6057cf9b101f863debe91f65385b`; 91 tests / 411 assertions; frontend build passed.
- PR #35: GitHub Actions run #45359643308 passed on the PR merge tree; 74 tests / 311 assertions; frontend build passed. PR merge commit `696c36e03062d65133cc5eb78f712a71ad617f9a`.
- PR #46: GitHub Actions run #45361162584 passed on the current main merge tree; 97 tests / 428 assertions; frontend build passed.
- PR #48: GitHub Actions run #45361479415 passed on exact head `cb7ac75c38b7f44a4709dfada8fb9b9913400453`; 102 tests / 443 assertions; frontend build passed.
- PR #49's prior run #45359930733 passed against older main `696c36e03062d65133cc5eb78f712a71ad617f9a`; current route reconciliation and checkpoint update require a fresh CI run.
- No local PHPUnit, browser/device, or staging smoke tests were executed in this remote session.
- No deployment or production data/configuration operation was performed.

## Next actions

1. Finish PR #49 synchronization with current main, run CI on the final tree, then mark ready and merge.
2. Remove the stale checkpoint file from PR #45's diff, synchronize it with main, and rerun CI.
3. Resolve PR #47's alternatives/consequences acceptance gap or record an explicit maintainer waiver before merge.
4. Re-run combined verification after each integration and keep evidence tied to exact SHAs.


## 2026-11-27 — Todo due indicator semantics

- User approved a direct-to-main change without a PR or manual acceptance check for the due indicator bug.
- Rule: only non-Done Todos can be `overdue`; Done Todos with a due date are classified from `completed_at` as `completed_on_time` or `completed_late`. Legacy Done rows without `completed_at` use neutral `completed` rather than guessing.
- The due date is date-only, so completion on the due date counts as on time. No schema or migration change.
- Keep List and Board labels/badges sourced from Todo model methods. Automated tests and CI are still required; no manual browser acceptance requested.

- Follow-up test hardening: simulate a legacy missing `completed_at` with a direct query-builder update (bypassing the model's saving hook), and verify the Board JSON exposes the completed-late label and badge.

- Verification for application code commit `245ad2bbbfb5476d2cbe261d7d66211ea8cfc4cd`: GitHub Actions run [#45935445923](https://github.com/imandodo01/Tymetry-web/actions/runs/45935445923) passed; 120 tests, 505 assertions, frontend build passed.
- Earlier code commit `260943ccc8a1af5b02ca2238cf7e62c3eecc3d5f` had one failing legacy-row test because the model saving hook repopulated `completed_at`; the test now simulates legacy data through the query builder and passes on the verified commit above.
- No local PHPUnit or manual browser acceptance was run. No migration, merge, or deployment was needed for this direct-to-main change.
