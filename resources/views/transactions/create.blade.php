<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('transactions.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Tambah Transaksi</h1>
                <p class="text-xs text-slate-500 mt-0.5">Catat arus kas baru atau lakukan transfer antar-dompet</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        currentType: '{{ old('type', $selectedType) }}',
        rawAmount: '{{ old('amount', '') }}',
        formatRupiah(value) {
            let num = parseFloat(value);
            if (isNaN(num)) return 'Rp0';
            return 'Rp' + num.toLocaleString('id-ID');
        }
    }">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">

                {{-- Type Selection Tabs --}}
                <div class="p-2 bg-slate-50 border-b border-slate-100 grid grid-cols-3 gap-2">
                    <button type="button" @click="currentType = 'expense'"
                            :class="currentType === 'expense'
                                ? 'bg-white text-rose-700 shadow-xs border-slate-200'
                                : 'text-slate-600 hover:text-slate-900 border-transparent'"
                            class="py-2.5 px-3 rounded-xl text-sm font-semibold border flex items-center justify-center gap-2 transition-all">
                        <i data-lucide="arrow-up-right" class="w-4 h-4 text-rose-600"></i>
                        <span>Pengeluaran</span>
                    </button>

                    <button type="button" @click="currentType = 'income'"
                            :class="currentType === 'income'
                                ? 'bg-white text-emerald-700 shadow-xs border-slate-200'
                                : 'text-slate-600 hover:text-slate-900 border-transparent'"
                            class="py-2.5 px-3 rounded-xl text-sm font-semibold border flex items-center justify-center gap-2 transition-all">
                        <i data-lucide="arrow-down-left" class="w-4 h-4 text-emerald-600"></i>
                        <span>Pemasukan</span>
                    </button>

                    <button type="button" @click="currentType = 'transfer'"
                            :class="currentType === 'transfer'
                                ? 'bg-white text-sky-700 shadow-xs border-slate-200'
                                : 'text-slate-600 hover:text-slate-900 border-transparent'"
                            class="py-2.5 px-3 rounded-xl text-sm font-semibold border flex items-center justify-center gap-2 transition-all">
                        <i data-lucide="arrow-left-right" class="w-4 h-4 text-sky-600"></i>
                        <span>Transfer</span>
                    </button>
                </div>

                {{-- Form Body --}}
                <form method="POST" action="{{ route('transactions.store') }}" class="p-6 sm:p-8 space-y-6">
                    @csrf

                    {{-- Hidden Type Input --}}
                    <input type="hidden" name="type" :value="currentType">

                    {{-- Transfer Info Callout --}}
                    <div x-show="currentType === 'transfer'" x-transition
                         class="p-4 bg-sky-50 border border-sky-100 rounded-xl flex items-start gap-3 text-sky-800 text-xs">
                        <i data-lucide="info" class="w-4 h-4 text-sky-600 shrink-0 mt-0.5"></i>
                        <div>
                            <span class="font-semibold">Informasi Transfer:</span>
                            Saldo akan dipindahkan dari dompet asal ke dompet tujuan secara otomatis. Nilai transfer tidak dihitung sebagai pemasukan atau pengeluaran dalam laporan keuangan.
                        </div>
                    </div>

                    {{-- Amount Input --}}
                    <div>
                        <label for="amount" class="block text-sm font-semibold text-slate-800 mb-1">
                            Jumlah Nominal (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-base">Rp</span>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount"
                                   x-model="rawAmount"
                                   value="{{ old('amount', '') }}"
                                   placeholder="0"
                                   required
                                   class="w-full pl-12 pr-4 py-3 text-lg font-bold text-slate-900 rounded-xl border @error('amount') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                        </div>
                        <div class="flex items-center justify-between mt-1.5">
                            <p class="text-xs text-slate-400">Pratinjau: <span class="font-semibold text-slate-700 tabular-nums" x-text="formatRupiah(rawAmount)"></span></p>
                            @error('amount')
                                <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Date & Wallet Rows --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Transaction Date --}}
                        <div>
                            <label for="transaction_date" class="block text-sm font-semibold text-slate-800 mb-1">
                                Tanggal Transaksi <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" id="transaction_date" name="transaction_date"
                                   value="{{ old('transaction_date', $todayDate) }}"
                                   required
                                   class="w-full py-2.5 px-3 text-sm rounded-xl border @error('transaction_date') border-rose-300 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                            @error('transaction_date')
                                <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Source / Primary Wallet --}}
                        <div>
                            <label for="wallet_id" class="block text-sm font-semibold text-slate-800 mb-1">
                                <span x-show="currentType === 'expense'">Dompet Pembayaran</span>
                                <span x-show="currentType === 'income'">Dompet Penerima</span>
                                <span x-show="currentType === 'transfer'">Dompet Asal (Sumber)</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <select id="wallet_id" name="wallet_id" required
                                    class="w-full py-2.5 px-3 text-sm rounded-xl border @error('wallet_id') border-rose-300 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                                <option value="" disabled {{ old('wallet_id', $selectedWalletId) ? '' : 'selected' }}>Pilih Dompet</option>
                                @foreach($activeWallets as $wallet)
                                    <option value="{{ $wallet->id }}"
                                        {{ (string)old('wallet_id', $selectedWalletId) === (string)$wallet->id ? 'selected' : '' }}>
                                        {{ $wallet->name }} (Saldo: {{ $wallet->formatted_balance }})
                                    </option>
                                @endforeach
                            </select>
                            @error('wallet_id')
                                <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Target Wallet (Transfer Only) --}}
                    <div x-show="currentType === 'transfer'" x-transition>
                        <label for="target_wallet_id" class="block text-sm font-semibold text-slate-800 mb-1">
                            Dompet Tujuan Transfer <span class="text-rose-500">*</span>
                        </label>
                        <select id="target_wallet_id" name="target_wallet_id"
                                :required="currentType === 'transfer'"
                                class="w-full py-2.5 px-3 text-sm rounded-xl border @error('target_wallet_id') border-rose-300 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                            <option value="" disabled {{ old('target_wallet_id', $selectedTargetWalletId) ? '' : 'selected' }}>Pilih Dompet Tujuan</option>
                            @foreach($activeWallets as $wallet)
                                <option value="{{ $wallet->id }}"
                                    {{ (string)old('target_wallet_id', $selectedTargetWalletId) === (string)$wallet->id ? 'selected' : '' }}>
                                    {{ $wallet->name }} (Saldo: {{ $wallet->formatted_balance }})
                                </option>
                            @endforeach
                        </select>
                        @error('target_wallet_id')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                        @if($activeWallets->count() < 2)
                            <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                Anda membutuhkan minimal 2 dompet aktif untuk melakukan transfer.
                            </p>
                        @endif
                    </div>

                    {{-- Category (Income & Expense Only) --}}
                    <div x-show="currentType !== 'transfer'" x-transition>
                        <label for="category_id" class="block text-sm font-semibold text-slate-800 mb-1">
                            Kategori Transaksi <span class="text-rose-500">*</span>
                        </label>

                        {{-- Expense Categories --}}
                        <div x-show="currentType === 'expense'">
                            <select id="category_id_expense" name="category_id"
                                    :required="currentType === 'expense'"
                                    :disabled="currentType !== 'expense'"
                                    class="w-full py-2.5 px-3 text-sm rounded-xl border @error('category_id') border-rose-300 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                                <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Pilih Kategori Pengeluaran</option>
                                @foreach($expenseCategories as $category)
                                    <option value="{{ $category->id }}" {{ (string)old('category_id') === (string)$category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Income Categories --}}
                        <div x-show="currentType === 'income'">
                            <select id="category_id_income" name="category_id"
                                    :required="currentType === 'income'"
                                    :disabled="currentType !== 'income'"
                                    class="w-full py-2.5 px-3 text-sm rounded-xl border @error('category_id') border-rose-300 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">
                                <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Pilih Kategori Pemasukan</option>
                                @foreach($incomeCategories as $category)
                                    <option value="{{ $category->id }}" {{ (string)old('category_id') === (string)$category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @error('category_id')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="notes" class="block text-sm font-semibold text-slate-800 mb-1">
                            Catatan (Opsional)
                        </label>
                        <textarea id="notes" name="notes" rows="3"
                                  placeholder="Contoh: Makan siang bersama rekan, gaji bulanan, transfer tabungan..."
                                  class="w-full py-2.5 px-3 text-sm rounded-xl border @error('notes') border-rose-300 @else border-slate-300 @enderror focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('transactions.index') }}"
                           class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                :class="{
                                    'bg-rose-600 hover:bg-rose-700': currentType === 'expense',
                                    'bg-emerald-600 hover:bg-emerald-700': currentType === 'income',
                                    'bg-sky-600 hover:bg-sky-700': currentType === 'transfer'
                                }"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-white text-sm font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span x-show="currentType === 'expense'">Simpan Pengeluaran</span>
                            <span x-show="currentType === 'income'">Simpan Pemasukan</span>
                            <span x-show="currentType === 'transfer'">Lakukan Transfer</span>
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>
