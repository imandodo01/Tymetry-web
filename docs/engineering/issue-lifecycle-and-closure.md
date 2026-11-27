# Issue Lifecycle and Closure Policy

> Status: Active project workflow
> Updated: 2026-11-27
> Applies to: Tymetry Web GitHub Issues and linked Pull Requests

This document defines how Tymetry work items move from discovery through implementation, verification, acceptance, and closure. GitHub Issues are the operational source of truth for actionable work. Notion and session checkpoints provide planning and handoff context; they must link to GitHub rather than maintain competing issue statuses.

## 1. Keep three states distinct

A GitHub issue can be open or closed, but its body must make the underlying state clear:

| Dimension | Values | Meaning |
|---|---|---|
| Decision | Proposed, Under Review, Accepted, Rejected, Deferred | Whether the work is valid and approved |
| Delivery | Not Planned, Planned, In Progress, Blocked, Implemented, Verified, Completed, Cancelled | Progress toward the agreed outcome |
| Acceptance | Pending, Accepted, Changes Requested, Rejected, Deferred, Not Required | Whether the required owner has accepted the outcome |

Do not use a merged PR, passing CI, or a closed issue as a substitute for the other dimensions.

## 2. Required issue content

Keep each issue concise, but preserve the information needed to resume and verify it:

- Stable Work Item ID where the project workflow requires one.
- Problem or intended outcome and scope boundaries.
- Observable acceptance criteria, including relevant negative/failure cases.
- Risk tier and linked Work Session when applicable.
- Related PRs and dependencies.
- Verification evidence tied to exact commit SHA, with command/scenario, result, and environment where relevant.
- Explicitly unverified checks, blockers, residual risks, and next action.
- Closure disposition and reason when the issue is closed.

Do not copy an entire PR description or duplicate mutable status into multiple documents. Link to the authoritative evidence.

## 3. Closure gates

### Completed

Close with GitHub state reason `completed` only when:

1. All required acceptance criteria are satisfied, or each remaining criterion has an explicit, approved disposition.
2. Implementation is integrated where implementation is in scope.
3. Required automated and manual verification is recorded against the exact tested revision.
4. Required independent review and QA gates are complete for the risk tier.
5. Required user/domain-owner acceptance is recorded; do not infer it from CI.
6. Remaining risks and follow-up work are either acceptable and documented or moved to separate linked issues.
7. The issue body includes a concise closure summary and evidence links.

For Tier 3 security, authentication, concurrency, and data-integrity work, source review and CI alone do not establish environment-specific QA. MySQL row-lock behavior, shared-cache atomicity, SMTP delivery, and authenticator/recovery flows must remain explicitly pending unless tested safely in the intended environment.

### Not planned / Deferred

Close with state reason `not_planned` only when the work is intentionally not proceeding in the current plan. Record:

- Why it is not proceeding.
- Whether the requirement is rejected or merely deferred.
- Any replacement issue/document, if one exists.
- A revisit trigger or condition when useful.

A `not_planned` closure is an administrative disposition, not evidence that the work was implemented, verified, or accepted. Preserve the issue as historical context.

### Duplicate / Superseded

Use `duplicate` only when a canonical issue exists and is linked. Use `completed` for superseded work only when the replacement fully satisfies the original outcome; otherwise use `not_planned` and explain the remaining gap.

## 4. Evidence status vocabulary

Use these labels consistently in issue bodies and handoffs:

| Evidence status | Meaning |
|---|---|
| Independently Executed | The reviewer/QA agent ran the check and recorded its result |
| Evidence Reviewed | Existing evidence was inspected; the reviewer did not rerun it |
| Not Verified | No sufficient evidence is available |
| Blocked | Verification could not proceed; include the concrete blocker |
| Not Applicable | The check does not apply; explain why |

Maintainer-reported local results are useful evidence, but must be labeled as maintainer-reported unless the execution output or an independently inspectable CI run is available.

## 5. PR and issue relationship

