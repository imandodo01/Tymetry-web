# Tymetry Engineering Session Checkpoint

> Updated: 2026-11-20 15:30 Asia/Jakarta  
> Status: In Progress — implementation PRs open; current maintenance change published for review

## Project

- Web repository: https://github.com/imandodo01/Tymetry-web
- Android repository: https://github.com/imandodo01/Tymetri (same repository; no relocation or repository change)
- Web default branch: `main`
- Verified Web main HEAD at recovery: `6b17019dead029acc1b4e014ccb3e28ead52b751`
- Current work branch: `chore/remove-unused-sample-route`
- Local checkout / working tree: not available in this session; source inspection and edits were performed through GitHub. Do not infer a local clean state.

## Objective and acceptance

Continue the existing Tymetry audit and implementation without repeating decisions already made.

Acceptance expectations:
- Complete straightforward, approved work without pausing for minor decisions.
- Preserve independent implementation, review, QA, and acceptance responsibilities.
- Tie verification evidence to exact commit SHAs.
- Do not merge or deploy automatically.
- Record only questions that materially require maintainer/product input.

## Accepted decisions

1. The unused `/sample` route and its Blade view may be removed.
2. Account calendar timezone default is `Asia/Jakarta`, following the maintainer's calendar context. This confirms the existing S08-03 contract decision; it does not authorize changing `APP_TIMEZONE` or converting date-only / wall-clock values to UTC.
3. Temporal semantics remain:
   - `due_date`: date-only calendar value; preserve unchanged across timezones.
   - `due_time`: wall-clock metadata only; do not convert into an instant or use for reminders/order.
   - `created_at`, `updated_at`, `completed_at`: instants serialized as UTC.
   - `time_zone`: canonical account IANA timezone; new-account default `Asia/Jakarta`.
4. Android remains at the existing repository `imandodo01/Tymetri`; no repository move or replacement occurred. Its source baseline remains the one documented in PR #47.
5. No staging environment is available. Real SMTP delivery and manual authenticator-app enrollment cannot currently be performed as staging smoke tests. Do not use production as a substitute.

## Current change — remove unused sample page

- Branch: `chore/remove-unused-sample-route`
- Base: `main` at `6b17019dead029acc1b4e014ccb3e28ead52b751`
- Changes:
  - Removed the `GET /sample` route from `routes/web.php`.
  - Deleted `resources/views/sample.blade.php`.
- Commits:
  - `60986b5586dcc11cb304587eec4327ce0a7028b0` — `chore(routes): remove unused sample route`
  - `d2930c65e7c2617c1f933afd307b8601a20c216b` — `chore(views): remove unused sample page`
