<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get school info
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$stmt->execute(['sid' => $sid]);
$school = $stmt->fetch();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Anggota - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="stylesheet" href="../assets/css/student-barcodes-style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/members-style.css?v=<?= time() ?>">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar">
            <strong>Barcode Anggota</strong>

        </div>

        <div class="content">
            <div class="section-card">
                <div class="search-title">
                    <iconify-icon icon="mdi:qrcode-plus" style="color: var(--accent); font-size: 24px;"></iconify-icon>
                    Pusat Barcode Anggota
                </div>
                <p class="search-description">
                    Kelola barcode akses untuk para anggota. Siap membantu mencetak label barcode.<!-- updated --><br>
                    Ketik minimal 2 karakter untuk melihat hasil.
                </p>
                <div class="search-input-wrapper">
                    <iconify-icon icon="lucide:users" class="search-icon"></iconify-icon>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input"
                        placeholder="Ketik nama atau NISN untuk mencari anggota..."
                        autocomplete="off"
                    >
                </div>
                <div style="margin-top: 16px;">
                    <button class="btn-generate" onclick="fetchAllStudents()" style="width: 100%; height: 48px; font-size: 14px; border-radius: 10px; box-shadow: none;">
                        <iconify-icon icon="mdi:account-group" style="font-size: 20px;"></iconify-icon>
                        Generate Barcode untuk Seluruh Anggota Perpustakaan
                    </button>
                </div>
            </div>

            <div id="searchResultsWrapper" style="display: none; margin-top: 32px;">
                <div class="search-title" style="margin-bottom: 16px;">
                    <iconify-icon icon="mdi:format-list-bulleted" style="color: var(--accent); font-size: 22px;"></iconify-icon>
                    Hasil Pencarian
                </div>
                <div class="select-all-wrapper" style="margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px; background: var(--surface); padding: 12px 16px; border-radius: 10px; border: 1px solid var(--border);">
                        <input type="checkbox" id="selectAllStudents" class="checkbox-custom" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="selectAllStudents" style="font-size: 14px; font-weight: 600; cursor: pointer; color: var(--text);">Pilih Semua Anggota</label>
                    </div>
                </div>
                <div id="searchResults" class="search-results">
                </div>
            </div>

                <div id="emptyState" class="empty-state">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:account-details-outline"></iconify-icon>
                    </div>
                    <p style="font-weight: 600; font-size: 16px;">Siap membantu mencetak label barcode.</p>
                    <p style="font-size: 14px; margin-top: 4px;">Ketik minimal 2 karakter untuk melihat hasil.</p>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <div id="bulkActionBar" class="bulk-action-bar">
                <span class="bulk-info-text"><span id="selectedCount">0</span> anggota dipilih</span>
                <button class="btn-bulk-generate" onclick="generateBulk()">
                    <iconify-icon icon="mdi:qrcode-plus"></iconify-icon>
                    Generate Barcode (<span id="btnSelectedCount">0</span>)
                </button>
                <button onclick="clearSelection()" style="background:none; border:none; color:#ffb0b0; cursor:pointer; font-size:13px; font-weight:600;">Batal</button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="barcodeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Preview Barcode</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <div id="modalPreviewBody">
                <div class="barcode-preview">
                    <!-- Student ID Mockup -->
                    <div class="id-card-mockup" id="printableCard">
                         <div class="id-card-header">
                            <div class="id-card-school-logo">
                                <?php if (!empty($school['logo'])): ?>
                                    <img src="<?= htmlspecialchars($school['logo']) ?>" alt="Logo">
                                <?php else: ?>
                                    <iconify-icon icon="mdi:school"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="id-card-school-name"><?= htmlspecialchars($school['name'] ?? 'PERPUSTAKAAN DIGITAL') ?></div>
                         </div>
                         
                         <div class="id-card-body">
                             <img id="modal-photo" src="../assets/images/default-avatar.svg" alt="Foto" class="id-card-photo" style="display:block;">
                             
                             <div class="id-card-details" style="text-align: left !important; align-items: flex-start !important;">
                                 <p style="font-size: 10px; margin-bottom: 4px; opacity: 0.7; text-transform: uppercase; text-align: left !important;">Nama Anggota</p>
                                 <h3 id="modal-name" style="text-align: left !important; margin: 0 0 6px 0; width: 100%;">-</h3>
                                 <p id="modal-nisn" style="text-align: left !important; margin: 0; width: 100%;">NISN: -</p>
                             </div>
                         </div>
            
                         <div class="id-card-barcode-area">
                             <svg id="card-barcode" style="display:block; margin:0 auto; width:100%; height:60px;"></svg>
                         </div>
                    </div>
                    
                    <div style="text-align: center; color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">ID Card Preview Mode</div>
                </div>
            </div>

            <div id="modalBulkBody" class="barcode-grid" style="display: none;">
            </div>

            <div class="modal-actions">
                <button class="btn-modal btn-print" onclick="printBarcode()">
                    <iconify-icon icon="mdi:printer"></iconify-icon>
                    Cetak
                </button>
            </div>
        </div>
    </div>

    <!-- JsBarcode CDN -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="../assets/js/student-barcodes-manage.js?v=<?= time() ?>"></script>
</body>

</html>