- A PR may close an implementation issue when its merge satisfies the issue's implementation scope.
- A review/QA issue may remain open after the implementation PR merges.
- If a PR addresses only one finding, mark only that finding implemented and keep unrelated findings open.
- When a finding is split into a follow-up issue, link both directions and retain the original review rationale.
- Do not close a parent workflow issue while its required pilot/adoption criteria remain incomplete.
- Do not close an issue merely to make the open-issue list shorter.

## 6. Tymetry current disposition — 2026-11-27

| Issue | Current disposition | Closure rule / next action |
|---|---|---|
| [#36 TYM-OPS-001](https://github.com/imandodo01/Tymetry-web/issues/36) | Open | Policy and pilot structure exist. Keep open until role-chat adoption and practical pilot validation are evidenced. |
| [#37 TYM-PILOT-01](https://github.com/imandodo01/Tymetry-web/issues/37) | Closed — completed | Tier 1 CI evidence review completed; separate Review/QA was not required. |
| [#38 TYM-PILOT-02](https://github.com/imandodo01/Tymetry-web/issues/38) | Closed — completed | Tier 2 exact-SHA regression/CI evidence reviewed; documented verification boundary retained. |
| [#39 TYM-PILOT-03](https://github.com/imandodo01/Tymetry-web/issues/39) | Open | Bulk Done/Archive/Restore rollback regression coverage and successful bulk Restore ActivityLog assertion remain. |
| [#40 TYM-PILOT-04](https://github.com/imandodo01/Tymetry-web/issues/40) | Open | Email OTP multi-process concurrency, deployed cache/mail timeout compatibility, and contention UX disposition remain. |
| [#41 TYM-PILOT-05](https://github.com/imandodo01/Tymetry-web/issues/41) | Open | PR #55 completed F-01 TOTP account rate-limit atomicity. F-02 replay regression and environment-specific MFA QA remain. |
| [#42 S08-01](https://github.com/imandodo01/Tymetry-web/issues/42) | Closed — not planned | Local Web/Android baseline verification was not recorded as completed. Do not treat closure as a passing baseline. |
| [#43 S08-02](https://github.com/imandodo01/Tymetry-web/issues/43) | Closed — not planned | Canonical Task contract finalization/approval was not recorded as completed. Do not treat closure as an approved contract. |
| [#44 S08-03 legacy tracker](https://github.com/imandodo01/Tymetry-web/issues/44) | Closed — not planned | Retired in favor of the standalone S08-03 contract document and PR #47. |
| [#47 S08-03 contract](https://github.com/imandodo01/Tymetry-web/pull/47) | Integrated documentation | Contract proposal is documented; account-timezone runtime implementation remains separate work. |

This table is a dated reconciliation, not a replacement for live GitHub issue state. Recheck the issue and PR before acting on it.

## 7. Current open-issue closure order

1. #39 — complete the missing bulk transaction/audit regression tests and verify the failure path.
2. #40 — decide which recommendations are release blockers versus accepted residual risks; verify only in a safe environment using the intended cache/mail configuration.
3. #41 — add direct sequential TOTP replay regression coverage; separately disposition concurrent duplicate-code, MySQL row-lock, enrollment, and recovery QA.
4. #36 — close only after role-chat adoption and pilot protocol validation are recorded.

For each issue, update the body with exact evidence first, then close it with the correct state reason. If an item is deferred, keep the rationale and revisit condition visible.

## 8. Documentation ownership

- `README.md`: concise current project status and entry points.
- `docs/README.md`: documentation index and project-level status.
- `docs/engineering/session-checkpoint.md`: latest verified handoff, exact main SHA, open work, and next action.
- `docs/engineering/issue-lifecycle-and-closure.md`: durable closure rules and dated issue disposition.
- `docs/TESTING.md`: reusable test procedures; a checklist is not evidence that a run passed.
- Product/contract documents: decisions, proposals, and feature behavior; do not use them as live issue trackers.

Update these artifacts together when a milestone or issue disposition materially changes. Avoid duplicating full mutable backlogs in README files.
