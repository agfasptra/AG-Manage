# Security Requirements — AG Manage

AG Manage handles private financial records. Security and data integrity are release requirements, not optional polish.

## 1. Authentication and sessions

- Use Laravel's supported authentication starter kit or another established implementation.
- Never implement password storage or verification manually.
- Store passwords using the framework's password hashing facilities.
- Use secure session settings appropriate to the deployment environment.
- Regenerate session IDs after authentication where supported by the framework.
- Apply rate limiting to login and other sensitive endpoints.
- Password reset must use a safe, time-limited token flow.
- Do not reveal whether an email address is registered in password reset responses.
- Require HTTPS in production.

## 2. Authorization and tenant isolation

Every financial record belongs to a user. The server must enforce ownership on every request.

- Scope queries to the authenticated user wherever possible.
- Use policies/gates or equivalent server-side authorization for individual records.
- Never trust a user ID, wallet ID, category ID, goal ID, or transaction ID supplied by the client without verifying ownership.
- Prevent insecure direct object reference (IDOR) vulnerabilities.
- A user must not view, update, delete, or export another user's data by modifying URL parameters, form fields, or request payloads.
- For transfers, both wallets must belong to the same authenticated user.
- For a category, goal, or budget reference, verify ownership and compatibility.
- Include cross-user access tests for all core entities.

## 3. Input validation and web protections

- Validate all input on the server even when client-side validation exists.
- Use Laravel's CSRF protection for state-changing web forms.
- Rely on parameterized ORM/query builder operations; do not concatenate untrusted input into SQL.
- Escape untrusted content in rendered HTML.
- Validate and normalize dates, amounts, categories, notes, and filters.
- Apply length limits to text fields.
- Reject negative or zero transaction amounts unless a specific documented use case permits them.
- Prevent mass assignment by defining allowed fillable fields or equivalent explicit assignment.
- Do not expose stack traces, SQL errors, secrets, or sensitive records to end users in production.
- Set appropriate security headers where practical.

## 4. Financial integrity

- Use integer or fixed-precision decimal storage for money; never use binary floating-point for financial calculations.
- Use database transactions for transfer creation, updates, deletions, and any operation that modifies multiple related records.
- Validate that source and destination wallets differ.
- Ensure both wallets belong to the same user.
- Ensure retries or double submissions cannot accidentally create duplicate transfers.
- Ensure transaction edits/deletes reverse prior effects correctly.
- Keep transfer records neutral to income/expense totals.
- Keep opening balances out of income reports.
- Centralize balance formulas and test them.
- Use constraints and foreign keys where suitable.
- Do not silently round or modify a user's entered amount.
- Define and test negative-balance behavior.
- Avoid trusting a client-submitted balance; derive balances from authoritative server-side records.

## 5. Secrets and configuration

- Never commit `.env`, credentials, API keys, database passwords, session secrets, or private keys.
- Keep `.env.example` limited to safe placeholder values.
- Ensure `.env` is ignored by Git.
- Do not log passwords, tokens, full financial records, or other unnecessary sensitive data.
- Use environment variables or a managed secret store for production secrets.
- Do not place secrets in frontend JavaScript or public assets.
- Do not send financial data to third-party services unless the user explicitly enables the feature and the data flow is documented.

## 6. CSV export safety

- Export only the authenticated user's authorized records.
- Apply active filters consistently.
- Prevent CSV formula injection: escape or neutralize cells beginning with characters that spreadsheet software may interpret as formulas, such as `=`, `+`, `-`, or `@`, while preserving expected numeric fields correctly.
- Use safe, predictable filenames.
- Do not include secrets or unnecessary profile data in exports.
- Test export authorization and malicious text values.

## 7. Privacy and data minimization

- Collect only the information needed for the product.
- Do not ask users for bank passwords, PINs, full payment-card details, or e-wallet credentials.
- Avoid logging transaction notes or amounts unless necessary for debugging.
- Provide a clear account/data deletion strategy before production release.
- Backups and logs must be protected with access controls.
- Do not use real personal financial records in demo seeders or screenshots.

## 8. Error handling and dependencies

- Show users concise, non-sensitive error messages.
- Log enough technical detail for debugging without leaking financial data or secrets.
- Keep dependencies updated through deliberate, tested changes.
- Review new packages before installation.
- Do not disable framework security middleware to make a feature work.

## 9. Production readiness checklist

Before deployment:
- [ ] Debug mode is disabled (set `APP_DEBUG=false` in production environment).
- [ ] HTTPS is configured (force HTTPS via web server / Cloud proxy).
- [x] Environment secrets are secure and not committed (.env in .gitignore, clean .env.example).
- [x] Authentication and rate limits are configured (Laravel Breeze & Throttle middleware).
- [x] Authorization checks exist for every user-owned resource (Policies & Gate on all controllers).
- [x] Cross-user access tests pass (SecurityAndQualityTest with 28 automated checks).
- [x] Financial calculations and transfer tests pass (atomic transactions, bcmath precision).
- [x] CSV export is tested for ownership and formula injection (UTF-8 BOM, sanitized prefix).
- [x] Database backups and restore procedures are considered.
- [x] Error pages do not reveal internal details (branded Indonesian 403, 404, 500 views).
- [x] Dependencies and runtime versions are supported (PHP 8.3, Laravel 13, MySQL, Node v24).
