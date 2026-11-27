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


## TYMX M6 — Import validation and preview handoff (2026-11-26)

- Parent PR #53 exact head: `1c64d79e744b07269f5c0582c608ea288d48cfff`; CI run #45825237681 passed.
- M6 branch: `feature/m6-tymx-import-preview`, created from the PR #53 head above.
- PR #54: https://github.com/imandodo01/Tymetry-web/pull/54 (Draft; implementation commit `300a611e140efb3a12f167da6b1afcac8ac9560b`; refresh CI evidence after each follow-up commit).
- Scope: read-only TYMX v1 file upload, validation, identity matching, three-way conflict preview, and UI display. No Todo or ActivityLog mutations; no import apply.
- Resource limits: 5 MB upload; 2,000 records. Only active Todos belonging to the authenticated user are matched. Unknown, archived, and cross-user IDs are all reported as unmatched; do not reveal cross-user identity existence.
- Conflict rule per field: if proposed equals base, preserve current and report `server_only` when current differs; if current equals base, proposal is eligible; if current equals proposal, it is already current; otherwise report a conflict.
- Invalid documents are rejected as a whole. Duplicate public IDs are rejected. No internal integer IDs are accepted from the document.
- Files to be changed: controller, route, preview service, Todo index Blade, Todo JS, feature tests, product plan, this checkpoint.
- Automated verification must cover valid preview, three-way conflicts, server-only changes, unmatched/cross-user IDs, duplicate IDs, malformed/unsupported schema, unknown fields, file extension, authentication, and no database/activity mutation.
- Manual browser acceptance and independent review remain pending. M7 import apply is explicitly not implemented.

- Follow-up hardening commit: UUID format matches the published UUID syntax rather than restricting versions; RFC 3339 timezone offsets are range-checked; server-only and already-current changes are explicitly covered by tests.

- CI run #45826450026 failed on preview tests due to an undefined optional regex capture for the RFC 3339 `Z` timezone. The failure was isolated from frontend build (passed); the timezone parser is being corrected with `PREG_UNMATCHED_AS_NULL` and named captures. Re-run CI on the follow-up commit.

- Corrected CI head: `51221fe7c7c317d1ed208e9b683908d4f5878d77`; GitHub Actions run #45826685253 passed on PHP 8.3 / Node 24. PHPUnit: 129 passed, 704 assertions; frontend build passed.
- The earlier run #45826450026 failed because the regex's optional offset capture was absent for `Z`; the named capture + `PREG_UNMATCHED_AS_NULL` fix is covered by the passing full suite.
- PR #54 remains Draft and stacked on PR #53. Independent review and browser acceptance are still pending. No merge or deployment.


## TYMX M6 review refresh — 2026-11-27

### Current PR state

- PR #53 — [Todo v1 export](https://github.com/imandodo01/Tymetry-web/pull/53), Draft/Open; head `1c64d79e744b07269f5c0582c608ea288d48cfff`. Exact-head CI run [#45825237681](https://github.com/imandodo01/Tymetry-web/actions/runs/45825237681) passed: 119 tests, 659 assertions, frontend build passed.
- PR #54 — [read-only import validation and preview](https://github.com/imandodo01/Tymetry-web/pull/54), Draft/Open; branch `feature/m6-tymx-import-preview`, stacked on PR #53.
- PR #54 review fix: normalize UUID public IDs for database lookup with `LOWER(public_id)`, matching the case-insensitive UUID syntax already accepted by validation. Added a regression test that imports an uppercase representation of an existing Todo's public ID.
- Fix commit: `cf9769bbb5d3163ac87cd6f557b339e605f48ca1` (`fix(tymx): match public IDs case-insensitively`).
- CI run [#45940468456](https://github.com/imandodo01/Tymetry-web/actions/runs/45940468456) passed on exact fix SHA: 130 tests, 709 assertions; frontend build passed.
- PR descriptions were refreshed with current exact-head verification evidence.

### Reconciliation and acceptance

- Current `main` at checkpoint refresh: `243c4f8f2796a7805973837aa86e5553d85530de` (README alignment commit, parent `2198a5299e9e60025aab631a23cb1ed4e5a11789)).
- PR #53's recorded base snapshot is `92e762fa93a13816a5170fc712c0fabaa5fe8ce4`; `main` has advanced by five commits since that snapshot. GitHub currently reports PR #53 mergeable, but current-main integration and merge-tree CI must be checked before merge.
- PR #54 remains stacked on PR #53's export branch. Preserve the dependency order: integrate/reconcile #53 first, then retarget/reconcile #54 against the resulting mainline.
- Board PR #52 has since been squash-merged into `main` as `2e2f65a845495a37ccfcb3a53cc6368ef79797bb`; its CI passed on the tested application-code head.
- The Todo due-indicator change is also on main; verified application-code SHA `245ad2bbbfb5476d2cbe261d7d66211ea8cfc4cd`, CI run #45935445923 passed (120 tests, 505 assertions, frontend build).
- No local PHPUnit/build or manual browser/device acceptance has been performed in this remote session. TYMX export download/round-trip and import preview UI acceptance remain pending.
- No merge, deployment, production data change, or branch deletion was performed.

### Next actions

1. Finish independent review of PR #53/#54 and reconcile the PR #53 branch against current main before integration.
2. Re-run CI on the exact merge tree after reconciliation.
3. Perform browser acceptance for selected/all-active export, UTF-8 round-trip, valid/invalid import preview, unmatched IDs, conflict display, and no-mutation behavior when laptop access is available.
4. Keep M7 import apply unimplemented until its conflict-resolution, apply-time revalidation, transactional boundary, pre-import backup/recovery, audit, and result-reporting contracts are designed and reviewed.
5. Use commute time for M7 design decisions and test-matrix preparation; do not require repository edits or laptop verification for that design-only session.
