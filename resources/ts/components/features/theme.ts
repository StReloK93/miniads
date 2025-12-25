export type ThemeMode = 'system' | 'light' | 'dark';

const THEME_KEY = 'theme-mode';

export function applyTheme(mode: ThemeMode): void {
    const html = document.documentElement;

    // eski holatni tozalash
    html.classList.remove('p-dark');

    if (mode === 'dark') {
        html.classList.add('p-dark');
    }

    if (mode === 'system') {
        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        html.classList.toggle('p-dark', isDark);
    }

    localStorage.setItem(THEME_KEY, mode);
}

export function initTheme(): ThemeMode {
    const saved = (localStorage.getItem(THEME_KEY) as ThemeMode) || 'system';
    applyTheme(saved);

    // system theme real vaqtda o‘zgarsa
    if (saved === 'system') {
        window
            .matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', (e: MediaQueryListEvent) => {
                document.documentElement.classList.toggle('p-dark', e.matches);
            });
    }

    return saved;
}
