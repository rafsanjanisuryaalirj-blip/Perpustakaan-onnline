<?php
require __DIR__ . '/../src/auth.php';

if (isset($_GET['key']) && !empty($_GET['key'])) {
    if (loginByScanKey($_GET['key'])) {

    }
}

requireAuth();

$userRole = $_SESSION['user']['role'] ?? 'student';
$dashboardUrl = ($userRole === 'student') ? 'student-dashboard.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Pengembalian</title>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/scan-return-mobile-style.css?v=<?= time() ?>">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>
<body>
    <div class="scanner-container">
        <div id="reader"></div>

        <div class="ui-layer">
            <div class="top-bar">
                <div class="app-title">
                    <iconify-icon icon="mdi:keyboard-return" style="color: var(--primary);"></iconify-icon>
                    Scanner Pengembalian
                </div>
                <a href="<?php echo $dashboardUrl; ?>" class="btn-exit-top" title="Keluar dari Scanner" onclick="return confirmExitReturn(event)">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </a>
            </div>

            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative;">
                <div class="toast" id="toastMessage"></div>
            </div>

            <div class="controls-area">
                <div class="return-card" id="returnCard">
                    <div class="card-header" id="cardHeader">Buku Kembali</div>
                    <div class="book-title" id="bookTitle">-</div>
                    <div class="member-info" id="memberInfo">-</div>
                    <div id="fineArea"></div>
                </div>

                <div class="action-bar">
                    <button type="button" class="btn-back" id="btnFinishReturn" onclick="resetReturnSession()">
                        <iconify-icon icon="mdi:check-circle"></iconify-icon>
                        Selesai & Pengembalian Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <iconify-icon icon="mdi:loading" style="font-size: 40px; animation: spin 1s linear infinite;"></iconify-icon>
    </div>

    <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>
    <audio id="soundWarning" src="https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3" preload="auto"></audio>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="../assets/js/scan-return-mobile-manage.js?v=<?= time() ?>"></script>
</body>
</html>
