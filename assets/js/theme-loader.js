// Inline Theme Loader - Prevent Flash on Page Refresh
// Runs synchronously BEFORE CSS loads to apply theme immediately

(function () {
    const themes = {
        light: {
            '--primary': '#2563eb',
            '--primary-hover': '#1d4ed8',
            '--primary-2': '#3b82f6',
            '--primary-dark': '#1e40af',
            '--primary-light': '#dbeafe',
            '--bg': '#f8fafc',
            '--surface': '#ffffff',
            '--surface-hover': '#f1f5f9',
            '--card': '#ffffff',
            '--muted-surface': '#f8fafc',
            '--text': '#0f172a',
            '--muted': '#64748b',
            '--text-muted': '#64748b',
            '--border': '#e2e8f0',
            '--border-light': '#f1f5f9',
            '--accent': '#2563eb',
            '--accent-soft': 'rgba(37, 99, 235, 0.08)',
            '--danger': '#ef4444',
            '--success': '#10b981',
            '--warning': '#f59e0b',
            '--sidebar-bg': '#0f172a',
            '--sidebar-text': '#f8fafc',
            '--sidebar-accent': 'rgba(255, 255, 255, 0.08)',
            '--shadow-sm': '0 1px 3px rgba(15, 23, 42, 0.06)',
            '--shadow': '0 4px 12px -2px rgba(15, 23, 42, 0.08)',
            '--shadow-md': '0 12px 24px -4px rgba(15, 23, 42, 0.12)',
            '--glass': 'rgba(255, 255, 255, 0.85)',
            '--glass-border': 'rgba(226, 232, 240, 0.8)',
            '--section-header': '#f0f9ff',
            '--section-header-text': '#0f172a'
        },
        dark: {
            '--primary': '#38bdf8',
            '--primary-hover': '#0ea5e9',
            '--primary-2': '#60a5fa',
            '--primary-dark': '#0284c7',
            '--primary-light': 'rgba(56, 189, 248, 0.15)',
            '--bg': '#090d16',
            '--surface': '#111827',
            '--surface-hover': '#1f293d',
            '--card': '#131b2e',
            '--muted-surface': '#0d1424',
            '--text': '#f1f5f9',
            '--muted': '#94a3b8',
            '--text-muted': '#94a3b8',
            '--border': '#1e293b',
            '--border-light': '#293548',
            '--accent': '#38bdf8',
            '--accent-soft': 'rgba(56, 189, 248, 0.12)',
            '--danger': '#f87171',
            '--success': '#34d399',
            '--warning': '#fbbf24',
            '--sidebar-bg': '#070a12',
            '--sidebar-text': '#e2e8f0',
            '--sidebar-accent': 'rgba(56, 189, 248, 0.12)',
            '--shadow-sm': '0 1px 3px rgba(0, 0, 0, 0.4)',
            '--shadow': '0 4px 16px rgba(0, 0, 0, 0.5)',
            '--shadow-md': '0 12px 30px rgba(0, 0, 0, 0.65)',
            '--glass': 'rgba(17, 24, 39, 0.85)',
            '--glass-border': 'rgba(255, 255, 255, 0.08)',
            '--section-header': '#151e32',
            '--section-header-text': '#38bdf8'
        },
        blue: {
            '--primary': '#3b82f6',
            '--primary-hover': '#2563eb',
            '--primary-2': '#60a5fa',
            '--primary-dark': '#1d4ed8',
            '--primary-light': 'rgba(59, 130, 246, 0.15)',
            '--bg': '#0a192f',
            '--surface': '#112240',
            '--surface-hover': '#1d335a',
            '--card': '#112240',
            '--muted-surface': '#0d1e38',
            '--text': '#e6f1ff',
            '--muted': '#8892b0',
            '--text-muted': '#8892b0',
            '--border': '#233554',
            '--border-light': '#2e4368',
            '--accent': '#60a5fa',
            '--accent-soft': 'rgba(96, 165, 250, 0.15)',
            '--danger': '#f87171',
            '--success': '#4ade80',
            '--warning': '#fbbf24',
            '--sidebar-bg': '#071324',
            '--sidebar-text': '#e6f1ff',
            '--sidebar-accent': 'rgba(59, 130, 246, 0.15)',
            '--shadow-sm': '0 1px 3px rgba(0, 0, 0, 0.4)',
            '--shadow': '0 4px 16px rgba(0, 0, 0, 0.5)',
            '--shadow-md': '0 12px 30px rgba(0, 0, 0, 0.65)',
            '--glass': 'rgba(17, 34, 64, 0.85)',
            '--glass-border': 'rgba(96, 165, 250, 0.15)',
            '--section-header': '#0d1e38',
            '--section-header-text': '#60a5fa'
        },
        monochrome: {
            '--primary': '#ffffff',
            '--primary-hover': '#e4e4e7',
            '--primary-2': '#d4d4d8',
            '--primary-dark': '#a1a1aa',
            '--primary-light': 'rgba(255, 255, 255, 0.15)',
            '--bg': '#121212',
            '--surface': '#1e1e1e',
            '--surface-hover': '#292929',
            '--card': '#1e1e1e',
            '--muted-surface': '#181818',
            '--text': '#ffffff',
            '--muted': '#a1a1aa',
            '--text-muted': '#a1a1aa',
            '--border': '#383838',
            '--border-light': '#484848',
            '--accent': '#ffffff',
            '--accent-soft': 'rgba(255, 255, 255, 0.12)',
            '--danger': '#ff5555',
            '--success': '#50fa7b',
            '--warning': '#f1fa8c',
            '--sidebar-bg': '#0a0a0a',
            '--sidebar-text': '#ffffff',
            '--sidebar-accent': 'rgba(255, 255, 255, 0.15)',
            '--shadow-sm': '0 1px 3px rgba(0, 0, 0, 0.8)',
            '--shadow': '0 4px 16px rgba(0, 0, 0, 0.9)',
            '--shadow-md': '0 12px 30px rgba(0, 0, 0, 0.95)',
            '--glass': 'rgba(30, 30, 30, 0.95)',
            '--glass-border': 'rgba(255, 255, 255, 0.2)',
            '--section-header': '#242424',
            '--section-header-text': '#ffffff'
        }
    };

    // Aliases
    themes.terang = themes.light;
    themes.gelap = themes.dark;
    themes.biru = themes.blue;

    // Jika tema hari besar / event khusus aktif, JANGAN timpa token tema event!
    if (window.isSpecialThemeActive === true || document.getElementById('holiday-theme-vars') || document.getElementById('special-theme-css')) {
        return;
    }

    // Jika tema server sudah diterapkan langsung oleh PHP, JANGAN timpa secara sepihak
    if (document.getElementById('server-theme-vars')) {
        return;
    }

    try {
        const cachedTheme = sessionStorage.getItem('theme_cache') || 'light';
        const theme = themes[cachedTheme] || themes.light;

        Object.entries(theme).forEach(([key, value]) => {
            document.documentElement.style.setProperty(key, value);
        });

        const cachedCustom = sessionStorage.getItem('custom_colors_cache');
        if (cachedCustom) {
            const custom = JSON.parse(cachedCustom);
            Object.entries(custom).forEach(([colorId, value]) => {
                const cssVar = colorId.replace('color-', '--');
                if (cssVar.startsWith('--sidebar-')) {
                    document.documentElement.style.setProperty(cssVar, value);
                } else if (cachedTheme === 'custom') {
                    document.documentElement.style.setProperty(cssVar, value);
                    if (cssVar === '--accent') {
                        document.documentElement.style.setProperty('--primary', value);
                    }
                }
            });
        }
    } catch (e) {
        // Fallback default
        const lightTheme = themes.light;
        Object.entries(lightTheme).forEach(([key, value]) => {
            document.documentElement.style.setProperty(key, value);
        });
    }
})();
