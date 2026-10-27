<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Transaksi Keuangan</h1>
                <p class="text-xs text-slate-500 mt-0.5">Catat dan pantau seluruh pemasukan, pengeluaran, dan transfer Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('transactions.create', ['type' => 'transfer']) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-sky-50 text-sky-700 border border-sky-200 text-sm font-semibold rounded-xl hover:bg-sky-100 transition-all duration-200 shadow-xs">
                    <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                    <span>Transfer Antar-Dompet</span>
                </a>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Transaksi</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ deleteModalOpen: false, deleteUrl: '', deleteInfo: '' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-emerald-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            @if (session('warning'))
                <div class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0"></i>
                    <span>{{ session('warning') }}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-amber-400 hover:text-amber-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            {{-- Summary Cards (Filtered Scope) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Income Card --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pemasukan</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="arrow-down-left" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 mt-2 tracking-tight tabular-nums text-emerald-600">
                        +{{ $formattedIncome }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Sesuai filter aktif (di luar transfer)</p>
                </div>

                {{-- Expense Card --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengeluaran</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 mt-2 tracking-tight tabular-nums text-rose-600">
                        -{{ $formattedExpense }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Sesuai filter aktif (di luar transfer)</p>
                </div>

                {{-- Net Cash Flow Card --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Arus Kas Bersih</span>
                        <div class="w-9 h-9 rounded-xl {{ (float)$netCashFlow >= 0 ? 'bg-teal-50 text-teal-600' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center">
                            <i data-lucide="{{ (float)$netCashFlow >= 0 ? 'trending-up' : 'trending-down' }}" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold mt-2 tracking-tight tabular-nums {{ (float)$netCashFlow >= 0 ? 'text-teal-700' : 'text-amber-700' }}">
                        {{ (float)$netCashFlow >= 0 ? '+' : '' }}{{ $formattedNet }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Pemasukan dikurangi pengeluaran</p>
                </div>

                {{-- Transfer Volume Card --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Transfer Antar-Dompet</span>
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="arrow-left-right" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 mt-2 tracking-tight tabular-nums text-sky-700">
                        {{ $formattedTransfer }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $transferCount }} kali transfer tercatat</p>
                </div>
            </div>

            {{-- Filter & Search Toolbar --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                <form method="GET" action="{{ route('transactions.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                        {{-- Search Input --}}
                        <div class="lg:col-span-2">
                            <label for="search" class="block text-xs font-medium text-slate-600 mb-1">Cari Catatan</label>
                            <div class="relative">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                                       placeholder="Kata kunci catatan..."
                                       class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                            </div>
                        </div>

                        {{-- Type Filter --}}
                        <div>
                            <label for="type" class="block text-xs font-medium text-slate-600 mb-1">Tipe</label>
                            <select id="type" name="type"
                                    class="w-full py-2 px-3 text-sm rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                                <option value="">Semua Tipe</option>
                                <option value="income" {{ ($filters['type'] ?? '') === 'income' ? 'selected' : '' }}>Pemasukan</option>
                                <option value="expense" {{ ($filters['type'] ?? '') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                                <option value="transfer" {{ ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                        </div>

                        {{-- Wallet Filter --}}
                        <div>
                            <label for="wallet_id" class="block text-xs font-medium text-slate-600 mb-1">Dompet</label>
                            <select id="wallet_id" name="wallet_id"
                                    class="w-full py-2 px-3 text-sm rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                                <option value="">Semua Dompet</option>
                                @foreach($wallets as $wallet)
                                    <option value="{{ $wallet->id }}" {{ (string)($filters['wallet_id'] ?? '') === (string)$wallet->id ? 'selected' : '' }}>
                                        {{ $wallet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category Filter --}}
                        <div>
                            <label for="category_id" class="block text-xs font-medium text-slate-600 mb-1">Kategori</label>
                            <select id="category_id" name="category_id"
                                    class="w-full py-2 px-3 text-sm rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string)($filters['category_id'] ?? '') === (string)$category->id ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->type === 'income' ? 'Masuk' : 'Keluar' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-end gap-2">
                            <button type="submit"
                                    class="w-full py-2 px-3 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-colors flex items-center justify-center gap-1.5 shadow-xs">
                                <i data-lucide="filter" class="w-4 h-4"></i>
                                <span>Filter</span>
                            </button>
                            @if(array_filter($filters))
                                <a href="{{ route('transactions.index') }}"
                                   class="py-2 px-3 bg-slate-100 text-slate-600 hover:bg-slate-200 text-sm font-medium rounded-xl transition-colors flex items-center justify-center"
                                   title="Reset filter">
                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Date Range Filters --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
                        <div>
                            <label for="start_date" class="block text-xs font-medium text-slate-600 mb-1">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                                   class="w-full py-2 px-3 text-sm rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                        </div>
                        <div>
                            <label for="end_date" class="block text-xs font-medium text-slate-600 mb-1">Tanggal Akhir</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                                   class="w-full py-2 px-3 text-sm rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                        </div>
                    </div>
                </form>
            </div>

            {{-- Transactions Table & Card List --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Daftar Riwayat Transaksi</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Menampilkan {{ $transactions->total() }} catatan transaksi</p>
                    </div>
                </div>

                @if($transactions->isEmpty())
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="receipt" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        @if(array_filter($filters))
                            <h3 class="font-semibold text-slate-800 text-lg">Tidak ada transaksi ditemukan</h3>
                            <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">
                                Tidak ada transaksi yang sesuai dengan kriteria filter Anda. Coba ubah atau reset filter.
                            </p>
                            <a href="{{ route('transactions.index') }}"
                               class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-colors">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                Reset Filter
                            </a>
                        @else
                            <h3 class="font-semibold text-slate-800 text-lg">Belum ada transaksi</h3>
                            <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">
                                Mulai kelola keuangan Anda dengan mencatat pemasukan, pengeluaran, atau transfer antar-dompet pertama.
                            </p>
                            <div class="flex items-center justify-center gap-3 mt-5">
                                <a href="{{ route('transactions.create') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                    Catat Transaksi Pertama
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Desktop Table (Hidden on Mobile) --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5">Tanggal</th>
                                    <th scope="col" class="px-4 py-3.5">Tipe</th>
                                    <th scope="col" class="px-4 py-3.5">Kategori / Alur</th>
                                    <th scope="col" class="px-4 py-3.5">Dompet</th>
                                    <th scope="col" class="px-4 py-3.5">Catatan</th>
                                    <th scope="col" class="px-5 py-3.5 text-right">Jumlah</th>
                                    <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($transactions as $trx)
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        {{-- Date --}}
                                        <td class="px-5 py-4 whitespace-nowrap text-slate-900 font-medium">
                                            {{ \Carbon\Carbon::parse($trx->transaction_date)->translatedFormat('d M Y') }}
                                        </td>

                                        {{-- Type Badge --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if($trx->type === 'income')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                                    <i data-lucide="arrow-down-left" class="w-3.5 h-3.5"></i>
                                                    Pemasukan
                                                </span>
                                            @elseif($trx->type === 'expense')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                                                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                                    Pengeluaran
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                                                    <i data-lucide="arrow-left-right" class="w-3.5 h-3.5"></i>
                                                    Transfer
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Category or Transfer Flow --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if($trx->type === 'transfer')
                                                <div class="flex items-center gap-1.5 text-xs font-medium text-slate-700">
                                                    <span class="text-slate-900 font-semibold">{{ $trx->wallet->name }}</span>
                                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-sky-600 shrink-0"></i>
                                                    <span class="text-slate-900 font-semibold">{{ $trx->targetWallet?->name ?? '?' }}</span>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-2">
                                                    @if($trx->category)
                                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $trx->category->color ?? '#64748B' }};"></span>
                                                        <span class="font-medium text-slate-800">{{ $trx->category->name }}</span>
                                                    @else
                                                        <span class="text-slate-400 italic">Tanpa Kategori</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Wallet --}}
                                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">
                                            <div class="flex items-center gap-1.5">
                                                <i data-lucide="{{ $trx->wallet->type_icon }}" class="w-4 h-4 text-slate-400"></i>
                                                <span>{{ $trx->wallet->name }}</span>
                                            </div>
                                        </td>

                                        {{-- Notes --}}
                                        <td class="px-4 py-4 max-w-xs truncate text-slate-500 text-xs">
                                            {{ $trx->notes ?: '-' }}
                                        </td>

                                        {{-- Amount --}}
                                        <td class="px-5 py-4 whitespace-nowrap text-right font-bold tabular-nums">
                                            @if($trx->type === 'income')
                                                <span class="text-emerald-600">+{{ $trx->formatted_amount }}</span>
                                            @elseif($trx->type === 'expense')
                                                <span class="text-rose-600">-{{ $trx->formatted_amount }}</span>
                                            @else
                                                <span class="text-sky-700">{{ $trx->formatted_amount }}</span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('transactions.edit', $trx) }}"
                                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                                   title="Edit transaksi">
                                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                                </a>
                                                <button type="button"
                                                        @click="deleteModalOpen = true; deleteUrl = '{{ route('transactions.destroy', $trx) }}'; deleteInfo = '{{ $trx->type_label }} {{ $trx->formatted_amount }} ({{ \Carbon\Carbon::parse($trx->transaction_date)->translatedFormat('d M Y') }})'"
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                        title="Hapus transaksi">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Card List (Visible only on Mobile) --}}
                    <div class="md:hidden divide-y divide-slate-100">
                        @foreach($transactions as $trx)
                            <div class="p-4 space-y-2.5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                            {{ $trx->type === 'income' ? 'bg-emerald-50 text-emerald-600' : ($trx->type === 'expense' ? 'bg-rose-50 text-rose-600' : 'bg-sky-50 text-sky-600') }}">
                                            <i data-lucide="{{ $trx->type_icon }}" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-900 leading-tight">
                                                @if($trx->type === 'transfer')
                                                    {{ $trx->wallet->name }} → {{ $trx->targetWallet?->name ?? '?' }}
                                                @else
                                                    {{ $trx->category?->name ?? 'Tanpa Kategori' }}
                                                @endif
                                            </p>
                                            <p class="text-[11px] text-slate-400">
                                                {{ \Carbon\Carbon::parse($trx->transaction_date)->translatedFormat('d M Y') }} · {{ $trx->wallet->name }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold tabular-nums {{ $trx->type === 'income' ? 'text-emerald-600' : ($trx->type === 'expense' ? 'text-rose-600' : 'text-sky-700') }}">
                                            {{ $trx->type === 'income' ? '+' : ($trx->type === 'expense' ? '-' : '') }}{{ $trx->formatted_amount }}
                                        </p>
                                        <span class="text-[10px] uppercase font-bold text-slate-400">{{ $trx->type_label }}</span>
                                    </div>
                                </div>

                                @if($trx->notes)
                                    <p class="text-xs text-slate-600 bg-slate-50 rounded-lg p-2">
                                        {{ $trx->notes }}
                                    </p>
                                @endif

                                <div class="flex items-center justify-end gap-2 pt-1">
                                    <a href="{{ route('transactions.edit', $trx) }}"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 px-2.5 py-1 rounded-md hover:bg-slate-100 transition-colors">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        Edit
                                    </a>
                                    <button type="button"
                                            @click="deleteModalOpen = true; deleteUrl = '{{ route('transactions.destroy', $trx) }}'; deleteInfo = '{{ $trx->type_label }} {{ $trx->formatted_amount }} ({{ \Carbon\Carbon::parse($trx->transaction_date)->translatedFormat('d M Y') }})'"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-rose-600 hover:text-rose-800 px-2.5 py-1 rounded-md hover:bg-rose-50 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($transactions->hasPages())
                        <div class="p-4 border-t border-slate-100">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="deleteModalOpen" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4"
             style="display: none;">
            <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl" @click.away="deleteModalOpen = false">
                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 text-center">Hapus Transaksi?</h3>
                <p class="text-sm text-slate-500 text-center mt-2">
                    Transaksi <span class="font-semibold text-slate-700" x-text="deleteInfo"></span> akan dihapus dan pengaruh saldo dompet terkait akan dipulihkan secara otomatis.
                </p>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="deleteModalOpen = false"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <form method="POST" :action="deleteUrl" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2.5 bg-rose-600 text-white text-sm font-semibold rounded-xl hover:bg-rose-700 transition-colors shadow-xs">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
