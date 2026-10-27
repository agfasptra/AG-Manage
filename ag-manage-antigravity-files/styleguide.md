# Style Guide — AG Manage

## 1. Design direction

Build a premium, calm, trustworthy fintech experience. The interface should feel modern and polished without looking flashy, crowded, or like a generic admin template.

- Product language: Indonesian.
- Tone: clear, supportive, concise, non-judgmental.
- Primary audience: students and working adults.
- Layout: desktop sidebar; compact mobile navigation; content-first dashboard.
- Prioritize readability and quick transaction entry.

## 2. Visual tokens

Use CSS variables or Tailwind theme tokens rather than scattering arbitrary values throughout the app. If the repository already has a token system, extend it rather than duplicating it.

Suggested palette:
- Primary / emerald: `#059669`
- Primary hover: `#047857`
- Dark navy: `#0F172A`
- Main text: `#111827`
- Secondary text: `#64748B`
- Page background light: `#F8FAFC`
- Surface: `#FFFFFF`
- Border: `#E2E8F0`
- Income/success: `#16A34A`
- Expense/danger: `#DC2626`
- Warning: `#D97706`
- Info: `#2563EB`

These are starting tokens, not a requirement to use every color everywhere. Ensure accessible contrast. Do not use color as the only indicator of transaction type.

## 3. Typography

- Preferred font: Inter, with a sensible system sans-serif fallback.
- Use a clear hierarchy: page title, section heading, card label, body text, helper text.
- Monetary figures should be easy to scan and use tabular numerals where possible.
- Avoid tiny text for important financial information.
- Keep line lengths comfortable and labels concise.

## 4. Layout and spacing

- Use a consistent spacing scale based on 4px or 8px increments.
- Desktop: persistent sidebar, top header, centered content area with a reasonable maximum width.
- Mobile: no horizontal page overflow; use compact navigation and stacked cards.
- Use responsive grids that collapse gracefully.
- Keep dashboard density moderate: key balance, income/expense, cash flow chart, budgets, goals, and recent activity.
- Avoid excessive card nesting, oversized empty whitespace, and too many decorative gradients.
- Use consistent border radius (for example 10–16px) and subtle shadows only when they improve hierarchy.

## 5. Component standards

Create reusable components/patterns for:
- Buttons: primary, secondary, ghost, danger.
- Form fields: label, hint, validation error, focus state.
- Cards and summary cards.
- Data table and mobile transaction list.
- Badges for income/expense/status.
- Dialog/modal and confirmation dialog.
- Empty states.
- Toast/flash messages.
- Pagination and filters.
- Chart containers with titles and summaries.
- Loading skeletons where useful.

Every visible control must work. If an action is unavailable, disable it and explain why rather than rendering a fake button.

## 6. Transaction UX

- Make “Tambah transaksi” easy to find.
- Keep the form short and change fields based on transaction type.
- For income/expense: type, amount, category, wallet, date, optional note.
- For transfer: source wallet, destination wallet, amount, date, optional note.
- Show currency formatting in Indonesian Rupiah, e.g. `Rp1.250.000`.
- Store the underlying amount as a numeric value, not a formatted string.
- Prevent accidental double submission and show clear success/error feedback.
- Confirm destructive actions.

## 7. Charts and data presentation

- Use Chart.js or the existing chart library.
- Include chart titles, legends/tooltips, and an accessible textual summary.
- Do not imply precision that the underlying data cannot support.
- Handle empty data and zero values gracefully.
- Income and expense must remain distinguishable in grayscale or for color-blind users through labels/icons/patterns as appropriate.
- Do not animate charts excessively.

## 8. Responsive behavior

Test at approximately:
- Small mobile: 320–375px
- Large mobile: 390–430px
- Tablet: 768px
- Desktop: 1024px and above

Requirements:
- No horizontal overflow.
- Tables should become card lists or use deliberate horizontal scrolling on narrow screens.
- Modals should fit within the viewport.
- Tap targets should be comfortably usable.
- Forms should remain legible and not require desktop-only hover interactions.

## 9. Accessibility

- Use semantic HTML and correct heading order.
- All inputs must have visible labels.
- All interactive controls must be keyboard accessible and have visible focus states.
- Use sufficient color contrast.
- Provide accessible names for icon-only buttons.
- Associate validation errors with their fields.
- Respect reduced-motion preferences where animations exist.

## 10. Copywriting rules

Prefer concise Indonesian:
- “Tambah transaksi”
- “Pemasukan”
- “Pengeluaran”
- “Transfer”
- “Saldo tersedia”
- “Anggaran tersisa”
- “Target tabungan”
- “Belum ada transaksi”
- “Coba ubah filter atau tambahkan transaksi pertama.”

Avoid overly formal, vague, judgmental, or alarmist messages. Do not shame users for spending. Explain calculations when a figure could be misunderstood.

## 11. Dark mode

Dark mode is optional for the first release. If implemented:
- Use a deliberate dark palette, not simply invert colors.
- Preserve contrast and semantic colors.
- Check charts, borders, dialogs, dropdowns, and form controls.
- Persist the user's theme preference only through an appropriate app preference mechanism.
