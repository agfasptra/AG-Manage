<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Dompet Saya</h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola semua dompet dan saldo Anda</p>
            </div>
            <a href="{{ route('wallets.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Tambah Dompet
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div id="flash-success" class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm animate-fade-in">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-emerald-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            {{-- Total Balance Card --}}
            <div class="bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 rounded-2xl p-6 sm:p-8 text-white shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Total Saldo Aktif</p>
                        <p class="text-3xl sm:text-4xl font-bold mt-1 tracking-tight">{{ $formattedTotalBalance }}</p>
                        <p class="text-emerald-200 text-xs mt-2">
                            {{ $activeWallets->count() }} dompet aktif
                            @if($archivedWallets->count() > 0)
                                · {{ $archivedWallets->count() }} diarsipkan
                            @endif
                        </p>
                    </div>
                    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center">
                        <i data-lucide="wallet" class="w-8 h-8 text-white"></i>
                    </div>
                </div>
            </div>

            {{-- Active Wallets --}}
            @if($activeWallets->isEmpty())
                <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="wallet" class="w-8 h-8 text-slate-400"></i>
                    </div>
                    <h3 class="font-semibold text-slate-800 text-lg">Belum ada dompet</h3>
                    <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">Mulai kelola keuangan Anda dengan membuat dompet pertama.</p>
                    <a href="{{ route('wallets.create') }}"
                       class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Buat Dompet Pertama
                    </a>
                </div>
            @else
                <div>
                    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">Dompet Aktif</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($activeWallets as $wallet)
                            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-200 transition-colors duration-200">
                                            <i data-lucide="{{ $wallet->type_icon }}" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-slate-900 text-sm leading-tight">{{ $wallet->name }}</h3>
                                            <span class="text-xs text-slate-500">{{ $wallet->type_label }}</span>
                                        </div>
                                    </div>
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition
                                             class="absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-20">
                                            <a href="{{ route('wallets.show', $wallet) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> Detail
                                            </a>
                                            <a href="{{ route('wallets.edit', $wallet) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                                            </a>
                                            <form method="POST" action="{{ route('wallets.toggle-archive', $wallet) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="flex items-center gap-2 px-3 py-2 text-sm text-amber-600 hover:bg-amber-50 w-full text-left">
                                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i> Arsipkan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t border-slate-100">
                                    <p class="text-xs text-slate-500 mb-0.5">Saldo saat ini</p>
                                    @php $balance = (float) $wallet->current_balance; @endphp
                                    <p class="text-xl font-bold {{ $balance >= 0 ? 'text-slate-900' : 'text-red-600' }} tabular-nums">
                                        {{ $wallet->formatted_balance }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Saldo awal: {{ $wallet->formatted_opening_balance }}
                                    </p>
                                </div>

                                <a href="{{ route('wallets.show', $wallet) }}"
                                   class="mt-3 flex items-center justify-center gap-1.5 py-2 text-xs font-medium text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors duration-200">
                                    Lihat Detail
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Archived Wallets --}}
            @if($archivedWallets->isNotEmpty())
                <div x-data="{ showArchived: false }">
                    <button @click="showArchived = !showArchived"
                            class="flex items-center gap-2 text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 hover:text-slate-700 transition-colors">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                        Dompet Diarsipkan ({{ $archivedWallets->count() }})
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showArchived }"></i>
                    </button>

                    <div x-show="showArchived" x-transition class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($archivedWallets as $wallet)
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 opacity-75">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center">
                                            <i data-lucide="{{ $wallet->type_icon }}" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-slate-600 text-sm">{{ $wallet->name }}</h3>
                                            <span class="text-xs text-slate-400">{{ $wallet->type_label }} · Diarsipkan</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <form method="POST" action="{{ route('wallets.toggle-archive', $wallet) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 hover:text-emerald-600 transition-colors" title="Aktifkan kembali">
                                                <i data-lucide="archive-restore" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-200">
                                    <p class="text-xs text-slate-400 mb-0.5">Saldo terakhir</p>
                                    <p class="text-lg font-bold text-slate-500 tabular-nums">{{ $wallet->formatted_balance }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
