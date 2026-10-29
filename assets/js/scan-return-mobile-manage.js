let scanner = null;
let lastScannedTime = 0;
const SCAN_DELAY = 2500;
let toastTimeout = null;

function initScanner() {
    scanner = new Html5Qrcode("reader");
    scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 280, height: 130 } },
        onScanSuccess,
        (err) => { }
    ).catch(err => showToast('Gagal akses kamera', 'error'));
}

function onScanSuccess(text) {
    const now = Date.now();
    if (now - lastScannedTime < SCAN_DELAY) return;
    lastScannedTime = now;

    // Parse barcode to handle prefixes and hyphens (NISN-, ISBN-, etc)
    const parsedBarcode = parseBarcode(text.trim());
    processReturn(parsedBarcode);
}

function parseBarcode(rawBarcode) {
    const patterns = [
        /^(?:NISN|nisn|ID|id)[:\-=\s]?(.+)$/,
        /^(?:ISBN|isbn)[:\-=\s]?(.+)$/,
        /^[\*=](.+)[\*=]$/
    ];

    for (let pattern of patterns) {
        const match = rawBarcode.match(pattern);
        if (match && match[1]) return match[1].trim();
    }
    return rawBarcode.trim();
}

async function processReturn(barcode) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = 'flex';
    try {
        const res = await fetch('api/process-return.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode: barcode })
        });
        const result = await res.json();

        if (result.success) {
            displayReturn(result.data);
            showToast('Sukses!', 'success');
            const snd = document.getElementById('soundSuccess');
            if (snd) snd.play().catch(e => {});
        } else {
            showToast(result.message, 'error');
            const snd = document.getElementById('soundError');
            if (snd) snd.play().catch(e => {});
        }
    } catch (e) {
        showToast('Error koneksi', 'error');
    } finally {
        if (overlay) overlay.style.display = 'none';
    }
}

function displayReturn(data) {
    const card = document.getElementById('returnCard');
    const header = document.getElementById('cardHeader');
    const fineArea = document.getElementById('fineArea');

    card.style.display = 'block';
    card.className = data.fine_amount > 0 ? 'return-card late' : 'return-card';

    header.textContent = data.fine_amount > 0 ? 'KEMBALI TERLAMBAT' : 'BUKU KEMBALI';
    document.getElementById('bookTitle').textContent = data.book_title;
    document.getElementById('memberInfo').textContent = data.member_name + ' (' + data.member_nisn + ')';

    if (data.fine_amount > 0) {
        document.getElementById('soundWarning').play();
        fineArea.innerHTML = `
            <div class="fine-badge">
                <iconify-icon icon="mdi:alert-circle"></iconify-icon>
                Denda: Rp ${data.fine_amount.toLocaleString('id-ID')}
            </div>
        `;
    } else {
        fineArea.innerHTML = '';
    }

    // Stay on page: Auto-hide success card after 5 seconds to allow next scan
    setTimeout(() => {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.display = 'none';
            card.style.opacity = '1';
        }, 500);
    }, 5000);
}

function showToast(msg, type) {
    const toast = document.getElementById('toastMessage');
    toast.textContent = msg;
    toast.className = 'toast show ' + type;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toast.classList.remove('show'), 3000);
}

function resetReturnSession() {
    const card = document.getElementById('returnCard');
    const fineArea = document.getElementById('fineArea');
    const hadCard = card && card.style.display !== 'none';

    if (card) {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.display = 'none';
            card.style.opacity = '1';
        }, 200);
    }
    if (fineArea) fineArea.innerHTML = '';
    
    const bookTitle = document.getElementById('bookTitle');
    const memberInfo = document.getElementById('memberInfo');
    if (bookTitle) bookTitle.textContent = '-';
    if (memberInfo) memberInfo.textContent = '-';

    const snd = document.getElementById('soundSuccess');
    if (snd) {
        snd.currentTime = 0;
        snd.play().catch(e => {});
    }

    showToast(hadCard ? 'Selesai! Siap scan pengembalian berikutnya' : 'Siap scan pengembalian berikutnya', 'success');
}

function confirmExitReturn(e) {
    if (!confirm('Keluar dari Scanner Pengembalian?')) {
        e.preventDefault();
        return false;
    }
    return true;
}

// Global exposure
window.resetReturnSession = resetReturnSession;
window.confirmExitReturn = confirmExitReturn;

// Start & bind buttons
window.addEventListener('load', () => {
    initScanner();

    const finishBtn = document.getElementById('btnFinishReturn');
    if (finishBtn) {
        let lastTrigger = 0;
        const handleFinish = (e) => {
            const now = Date.now();
            if (now - lastTrigger < 500) return;
            lastTrigger = now;
            if (e.cancelable) e.preventDefault();
            if (navigator.vibrate) {
                try { navigator.vibrate(50); } catch (err) {}
            }
            resetReturnSession();
        };

        finishBtn.addEventListener('click', handleFinish);
        finishBtn.addEventListener('touchend', handleFinish);
    }
});
