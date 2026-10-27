<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Ubah Target Tabungan</h1>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui rincian atau status target tabungan "{{ $savingsGoal->name }}"</p>
            </div>
            <a href="{{ route('savings-goals.show', $savingsGoal) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <form method="POST" action="{{ route('savings-goals.update', $savingsGoal) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Name --}}
                    <div>
                        <x-input-label for="name" value="Nama Target Tabungan" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-sm" :value="old('name', $savingsGoal->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    {{-- Status --}}
                    <div>
                        <x-input-label for="status" value="Status Target" />
                        <select id="status" name="status" class="mt-1 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500" required>
                            <option value="active" {{ old('status', $savingsGoal->status) === 'active' ? 'selected' : '' }}>Aktif (Sedang Berjalan)</option>
                            <option value="paused" {{ old('status', $savingsGoal->status) === 'paused' ? 'selected' : '' }}>Dijeda (Sementara Berhenti)</option>
                            <option value="completed" {{ old('status', $savingsGoal->status) === 'completed' ? 'selected' : '' }}>Selesai (Target Tercapai)</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>

                    {{-- Target Amount --}}
                    <div>
                        <x-input-label for="target_amount" value="Target Nominal (Rp)" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
                                Rp
                            </span>
                            <x-text-input id="target_amount" name="target_amount" type="number" step="1000" min="1000" class="pl-11 block w-full text-sm" :value="old('target_amount', (int)$savingsGoal->target_amount)" required />
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Dana yang sudah terkumpul saat ini: <strong>{{ $savingsGoal->formatted_saved }}</strong></p>
                        <x-input-error :messages="$errors->get('target_amount')" class="mt-1" />
                    </div>

                    {{-- Target Date --}}
                    <div>
                        <x-input-label for="target_date" value="Target Tanggal Pencapaian (Opsional)" />
                        <x-text-input id="target_date" name="target_date" type="date" class="mt-1 block w-full text-sm" :value="old('target_date', $savingsGoal->target_date?->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('target_date')" class="mt-1" />
                    </div>

                    {{-- Notes --}}
                    <div>
                        <x-input-label for="notes" value="Catatan / Deskripsi (Opsional)" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $savingsGoal->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('savings-goals.show', $savingsGoal) }}"
                           class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                            Batal
                        </a>
                        <x-primary-button class="text-xs">
                            Simpan Perubahan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
