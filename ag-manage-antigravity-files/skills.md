# AI Coding Agent Instructions — AG Manage

These instructions apply to Antigravity or another coding agent working in this repository.

## 1. Required reading

Before coding, read:
1. `prd.md`
2. `task.md`
3. `styleguide.md`
4. `techstack.md`
5. `security.md`
6. `readme.md`

Treat these files as the project contract. If repository code conflicts with them, inspect the situation and explain the conflict before making a risky change.

## 2. Work process

1. Inspect the repository, framework version, directory structure, current git status, and existing features.
2. Do not overwrite or delete existing work without understanding it.
3. Select the next incomplete task in `task.md`.
4. State a concise plan for non-trivial changes.
5. Implement one coherent slice at a time.
6. Run relevant tests and checks after changes.
7. Inspect the actual result, including responsive behavior when UI changes.
8. Update `task.md` only for work that is truly complete.
9. Update `readme.md` when setup, commands, dependencies, features, or limitations change.
10. Report changed files, commands run, test outcomes, and any known issues.

## 3. Coding standards

- Follow Laravel conventions and the existing repository style.
- Keep controllers thin and financial business logic centralized in services/actions.
- Validate all untrusted input on the server.
- Use policies/gates or equivalent authorization checks for user-owned records.
- Use named routes and reusable Blade components.
- Prefer readable, explicit code over clever abstractions.
- Use clear, consistent names in English for code identifiers; UI text should be Indonesian.
- Avoid duplicated financial formulas.
- Do not use floating-point arithmetic for monetary values.
- Use database transactions for transfers and multi-record financial changes.
- Add tests for financial calculations and authorization.
- Avoid unnecessary dependencies and overengineering.

## 4. UI quality rules

- Follow `styleguide.md`.
- Every visible button, link, form, filter, and menu must perform its stated action.
- Do not leave fake or placeholder controls in a completed feature.
- Include loading, empty, success, validation-error, and failure states as appropriate.
- Make layouts responsive and keyboard-accessible.
- Keep transaction entry fast and understandable.
- Use realistic demo data only through development seeders.
- Do not use a chart or decorative graphic as a substitute for correct data.

## 5. Scope control

- Implement the PRD in priority order: P0 before P1, P1 before P2.
- Do not add bank integrations, AI chat, OCR, investment tools, or other out-of-scope features without explicit approval.
- If a requirement is ambiguous, choose the safest minimal implementation and document the assumption; ask a question if the ambiguity affects financial correctness or security.
- Do not silently change the product scope or technology stack.
- Do not claim a feature is implemented if it is only mocked or visually represented.

## 6. Commands and environment

- Inspect package scripts and framework versions before assuming commands.
- Never overwrite `.env` or expose credentials.
- Never run destructive database commands against a non-test environment without explicit confirmation.
- Use migrations rather than manually changing production schema.
- Do not run broad cleanup commands or delete user data to solve a local issue.
- If a command fails, inspect the cause instead of repeatedly running it blindly.

## 7. Completion report format

At the end of each meaningful task, report:
- **Implemented:** concise list.
- **Files changed:** paths.
- **Validation:** exact test/build/lint commands run and whether they passed.
- **Known issues:** anything not completed or not verified.
- **Next task:** the next unchecked item in `task.md`.

Never invent test results, screenshots, URLs, or successful actions.
