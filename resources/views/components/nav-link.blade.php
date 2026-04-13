@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-blue-200/70 bg-white/90 px-4 py-2 text-sm font-semibold leading-5 text-slate-900 shadow-[0_16px_30px_-26px_rgba(37,99,235,0.55)] transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full border border-transparent px-4 py-2 text-sm font-semibold leading-5 text-slate-500 transition duration-150 ease-in-out hover:border-slate-200/80 hover:bg-white/80 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
