<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-rose-500/15 bg-gradient-to-r from-rose-600 to-red-500 px-4 py-2.5 font-semibold text-xs uppercase tracking-[0.18em] text-white shadow-[0_18px_32px_-24px_rgba(225,29,72,0.7)] transition duration-150 ease-in-out hover:-translate-y-px hover:from-rose-700 hover:to-red-600 focus:outline-none focus:ring-2 focus:ring-rose-500/35 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
