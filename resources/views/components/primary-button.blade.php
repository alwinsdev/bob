<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-blue-500/20 bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-2.5 font-semibold text-xs uppercase tracking-[0.18em] text-white shadow-[0_20px_34px_-24px_rgba(37,99,235,0.82)] transition duration-150 ease-in-out hover:-translate-y-px hover:from-blue-700 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2 active:translate-y-0 disabled:opacity-60']) }}>
    {{ $slot }}
</button>
