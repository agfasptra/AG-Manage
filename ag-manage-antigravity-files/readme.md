# AG Manage — Personal Finance Manager

AG Manage is a responsive personal finance web application for students and working adults. It helps users record income and expenses, manage wallets, set monthly budgets, track savings goals, and review financial reports.

> This repository is the application project. The product scope and implementation rules are documented in the accompanying Markdown files.

## Documentation

- `prd.md` — product scope, feature requirements, business rules, and acceptance criteria.
- `task.md` — phased implementation checklist.
- `styleguide.md` — UI and interaction guidelines.
- `techstack.md` — technology choices and architecture.
- `skills.md` — coding-agent workflow and engineering practices.
- `security.md` — security requirements and verification checklist.

## Intended stack

- Laravel (PHP)
- Blade templates
- Tailwind CSS
- MySQL
- Chart.js
- JavaScript for lightweight interactions

See `techstack.md` for compatibility and setup guidance. Confirm versions against the actual environment before installation.

## Requirements

Install versions compatible with the Laravel version selected for the project:
- PHP and required PHP extensions
- Composer
- Node.js and npm
- MySQL or a compatible local development database
- Git (recommended)

## Setup

The exact commands may vary based on the Laravel version and starter kit selected. After scaffolding the project:

1. Clone or open the repository.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install frontend dependencies:
   ```bash
   npm install
   ```
4. Copy the environment template:
   ```bash
   cp .env.example .env
   ```
   On Windows PowerShell, use:
   ```powershell
   Copy-Item .env.example .env
   ```
5. Configure database connection values in `.env`. Never commit real credentials.
6. Generate the application key:
   ```bash
   php artisan key:generate
   ```
7. Run migrations:
   ```bash
   php artisan migrate
   ```
8. If development seeders exist, seed only a local development database:
   ```bash
   php artisan db:seed
   ```
9. Start the frontend build process:
   ```bash
   npm run dev
   ```
10. In a separate terminal, start Laravel:
   ```bash
   php artisan serve
   ```

Open the local URL printed by Laravel. If the repository uses a different setup, update these instructions to match the actual implementation.

## Tests and quality checks

Run the project's actual configured test commands. A typical Laravel test command is:

```bash
php artisan test
```

If configured, also run the formatter, linter, and production frontend build, for example:

```bash
npm run build
```

Do not state that tests passed unless they have actually been executed and passed.

## Financial data principles

- Monetary amounts must use exact numeric storage, not floating-point.
- Transfers must not be counted as income or expenses.
- Opening balances must not be counted as income.
- Transaction create/update/delete operations must preserve correct wallet balances.
- Each user's records must be protected by server-side authorization.
- Never store bank passwords, PINs, or payment credentials.

## Development status

Keep this section current:
- Implemented: 
  - Fase 1: Analisis menyeluruh seluruh dokumen PRD, Techstack, Styleguide, Security, Skills, Task; pemeriksaan environment sistem dan perumusan rencana arsitektur.
  - Fase 2: Inisialisasi proyek Laravel 13, instalasi runtime Node.js v24 & npm v11, konfigurasi database MySQL `ag_manage` (port 3306), setup Tailwind CSS v4, Chart.js, Lucide icons, font Inter, migrasi tabel dasar, pembuatan landing page fintech AG Manage dengan live DB status & visual chart preview, serta verifikasi server via automated tests dan browser inspection.
  - Fase 3: Database & autentikasi — 6 migration domain (wallets, categories, transactions, budgets, savings_goals, goal_contributions), 6 Eloquent model dengan relasi & type casting, DefaultCategorySeeder (14 kategori), Laravel Breeze (register/login/logout), isolasi multi-tenant, 30 tests / 114 assertions passing.
  - Fase 4: Dompet & kategori — WalletController CRUD (create/read/update/archive/delete), CategoryController CRUD dengan proteksi kategori bawaan, WalletPolicy & CategoryPolicy (otorisasi kepemilikan), formula saldo kanonik (opening_balance + income - expense + incoming_transfers - outgoing_transfers), WalletFactory, navigasi dengan link Dompet & Kategori, 73 total tests / 208 assertions passing 100%.
  - Fase 5: Transaksi keuangan — TransactionController CRUD lengkap (pemasukan, pengeluaran, transfer antar-dompet), TransactionPolicy untuk otorisasi & isolasi data multi-tenant, pemutakhiran saldo otomatis & atomik via DB transactions, penegakan aturan keuangan bahwa transfer antar-dompet tidak dihitung sebagai pemasukan/pengeluaran dalam ringkasan, dukungan saldo negatif (`-RpX.XXX`), TransactionFactory & CategoryFactory, antarmuka transaksi modern responsif (desktop data table, mobile card list, tab switcher pemasukan/pengeluaran/transfer, toolbar pencarian & filter, modal konfirmasi hapus), 91 total tests / 270 assertions passing 100%.
- In progress: Menunggu instruksi untuk Fase 6 (Dashboard).
- Not yet implemented: Fase 6 hingga Fase 10 (Dashboard, Anggaran & Tabungan, Laporan & Ekspor, Pengujian & Keamanan, Finalisasi).
- Known limitations: Eksekusi script `.ps1` dibatasi oleh PowerShell Execution Policy di sistem pengguna (gunakan `cmd.exe /c` untuk eksekusi npm/npx). Peringatan lightningcss tentang `@theme` dan `@source` pada Vite build tidak mempengaruhi output CSS.

## Contributing / AI coding agent

Before making changes, the coding agent must read all project instruction files. Inspect the repository before modifying it, implement tasks incrementally, run relevant tests, and update `task.md` and this README as appropriate.

Do not replace existing working code wholesale without first explaining why it is necessary.
