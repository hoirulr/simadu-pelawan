@props([
    'category' => 'Rendah',
    'score' => null,
])

@php
    $cat = is_object($category) ? $category->value : $category;
    $classes = match($cat) {
        'Rendah' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30',
        'Sedang' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30',
        'Tinggi' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30',
        default => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
    };
    $dotClasses = match($cat) {
        'Rendah' => 'bg-emerald-500',
        'Sedang' => 'bg-amber-500',
        'Tinggi' => 'bg-rose-500 animate-pulse',
        default => 'bg-slate-400',
    };
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $classes }}">
    <span class="size-1.5 rounded-full {{ $dotClasses }}"></span>
    <span>{{ $cat }}</span>
    @if($score !== null)
        <span class="opacity-75 font-mono">({{ $score }}/15)</span>
    @endif
</span>
