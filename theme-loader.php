<?php
/**
 * Server-Side Theme Loader
 * Exclusive Theme Controller:
 * - Jika Tema Hari Besar / Event aktif: Tema Dasar dinonaktifkan total (Zero Pollution)
 * - Jika Tema Hari Besar tidak aktif: Tema Dasar diterapkan secara normal
 * Animasi partikel (ketupat, lentera, salju) dinonaktifkan agar antarmuka bersih & profesional.
 */

if (!isset($pdo)) {
    $pdo = require __DIR__ . '/src/db.php';
}

require_once __DIR__ . '/src/ThemeModel.php';
$themeModel = new ThemeModel($pdo);

// Resolve school_id accurately for multi-tenant isolation
$school_id = $_SESSION['user']['school_id'] ?? ($sid ?? ($school['id'] ?? null));

// JIKA TIDAK ADA KONTEKS SEKOLAH (Misal di Landing Page / index.php publik):
// Tetap gunakan tema bawaan/default platform dan jangan terapkan tema tenant manapun!
if (!$school_id) {
    return;
}

// 1. CEK TEMA KHUSUS / HARI BESAR PERTAMA KALI
$activeKey = $themeModel->checkSpecialTheme($school_id);

if (!function_exists('hexToRgb_local')) {
    function hexToRgb_local($hex) {
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return "$r, $g, $b";
    }
}

