<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Kategori</h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola kategori pemasukan dan pengeluaran Anda</p>
            </div>
            <a href="{{ route('categories.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Tambah Kategori
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm animate-fade-in">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-emerald-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            {{-- User Custom Categories --}}
            <div>
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    Kategori Kustom Anda
                </h2>

                @if($userCategories->isEmpty())
                    <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="tag" class="w-7 h-7 text-slate-400"></i>
                        </div>
                        <h3 class="font-semibold text-slate-800">Belum ada kategori kustom</h3>
                        <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Anda dapat menggunakan kategori bawaan, atau tambahkan kategori sendiri sesuai kebutuhan.</p>
                        <a href="{{ route('categories.create') }}"
                           class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Buat Kategori Baru
                        </a>
                    </div>
                @else
                    @php
                        $userIncome = $userCategories->where('type', 'income');
                        $userExpense = $userCategories->where('type', 'expense');
                    @endphp

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- User Income --}}
                        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                <h3 class="text-sm font-semibold text-slate-700">Pemasukan ({{ $userIncome->count() }})</h3>
                            </div>
                            @if($userIncome->isEmpty())
                                <div class="p-5 text-center text-sm text-slate-400">Belum ada kategori pemasukan kustom.</div>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach($userIncome as $cat)
                                        @include('categories._category-row', ['category' => $cat])
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- User Expense --}}
                        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                <h3 class="text-sm font-semibold text-slate-700">Pengeluaran ({{ $userExpense->count() }})</h3>
                            </div>
                            @if($userExpense->isEmpty())
                                <div class="p-5 text-center text-sm text-slate-400">Belum ada kategori pengeluaran kustom.</div>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach($userExpense as $cat)
                                        @include('categories._category-row', ['category' => $cat])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Default Categories --}}
            <div>
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                    Kategori Bawaan Sistem
                </h2>
                <p class="text-xs text-slate-400 mb-4">Kategori ini tersedia untuk semua pengguna dan tidak dapat diubah.</p>

                @php
                    $defaultIncome = $defaultCategories->where('type', 'income');
                    $defaultExpense = $defaultCategories->where('type', 'expense');
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Default Income --}}
                    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <h3 class="text-sm font-semibold text-slate-700">Pemasukan ({{ $defaultIncome->count() }})</h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach($defaultIncome as $cat)
                                <div class="flex items-center gap-3 px-5 py-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                         style="background-color: {{ $cat->color }}20; color: {{ $cat->color }}">
                                        <i data-lucide="{{ $cat->icon ?? 'tag' }}" class="w-4 h-4"></i>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">{{ $cat->name }}</span>
                                    <span class="ml-auto text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">Bawaan</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Default Expense --}}
                    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <h3 class="text-sm font-semibold text-slate-700">Pengeluaran ({{ $defaultExpense->count() }})</h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach($defaultExpense as $cat)
                                <div class="flex items-center gap-3 px-5 py-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                         style="background-color: {{ $cat->color }}20; color: {{ $cat->color }}">
                                        <i data-lucide="{{ $cat->icon ?? 'tag' }}" class="w-4 h-4"></i>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">{{ $cat->name }}</span>
                                    <span class="ml-auto text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">Bawaan</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
