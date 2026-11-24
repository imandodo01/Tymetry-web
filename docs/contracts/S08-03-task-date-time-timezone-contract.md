# S08-03 — Task Date, Time, and Timezone Contract

**Status:** Core temporal semantics accepted by maintainer; remaining implementation-level boundaries are documented as open.  
**Tracking:** Standalone S08-03 temporal contract reference; former planning issue retired as not planned.  
**Work Session:** TYM-WS-20261119-001  
**Scope:** Temporal semantics for the existing Laravel Web client and the native Android client, and the future shared Task wire representation. This document does not implement an API, persistence migration, reminder, or timezone-preference UI.

## 1. Purpose and contract boundary

This document defines how Task temporal values are represented, serialized, parsed, retained, and displayed across Web and Android. It deliberately does not redefine Task identity, lifecycle, ownership, status, priority, revision, API authentication, or synchronization behavior. Those belong to S08-02, S08-04, and the related cross-client work.

The contract distinguishes four different concepts:

1. **Calendar date** — a year/month/day on a calendar, without a time or timezone.
2. **Wall-clock time** — a clock reading such as 09:30, without a date, offset, or timezone.
3. **Instant** — one unique point on the global timeline, represented using UTC.
4. **Timezone** — a named rule set used to interpret calendar context and render instants, not a property to append indiscriminately to every temporal value.

Never convert one category into another implicitly.

## 2. Decisions already approved

The Sprint 08 source record contains maintainer-approved decisions dated 2026-11-18. This deliverable treats them as decisions, not new proposals:

- Every account has one explicit canonical IANA timezone preference.
- The default timezone for newly created accounts is **`Asia/Jakarta`**. The account owner may change it later.
- The canonical API field name is **`time_zone`**.
- A device-local timezone may be used to display actual instants when explicitly selected, but must not silently replace or mutate the account timezone preference.
- Due date remains date-only. Due time remains wall-clock metadata. Neither is shifted when the timezone changes.
- UTC instants remain the same instant when displayed in a different timezone.
- **`due_time` is informational metadata only.** It does not affect ordering, dashboard grouping, reminder behavior, or notification scheduling.

These decisions do not authorize an implementation change to `APP_TIMEZONE`, account schema, timezone preference UI, API, or reminder subsystem.


### Maintainer confirmation — 2026-11-20

The maintainer confirmed that the account calendar should follow their calendar context, using `Asia/Jakarta` as the default account timezone. This confirms the existing new-account default in this contract; it does not authorize changing Laravel's `APP_TIMEZONE`, migrating existing accounts, or changing UTC instant storage.

The maintainer also confirmed that the Android client remains in the existing `imandodo01/Tymetri` repository; there has been no repository move or replacement. The Android source baseline and its current limitations remain as recorded in section 10. No Android code change is included in S08-03.

No staging environment is available. Real SMTP delivery and manual authenticator-app enrollment therefore remain unverified and must not be represented as acceptance evidence for this contract.

## 3. Field semantics and representation

| Field / concept | Semantic type | Canonical wire representation | Authority and interpretation |
|---|---|---|---|
| `due_date` | Nullable calendar date | `YYYY-MM-DD` or `null` | No time, offset, or timezone. Preserve the calendar components exactly. |
| `due_time` | Nullable wall-clock time | `HH:mm` (24-hour) or `null` | Informational metadata only. No date, offset, or timezone is implied. Never UTC-convert it. |
| `completed_at` | Nullable instant | RFC 3339 UTC timestamp, e.g. `2026-11-18T02:15:00Z` or `null` | Server-authoritative for cloud Tasks. It is an instant, not a local datetime. |
| `created_at` | Instant | RFC 3339 UTC timestamp with `Z` | Server-authoritative for cloud Tasks. Client clocks must not overwrite it. |
| `updated_at` | Instant | RFC 3339 UTC timestamp with `Z` | Server-authoritative for cloud Tasks. Informational freshness metadata; not a concurrency token by itself. |
| `time_zone` | IANA timezone identifier | IANA ID, e.g. `Asia/Jakarta` | Account preference. Validate against the runtime's IANA timezone database. Not a numeric offset. |
| Device display timezone | Presentation context | Not a Task field | Account timezone by default; device-local timezone only when explicitly selected for display. Never silently writes account preference. |

### 3.1 Date-only values

