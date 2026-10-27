# Product Requirements Document (PRD)
## AG Manage — Personal Finance Manager

- **Product type:** Responsive web application
- **Target users:** University students and working adults
- **Primary language:** Indonesian
- **Currency:** Indonesian Rupiah (IDR / Rp)
- **Status:** Initial product specification
- **Working product name:** AG Manage (can be renamed later)

## 1. Product overview

AG Manage is a personal finance management web application that helps users record income and expenses, manage multiple wallets, plan category budgets, track savings goals, and understand their financial habits through clear reports.

The product should feel like a polished fintech product while remaining simple enough for everyday use. It is a manual-entry finance tracker in its first version; it must not claim to connect to banks or e-wallets automatically.

## 2. Product goals

1. Let a user record an income or expense in a few clear steps.
2. Show an accurate, explainable balance for each wallet and in total.
3. Help users plan monthly spending and see budget usage.
4. Help users track savings goals.
5. Present useful reports and rule-based insights.
6. Work well on desktop, tablet, and mobile screens.
7. Protect each user's financial data from access by other users.

## 3. Non-goals for the first release

Do not implement these unless explicitly approved later:
- Direct bank or e-wallet integrations, open banking, or payment processing.
- Investment trading, portfolio management, loans, or credit scoring.
- Receipt OCR or AI chat assistant.
- Multi-currency accounts.
- Shared household accounts or team workspaces.
- Native Android/iOS applications.
- Complex financial forecasting presented as guaranteed results.

## 4. Users and core scenarios

### Primary user
A student or worker who wants to know how much money they have, where it goes, and whether their spending is within plan.

### Core scenarios
- Create an account and set up a first wallet with an opening balance.
- Record income such as salary, allowance, or freelance income.
- Record expenses such as food, transport, rent, subscriptions, and shopping.
- Transfer money between wallets without counting the transfer as income or expense.
- Set a monthly budget by category.
- Create a savings goal and record contributions.
- Review monthly reports and export transaction data.

## 5. Scope and requirements

### 5.1 Authentication and profile — P0
- Register with name, email, and password.
- Log in and log out securely.
- Password reset should use the framework's supported flow if mail is configured; otherwise show a clear setup note and never reveal whether an arbitrary email exists.
- Each user can access only their own data.
- Profile settings: display name and basic preferences.
- Use the framework's supported authentication and CSRF protections.

### 5.2 First-run setup — P0
- On first login, guide the user to create a wallet.
- Wallet fields: name, type (cash, bank, e-wallet, other), opening balance, and optional description.
- Opening balance is the starting amount at the time the wallet is created; do not treat it as income.
- Provide sensible default categories, while allowing users to add and edit their own categories.
- A user must be able to skip optional setup items.

### 5.3 Dashboard — P0
Display:
- Total current balance across active wallets.
- Total income and expenses for the selected period.
- Net cash flow for the selected period.
- Income-versus-expense chart.
- Expense breakdown by category.
- Budget progress.
- Savings goal progress.
- Recent transactions.
- A prominent “Add transaction” action.
- Period filters: current month, previous month, and custom date range where practical.

All figures must be calculated from the authenticated user's valid records and must be consistent with transaction and wallet rules.

### 5.4 Transactions — P0
Transaction types:
- Income
- Expense
- Transfer

Common fields:
- Amount
- Date
- Wallet
- Optional note
- Category for income/expense
- Created/updated timestamps

Requirements:
- Create, view, edit, and delete transactions with confirmation for destructive actions.
- Search and filter by date range, type, category, wallet, and text where practical.
- Sort by date, newest first by default.
- Validate amount is greater than zero and date is valid.
- Income and expense require a category.
- Transfer requires source wallet, destination wallet, and amount; source and destination must differ.
- Editing or deleting a transaction must correctly reverse its previous effect before applying the new effect.
- Use a database transaction for multi-record operations.
- Never count transfers as income or expense in reports.
- Do not permit a user to modify another user's transaction by changing an ID in a request.
- Prefer soft-delete or a reversible audit-friendly approach only if it can be implemented consistently; otherwise use a confirmed delete and maintain correct balance calculations.

### 5.5 Wallets — P0
- Create, view, edit, and archive wallets.
- Wallet types: cash, bank, e-wallet, other.
- Display opening balance and calculated current balance.
- Transfer between two wallets.
- Prevent deleting a wallet with transaction history unless a safe archival/migration flow is implemented.
- A wallet must belong to one user.
- Do not store bank credentials, PINs, card numbers, or e-wallet passwords.

### 5.6 Categories — P0
- Separate income and expense categories.
- Provide default categories such as Salary, Allowance, Freelance, Food, Transport, Housing, Bills, Education, Health, Shopping, Entertainment, Savings, and Other as appropriate.
- Allow users to create, rename, and deactivate custom categories.
- Prevent category deactivation from corrupting historical transactions.
- Category ownership must be enforced server-side.