if ($activeKey) {
    // =========================================================================
    // MODE EKSKLUSIF TEMA HARI BESAR: TEMA DASAR DINONAKTIFKAN TOTAL
    // Mencegah percampuran rule dark.css / base theme yang menyebabkan teks samar
    // =========================================================================
    $configPath = __DIR__ . '/theme-config.json';
    $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
    $theme = $config[$activeKey] ?? [
        'primary_color' => '#059669',
        'secondary_color' => '#064e3b',
        'accent_gold' => '#fbbf24',
        'text_color' => '#0f172a',
        'background_color' => '#f0fdf4',
        'card_color' => '#ffffff',
        'border_color' => '#d1fae5',
        'name' => ucfirst($activeKey),
        'tagline' => 'Peringatan Hari Besar',
        'badge' => strtoupper($activeKey),
        'icon' => 'mdi:calendar-star'
    ];

    $primary = $theme['primary_color'];
    $secondary = $theme['secondary_color'] ?? $primary;
    $accent_gold = $theme['accent_gold'] ?? '#fbbf24';
    $bg = $theme['background_color'] ?? '#ffffff';
    $text = $theme['text_color'] ?? '#0f172a';
    $card = $theme['card_color'] ?? '#ffffff';
    $border = $theme['border_color'] ?? '#e2e8f0';
    $eventName = htmlspecialchars($theme['name'] ?? ucfirst($activeKey));
    $eventTagline = htmlspecialchars($theme['tagline'] ?? '');
    $eventBadge = htmlspecialchars($theme['badge'] ?? strtoupper($activeKey));
    $eventIcon = htmlspecialchars($theme['icon'] ?? 'mdi:calendar-star');
    $rgb_primary = hexToRgb_local($primary);

    echo "<!-- National Holiday Event Active (Exclusive Mode - Base Theme Disabled): $activeKey -->\n";
    echo "<script>
        window.isSpecialThemeActive = true;
        window.activeSpecialThemeKey = '{$activeKey}';
        // Hapus stylesheet tema dasar dari DOM jika ada
        (function() {
            const baseLink = document.getElementById('base-theme-link');
            if (baseLink) baseLink.remove();
            // Bersihkan inline properties sisa tema dasar dari documentElement
            ['--surface', '--card', '--muted-surface', '--text', '--muted', '--text-muted', '--border', '--border-light', '--bg', '--primary', '--accent'].forEach(function(v) {
                document.documentElement.style.removeProperty(v);
            });
        })();
    </script>\n";

    echo <<<EOT
    <style id="holiday-theme-vars">
        :root {
            --primary: {$primary} !important;
            --primary-hover: {$secondary} !important;
            --primary-dark: {$secondary} !important;
            --primary-light: rgba({$rgb_primary}, 0.12) !important;
            --accent: {$primary} !important;
            --accent-soft: rgba({$rgb_primary}, 0.08) !important;
            --accent-gold: {$accent_gold} !important;
            --bg: {$bg} !important;
            --surface: #ffffff !important;
            --surface-hover: #f8fafc !important;
            --card: #ffffff !important;
            --muted-surface: #f8fafc !important;
            --text: {$text} !important;
            --muted: #475569 !important;
            --text-muted: #475569 !important;
            --border: {$border} !important;
            --border-light: rgba({$rgb_primary}, 0.15) !important;
            --sidebar-bg: {$secondary} !important;
            --sidebar-text: #ffffff !important;
            --sidebar-accent: rgba(255, 255, 255, 0.12) !important;
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06) !important;
            --shadow: 0 4px 14px rgba(15, 23, 42, 0.08) !important;
            --shadow-md: 0 12px 28px rgba(15, 23, 42, 0.12) !important;
        }
        
        html body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background-color: {$bg};
            background-image: 
                radial-gradient(at 0% 0%, rgba({$rgb_primary}, 0.07) 0px, transparent 45%),
                radial-gradient(at 100% 100%, rgba({$rgb_primary}, 0.04) 0px, transparent 45%);
        }

        /* --- CELEBRATORY TOPBAR RIBBON --- */
        .holiday-celebration-ribbon {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 42px;
            max-height: 42px;
            box-sizing: border-box;
            z-index: 1001;
            background: linear-gradient(90deg, {$secondary} 0%, {$primary} 50%, {$secondary} 100%);
            color: #ffffff;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 2px solid {$accent_gold};
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 769px) {
            body:has(.nav-sidebar) .holiday-celebration-ribbon {
                left: 240px;
                width: calc(100% - 240px);
            }
        }

        @media (max-width: 768px) {
            .holiday-celebration-ribbon {
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                padding: 0 12px;
            }
        }

        .holiday-ribbon-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            overflow: hidden;
        }

        .holiday-ribbon-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid {$accent_gold};
            color: {$accent_gold};
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 999px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .holiday-ribbon-text {
            font-size: 13px;
            font-weight: 500;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .holiday-ribbon-text strong {
            font-weight: 800;
            color: #ffffff;
        }

        .holiday-ribbon-sep {
            opacity: 0.6;
            margin: 0 6px;
        }

        .holiday-ribbon-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s ease;
            flex-shrink: 0;
        }

        .holiday-ribbon-close:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        /* Adjust layout when ribbon is active (Height: 42px) */
        /* 1. Admin Topbar & Content */
        body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .topbar,
        body.has-holiday-ribbon .topbar {
            top: 42px !important;
        }

        body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .content,
        body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .app .content,
        body.has-holiday-ribbon .content,
        body.has-holiday-ribbon .app .content {
            margin-top: 106px !important;
        }

        /* 2. Student / Member Header (.header & header.header) - PENTING: Mencegah tertutup seperti di Gambar 2 */
        body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .header,
        body:has(.holiday-celebration-ribbon:not([style*="display: none"])) header.header,
        body.has-holiday-ribbon .header,
        body.has-holiday-ribbon header.header {
            top: 42px !important;
            margin-top: 42px !important;
        }

        /* 3. Mobile Hamburger Toggle */
        body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .nav-toggle,
        body.has-holiday-ribbon .nav-toggle {
            top: 46px !important;
        }

        /* 4. Pages without Sidebar (Landing/Public/Login) */
        body:not(:has(.nav-sidebar)):has(.holiday-celebration-ribbon:not([style*="display: none"])),
        body.has-holiday-ribbon:not(:has(.nav-sidebar)) {
            padding-top: 42px !important;
        }

        body:not(:has(.nav-sidebar)):has(.holiday-celebration-ribbon:not([style*="display: none"])) .navbar,
        body:not(:has(.nav-sidebar)):has(.holiday-celebration-ribbon:not([style*="display: none"])) nav.landing-navbar,
        body:not(:has(.nav-sidebar)):has(.holiday-celebration-ribbon:not([style*="display: none"])) header.main-header,
        body.has-holiday-ribbon:not(:has(.nav-sidebar)) .navbar,
        body.has-holiday-ribbon:not(:has(.nav-sidebar)) nav.landing-navbar,
        body.has-holiday-ribbon:not(:has(.nav-sidebar)) header.main-header {
            top: 42px !important;
        }

        @media (max-width: 768px) {
            .holiday-ribbon-text {
                font-size: 11px;
            }
            .holiday-ribbon-pill {
                display: none;
            }
            body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .topbar,
            body.has-holiday-ribbon .topbar {
                left: 0 !important;
            }
            body:has(.holiday-celebration-ribbon:not([style*="display: none"])) .header,
            body:has(.holiday-celebration-ribbon:not([style*="display: none"])) header.header,
            body.has-holiday-ribbon .header,
            body.has-holiday-ribbon header.header {
                margin-left: 0 !important;
            }
        }
    </style>
    
    <div class="holiday-celebration-ribbon" id="holidayRibbonBar">
        <div class="holiday-ribbon-content">
            <div class="holiday-ribbon-pill">
                <iconify-icon icon="{$eventIcon}"></iconify-icon>
                <span>{$eventBadge}</span>
            </div>
            <div class="holiday-ribbon-text">
                <strong>{$eventName}</strong>
                <span class="holiday-ribbon-sep">•</span>
                <span>{$eventTagline}</span>
            </div>
        </div>
        <button type="button" class="holiday-ribbon-close" onclick="dismissHolidayRibbon('{$activeKey}')" title="Sembunyikan Pengumuman">
            <iconify-icon icon="mdi:close"></iconify-icon>
        </button>
    </div>
    <script>
        (function() {
            // Bersihkan flag dismiss lama agar ucapan selalu tampil segar saat tema aktif
            try {
                sessionStorage.removeItem('dismiss_holiday_{$activeKey}');
                sessionStorage.removeItem('dismiss_holiday_kemerdekaan');
                sessionStorage.removeItem('dismiss_holiday_idulfitri');
                sessionStorage.removeItem('dismiss_holiday_hariguru');
                sessionStorage.removeItem('dismiss_holiday_tahunbaru');
                sessionStorage.removeItem('dismiss_holiday_imlek');
                sessionStorage.removeItem('dismiss_holiday_natal');
            } catch (e) {}

            function syncRibbonState() {
                const el = document.getElementById('holidayRibbonBar');
                if (el && el.style.display !== 'none') {
                    document.body.classList.add('has-holiday-ribbon');
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', syncRibbonState);
            } else {
                syncRibbonState();
            }
        })();

        function dismissHolidayRibbon(k) {
            const el = document.getElementById('holidayRibbonBar');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-100%)';
                setTimeout(function() { 
                    el.style.display = 'none';
                    document.body.classList.remove('has-holiday-ribbon');
                    document.querySelectorAll('.topbar').forEach(function(t) { t.style.top = '0'; });
                    document.querySelectorAll('.content, .app .content').forEach(function(c) { c.style.marginTop = '64px'; });
                    document.querySelectorAll('.header, header.header').forEach(function(h) { 
                        h.style.top = '0'; 
                        h.style.marginTop = '0'; 
                    });
                    document.querySelectorAll('.nav-toggle').forEach(function(nt) { nt.style.top = '4px'; });
                    document.querySelectorAll('.navbar, nav.landing-navbar, header.main-header').forEach(function(nav) { nav.style.top = '0'; });
                    document.body.style.paddingTop = '0';
                }, 250);
            }
        }
    </script>
