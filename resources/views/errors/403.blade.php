<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Akses Ditolak | AG Manage</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex items-center justify-center bg-[#F8FAFC] px-4 py-12">
    <div class="max-w-md w-full text-center bg-white p-8 sm:p-10 rounded-2xl border border-slate-200 shadow-sm">
        <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <i data-lucide="shield-alert" class="w-8 h-8"></i>
        </div>
        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 mb-3">Kode 403</span>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Akses Ditolak</h1>
        <p class="text-slate-600 text-sm mb-6 leading-relaxed">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman atau data ini. Seluruh data keuangan di AG Manage dilindungi oleh isolasi kepemilikan yang ketat.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition shadow-xs">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                Kembali ke Dashboard
            </a>
            <button onclick="window.history.back()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Halaman Sebelumnya
            </button>
        </div>
    </div>
</body>
</html>
