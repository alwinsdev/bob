@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-slate-200 bg-white/90 shadow-sm transition duration-150 ease-in-out focus:border-blue-500 focus:ring-blue-500/25']) }}>
