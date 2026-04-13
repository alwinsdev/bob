@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl border border-blue-200/70 bg-white/90 px-4 py-3 text-start text-base font-semibold text-slate-900 shadow-[0_16px_30px_-26px_rgba(37,99,235,0.45)] transition duration-150 ease-in-out'
            : 'block w-full rounded-2xl border border-transparent px-4 py-3 text-start text-base font-semibold text-slate-600 transition duration-150 ease-in-out hover:border-slate-200/80 hover:bg-white/80 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
