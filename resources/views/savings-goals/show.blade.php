<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('savings-goals.index') }}"
                   class="p-2 bg-slate-100 text-slate-600 hover:text-slate-900 hover:bg-slate-200 rounded-xl transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-bold text-xl text-slate-900 leading-tight">{{ $savingsGoal->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold
                            @if ($savingsGoal->status === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                            @elseif ($savingsGoal->status === 'paused') bg-amber-50 text-amber-700 border border-amber-200
                            @else bg-purple-50 text-purple-700 border border-purple-200
                            @endif">
                            {{ $savingsGoal->status_label }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Detail progres dan riwayat alokasi tabungan</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Quick Status Toggle Form --}}
                <form method="POST" action="{{ route('savings-goals.toggle-status', $savingsGoal) }}" class="inline-flex" x-data>
                    @csrf
                    @method('PATCH')
                    @if ($savingsGoal->status === 'active')
                        <button type="submit" name="status" value="completed"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-700 border border-purple-200 text-xs font-semibold rounded-xl hover:bg-purple-100 transition shadow-xs">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                            <span>Tandai Selesai</span>
                        </button>
                    @elseif ($savingsGoal->status === 'completed')
                        <button type="submit" name="status" value="active"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl hover:bg-emerald-100 transition shadow-xs">
                            <i data-lucide="play" class="w-3.5 h-3.5"></i>
                            <span>Aktifkan Kembali</span>
                        </button>
                    @endif
                </form>

                <a href="{{ route('savings-goals.edit', $savingsGoal) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    <span>Ubah</span>
                </a>

                <button @click="$dispatch('open-delete-goal-modal')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-50 text-red-700 border border-red-200 text-xs font-semibold rounded-xl hover:bg-red-100 transition">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        deleteGoalModal: false,
        deleteContributionModal: false,
        deleteContributionUrl: ''
    }"
    @open-delete-goal-modal.window="deleteGoalModal = true">
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

            {{-- ═══════════════════════════════════════ --}}
            {{-- HERO PROGRESS CARD                      --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Progres Tabungan</span>
                                <h2 class="text-3xl font-extrabold text-slate-900 mt-1">
                                    {{ $savingsGoal->formatted_saved }}
                                    <span class="text-base font-normal text-slate-400">/ {{ $savingsGoal->formatted_target_amount }}</span>
                                </h2>
                            </div>
                            <div class="text-right">
                                <span class="text-3xl font-extrabold text-purple-600">
                                    {{ $savingsGoal->progress_percentage }}%
                                </span>
                                @if ($savingsGoal->is_target_reached)
                                    <p class="text-[11px] font-semibold text-emerald-600 flex items-center justify-end gap-1 mt-0.5">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        Target Tercapai! 🎉
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        <div class="w-full bg-slate-100 rounded-full h-3.5 overflow-hidden">
                            <div class="h-3.5 rounded-full bg-gradient-to-r from-purple-500 via-indigo-500 to-emerald-500 transition-all duration-700"
                                style="width: {{ $savingsGoal->progress_percentage }}%"></div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-2 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-400">Sisa Target</span>
                                <p class="font-bold text-slate-900 mt-0.5 text-sm">{{ $savingsGoal->formatted_remaining }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-400">Target Tanggal</span>
                                <p class="font-bold text-slate-900 mt-0.5 text-sm">
                                    {{ $savingsGoal->target_date ? $savingsGoal->target_date->translatedFormat('d M Y') : 'Tidak ditentukan' }}
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 col-span-2 sm:col-span-1">
                                <span class="text-slate-400">Total Alokasi</span>
                                <p class="font-bold text-slate-900 mt-0.5 text-sm">{{ $contributions->total() }} kali simpan</p>
                            </div>
                        </div>

                        @if ($savingsGoal->notes)
                            <p class="text-xs text-slate-500 italic bg-purple-50/50 p-3 rounded-xl border border-purple-100">
                                "{{ $savingsGoal->notes }}"
                            </p>
                        @endif
                    </div>

                    {{-- Add Contribution Form Card --}}
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100/80 p-5 rounded-2xl border border-slate-200">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5 mb-3">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i>
                            Tambah Alokasi Tabungan
                        </h3>
                        <form method="POST" action="{{ route('goal-contributions.store', $savingsGoal) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="amount" class="text-xs font-semibold text-slate-700">Nominal (Rp)</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                        Rp
                                    </span>
                                    <input type="number" id="amount" name="amount" step="1000" min="1000" required
                                           class="pl-9 text-xs block w-full border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500"
                                           placeholder="100000" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label for="contribution_date" class="text-xs font-semibold text-slate-700">Tanggal</label>
                                    <input type="date" id="contribution_date" name="contribution_date" value="{{ date('Y-m-d') }}" required
                                           class="mt-1 text-xs block w-full border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500" />
                                </div>
                                <div>
                                    <label for="wallet_id" class="text-xs font-semibold text-slate-700">Dompet Asal (Opsional)</label>
                                    <select id="wallet_id" name="wallet_id" class="mt-1 text-xs block w-full border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($activeWallets as $w)
                                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="contrib_notes" class="text-xs font-semibold text-slate-700">Catatan (Opsional)</label>
                                <input type="text" id="contrib_notes" name="notes" placeholder="Contoh: Sisa THR, bonus lembur"
                                       class="mt-1 text-xs block w-full border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500" />
                            </div>

                            <button type="submit"
                                    class="w-full mt-2 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition shadow-xs flex items-center justify-center gap-1.5">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                Simpan Alokasi
                            </button>
                        </form>
                        <p class="text-[10px] text-slate-400 mt-2 text-center">
                            *Alokasi tabungan untuk pelacakan target & tidak mengurangi saldo dompet secara langsung (PRD 5.8).
                        </p>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- CONTRIBUTION HISTORY TABLE              --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Riwayat Alokasi Tabungan</h2>
                        <p class="text-xs text-slate-500">Semua setoran yang telah Anda catat untuk target ini</p>
                    </div>
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        {{ $contributions->total() }} Catatan
                    </span>
                </div>

                @if ($contributions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                    <th class="py-3 px-3">Tanggal</th>
                                    <th class="py-3 px-3">Nominal</th>
                                    <th class="py-3 px-3">Dompet Asal</th>
                                    <th class="py-3 px-3">Catatan</th>
                                    <th class="py-3 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($contributions as $c)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-3 font-medium text-slate-900">
                                            {{ $c->contribution_date->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="py-3 px-3 font-bold text-emerald-600">
                                            +{{ $c->formatted_amount }}
                                        </td>
                                        <td class="py-3 px-3">
                                            {{ $c->wallet?->name ?? '—' }}
                                        </td>
                                        <td class="py-3 px-3 text-slate-500">
                                            {{ $c->notes ?: '—' }}
                                        </td>
                                        <td class="py-3 px-3 text-right">
                                            <button @click="deleteContributionModal = true; deleteContributionUrl = '{{ route('goal-contributions.destroy', $c) }}'"
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                                    title="Hapus Catatan">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $contributions->links() }}
                    </div>
                @else
                    <div class="text-center py-10">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                        <p class="text-xs text-slate-500">Belum ada alokasi dana untuk target tabungan ini.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Gunakan formulir di atas untuk mencatat alokasi tabungan pertama Anda.</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- Delete Goal Modal --}}
        <div x-show="deleteGoalModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="deleteGoalModal"
                     class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                     @click="deleteGoalModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="deleteGoalModal"
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-red-100 text-red-600 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-bold text-slate-900">Hapus Target Tabungan</h3>
                            <div class="mt-2">
                                <p class="text-xs text-slate-600">
                                    Apakah Anda yakin ingin menghapus target "<strong>{{ $savingsGoal->name }}</strong>"? Seluruh riwayat alokasi tabungan untuk target ini juga akan terhapus.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <form action="{{ route('savings-goals.destroy', $savingsGoal) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-xs px-4 py-2 bg-red-600 text-xs font-semibold text-white hover:bg-red-700 transition sm:w-auto">
                                Ya, Hapus Target
                            </button>
                        </form>
                        <button type="button"
                                @click="deleteGoalModal = false"
                                class="mt-2 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-xs px-4 py-2 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition sm:w-auto">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete Contribution Modal --}}
        <div x-show="deleteContributionModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="deleteContributionModal"
                     class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                     @click="deleteContributionModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="deleteContributionModal"
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-red-100 text-red-600 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-bold text-slate-900">Hapus Catatan Alokasi</h3>
                            <div class="mt-2">
                                <p class="text-xs text-slate-600">
                                    Apakah Anda yakin ingin menghapus catatan alokasi ini? Total tabungan Anda akan disesuaikan kembali.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <form :action="deleteContributionUrl" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-xs px-4 py-2 bg-red-600 text-xs font-semibold text-white hover:bg-red-700 transition sm:w-auto">
                                Ya, Hapus Catatan
                            </button>
                        </form>
                        <button type="button"
                                @click="deleteContributionModal = false"
                                class="mt-2 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-xs px-4 py-2 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition sm:w-auto">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
