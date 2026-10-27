# Technology Stack and Architecture — AG Manage

## 1. Recommended stack

### Backend
- **Laravel** as the primary web framework.
- Use a currently supported Laravel release compatible with the installed PHP version.
- Use Laravel's supported authentication starter kit rather than building password handling from scratch.
- Use Form Requests or equivalent validation, policies/gates for authorization, Eloquent models, migrations, and database transactions.

### Frontend
- **Blade** for server-rendered pages.
- **Tailwind CSS** for responsive styling and design tokens.
- Lightweight JavaScript for interactions.
- Alpine.js or Livewire may be added if the implementation has a clear need; do not add both automatically.
- **Chart.js** for dashboard and report visualizations.
- Lucide icons or another consistent icon set.

### Database
- **MySQL** for persistent application data.
- Use foreign keys, indexes, appropriate constraints, and migrations.
- SQLite may be used for fast tests only if the project's tests also cover relevant MySQL behavior and constraints.

### Development tools
- Composer
- Node.js and npm
- Git
- Laravel test tooling / PHPUnit or Pest depending on the chosen starter
- Laravel Pint or the formatter configured by the project

## 2. Architecture principles

Use a straightforward modular Laravel application. Avoid premature microservices and a separate frontend SPA for the first version.

Suggested layers:
- **Routes:** define web routes and middleware.
- **Controllers:** handle HTTP requests and responses; keep them thin.
- **Form Requests:** validate input and authorize when appropriate.
- **Policies:** enforce record ownership and permissions.
- **Models:** represent persisted domain entities and relationships.
- **Services/actions:** encapsulate financial operations and multi-record updates.
- **Views/components:** render consistent UI patterns.
- **Tests:** verify domain rules, authorization, and important UI flows.

Suggested domain services:
- `WalletBalanceService`
- `TransactionService`
- `TransferService` (may be combined with `TransactionService` if simpler)
- `BudgetCalculationService`
- `ReportService`
- `CsvExportService`

Do not create an abstraction for every trivial operation. Keep the architecture easy for one developer to understand.

## 3. Data model overview

Core entities:
- User
- Wallet
- Category
- Transaction
- Budget
- SavingsGoal
- GoalContribution
- RecurringTransaction (later phase)
- Notification (later phase)

Use explicit relationships and ownership:
- User has many wallets, categories, transactions, budgets, and savings goals.
- Wallet belongs to user.
- Transaction belongs to user and references its wallet; transfer representation must also reference the destination wallet.
- Category belongs to a user or is a system default with an explicit, safe ownership model.
- Budget belongs to user and category and identifies a monthly period.
- SavingsGoal belongs to user and has many contributions.
- GoalContribution belongs to a savings goal and user; source wallet is optional if the business rules support it.

For transactions, choose one clear representation for transfers. Options include one transaction record with source and destination wallet references, or a transfer parent with linked ledger entries. Prefer the simplest representation that preserves atomicity and auditability. Document the choice before implementing.

## 4. Money and balance handling

- Store whole IDR amounts as integer values if fractional rupiah is not required.
- Use appropriate database integer capacity for expected balances.
- If fractional amounts are required, use fixed-precision `DECIMAL`, not `FLOAT` or `DOUBLE`.
- Keep the raw numeric amount separate from formatted display strings.
- Centralize balance calculations and period aggregates.
- Use database transactions for operations that update multiple records.
- Ensure transfer source and destination are different wallets owned by the same user.
- Make transaction edits and deletes safe and idempotent where appropriate.
- Do not count opening balances as income.
- Do not count transfers as income or expense.

## 5. Route and page organization

Suggested routes (adapt to Laravel conventions):
- `/dashboard`
- `/transactions`
- `/wallets`
- `/budgets`
- `/goals`
- `/reports`
- `/settings`

Authentication routes should be supplied by the selected starter kit. Apply authentication middleware to private routes and policies/ownership checks to every record-specific action.

Use named routes instead of hard-coded URL strings in views where practical.

## 6. Frontend implementation

- Keep shared layouts and reusable components in predictable locations.
- Use Blade components for buttons, inputs, cards, badges, empty states, and flash messages.
- Store design tokens in Tailwind configuration or CSS variables.
- Use Chart.js with a textual summary for accessibility.
- Use responsive tables or mobile-specific transaction cards.
- Format dates and Rupiah values through reusable helpers/presenters, not repeated ad hoc code.

## 7. Testing strategy

Minimum automated coverage:
- Registration/login authorization behavior as supported by the starter.
- User A cannot view, edit, delete, or export User B's records.
- Opening balance handling.
- Income and expense balance calculations.
- Transfer calculations and report neutrality.
- Transaction update and delete correctness.
- Budget period calculations and thresholds.
- Savings goal progress.
- CSV export filters and user isolation.
- Validation for invalid amount, category, wallet, and transfer source/destination.

## 8. Dependency policy

- Check the actual repository and installed versions before adding dependencies.
- Prefer maintained packages with clear documentation.
- Avoid adding packages for features that can be implemented safely with the existing stack.
- Pin and commit lockfiles as appropriate.
- Never upgrade the framework or major dependencies blindly during feature work.
- Document new dependencies and why they are needed.