- `due_date` is a calendar date, not midnight UTC and not a timestamp.
- The wire format is exactly `YYYY-MM-DD` (zero-padded Gregorian year-month-day).
- A date must round-trip unchanged regardless of account timezone, device timezone, locale, or travel.
- A client must not parse a date-only value into an instant at UTC midnight and then render it through a local timezone. This common conversion can change the visible calendar day.
- Empty/unset dates serialize as JSON `null`, not an empty string or a fabricated epoch value.
- Date-only comparisons and due-day grouping use the applicable account calendar date once the account timezone is available. Before account linking, a standalone Android screen may use its device calendar for local presentation; it must not claim that this is the account calendar.

### 3.2 Wall-clock due time

- `due_time` is a local clock reading represented as `HH:mm`, 24-hour, zero-padded.
- Valid values are `00:00` through `23:59`. Seconds and fractional seconds are not part of the canonical wire representation.
- It is independent of `due_date`; both are nullable independently because this matches the current Web data model.
- A time without a date remains informational metadata. It must not be combined with today's date, the due date, or an arbitrary timezone to manufacture an instant.
- It is not shifted on account timezone changes, device timezone changes, travel, DST transitions, or locale changes.
- It must not be used for sorting, dashboard grouping, overdue calculation, notification scheduling, or reminder delivery.

### 3.3 Instants and timestamps

- Cloud `created_at`, `updated_at`, and `completed_at` represent instants.
- Their canonical serialized form is RFC 3339 UTC with the literal `Z` suffix, for example `2026-11-18T02:15:00Z`.
- The server is the authority for cloud timestamps. Client local wall clocks must not overwrite these fields.
- A client parses the timestamp as an instant, retains that instant, and formats it for display in the selected display timezone. Display conversion must never mutate the stored/canonical instant.
- Equivalent offsets may denote the same instant, but emitters must normalize API timestamps to `Z` for consistency. Consumers should parse valid RFC 3339 offsets correctly rather than treating their text as local time.
- Reject malformed or timezone-less datetime strings at a future API boundary; do not guess the missing timezone.
- `updated_at` alone is not a revision, ETag, ordering-of-edits, or conflict-resolution token. Use the separately defined S08-02/S08-04 revision contract.

### 3.4 Account timezone

- `time_zone` identifies the account's canonical IANA timezone, not the current device's numeric UTC offset.
- The approved initial default for new accounts is `Asia/Jakarta`; explicit user changes update the account preference only through a separately scoped product flow.
- Do not infer or change the account preference from IP geolocation, browser timezone, Android device timezone, travel, or an OS timezone change.
- Use a named IANA rule set (for example, `America/New_York`), not a fixed offset such as `UTC-05:00`, because offsets do not encode historical/future DST rules.
- Account timezone is used for account-calendar interpretation (for example, determining which date is “today”) and default rendering of actual instants.
- A device may explicitly choose local-time display of instants. That is a presentation override only and must not change account-level date grouping or saved account preference without an explicit separate decision.
- Invalid or unknown IANA identifiers must be rejected at the future account settings/API validation boundary. Do not silently fall back to UTC or a device timezone after an invalid preference; return an actionable validation error.

## 4. Web and Android mapping

### 4.1 Laravel Web — current implementation facts

Remote source inspection of `imandodo01/Tymetry-web` confirms:

- `todos.due_date` is a nullable SQL `DATE`.
- `todos.due_time` is a nullable SQL `TIME`.
- `completed_at` and Laravel `created_at`/`updated_at` are SQL `dateTime` columns.
- The create/edit form uses HTML `input type="date"` and `input type="time"`.
- `TodoRequest` validates `due_date` as nullable `date` and `due_time` as nullable `date_format:H:i`.
- `config/app.php` currently reads `APP_TIMEZONE` and defaults to `UTC`. This is current application configuration, not a decision to change it.
- `due_time` is not used by the current smart-order query or dashboard due-date grouping.

The database types establish storage shape but do not by themselves define API serialization or account-timezone behavior. In particular, SQL `DATE`/`TIME` values must remain date-only/time-only at the application boundary; do not pass either through a timezone conversion.

