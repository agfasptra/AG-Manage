# Implementation Tasks — AG Manage

This file is the implementation checklist for the coding agent. Keep it updated as work is completed. Do not mark a task complete unless the implementation exists and has been checked.

Status legend: `[ ]` not started · `[-]` in progress · `[x]` completed · `[!]` blocked

## Phase 0 — Inspect and plan
- [x] Inspect the existing repository before changing files.
- [x] Identify current framework, runtime, package manager, and database configuration.
- [x] Read `prd.md`, `techstack.md`, `styleguide.md`, `skills.md`, and `security.md`.
- [x] If an app already exists, preserve working functionality and adapt the plan rather than replacing it blindly.
- [x] Record any conflicts between repository state and project documents.
- [x] Create a short implementation plan before major changes.

## Phase 1 — Project foundation
- [x] Set up Laravel application structure using a stable version compatible with the installed PHP runtime.
- [x] Configure `.env.example` without real credentials.
- [x] Configure database connection and verify migrations can run.
- [x] Add supported authentication starter kit (Laravel Breeze — Fase 3).
- [x] Create shared responsive layout, navigation, typography, buttons, forms, cards, tables, alerts, and modal patterns.
- [x] Add consistent Indonesian validation and flash messages.
- [x] Add test setup and confirm tests can run.

## Phase 2 — Database and domain models
- [x] Create users/profile-related fields only when needed.
- [x] Create wallets migration/model with owner, type, opening balance, and status.
- [x] Create categories migration/model with type and ownership/default-category handling.
- [x] Create transactions migration/model with type, amount, date, wallet references, category, note, and timestamps.
- [x] Define a robust transfer representation that supports source and destination wallets atomically.
- [x] Create budgets migration/model with category and month/year period.
- [x] Create savings goals and goal contributions migrations/models.
- [x] Add foreign keys, indexes, uniqueness constraints where appropriate, and safe deletion behavior.
- [x] Add factories and development seeders with realistic dummy data.
- [x] Verify migrations roll back and run cleanly.

## Phase 3 — Financial domain logic
- [x] Implement a single service/class for wallet balance calculations.
- [x] Implement transaction creation with server-side validation and authorization.
- [x] Implement transaction update that reverses old effects and applies new effects safely.
- [x] Implement transaction deletion with correct balance impact.
- [x] Implement transfer creation/update/deletion atomically.
- [x] Ensure transfers do not count as income or expense in reports.
- [x] Implement period-based income, expense, and net cash-flow calculations.
- [x] Add tests for opening balances, income, expense, transfers, edits, deletions, and negative balances.
- [x] Check exact numeric handling for all monetary values.

## Phase 4 — Core pages (Dompet & Kategori — Fase 4)
- [x] Build Dashboard page with period filter, summary cards, charts, budget progress, goals, and recent transactions.
- [x] Build Transactions list with search, filters, sort, pagination, and empty states.
- [x] Build create/edit transaction forms with appropriate fields per type.
- [x] Build Wallet list and detail pages.
- [x] Build create/edit/archive wallet flows.
- [x] Build transfer form and confirmation state.
- [x] Build category management and default categories.
- [x] Ensure all views use the shared design system and responsive behavior.


## Phase 5 — Budgets and savings goals
- [x] Build monthly budget CRUD.
- [x] Calculate spending per category and period from valid expense transactions.
- [x] Show remaining budget and percentage used.
- [x] Add 80% and 100% threshold visual warnings.
- [x] Build savings goal CRUD and goal detail.
- [x] Build contribution create/list flow.
- [x] Test progress and remaining target calculations.
- [x] Clearly distinguish goal contributions from actual expenses or transfers.

## Phase 6 — Reports and insights
- [x] Build report page with income, expense, net cash flow, category breakdown, and month comparison.
- [x] Add date-range and relevant filters.
- [x] Add CSV export with safe output handling.
- [x] Add simple rule-based insights only when there is sufficient data.
- [x] Verify all aggregates match transaction records and exclude transfers from income/expense totals.

## Phase 7 — Security and quality
- [x] Verify authorization and ownership checks for every read/write endpoint.
- [x] Add tests proving one user cannot access another user's records.
- [x] Verify CSRF, validation, mass-assignment protection, and safe error handling.
- [x] Ensure no secrets are committed and `.env` is ignored.
- [x] Check deletion behavior for wallets/categories/goals with related records.
- [x] Test mobile layout and keyboard accessibility.
- [x] Test loading, empty, success, validation, and error states.
- [x] Run formatter/linter and all automated tests.
- [x] Fix all critical errors before calling the app ready.

## Phase 8 — Documentation and handoff
- [x] Update `README.md` with actual setup steps and commands.
- [x] Document environment variables without including secret values.
- [x] Document implemented features and known limitations.
- [x] Confirm a fresh developer can install, migrate, seed, test, and run the application.
- [x] Summarize completed work, tests run, known issues, and next recommended tasks.

## Definition of done
A task is done only when code exists, relevant tests pass, the UI works at expected screen sizes, and security/financial correctness has been considered. Never claim tests passed unless they were actually run.
