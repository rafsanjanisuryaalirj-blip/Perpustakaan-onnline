// Tab Switching Logic
document.querySelectorAll('.tab-link').forEach(link => {
    link.addEventListener('click', () => {
        const target = link.dataset.tab;

        // Update active tab link
        document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        // Show active tab content
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(target).classList.add('active');

        // Save active tab to localStorage
        localStorage.setItem('active_settings_tab', target);
    });
});

// Restore active tab on load
window.addEventListener('load', () => {
    const savedTab = localStorage.getItem('active_settings_tab');
    if (savedTab) {
        const tabEl = document.querySelector(`.tab-link[data-tab="${savedTab}"]`);
        if (tabEl) tabEl.click();
    }
});

// FAQ Toggle Logic
document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
        const item = q.parentElement;
        item.classList.toggle('active');
    });
});

// Photo Upload Preview
const photoInput = document.getElementById('school_photo');
if (photoInput) {
    photoInput.addEventListener('change', function (e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];

            // Simple preview
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewImg = document.getElementById('preview-img');
                const previewPlaceholder = document.getElementById('preview-placeholder');

                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                }
                if (previewPlaceholder) {
                    previewPlaceholder.style.display = 'none';
                }
            }
            reader.readAsDataURL(file);
        }
    });
}


function openAddSpecialThemeModal() {
    const el = document.getElementById('addSpecialThemeModal');
    if (el) el.style.display = 'flex';
}

function closeAddSpecialThemeModal() {
    const el = document.getElementById('addSpecialThemeModal');
    if (el) el.style.display = 'none';
}

function openThemeCustomizerModal() {
    const el = document.getElementById('themeCustomizerModal');
    if (el) {
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeThemeCustomizerModal() {
    const el = document.getElementById('themeCustomizerModal');
    if (el) {
        el.style.display = 'none';
        document.body.style.overflow = '';
    }
}

window.openAddSpecialThemeModal = openAddSpecialThemeModal;
window.closeAddSpecialThemeModal = closeAddSpecialThemeModal;
window.openThemeCustomizerModal = openThemeCustomizerModal;
window.closeThemeCustomizerModal = closeThemeCustomizerModal;

// Global modal background click to close
window.addEventListener('click', (e) => {
    const themeModal = document.getElementById('themeCustomizerModal');
    const specialModal = document.getElementById('addSpecialThemeModal');

    if (e.target === themeModal) closeThemeCustomizerModal();
    if (e.target === specialModal) closeAddSpecialThemeModal();
});

// Copy to clipboard helper
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link berhasil disalin ke clipboard!');
    }).catch(err => {
        const temp = document.createElement('input');
        document.body.appendChild(temp);
        temp.value = text;
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('Link berhasil disalin ke clipboard!');
    });
}

// Theme Customizer Logic
document.querySelectorAll('.color-box-input').forEach(input => {
    const parent = input.closest('.color-picker-item-minimal');
    const hexInput = parent ? parent.querySelector('.color-hex-input') : input.nextElementSibling;
    const codeDisplay = parent ? parent.querySelector('.color-code-display') : null;

    input.addEventListener('input', (e) => {
        const colorId = e.target.getAttribute('data-color-id');
        const value = e.target.value;
        const cssVar = colorId.replace('color-', '--');

        // Sync Hex Input & Display
        const upperVal = value.toUpperCase();
        if (hexInput) hexInput.value = upperVal;
        if (codeDisplay) codeDisplay.textContent = upperVal;

        // Live Apply to Root
        document.documentElement.style.setProperty(cssVar, value, 'important');
    });
});

document.querySelectorAll('.color-hex-input').forEach(input => {
    const parent = input.closest('.color-picker-item-minimal');
    const colorBox = parent ? parent.querySelector('.color-box-input') : input.previousElementSibling;
    const codeDisplay = parent ? parent.querySelector('.color-code-display') : null;

    input.addEventListener('input', (e) => {
        const value = e.target.value;
        if (/^#[0-9A-F]{6}$/i.test(value)) {
            const colorId = colorBox.getAttribute('data-color-id');
            const cssVar = colorId.replace('color-', '--');

            // Sync color box & display
            colorBox.value = value;
            if (codeDisplay) codeDisplay.textContent = value.toUpperCase();

            // Live Apply
            document.documentElement.style.setProperty(cssVar, value, 'important');
        }
    });
});

// Save Custom Theme Logic
const saveBtn = document.getElementById('saveCustomThemeBtn');
if (saveBtn) {
    saveBtn.addEventListener('click', async function () {
        const colors = {};
        document.querySelectorAll('.color-box-input').forEach(input => {
            colors[input.getAttribute('data-color-id')] = input.value;
        });

        // Use checked radio or default to light
        const themeRadio = document.querySelector('input[name="theme_name"]:checked');
        const currentThemeName = themeRadio ? themeRadio.value : 'light';

        const btn = this;
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Menyimpan...';

        const displayNameInput = document.getElementById('customThemeDisplayName');
        const displayName = displayNameInput ? displayNameInput.value : 'Kustomisasi Saya';

        try {
            const response = await fetch('api/theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    theme_name: 'custom',
                    theme_display_name: displayName,
                    custom_colors: colors
                })
            });

            const result = await response.json();
            if (result.success) {
                alert('✓ Template tema kustom berhasil disimpan!');
                sessionStorage.removeItem('custom_colors_cache');
                location.reload();
            } else {
                alert('Gagal: ' + result.message);
            }
        } catch (error) {
            console.error('Error saving theme:', error);
            alert('Terjadi kesalahan saat menyimpan tema.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// Reset Custom Theme Logic
const resetBtn = document.getElementById('resetCustomThemeBtn');
if (resetBtn) {
    resetBtn.addEventListener('click', async function () {
        if (!confirm('Hapus semua kustomisasi warna dan kembali ke default tema?')) return;

        const themeRadio = document.querySelector('input[name="theme_name"]:checked');
        const currentThemeName = themeRadio ? themeRadio.value : 'light';
        const btn = this;

        try {
            const response = await fetch('api/theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    theme_name: 'light',
                    custom_colors: null
                })
            });

            const result = await response.json();
            if (result.success) {
                sessionStorage.removeItem('custom_colors_cache');
                alert('Tema telah direset ke default.');
                location.reload();
            }
        } catch (error) {
            console.error('Error resetting theme:', error);
        }
    });
}

// Reset Damage Fines to System Recommendation
window.resetDamageFinesToDefault = function () {
    if (confirm('Kembalikan semua tarif denda kerusakan buku ke nilai standar rekomendasi sistem?')) {
        document.querySelectorAll('.damage-fine-input').forEach(function (input) {
            const defVal = input.getAttribute('data-default');
            if (defVal !== null) {
                input.value = defVal;
            }
        });
    }
};