### 5.7 Budgets — P1
- Create a monthly budget for an expense category.
- Fields: category, period (month/year), planned amount.
- Display spent amount, remaining amount, and percentage used.
- Warn visually at configurable thresholds; default 80% and 100%.
- Do not create an expense transaction when a budget is created.
- Define spent amount using valid expense transactions for the same user, category, and period.
- Avoid duplicate active budgets for the same user/category/month unless the product intentionally supports revisions.

### 5.8 Savings goals — P1
- Create, edit, pause, and complete a goal.
- Fields: name, target amount, optional target date, optional description.
- Track contributions with date, amount, and optional source wallet/note.
- Display total contributions, remaining amount, and progress percentage.
- Contributions are allocations for goal tracking, not expenses by themselves, unless a separate wallet transfer is explicitly recorded.
- Prevent progress from exceeding 100% visually; show any excess as a surplus if supported.

### 5.9 Safe-to-spend estimate — P1
Show an explanatory estimate based on:
`Available wallet balance - unpaid obligations entered by the user - amount allocated to savings goals/budget reserves`, but only implement this formula after the exact meaning of each deduction is defined.

For the initial version, use a transparent, simple formula and clearly label it as an estimate. Do not double-subtract money already reflected in wallet balances or count the same savings allocation twice. Explain included and excluded amounts in the UI.

### 5.10 Reports and export — P1
- Monthly income, expenses, and net cash flow.
- Expense breakdown by category.
- Comparison with the previous month.
- Filter by date range, wallet, and category where practical.
- Export transactions to CSV using safe encoding and clear column headings.
- Export must include only the authenticated user's filtered records.
- Format currency in Indonesian Rupiah and dates consistently.
- Avoid exporting secrets or unnecessary personal data.

### 5.11 Recurring transactions — P2
- Allow users to define a schedule for a future recurring transaction.
- For the initial implementation, a recurring rule may generate a draft or require confirmation; avoid background scheduling unless configured.
- Prevent duplicate generation on retries.
- Clearly distinguish a planned recurring transaction from a completed transaction.

### 5.12 Notifications and insights — P2
- In-app budget threshold warnings.
- Rule-based insight examples: “Pengeluaran makan bulan ini lebih tinggi dibanding bulan lalu.”
- Insights must be based on actual available data, avoid misleading conclusions, and handle missing comparison periods.
- No external AI service required.

## 6. Business rules and financial correctness

1. Store amounts using an exact numeric representation (integer rupiah for whole-rupiah amounts, or an appropriate fixed-precision decimal if fractional values are required). Do not use floating-point for money.
2. Define one canonical balance formula and reuse it across dashboard, wallet detail, and reports.
3. Wallet balance = opening balance + valid income - valid expenses + incoming transfers - outgoing transfers.
4. Transfer has a source and destination and is neutral to overall net worth across the user's wallets.
5. Reports must exclude transfers from income and expense totals.
6. Editing/deleting a transaction must not leave stale wallet balances or duplicate effects.
7. Use database transactions for transfer creation and other multi-row financial updates.
8. Store timestamps consistently and display dates in the user's expected local timezone; transaction dates should be date-only where appropriate.
9. Validate ownership of all referenced wallet/category/goal IDs on the server.
10. Decide whether negative wallet balances are allowed. Default: allow them but visibly indicate the balance is below zero, unless product requirements later prohibit them.
11. Do not silently modify amounts through rounding.
12. Opening balance is not counted as income in period reports.

## 7. UX requirements

- Indonesian-first interface and copy.
- Responsive layouts from mobile to desktop.
- Quick transaction form with minimal required fields.
- Clear empty states with an action to get started.
- Confirmation before destructive actions.
- Inline validation messages in plain Indonesian.
- Loading, success, and failure states for forms and actions.
- Charts must have accessible labels or an equivalent summary.
- Do not rely on color alone to distinguish income and expenses.
- No fake buttons or nonfunctional links.
- Use realistic demo data only in development/demo mode, never silently mix demo data into a real account.

## 8. Acceptance criteria

The release is acceptable when:
- A user can register, log in, and log out.
- Users cannot read or alter another user's data.
- A user can create a wallet with an opening balance.
- Income, expense, and transfer transactions can be created, edited, filtered, and deleted correctly.
- Wallet balances match the defined formula after create/edit/delete operations.
- Transfers do not inflate income or expenses.
- Dashboard totals and reports match the transaction list for the same period.
- Budgets calculate spending and remaining amounts correctly.
- Savings goals calculate contribution totals and progress correctly.
- CSV export respects filters and user ownership.
- Main pages are usable at mobile and desktop widths.
- Validation, authorization, and critical financial logic have automated tests.
- README documents installation, environment setup, migration, seeding, testing, and running the app.

## 9. Implementation principles

- Build in small, verifiable increments.
- Follow `techstack.md`, `styleguide.md`, and `security.md`.
- Treat this PRD as the product scope source of truth.
- Update `task.md` as work progresses.
- Do not add unrequested features without explaining the trade-off and receiving approval.