EOT;

    $specialCssPath = "/perpustakaan-online/public/themes/special/{$activeKey}.css";
    $fsPath = __DIR__ . "/public/themes/special/{$activeKey}.css";
    if (file_exists($fsPath)) {
        echo "<link rel='stylesheet' id='special-theme-css' href='{$specialCssPath}'>\n";
    }

} else {
    // =========================================================================
    // MODE TEMA DASAR SEKOLAH (JIKA TIDAK ADA TEMA HARI BESAR YANG AKTIF)
    // =========================================================================
    echo "<script>
        window.isSpecialThemeActive = false;
        window.activeSpecialThemeKey = null;
        // Bersihkan sisa elemen tema khusus dari DOM jika ada
        (function() {
            const specialLink = document.getElementById('special-theme-css');
            if (specialLink) specialLink.remove();
            const holidayVars = document.getElementById('holiday-theme-vars');
            if (holidayVars) holidayVars.remove();
            const holidayRibbon = document.getElementById('holidayRibbonBar');
            if (holidayRibbon) holidayRibbon.remove();
        })();
    </script>\n";

    $themeData = $school_id ? $themeModel->getThemeData($school_id) : ['theme_name' => 'light', 'custom_colors' => []];
    $activeThemeName = $themeData['theme_name'] ?? 'light';
    $customColors = $themeData['custom_colors'] ?? [];

    $serverThemePalettes = [
        'light' => [
            '--primary' => '#2563eb',
            '--primary-hover' => '#1d4ed8',
            '--primary-2' => '#3b82f6',
            '--primary-dark' => '#1e40af',
            '--primary-light' => '#dbeafe',
            '--bg' => '#f8fafc',
            '--surface' => '#ffffff',
            '--surface-hover' => '#f1f5f9',
            '--card' => '#ffffff',
            '--muted-surface' => '#f8fafc',
            '--text' => '#0f172a',
            '--muted' => '#64748b',
            '--text-muted' => '#64748b',
            '--border' => '#e2e8f0',
            '--border-light' => '#f1f5f9',
            '--accent' => '#2563eb',
            '--accent-soft' => 'rgba(37, 99, 235, 0.08)',
            '--danger' => '#ef4444',
            '--success' => '#10b981',
            '--warning' => '#f59e0b',
            '--sidebar-bg' => '#0f172a',
            '--sidebar-text' => '#f8fafc',
            '--sidebar-accent' => 'rgba(255, 255, 255, 0.08)',
            '--shadow-sm' => '0 1px 3px rgba(15, 23, 42, 0.06)',
            '--shadow' => '0 4px 12px -2px rgba(15, 23, 42, 0.08)',
            '--shadow-md' => '0 12px 24px -4px rgba(15, 23, 42, 0.12)',
            '--glass' => 'rgba(255, 255, 255, 0.85)',
            '--glass-border' => 'rgba(226, 232, 240, 0.8)',
            '--section-header' => '#f0f9ff',
            '--section-header-text' => '#0f172a'
        ],
        'dark' => [
            '--primary' => '#38bdf8',
            '--primary-hover' => '#0ea5e9',
            '--primary-2' => '#60a5fa',
            '--primary-dark' => '#0284c7',
            '--primary-light' => 'rgba(56, 189, 248, 0.15)',
            '--bg' => '#090d16',
            '--surface' => '#111827',
            '--surface-hover' => '#1f293d',
            '--card' => '#131b2e',
            '--muted-surface' => '#0d1424',
            '--text' => '#f1f5f9',
            '--muted' => '#94a3b8',
            '--text-muted' => '#94a3b8',
            '--border' => '#1e293b',
            '--border-light' => '#293548',
            '--accent' => '#38bdf8',
            '--accent-soft' => 'rgba(56, 189, 248, 0.12)',
            '--danger' => '#f87171',
            '--success' => '#34d399',
            '--warning' => '#fbbf24',
            '--sidebar-bg' => '#070a12',
            '--sidebar-text' => '#e2e8f0',
            '--sidebar-accent' => 'rgba(56, 189, 248, 0.12)',
            '--shadow-sm' => '0 1px 3px rgba(0, 0, 0, 0.4)',
            '--shadow' => '0 4px 16px rgba(0, 0, 0, 0.5)',
            '--shadow-md' => '0 12px 30px rgba(0, 0, 0, 0.65)',
            '--glass' => 'rgba(17, 24, 39, 0.85)',
            '--glass-border' => 'rgba(255, 255, 255, 0.08)',
            '--section-header' => '#151e32',
            '--section-header-text' => '#38bdf8'
        ],
        'blue' => [
            '--primary' => '#3b82f6',
            '--primary-hover' => '#2563eb',
            '--primary-2' => '#60a5fa',
            '--primary-dark' => '#1d4ed8',
            '--primary-light' => 'rgba(59, 130, 246, 0.15)',
            '--bg' => '#0a192f',
            '--surface' => '#112240',
            '--surface-hover' => '#1d335a',
            '--card' => '#112240',
            '--muted-surface' => '#0d1e38',
            '--text' => '#e6f1ff',
            '--muted' => '#8892b0',
            '--text-muted' => '#8892b0',
            '--border' => '#233554',
            '--border-light' => '#2e4368',
            '--accent' => '#60a5fa',
            '--accent-soft' => 'rgba(96, 165, 250, 0.15)',
            '--danger' => '#f87171',
            '--success' => '#4ade80',
            '--warning' => '#fbbf24',
            '--sidebar-bg' => '#071324',
            '--sidebar-text' => '#e6f1ff',
            '--sidebar-accent' => 'rgba(59, 130, 246, 0.15)',
            '--shadow-sm' => '0 1px 3px rgba(0, 0, 0, 0.4)',
            '--shadow' => '0 4px 16px rgba(0, 0, 0, 0.5)',
            '--shadow-md' => '0 12px 30px rgba(0, 0, 0, 0.65)',
            '--glass' => 'rgba(17, 34, 64, 0.85)',
            '--glass-border' => 'rgba(96, 165, 250, 0.15)',
            '--section-header' => '#0d1e38',
            '--section-header-text' => '#60a5fa'
        ],
        'monochrome' => [
            '--primary' => '#ffffff',
            '--primary-hover' => '#e4e4e7',
            '--primary-2' => '#d4d4d8',
            '--primary-dark' => '#a1a1aa',
            '--primary-light' => 'rgba(255, 255, 255, 0.15)',
            '--bg' => '#121212',
            '--surface' => '#1e1e1e',
            '--surface-hover' => '#292929',
            '--card' => '#1e1e1e',
            '--muted-surface' => '#181818',
            '--text' => '#ffffff',
            '--muted' => '#a1a1aa',
            '--text-muted' => '#a1a1aa',
            '--border' => '#383838',
            '--border-light' => '#484848',
            '--accent' => '#ffffff',
            '--accent-soft' => 'rgba(255, 255, 255, 0.12)',
            '--danger' => '#ff5555',
            '--success' => '#50fa7b',
            '--warning' => '#f1fa8c',
            '--sidebar-bg' => '#0a0a0a',
            '--sidebar-text' => '#ffffff',
            '--sidebar-accent' => 'rgba(255, 255, 255, 0.15)',
            '--shadow-sm' => '0 1px 3px rgba(0, 0, 0, 0.8)',
            '--shadow' => '0 4px 16px rgba(0, 0, 0, 0.9)',
            '--shadow-md' => '0 12px 30px rgba(0, 0, 0, 0.95)',
            '--glass' => 'rgba(30, 30, 30, 0.95)',
            '--glass-border' => 'rgba(255, 255, 255, 0.2)',
            '--section-header' => '#242424',
            '--section-header-text' => '#ffffff'
        ]
    ];

    // Aliases
    $serverThemePalettes['terang'] = $serverThemePalettes['light'];
    $serverThemePalettes['gelap'] = $serverThemePalettes['dark'];
    $serverThemePalettes['biru'] = $serverThemePalettes['blue'];

    $paletteToApply = $serverThemePalettes[$activeThemeName] ?? $serverThemePalettes['light'];

    // Render initial server-side CSS tokens
    echo "<!-- Synced Server Theme: $activeThemeName -->\n<style id='server-theme-vars'>\n:root {\n";
    foreach ($paletteToApply as $varName => $varVal) {
        echo "    $varName: $varVal;\n";
    }

    // Custom overrides
    if (!empty($customColors) && is_array($customColors)) {
        foreach ($customColors as $key => $value) {
            $cssVar = str_replace('color-', '--', $key);
            if (strpos($cssVar, '--sidebar-') === 0) {
                echo "    $cssVar: $value !important;\n";
            } elseif ($activeThemeName === 'custom') {
                echo "    $cssVar: $value !important;\n";
                if ($cssVar === '--accent') {
                    echo "    --primary: $value !important;\n";
                }
            }
        }
    }
    echo "}\n</style>\n";

    // Inject base theme stylesheet if not default light
    if ($activeThemeName !== 'light' && file_exists(__DIR__ . "/public/themes/base/{$activeThemeName}.css")) {
        echo "<link rel='stylesheet' id='base-theme-link' href='/perpustakaan-online/public/themes/base/{$activeThemeName}.css'>\n";
    } else {
        echo "<script>(function(){ const b = document.getElementById('base-theme-link'); if (b) b.remove(); })();</script>\n";
    }
}
?>
<style id="anti-samar-global-styles">
/* ==========================================================================
   UNIVERSAL ANTI-SAMAR: FORM, SEARCH BAR, AND FILTER DROPDOWNS
   Memastikan tidak ada kolom input, search bar, dropdown kategori, atau filter
   yang berlatar abu-abu gelap dengan teks samar di seluruh tema.
   ========================================================================== */
