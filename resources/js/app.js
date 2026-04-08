import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

function isCompactSidebarEnabled(value) {
    return value === true || value === 'true' || value === 1 || value === '1';
}

function isMobileViewport() {
    return window.matchMedia('(max-width: 1024px)').matches;
}

function applySidebarMode() {
    const forcedCompact = isMobileViewport();
    const userCompact = localStorage.getItem('bob_compact_sidebar') === 'true';
    const shouldCollapse = forcedCompact || userCompact;

    document.documentElement.classList.toggle('bob-mobile-compact', forcedCompact);
    document.documentElement.classList.toggle('bob-sidebar-collapsed', shouldCollapse);
}

/**
 * Apply persisted theme from localStorage BEFORE Alpine starts.
 * This prevents a flash of the wrong theme (FOUT).
 */
(function applyTheme() {
    const savedTheme = localStorage.getItem('bob_theme') || 'dark';
    if (savedTheme === 'light') {
        document.documentElement.classList.add('bob-light');
    } else {
        document.documentElement.classList.remove('bob-light');
    }
})();

/**
 * Global helper: toggle theme from anywhere.
 * Usage: window.bobSetTheme('light') or window.bobSetTheme('dark')
 */
window.bobSetTheme = function(theme) {
    localStorage.setItem('bob_theme', theme);
    if (theme === 'light') {
        document.documentElement.classList.add('bob-light');
    } else {
        document.documentElement.classList.remove('bob-light');
    }
};

/**
 * Global helper: toggle compact sidebar mode.
 * Usage: window.bobSetCompactSidebar(true) or window.bobSetCompactSidebar(false)
 */
window.bobSetCompactSidebar = function(compactEnabled) {
    const compact = isCompactSidebarEnabled(compactEnabled);

    localStorage.setItem('bob_compact_sidebar', compact ? 'true' : 'false');
    applySidebarMode();
};

/**
 * Apply persisted compact sidebar state BEFORE Alpine starts.
 */
(function applyCompactSidebar() {
    applySidebarMode();
})();

window.addEventListener('resize', applySidebarMode);

/**
 * Global Fetch Wrapper for Session Expiry Handling (Enterprise Standard)
 * Detects 401 Unauthorized or 419 CSRF Expired and redirects to login.
 */
(function interceptFetch() {
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
        try {
            const response = await originalFetch(...args);
            if (!response.ok && (response.status === 401 || response.status === 419)) {
                // Ignore requests that specifically shouldn't redirect if needed
                console.warn('[Session] Session expired or unauthorized. Redirecting to login...');
                window.location.href = '/login?session_expired=1';
            }
            return response;
        } catch (error) {
            // Rethrow for catch blocks in specific components
            throw error;
        }
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});