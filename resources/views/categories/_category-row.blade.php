{{-- Reusable category row partial for user-owned categories --}}
<div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors group">
    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
         style="background-color: {{ $category->color ?? '#64748B' }}20; color: {{ $category->color ?? '#64748B' }}">
        <i data-lucide="{{ $category->icon ?? 'tag' }}" class="w-4 h-4"></i>
    </div>
    <div class="flex-1 min-w-0">
        <span class="text-sm font-medium {{ $category->is_active ? 'text-slate-700' : 'text-slate-400 line-through' }}">{{ $category->name }}</span>
    </div>

    @if(!$category->is_active)
        <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md">Nonaktif</span>
    @endif

    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <a href="{{ route('categories.edit', $category) }}"
           class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition-colors" title="Edit">
            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
        </a>
        <form method="POST" action="{{ route('categories.toggle-active', $category) }}" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit"
                    class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 hover:text-amber-600 transition-colors"
                    title="{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                <i data-lucide="{{ $category->is_active ? 'eye-off' : 'eye' }}" class="w-3.5 h-3.5"></i>
            </button>
        </form>
    </div>
</div>