- PR: [#49 — remove unused sample page](https://github.com/imandodo01/Tymetry-web/pull/49), Draft, targeting main.
- Verification: remote source edits confirmed by GitHub Contents API; GitHub Actions passed on code head `aab5111e3547f332285fc91f97e88e4828307473`. PHP output contained missing-`.env` warnings; see CI warning detail below. No local route:list, PHPUnit, frontend build, or browser smoke test was run.

## Active Tymetry Web pull requests

All PRs below were observed open on 2026-11-20. GitHub Actions check `PHP tests and frontend build` completed successfully on the recorded code heads. Warning-heavy PHP output is called out separately below.

| PR | Branch | Head SHA | Base | CI |
|---|---|---|---|---|
| [#30](https://github.com/imandodo01/Tymetry-web/pull/30) | `fix/m0-atomic-activity-logging` | `bcc2baf4f596e7db0f84060c423fe467c9564679` | `main` | Passed |
| [#31](https://github.com/imandodo01/Tymetry-web/pull/31) | `fix/otp-concurrency-protection` | `9e3e00790a6c3653b8107d187bceec93f5e43e41` | `main` | Passed |
| [#32](https://github.com/imandodo01/Tymetry-web/pull/32) | `feat/authenticator-totp` | `52f436bfcad97ccb670156c115c17577ae66b92e` | `chore/ci-test-env-bootstrap` | Passed |
| [#33](https://github.com/imandodo01/Tymetry-web/pull/33) | `chore/ci-test-env-bootstrap` | `6120921b4c0050270c1db7266079fb454324db72` | `main` | Passed |
| [#34](https://github.com/imandodo01/Tymetry-web/pull/34) | `test/security-todo-ownership-coverage` | `d3795cc96d614693e8df8c6be80f4b96714f8b6` | `main` | Passed |
| [#35](https://github.com/imandodo01/Tymetry-web/pull/35) | `fix/timezone-smart-order-date` | `a5b2c3b6a6108f1a81629377586337a24fe4620c` | `main` | Passed |
| [#45](https://github.com/imandodo01/Tymetry-web/pull/45) | `fix/task-input-normalization` | `9a61d868df793f611bfbc8d22246e6c42ae163ac` | `chore/ci-test-env-bootstrap` | Passed |
| [#46](https://github.com/imandodo01/Tymetry-web/pull/46) | `feat/public-uuidv7-identifiers` | `45037c7103974d0f6e20a607d7002674db864839` | `chore/ci-test-env-bootstrap` | Passed |
| [#47](https://github.com/imandodo01/Tymetry-web/pull/47) | `docs/s08-03-date-time-timezone-contract` | `5e103a7e208f0e42cc6d5995ae4e3dbb2154aca8` | `main` | Passed |
| [#48](https://github.com/imandodo01/Tymetry-web/pull/48) | `test/m0-activity-log-rollback-coverage` | `137ed77d5ec6abb889622915ff878d4fd2cbdb0b` | `fix/m0-atomic-activity-logging` | Passed |

PR #48 adds five ActivityLog failure rollback tests for Todo update, archive, restore, completion toggle, and due-date update. Its test file is stacked on PR #30; PR #48 should be reviewed/integrated only after its base work is available.

## Review findings and follow-up

1. **OTP concurrency — PR #31:** cache lock uses a 30-second lease while synchronous mail delivery is inside the critical section and SMTP timeout is unset in `config/mail.php`. A slow/stalled transport may outlive the lock lease. Review bounded SMTP timeout and lock lease relationship before production readiness.
2. **MFA and UUIDv7 — PR #32 / #46:** the shared `User` model regression is addressed on PR #46. Its current code head `9852e7778b222399bbf9f272df58e5b4d28324d8` preserves encrypted TOTP casts and hidden secret/recovery attributes, with a regression test. CI passed (55 tests, 248 assertions). Keep PR #32 / #46 changes coordinated during integration.
3. **Timezone — PR #35 / #47:** PR #35 aligns ordering to configured Laravel application timezone. PR #47 defines account timezone semantics and confirms `Asia/Jakarta` for new accounts. Account-aware calendar behavior is not yet implemented; do not treat the documentation contract as shipped behavior.
4. **Staging gap:** no staging environment exists. SMTP delivery, manual TOTP enrollment/recovery, and production cache-driver behavior remain unverified operationally. CI/unit coverage does not replace those smoke tests.
5. **Dependency maintenance:** branch `chore/m0-dependency-maintenance` was previously found incompatible because its Composer lock resolves Symfony 8.1 components requiring PHP >=8.4.1 while the project contract/CI target PHP 8.3. Keep blocked until lockfile is regenerated for the supported runtime and verified.
6. **PR #48 checkpoint:** its checkpoint file has been reconciled to the current session record. Keep the PR stacked on PR #30 until the base work is integrated.

## Verification evidence

- GitHub Actions checks queried for PR #30–#35 and #45–#48; all returned completed / success on the exact SHAs in the table.
- PR #47 is documentation-only; its CI also passed.
- Current sample-removal branch has not yet run CI.
- No local checkout was available; no local PHPUnit, lint, frontend build, Android build, or browser/device test was executed in this session.
- No staging environment is available; real SMTP and manual authenticator app smoke tests are not run.
- No merge, release, deployment, production configuration change, or database operation was performed.


### CI warning detail (exact current heads checked 2026-11-20)

- PR #48 code head `3d2f64e4e973161be36cb70e7419d29d2b4906f8`: GitHub Actions conclusion `success`; Vite production build succeeded. PHP test output reported 57 warnings and 1 passed (259 assertions), with warnings caused by tests reading a missing CI `.env` file. The five new rollback tests were present in this run, but warning-heavy output is not a clean verification result. Later commits on this branch only synchronized the checkpoint.
- PR #49 code head `aab5111e3547f332285fc91f97e88e4828307473`: GitHub Actions conclusion `success`; Vite production build succeeded. PHP test output reported 49 warnings and 1 passed (235 assertions), with the same missing CI `.env` warning pattern. Later commits on this branch only synchronized the checkpoint.
- PR #33 contains the CI environment bootstrap fix and its own exact head check passed. The warning-free workflow only applies once that workflow change is part of the tested base; do not transfer PR #33's clean result to other PR heads.

## Next actions

1. Recheck CI on the latest checkpoint-only heads of PR #48 and PR #49; then review the route removal and rollback tests.
2. Review PR #46 with PR #32 and preserve the now-tested MFA model compatibility.
3. Continue static review of PR #31's mail timeout/lock lease and PR #32/#46 shared User model integration.
4. Keep staging-dependent verification explicitly blocked until a safe staging environment is provided; do not substitute production.
5. Review PRs and merge only through the agreed human review/acceptance workflow.
