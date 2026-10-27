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
  - Fase 6: Dashboard finansial — DashboardController dengan agregasi data nyata dari database, 4 kartu ringkasan keuangan (total saldo seluruh dompet aktif, pemasukan periode, pengeluaran periode, arus kas bersih), filter periode dinamis (Bulan Ini, Bulan Lalu, Rentang Tanggal Kustom), visualisasi grafik interaktif Chart.js (grafik batang pemasukan vs pengeluaran harian, donat komposisi pengeluaran per kategori), progres anggaran bulanan dengan indikator peringatan visual (80% waspada & 100% terlampaui), pelacakan progres target tabungan aktif & kontribusi, 8 transaksi terbaru secara realtime, aksi cepat "Tambah Transaksi", factory pelengkap (BudgetFactory, SavingsGoalFactory, GoalContributionFactory), penegakan aturan bahwa transfer antar-dompet tidak dihitung dalam ringkasan pemasukan/pengeluaran, isolasi ketat antar-pengguna (multi-tenant), 105 total automated tests / 335 assertions passing 100%.
  - Fase 7: Anggaran, tabungan, insight —
    * Anggaran bulanan (BudgetController): CRUD batas pengeluaran kategori per bulan/tahun, pencegahan duplikasi anggaran dalam periode yang sama, perhitungan realisasi pengeluaran dari transaksi valid, peringatan visual ambang batas 80% (waspada) dan 100% (terlampaui).
    * Target tabungan & alokasi (SavingsGoalController & GoalContributionController): CRUD target tabungan, status target (aktif, dijeda, selesai), alokasi tabungan dengan referensi dompet opsional, kalkulasi progres dan sisa target, pembatasan visual 100% dengan pelacakan surplus dana (PRD 5.8).
    * Insight berbasis aturan & safe-to-spend (InsightService): Deteksi otomatis ambang batas anggaran, perbandingan tren pengeluaran per kategori bulan ini vs bulan lalu (MoM), milestone progres tabungan, serta kalkulasi estimasi Aman Dibelanjakan (Safe-to-Spend) transparan (Total Saldo Dompet Aktif - Sisa Komitmen Anggaran).
    * Otorisasi ketat (BudgetPolicy, SavingsGoalPolicy, GoalContributionPolicy) untuk perlindungan data multi-tenant.
  - Fase 8: Laporan keuangan & ekspor CSV —
    * Laporan finansial (ReportController): Analisis komparatif arus kas bulanan, perbandingan Month-over-Month (MoM) otomatis untuk mengukur pertumbuhan pemasukan dan penghematan pengeluaran, transfer antar-dompet dipertahankan netral.
    * Toolbar filter komprehensif: Pilihan preset periode (Bulan Ini, Bulan Lalu, 30 Hari Terakhir, Tahun Berjalan, Rentang Kustom), filter spesifik dompet, kategori, dan tipe transaksi.
    * Visualisasi & Audit: Grafik tren batang harian, donat distribusi porsi pengeluaran per kategori, tabel rincian proporsi, dan tabel buku kas transaksi lengkap (audit ledger) dengan paginasi.
    * Ekspor CSV aman (PRD 5.10 & Security Rule 6): Streaming berkas CSV dengan UTF-8 BOM untuk Microsoft Excel Windows, sanitasi pencegahan serangan injeksi formula spreadsheet (`=`, `+`, `-`, `@`), penamaan berkas terstandarisasi, dan pembatasan isolasi multi-tenant ketat.
  - Fase 9: Pengujian komprehensif, keamanan, dan kualitas —
    * Otorisasi dan isolasi tenant (IDOR): Pengujian mendalam pada seluruh endpoint read/write (Transactions, Wallets, Categories, Budgets, Savings Goals, Goal Contributions, Reports & Export) untuk menjamin data pengguna terlindungi dari akses ilegal antar-pengguna. Penambahan method `show` dengan otorisasi pada `TransactionController`.
    * Integritas finansial & atomisitas transfer: Verifikasi transfer antar-dompet tetap netral terhadap arus kas, saldo awal tidak dihitung sebagai pemasukan, update dan penghapusan transfer memulihkan saldo kedua dompet secara akurat dan atomik, serta pencegahan drift floating-point melalui presisi numerik tetap (`bcmath`).
    * Perilaku penghapusan aman (Safe Deletion & Cascade): Dompet dan kategori yang memiliki riwayat transaksi dilindungi dari penghapusan dan diarahkan untuk diarsipkan/dinonaktifkan; penghapusan target tabungan meng-cascade kontribusi terkait secara bersih.
    * Validasi & proteksi input: Penolakan jumlah nominal transaksi negatif atau nol, validasi format tanggal, proteksi panjang batas catatan, aturan proteksi CSRF form web, serta proteksi mass-assignment (`$fillable`) pada seluruh model Eloquent.
    * Keamanan berkas & kredensial: Verifikasi `.env` diabaikan oleh Git via `.gitignore`, `.env.example` bersih tanpa secret riil.
    * Pengujian empty states & build frontend: Pengujian tampilan state kosong (empty state) pada dompet, transaksi, dan laporan; build aset frontend Vite sukses dan lulus linter standar PSR-12/Laravel Pint.
    * 167 total automated tests / 555 assertions passing 100%.
  - Fase 10: Finalisasi, kesiapan rilis, dan dokumentasi serah terima —
    * Seeder Data Demo Realistis (`DemoDataSeeder`): Menyediakan dataset realistis untuk akun demo (`demo@agmanage.id` / `password123`) mencakup 3 dompet aktif (Kas Tunai, Rekening BCA, GoPay), transaksi arus kas bulanan, anggaran kategori dengan peringatan ambang batas, dan target tabungan aktif dengan riwayat kontribusi nyata.
    * Halaman Kesalahan Branded (Custom Error Views): Menambahkan halaman error kustom berdesain modern berbahasa Indonesia untuk status HTTP 403 (Akses Ditolak), 404 (Halaman Tidak Ditemukan), dan 500 (Kesalahan Server).
    * Pembersihan Aset & Responsivitas: Verifikasi layout responsif di desktop maupun mobile hamburger drawer, tipografi Inter, ikon Lucide, dan pembersihan berkas temporer/backup.
    * Dokumentasi Lengkap & Panduan Pengembang: Panduan instalasi langkah-demi-langkah, variabel lingkungan, pengujian, akun demo, dan checklist serah terima produksi.
- In progress: Selesai — Seluruh fase (Fase 1 hingga Fase 10) telah selesai 100%.
- Not yet implemented: Tidak ada (seluruh fitur spesifikasi PRD, keamanan, pengujian, dan dokumentasi telah diimplementasikan penuh).
- Known limitations: Eksekusi script `.ps1` dibatasi oleh PowerShell Execution Policy di sistem pengguna (gunakan `cmd.exe /c` untuk eksekusi npm/npx). Peringatan lightningcss tentang `@theme` dan `@source` pada Vite build tidak mempengaruhi output CSS.

## Akun Demo Pengembang

Untuk mencoba seluruh fitur aplikasi secara langsung tanpa perlu registrasi manual:
- **Email**: `demo@agmanage.id`
- **Kata Sandi**: `password123`
- **Cara generate data demo**:
  ```bash
  php artisan db:seed
  ```

## Contributing / AI coding agent

Before making changes, the coding agent must read all project instruction files. Inspect the repository before modifying it, implement tasks incrementally, run relevant tests, and update `task.md` and this README as appropriate.

Do not replace existing working code wholesale without first explaining why it is necessary.
