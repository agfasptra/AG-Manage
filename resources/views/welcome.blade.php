<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AG Manage — Manajemen Keuangan Pribadi</title>
    <meta name="description" content="Aplikasi pencatatan keuangan modern untuk mahasiswa dan pekerja. Kelola dompet, anggaran, target tabungan, dan arus kas dengan mudah dan aman.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col text-slate-800 bg-[#F8FAFC]">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-sm shadow-emerald-500/20">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tight text-slate-900">AG<span class="text-emerald-600">Manage</span></span>
                    <span class="hidden sm:inline-block ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">v1.0-dev</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if ($dbConnected)
                    <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>MySQL Terhubung ({{ $dbName }})</span>
                    </div>
                @endif

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 hover:text-emerald-700 hover:bg-slate-100 transition">
                                Masuk
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-xs">
                                    Daftar
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow">
        <section class="relative overflow-hidden pt-12 pb-16 lg:pt-20 lg:pb-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Hero Copy -->
                    <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                            <span>Aman, Akurat & Terisolasi Penuh</span>
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                            Kendali Penuh Atas <br class="hidden sm:inline" />
                            <span class="text-emerald-600">Keuangan Pribadi</span> Anda
                        </h1>

                        <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                            Dirancang khusus untuk mahasiswa dan pekerja. Catat pemasukan, pengeluaran, transfer antar-dompet, dan pantau target anggaran bulanan dengan presisi rupiah tanpa ribet.
                        </p>

                        <!-- System Status Card -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs max-w-xl mx-auto lg:mx-0 text-left">
                            <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <i data-lucide="cpu" class="w-3.5 h-3.5 text-slate-400"></i>
                                Status Lingkungan Proyek (Fase 2)
                            </h2>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <span class="text-slate-400 block">Framework</span>
                                    <span class="font-semibold text-slate-800">Laravel {{ app()->version() }}</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <span class="text-slate-400 block">Runtime</span>
                                    <span class="font-semibold text-slate-800">PHP {{ PHP_VERSION }}</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <span class="text-slate-400 block">Database</span>
                                    <span class="font-semibold text-slate-800">{{ ucfirst($driver) }} (3306)</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <span class="text-slate-400 block">Frontend</span>
                                    <span class="font-semibold text-slate-800">Tailwind + Chart.js</span>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Actions -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 text-white font-medium text-sm flex items-center justify-center gap-2 shadow-sm hover:bg-emerald-700 transition">
                                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                    <span>Buka Dashboard</span>
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 text-white font-medium text-sm flex items-center justify-center gap-2 shadow-sm hover:bg-emerald-700 transition">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                                    <span>Mulai Catat Keuangan</span>
                                </a>
                                <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-white border border-slate-300 text-slate-700 font-medium text-sm flex items-center justify-center gap-2 shadow-xs hover:bg-slate-50 transition">
                                    <i data-lucide="log-in" class="w-4 h-4"></i>
                                    <span>Masuk ke Akun</span>
                                </a>
                            @endauth
                        </div>
                    </div>

                    <!-- Right Interactive Preview / Chart Demo -->
                    <div class="lg:col-span-5">
                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-lg shadow-slate-200/50 p-6 space-y-6">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Simulasi Dashboard</span>
                                    <h3 class="text-lg font-bold text-slate-900">Arus Kas Bulanan (Demo)</h3>
                                </div>
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    Chart.js Aktif
                                </span>
                            </div>

                            <!-- Mini Summary Metric Cards -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                                    <div class="flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                                        <span>Pemasukan</span>
                                    </div>
                                    <div class="text-lg font-bold text-slate-900 mt-1">Rp5.500.000</div>
                                </div>
                                <div class="p-3.5 rounded-xl bg-red-50/60 border border-red-100">
                                    <div class="flex items-center gap-1.5 text-xs font-medium text-red-700">
                                        <i data-lucide="trending-down" class="w-3.5 h-3.5"></i>
                                        <span>Pengeluaran</span>
                                    </div>
                                    <div class="text-lg font-bold text-slate-900 mt-1">Rp3.250.000</div>
                                </div>
                            </div>

                            <!-- Canvas Container for Chart.js -->
                            <div class="relative h-56 w-full">
                                <canvas id="previewCashflowChart"></canvas>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                <span>Saldo Bersih: <strong class="text-emerald-700">+Rp2.250.000</strong></span>
                                <span class="flex items-center gap-1 text-slate-400">
                                    <i data-lucide="lock" class="w-3 h-3"></i> Terisolasi per user
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Features Grid -->
        <section class="py-12 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Dirancang Sesuai Spesifikasi PRD</h2>
                    <p class="text-sm text-slate-600 mt-2">Komponen inti manajemen keuangan yang akan diimplementasikan pada fase berikutnya.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-300 transition">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                            <i data-lucide="credit-card" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Multi-Dompet</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">Kelola Kas fisik, Rekening Bank, dan E-Wallet dengan perhitungan saldo kanonik terpisah.</p>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-300 transition">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-4">
                            <i data-lucide="arrow-left-right" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Transfer Atomik</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">Pindah dana antar-dompet secara instan dan netral tanpa menggelembungkan pemasukan atau pengeluaran.</p>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-300 transition">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
                            <i data-lucide="pie-chart" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Anggaran Bulanan</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">Atur batas belanja per kategori dengan peringatan visual saat mencapai 80% dan 100% plafon.</p>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-300 transition">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-4">
                            <i data-lucide="target" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Target Tabungan</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">Rencanakan impian masa depan dan catat alokasi tabungan dengan visual progres yang akurat.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-800">AG Manage</span>
                <span>— Manajemen Keuangan Pribadi</span>
            </div>
            <div>
                <span>PHP 8.3 · Laravel 13 · MySQL · Tailwind CSS · Chart.js</span>
            </div>
        </div>
    </footer>

    <!-- Chart.js Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart !== 'undefined') {
                const ctx = document.getElementById('previewCashflowChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
                            datasets: [
                                {
                                    label: 'Pemasukan',
                                    data: [1500000, 1000000, 2000000, 1000000],
                                    backgroundColor: '#16A34A',
                                    borderRadius: 6,
                                },
                                {
                                    label: 'Pengeluaran',
                                    data: [800000, 650000, 1100000, 700000],
                                    backgroundColor: '#DC2626',
                                    borderRadius: 6,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        font: {
                                            family: 'Inter, sans-serif',
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            return context.dataset.label + ': Rp' + new Intl.NumberFormat('id-ID').format(context.raw);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (val) {
                                            return 'Rp' + (val / 1000) + 'k';
                                        },
                                        font: {
                                            size: 10
                                        }
                                    },
                                    grid: {
                                        color: '#F1F5F9'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
