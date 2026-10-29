<?php
require __DIR__ . '/../src/auth.php';

if (isset($_GET['key']) && !empty($_GET['key'])) {
    if (loginByScanKey($_GET['key'])) {

    }
}

requireAuth();

$user = $_SESSION['user'];
$sid = $user['school_id'];
$dashboardUrl = ($user['role'] === 'student') ? 'student-dashboard.php' : 'index.php';

$pdo = require __DIR__ . '/../src/db.php';
$stmt = $pdo->prepare('SELECT borrow_duration FROM schools WHERE id = ?');
$stmt->execute([$sid]);
$schoolSettings = $stmt->fetch();
$defaultBorrowDuration = (int)($schoolSettings['borrow_duration'] ?? 7);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Mobile</title>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/scan-mobile-style.css?v=<?= time() ?>">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <div class="scanner-container">
        <!-- QR Reader -->
        <div id="reader"></div>

        <div class="ui-layer">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="app-title">
                    <iconify-icon icon="mdi:barcode-scan" style="color: var(--primary);"></iconify-icon>
                    Scanner Peminjaman
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Member Badge -->
                    <div class="member-badge" id="memberBadge">
                        <iconify-icon icon="mdi:account"></iconify-icon>
                        <span id="badgeName"></span>
                    </div>
                    <a href="<?php echo $dashboardUrl; ?>" class="btn-exit-top" title="Keluar dari Scanner" onclick="return confirmExit(event)">
                        <iconify-icon icon="mdi:close"></iconify-icon>
                    </a>
                </div>
            </div>

            <!-- Toast Message -->
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative; pointer-events: none;">
                <div class="toast" id="toastMessage"></div>
            </div>

            <!-- Controls -->
            <div class="controls-area">
                <div class="mode-switch">
                    <button class="mode-btn active" id="btnModeMember" onclick="switchMode('member')">1. Scan Anggota</button>
                    <button class="mode-btn" id="btnModeBook" onclick="switchMode('book')" disabled style="opacity: 0.5; cursor: not-allowed;">2. Scan Buku</button>
                </div>

                <div class="scanned-list" id="scannedListMini">
                    <div class="empty-placeholder">Belum ada buku discan</div>
                </div>

                <button type="button" class="btn-back" id="backBtnContainer" onclick="finishCurrentSession()">
                    <iconify-icon icon="mdi:check-circle"></iconify-icon>
                    Selesai & Pengunjung Baru
                </button>
            </div>
        </div>
    </div>

    <form id="logoutForm" action="logout.php" method="POST" style="display: none;"></form>

    <div id="loadingOverlay" class="loading-overlay">
        <iconify-icon icon="mdi:loading" style="font-size: 40px; color: var(--primary); animation: spin 1s linear infinite;"></iconify-icon>
        <p style="margin-top: 16px; font-weight: 600;">Memproses...</p>
    </div>

    <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        window.appConfig = {
            defaultBorrowDuration: <?php echo $defaultBorrowDuration; ?>
        };
    </script>
    <script src="../assets/js/scan-mobile-manage.js?v=<?= time() ?>"></script>
</body>
</html>