html body input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]),
html body select,
html body textarea,
html body .form-control,
html body .search-input,
html body .modern-search-input,
html body .filter-select,
html body .sort-select,
html body .category-dropdown-select,
html body .comment-textarea {
    background-color: var(--card, #ffffff) !important;
    color: var(--text, #0f172a) !important;
    border: 1.5px solid var(--border, #e2e8f0) !important;
}

html body input:focus,
html body select:focus,
html body textarea:focus,
html body .form-control:focus,
html body .search-input:focus,
html body .modern-search-input:focus,
html body .filter-select:focus,
html body .sort-select:focus,
html body .category-dropdown-select:focus,
html body .comment-textarea:focus {
    background-color: var(--card, #ffffff) !important;
    color: var(--text, #0f172a) !important;
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px var(--accent-soft, rgba(37, 99, 235, 0.15)) !important;
    outline: none !important;
}

html body input::placeholder,
html body textarea::placeholder,
html body .search-input::placeholder,
html body .modern-search-input::placeholder {
    color: var(--text-muted, var(--muted, #64748b)) !important;
    opacity: 0.8 !important;
}

html body .search-icon,
html body .search-bar-container iconify-icon,
html body .control-group iconify-icon {
    color: var(--text-muted, var(--muted, #64748b)) !important;
}

html body select option {
    background-color: var(--card, #ffffff) !important;
    color: var(--text, #0f172a) !important;
}

/* ==========================================================================
   UNIVERSAL BOOK MODAL & STATUS BADGE: ANTI-SAMAR & BEBAS BORDER
   Mencegah hover effect berlatar putih dengan teks samar di seluruh tema,
   serta memastikan badge status ketersediaan tidak memiliki border kaku.
   ========================================================================== */
html body .modal-book-item {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

html body .modal-book-item:hover {
    border-color: var(--primary) !important;
    background: color-mix(in srgb, var(--primary) 12%, var(--card, var(--bg, #111827))) !important;
    box-shadow: 0 4px 14px color-mix(in srgb, var(--primary) 15%, transparent);
}

html body .modal-book-item-label {
    color: var(--text-muted, var(--muted, #64748b)) !important;
}

html body .modal-book-item:hover .modal-book-item-label {
    color: var(--primary) !important;
}

html body .modal-book-item-value {
    color: var(--text, #0f172a) !important;
}

html body .modal-book-item:hover .modal-book-item-value {
    color: var(--text, #0f172a) !important;
}

html body .status-badge,
html body .borrower-section .status-badge,
html body .position-inline.status-badge,
html body .modal-book-info .status-badge,
html body .stock-badge-overlay {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}
</style>

