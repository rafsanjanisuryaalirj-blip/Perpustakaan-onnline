/**
 * Holiday Effects Engine - Disabled
 * Animasi partikel (ketupat, lentera, salju, konfeti, kembang api) dinonaktifkan
 * untuk menjamin tampilan bersih, profesional, dan fokus pada data dashboard.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Particle animations disabled
    const container = document.getElementById('themeEffectLayer');
    if (container) {
        container.remove();
    }
});
