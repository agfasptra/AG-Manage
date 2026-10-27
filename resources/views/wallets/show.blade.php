<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('wallets.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="font-bold text-xl text-slate-900 leading-tight">{{ $wallet->name }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $wallet->type_label }} · Detail dompet</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('transactions.create', ['type' => 'transfer', 'wallet_id' => $wallet->id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-xl transition-colors">
                    <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Transfer</span>
                </a>
                <a href="{{ route('transactions.create', ['wallet_id' => $wallet->id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors shadow-xs">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Transaksi</span>
                </a>
                <a href="{{ route('wallets.edit', $wallet) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            {{-- Balance Card --}}
            <div class="bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 rounded-2xl p-6 sm:p-8 text-white shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Saldo Saat Ini</p>
                        @php $balance = (float) $wallet->current_balance; @endphp
                        <p class="text-3xl sm:text-4xl font-bold mt-1 tracking-tight tabular-nums">
                            {{ $wallet->formatted_balance }}
                        </p>
                        @if($balance < 0)
                            <p class="text-amber-300 text-xs mt-2 flex items-center gap-1">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                Saldo negatif — periksa transaksi Anda
                            </p>
                        @endif
                    </div>
                    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center">
                        <i data-lucide="{{ $wallet->type_icon }}" class="w-8 h-8 text-white"></i>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div class="bg-white/10 rounded-xl p-3">
                        <p class="text-emerald-200 text-xs">Saldo Awal</p>
                        <p class="text-white font-bold text-sm mt-0.5 tabular-nums">{{ $wallet->formatted_opening_balance }}</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-3">
                        <p class="text-emerald-200 text-xs">Jenis</p>
                        <p class="text-white font-bold text-sm mt-0.5">{{ $wallet->type_label }}</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-3">
                        <p class="text-emerald-200 text-xs">Status</p>
                        <p class="text-white font-bold text-sm mt-0.5">{{ $wallet->is_archived ? 'Diarsipkan' : 'Aktif' }}</p>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            @if($wallet->description)
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Deskripsi</h2>
                    <p class="text-sm text-slate-700">{{ $wallet->description }}</p>
                </div>
            @endif

            {{-- Recent Transactions --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Transaksi Terakhir</h2>
                    <a href="{{ route('transactions.index', ['wallet_id' => $wallet->id]) }}"
                       class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                        <span>Lihat Semua</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                @if($recentTransactions->isEmpty() && $incomingTransfers->isEmpty())
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="receipt" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm text-slate-500">Belum ada transaksi di dompet ini.</p>
                        <p class="text-xs text-slate-400 mt-1">Transaksi akan muncul di sini setelah Anda menambahkan data.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentTransactions as $trx)
                            <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                                    {{ $trx->type === 'income' ? 'bg-green-100 text-green-600' : ($trx->type === 'expense' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600') }}">
                                    <i data-lucide="{{ $trx->type === 'income' ? 'arrow-down-left' : ($trx->type === 'expense' ? 'arrow-up-right' : 'arrow-left-right') }}" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">
                                        {{ $trx->category?->name ?? ($trx->type === 'transfer' ? 'Transfer' : '-') }}
                                    </p>
                                    <p class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($trx->transaction_date)->translatedFormat('d M Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold tabular-nums {{ $trx->type === 'income' ? 'text-green-600' : ($trx->type === 'expense' ? 'text-red-600' : 'text-blue-600') }}">
                                        {{ $trx->type === 'income' ? '+' : '-' }}Rp{{ number_format($trx->amount, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Danger Zone --}}
            <div class="bg-white border border-red-200 rounded-2xl p-5 shadow-xs" x-data="{ confirmDelete: false }">
                <h2 class="text-sm font-semibold text-red-600 uppercase tracking-wider mb-3">Zona Berbahaya</h2>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-700">Hapus dompet ini secara permanen.</p>
                        <p class="text-xs text-slate-500">Hanya bisa dilakukan jika tidak ada transaksi terkait.</p>
                    </div>
                    <button @click="confirmDelete = true"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors shrink-0">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Hapus Dompet
                    </button>
                </div>

                {{-- Delete Confirmation Modal --}}
                <div x-show="confirmDelete" x-transition
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl" @click.away="confirmDelete = false">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 text-center">Hapus Dompet?</h3>
                        <p class="text-sm text-slate-500 text-center mt-2">Dompet "{{ $wallet->name }}" akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="flex gap-3 mt-5">
                            <button @click="confirmDelete = false"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <form method="POST" action="{{ route('wallets.destroy', $wallet) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full px-4 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition-colors">
                                    Ya, Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