**Ordering implementation status at the inspected base SHA:** the current `main` version of `Todo::scopeSmartOrder()` still compares `due_date` with database `CURRENT_DATE` (Todo model blob `33d659d1e3a1f693b0b0287eea76ecda5dceb110`). A separate, still-open PR [#35](https://github.com/imandodo01/Tymetry-web/pull/35) at head `a5b2c3b6a6108f1a81629377586337a24fe4620c` proposes replacing this with `now()->toDateString()`, which uses the configured Laravel application timezone. That PR is not part of this document's base `main` SHA and must not be described as merged or deployed. Even if PR #35 is integrated, it aligns ordering with `APP_TIMEZONE`; it does not by itself implement the approved account-level `time_zone` preference. Account-timezone-aware date grouping remains a separate implementation step.

### 4.2 Native Android — current implementation facts

Remote source inspection of `imandodo01/Tymetri` confirms:

- The current Room `Todo` entity contains only an auto-generated integer `id`, `title`, nullable `description`, and Boolean `isDone`.
- Room database version is 1.
- The inspected add screen currently captures title only.
- No due date, due time, completion timestamp, creation/update timestamp, or account timezone is present in the current Todo entity.
- No remote API/synchronization/account-linking behavior is represented in this source baseline.

Therefore this contract is a future cross-client boundary, not a claim that Android currently implements these fields. Do not add Room columns, migrations, UI controls, or local timestamp fields in S08-03.

### 4.3 Client responsibility table

| Concern | Web | Android before account linking | Android after account linking (future contract) |
|---|---|---|---|
| Due date/time input | Existing HTML date/time controls | Not currently present | Must use date-only and wall-clock semantics above |
| Due date/time persistence | Existing server database | Not currently present | Preserve exact date/time values; never UTC-shift |
| Cloud timestamps | Server-generated | Not applicable | Server values are authoritative; parse as instants |
| Local-only timestamps | Not a separate contract here | No current timestamp fields observed | Any future local metadata must be separately named/defined; do not pretend it is server time |
| Account timezone | No account field observed in Web source baseline | No account identity/preference | Read account `time_zone`; do not silently replace it with device timezone |
| Display timezone | Existing app behavior is based on current Laravel timezone configuration | Device locale/timezone is only local presentation context | Account timezone by default; device timezone only as explicit presentation override |

## 5. Serialization and interpretation examples

The examples below define the proposed future wire representation. They are not implemented endpoints.

### 5.1 Canonical Task temporal fragment

```json
{
  "due_date": "2026-11-20",
  "due_time": "09:30",
  "completed_at": null,
  "created_at": "2026-11-18T07:00:00Z",
  "updated_at": "2026-11-18T07:00:00Z",
  "time_zone": "Asia/Jakarta"
}
```

Interpretation:
- Due date is November 20, 2026 in the calendar, not an instant.
- Due time is a 09:30 wall-clock label, not a scheduled instant.
- Created/updated values identify instants and may render differently in a selected timezone.
- `time_zone` supplies the account's calendar/display context. It is not appended to `due_date` or `due_time`.

### 5.2 Nullability and independent values

```json
{
  "due_date": null,
  "due_time": "08:15",
  "completed_at": null
}
```

This is valid under the existing Web nullability shape. The value `08:15` remains informational; it must not be interpreted as “today at 08:15”.

### 5.3 Same instant, different display timezones

Canonical instant:

```json
{ "created_at": "2026-11-18T02:15:00Z" }
```

Possible displays:
- In `Asia/Jakarta`: `2026-11-18 09:15` (UTC+07:00).
- In `America/New_York` on that date: `2026-11-17 21:15` (UTC−05:00).

Both displays refer to exactly the same instant. The second display must not rewrite the underlying value or account timezone.

### 5.4 Timezone change does not rewrite task temporal values

Before account preference change:

```json
{
  "due_date": "2026-11-20",
  "due_time": "09:30",
  "created_at": "2026-11-18T02:15:00Z",
  "time_zone": "Asia/Jakarta"
}
```

After changing account timezone to `America/New_York`:

```json
{
  "due_date": "2026-11-20",
  "due_time": "09:30",
  "created_at": "2026-11-18T02:15:00Z",
  "time_zone": "America/New_York"
}
```

Only the timezone preference changes. Date and wall-clock values remain identical; the instant remains identical but its displayed local date/time may differ.

### 5.5 Date boundary / account “today”

At one moment, UTC time is `2026-11-20T01:00:00Z`:
- In `Asia/Jakarta`, local time is `2026-11-20 08:00`; account date is November 20.
- In `America/Los_Angeles`, local time is `2026-11-19 17:00`; account date is November 19.

This demonstrates why account date grouping must use the account IANA timezone rather than the database server's date, UTC date, or whichever device happens to request the page.

### 5.6 DST gap and overlap

For a timezone with a daylight-saving spring-forward transition, some local wall-clock times do not occur. During a fall-back transition, some local wall-clock times occur twice.

Under this contract:
- A due time such as `02:30` remains the metadata string `02:30`; it is not invalidated merely because a hypothetical DST zone skips that wall-clock time on a particular date.
- A due time such as `01:30` remains `01:30` even if that clock time occurs twice on a particular date.
- No client may choose the “earlier” or “later” offset, move the time forward, or generate a UTC instant for these values.
- Because due date/time do not currently define a scheduled instant, DST gap/overlap resolution is not applicable to the current due metadata contract.

If a future, separately approved reminder feature combines a date, wall-clock time, and timezone into a scheduled instant, it must introduce explicit policy for nonexistent and ambiguous local times (reject, shift, earlier offset, later offset, or ask the user). That policy remains undecided and is intentionally outside this deliverable.

### 5.7 Leap day and calendar validity

- `2028-02-29` is a valid date-only value.
- `2027-02-29` is invalid and must fail date validation.
- Valid time boundaries include `00:00` and `23:59`; `24:00` and `12:60` are invalid.
- A valid timezone identifier is validated separately from due date/time; it is not embedded in either string.

## 6. Timezone-change and lifecycle rules

1. Changing the account timezone affects account-local rendering of instants and calculations that explicitly depend on the account calendar (such as “today”).
2. Changing the account timezone does not mutate `due_date`, `due_time`, `created_at`, `updated_at`, or `completed_at` values.
3. Changing the device timezone does not change the account timezone.
4. A device-local display override may change how an instant is formatted, but does not change the account's calendar bucket or serialized value.
5. Cloud `created_at`, `updated_at`, and `completed_at` remain server-authoritative. A client must not backdate or overwrite them with its device clock.
6. The semantics of timestamps for standalone, local-only Android records are not established by the current implementation. If/when such fields are introduced, their naming, clock source, timezone handling, and conversion to cloud timestamps must be designed explicitly. A local timestamp must not be mislabeled as a server-authoritative `created_at` or `updated_at`.
7. Changing the operating system timezone or travelling across zones must not reinterpret the same stored due date/time values.

## 7. Validation and error behavior at future boundaries

This section states contract expectations for future validation; it does not implement validation.

| Input | Expected result |
|---|---|
| `due_date: "2026-11-20"` | Accept as calendar date |
| `due_date: null` | Accept as unset |
| `due_date: "2026-02-30"` | Reject as invalid calendar date |
| `due_time: "09:30"` | Accept as wall-clock time |
| `due_time: null` | Accept as unset |
| `due_time: "9:30"` or `"09:30:00"` | Reject at canonical API boundary; normalize only in an explicit UI-input adapter if product/API policy allows |
| `due_time: "24:00"` or `"12:60"` | Reject |
| `created_at: "2026-11-18T02:15:00Z"` | Accept as an instant |
| `created_at: "2026-11-18T02:15:00+07:00"` | Parse as an instant, normalize to UTC when emitted |
| `created_at: "2026-11-18T02:15:00"` | Reject: timezone/offset missing |
| `time_zone: "Asia/Jakarta"` | Accept as IANA timezone ID |
| `time_zone: "UTC+7"` or `"GMT+7"` | Reject as non-IANA shorthand |
| unknown IANA timezone identifier | Reject with a validation error; do not silently fall back |

For future API consumers, validation errors should identify the field and expected format without disclosing implementation internals. Exact HTTP status and error envelope belong to S08-04.

## 8. DST and timezone database considerations

- The IANA timezone identifier, not a cached numeric offset, is the durable timezone preference.
- Timezone rules can change when the IANA database is updated. Clients and server should use maintained timezone databases; the server is authoritative for account-calendar calculations.
- Historical and future instant rendering can therefore reflect the applicable IANA rule version. The canonical UTC instant remains unchanged.
- A future contract may need to expose/update timezone-data version for deterministic historical rendering across old offline clients. This is not required for current due-date/time metadata and is unresolved for later synchronization design.
- No DST arithmetic is performed for date-only or wall-clock metadata in this contract.

## 9. Cross-client conformance scenarios

These scenarios are intended for later implementation/acceptance tests. No application code was changed by S08-03.

| ID | Setup / action | Expected result |
|---|---|---|
| DT-01 | Serialize due date `2026-11-20` in Web, read it in Android under UTC−05:00 | Android still displays/selects calendar date `2026-11-20`; no day shift |
| DT-02 | Serialize due time `09:30`; read it on a device in UTC+09:00 | Android displays `09:30`; no offset conversion |
| DT-03 | Round-trip null due date and non-null due time | Null date remains null; time stays metadata and is not turned into an instant |
| DT-04 | Round-trip instant `2026-11-18T02:15:00Z` | Parsing and re-serialization represent the same instant; output is UTC `Z` form |
| DT-05 | Display the same instant in `Asia/Jakarta` and `America/New_York` | Local formatted text differs appropriately; stored instant does not |
| DT-06 | Change account timezone from `Asia/Jakarta` to `America/New_York` | `time_zone` changes; due date/time and historical instant strings remain unchanged |
| DT-07 | Change device timezone while account timezone remains unchanged | Account preference and account calendar grouping remain unchanged |
| DT-08 | Evaluate one instant near midnight UTC in two account timezones | “Today” follows account-local date, not UTC/database/device date |
| DT-09 | Parse an offset timestamp such as `2026-11-18T02:15:00+07:00` | Parse to the equivalent instant; normalize emitted value to `2026-11-17T19:15:00Z` |
| DT-10 | Submit a timestamp with no offset | Reject; no implicit account/device timezone inference |
| DT-11 | Submit invalid `2027-02-29`, `24:00`, or an unknown timezone ID | Reject each invalid value at its respective validation boundary |
| DT-12 | Set due time to a DST-gap or DST-overlap wall-clock value with no reminder behavior | Preserve the raw `HH:mm` value; do not resolve it into an instant |
| DT-13 | Standalone Android record exists before account linking | Do not invent a cloud timestamp or assume account timezone; local timestamp semantics remain a separate future decision |
| DT-14 | Account timezone changes after a Task has `completed_at` | Preserve completion instant; only display conversion changes |
| DT-15 | A date is `2028-02-29` | Preserve as a valid date-only value across clients |

## 10. Existing evidence and source references

All refs below are remote source inspection, not local checkout or runtime execution.

### Web repository

Repository: [imandodo01/Tymetry-web](https://github.com/imandodo01/Tymetry-web)  
Remote `main` source snapshot used for this task: **`6b17019dead029acc1b4e014ccb3e28ead52b751`** (tree `6b17019dead029acc1b4e014ccb3e28ead52b751`).

| Source | Blob SHA | Evidence |
|---|---|---|
| `database/migrations/2026_05_21_043231_create_todos_table.php` | `1661d9bbd39605361b50ec36869217f46dc2ad85` | SQL `DATE`, `TIME`, `dateTime`, and Laravel timestamps |
| `app/Models/Todo.php` | `33d659d1e3a1f693b0b0287eea76ecda5dceb110` | Due metadata fields; due status uses date-only concept; due_time not used in smart ordering |
| `app/Http/Requests/TodoRequest.php` | `99dc24dfbf5ec79474a55652848a67d6a9501dfd` | Date and `H:i` request validation |
| `resources/views/todo/_form.blade.php` | `8e700db688ad9e75437641e45b2893f9a06f2eea` | HTML date/time input serialization and presentation |
| `config/app.php` | `28be821aae5a85b26ac508ad331afed18223f849` | `APP_TIMEZONE` env use; default UTC |
| `app/Http/Controllers/Web/DashboardController.php` | `63e781c02e3310b5a05fa2c919f45a1bb670a16d` | Dashboard “today” uses Carbon calendar date |
| `app/Http/Controllers/Web/Todo/TodoController.php` | `ba758b60eb184ecb230b31600015dfacd36533a8` | Due date display and request flow; no due_time ordering behavior |
| `public/assets/js/todo.js` | `d4f37e0cb187c7b3538969221cdf4ba4154f2d36` | Date input sends selected date string unchanged in the AJAX request |
| `tests/Feature/TodoDueTimeTest.php` | `c492e3829337b977f0ea9a5391e839e10e0e0c44` | Existing due-time persistence and format tests |
| `tests/Feature/DashboardDueTodayTest.php` | `cc5f725bf22ef8e2288b792b746dd6abd0386094` | Dashboard uses date-based buckets; completed tasks excluded |

Relevant prior Web baseline note: `docs/README.md` blob `28152e412087f5870f97b23acb976c65139e629f` and `docs/ARCHITECTURE.md` blob `877388bf2c2cb0b7260ebc070c928f5c64ca57ec`. The README's historical baseline and the M0 checkpoint should not be mistaken for the current remote HEAD.

### Android repository

Repository: [imandodo01/Tymetri](https://github.com/imandodo01/Tymetri), default branch `master`.  
Remote `master` snapshot inspected: **`8b35b1e1e4a988d78ec9302defee38b97c3b3bdf`**.

| Source | Blob SHA | Evidence |
|---|---|---|
| `app/src/main/java/com/millian/tymetri/model/Todo.kt` | `066d89dca1124351c2a683794ba61885ee5f1580` | Current Room Todo fields; no temporal properties |
| `app/src/main/java/com/millian/tymetri/data/AppDatabase.kt` | `42ffe69bba69819dc9d4bed09f9cb9850c6a9cea` | Room DB version 1 |
| `app/src/main/java/com/millian/tymetri/ui/screens/add/AddTodoScreen.kt` | `3411037a603970b879eb430733d8aebd0cd48f1d` | Current add form captures title only |
| `app/build.gradle.kts` | `0fb88360c31e699c35978672d2322bdafd42cbb7` | Kotlin/Android module target Java 11; build configuration context |

The Android source has not been changed. No Android branch was created.

### Product decision source

Notion: [Sprint 08 — Domain & Cross-Client Contracts](https://app.notion.com/p/41c549a7fe038164bb4fe5f9232a643c), inspected 2026-11-19 in this task. The S08-03 draft and maintainer decision update dated 2026-11-18 are the authoritative source for the approved account timezone, `Asia/Jakarta` default, `time_zone` field name, and non-conversion rules.

Notion: [Tymetry — Architecture & Product Decisions v0.2](https://app.notion.com/p/41c549a7fe038127b104cce8795b76d7), inspected as the architecture baseline. Product-direction statements are not evidence of shipped behavior.

## 11. Open implementation decisions — alternatives and consequences

The core decisions in section 2 are approved. The items below are implementation/product boundaries for later work; none reopens the approved date-only, wall-clock, UTC-instant, or account-timezone semantics.

| Open decision | Plausible alternatives | Consequences to evaluate before choosing |
|---|---|---|
| Standalone Android local timestamps before account linking | (A) Do not create cloud-style timestamps until account linking. (B) Add explicitly named local-only timestamps with a documented device clock source. (C) Store local events as UTC instants plus a separate local-origin marker. | A avoids false server authority but limits local audit/sort metadata. B is clearer about provenance but needs reconciliation rules. C is interoperable but device clocks may be wrong and must not be mistaken for server time. |
| Timestamp precision | (A) Normalize all wire timestamps to whole seconds. (B) Preserve fractional seconds supported by the database. | A simplifies cross-client comparisons but discards precision. B preserves more information but requires consistent parsing, serialization, and equality semantics across PHP, Android, SQL, and API layers. |
| Device-local display override persistence | (A) Apply only for the current screen/session. (B) Persist a per-device display preference. (C) Always display instants in account timezone. | A is simple but not sticky. B improves user control but introduces device-specific settings and UX. C is consistent across clients but does not offer a device-local view. None may mutate the account timezone implicitly. |
| Timezone database version coordination | (A) Let the server's IANA database be authoritative for account-calendar calculations. (B) Exchange/record tzdb versions for deterministic offline rendering. | A is simpler and keeps one authority, but old offline clients may display future instants differently after timezone-rule updates. B improves reproducibility but adds version negotiation, update, and compatibility complexity. |
| Future reminder timezone behavior | (A) Freeze the timezone/instant when a reminder is created. (B) Recalculate the scheduled instant when the account timezone changes. (C) Ask the user to resolve ambiguous/nonexistent local times at scheduling time. | A preserves the original instant. B preserves the wall-clock intent but can move the instant. C is explicit for DST gaps/overlaps but adds UI and policy complexity. This decision is required before due date/time can become a reminder instant. |
| API validation error envelope | (A) Use the existing API error envelope if one is approved in S08-04. (B) Define a dedicated field-error envelope. | Reusing an established envelope reduces client branching. A new envelope can be clearer but creates another contract clients must implement. Exact status codes and shape belong to S08-04. |
| Existing-account timezone migration | (A) Backfill all existing accounts to `Asia/Jakarta`. (B) Ask existing users to choose a timezone. (C) Use a temporary fallback until the user explicitly chooses. | A is deterministic but may change existing users' calendar grouping. B avoids assumptions but requires migration UX and a completion state. C eases rollout but creates mixed behavior and must have a clear expiry/fallback rule. The approved default for new accounts does not decide existing-account migration. |
| Web transition from global `APP_TIMEZONE` to account timezone | (A) Switch account-calendar calculations and display per account in one release. (B) Roll out account-aware behavior behind a feature flag while retaining the global default. | A avoids a long mixed-mode period but needs a complete migration and regression suite. B reduces rollout risk but creates temporary dual behavior and requires observability and a removal plan. Neither is authorized by this document alone. |

These alternatives are decision prompts, not recommendations or accepted choices. The responsible product/maintainer decision must be recorded before the relevant implementation begins.

### Explicit non-decisions

No decision is made here to:
- change `APP_TIMEZONE`;
- add an account timezone column or preference UI;
- change timestamp storage or database types;
- add Task date/time/timestamp fields to Android Room;
- combine due date/time into a timestamp;
- introduce reminder/notification behavior;
- change date ordering or dashboard grouping rules beyond clarifying their calendar context;
- implement an API, sync, or migration.

## 12. Document completeness review

| Review dimension | Result |
|---|---|
| Current Web and Android source baselines and exact source SHAs recorded | **Documented:** remote refs/SHAs recorded; local Git state and local runtime verification were unavailable |
| Temporal types and canonical representations | **Documented:** calendar date, wall-clock time, instant, and IANA timezone are distinct |
| `due_date` and `due_time` invariants | **Documented:** date-only and informational wall-clock semantics are explicit |
| Timestamp and timezone responsibilities | **Documented:** UTC instant authority, account timezone, and device display override are separated; no config changed |
| Date boundaries, timezone changes, DST gaps/overlaps | **Documented:** concrete examples and conformance scenarios are included |
| Remaining implementation decisions | **Documented:** alternatives and consequences are listed without presenting them as accepted decisions |
| Runtime implementation and cross-client verification | **Not verified:** this is a documentation-only contract; no runtime implementation is claimed |
| Sprint 08 Notion integration | **Pending:** this GitHub reference is standalone; the Notion Sprint 08 source page has not been changed by this PR |

## 13. Recommended next steps

1. Maintainer reviews the consolidated contract, especially the separation between account calendar timezone and device-local instant display.
2. Keep S08-03 limited to this date/time document; do not fold in S08-02 entity fields or S08-04 API/auth mechanics.
3. After approval, S08-04 should reference this contract verbatim for date, time, timestamp, and `time_zone` serialization and define only the transport/error/revision mechanics.
4. A later Web/Android implementation work item should add account timezone and any Android temporal fields only after schema/UX scope, migration path, local timestamp semantics, and account-linking are separately approved.
5. S08-05 should turn DT-01–DT-15 into automated/shared fixtures where feasible and add runtime tests for IANA zone/DST database behavior.
6. QA should verify the conformance scenarios against an exact implementation SHA once the relevant behavior exists. No runtime implementation is claimed as complete by this design-only work item.

## 14. Evidence status

- **Independently Executed:** None. No local build, test suite, browser session, Android build, or device test was run.
- **Evidence Reviewed:** Remote GitHub source, repository trees, source blobs, and the Notion Sprint 08 decision record were inspected and tied to the refs/SHAs listed above.
- **Not Verified:** Local worktree state, local branch isolation on maintainer machine, Web/Android builds/tests, browser/device rendering, API serialization (no API exists), account timezone validation (not implemented), and timezone-change runtime behavior.
- **Blocked:** Local Git inspection and execution were unavailable because this environment has no mounted project checkout. This does not block producing the source-based contract proposal; it limits the evidence claim.

## 15. Change log

- 2026-11-19: Initial S08-03 date/time/timezone contract proposal consolidated from the current Sprint 08 maintainer decisions and remote source inspection.
- 2026-11-20: Maintainer confirmed Asia/Jakarta as the account calendar default and confirmed the Android repository remains unchanged; core temporal semantics are accepted, while implementation boundaries remain open.
- 2026-11-24: Retired the old tracking reference and converted the document to a standalone contract; added explicit alternatives and consequences for unresolved implementation decisions.
- 2026-11-24: Synchronized the standalone contract branch with main after PR #45 merged; temporal semantics unchanged.
