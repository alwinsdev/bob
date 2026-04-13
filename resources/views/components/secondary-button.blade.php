<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white/90 px-4 py-2.5 font-semibold text-xs uppercase tracking-[0.18em] text-slate-700 shadow-[0_18px_32px_-28px_rgba(15,23,42,0.7)] transition duration-150 ease-in-out hover:-translate-y-px hover:border-slate-300 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:ring-offset-2 disabled:opacity-40']) }}>
    {{ $slot }}
</button>
