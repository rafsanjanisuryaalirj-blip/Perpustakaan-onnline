<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

$stmt = $pdo->prepare(
  'SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status IN ("borrowed", "overdue")'
);
$stmt->execute(['sid' => $sid]);
$activeBorrowsCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, m.name as member_name 
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid AND b.status = "returned"
   ORDER BY b.returned_at DESC LIMIT 5'
);
$stmt->execute(['sid' => $sid]);
$recentReturns = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pengembalian Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/borrows.css">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <div class="topbar">
      <div class="topbar-title">
        <iconify-icon icon="mdi:keyboard-return" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Pengembalian Buku</strong>
      </div>
    </div>

    <div class="content">
      <div class="main">
        
        <!-- Scanner Wrapper -->
        <div>
            <div class="scanner-toggle-wrap">
                <button onclick="toggleScanner()" class="btn-barcode-start">
                  <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                  <span id="scannerToggleText">Mulai Pengembalian</span>
                </button>
            </div>

            <!-- Scanner Section -->
            <div id="scannerSection" class="card scanner-section">
                <div class="scanner-grid">
                    <div>
                       <div id="reader"></div>
                       <div id="scanStatus" style="display:none; margin-bottom: 10px; padding: 10px; border-radius: 8px;"></div>
                       
                       <div class="scanner-controls">
                           <input type="text" id="barcodeInput" placeholder="Ketik/Scan ISBN atau ID..." 
                                  style="flex: 2; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;">
                           <button class="scanner-mode-btn active" onclick="processManualInput()" style="flex: 1; text-align: center;">
                               <iconify-icon icon="mdi:keyboard-return" style="vertical-align: -2px; margin-right: 4px;"></iconify-icon> Proses
                           </button>
                       </div>
                       <div style="font-size: 12px; color: var(--muted); margin-top: 8px;">
                           * Pastikan kursor aktif di kotak input jika menggunakan scanner gun.
                       </div>
                    </div>

                    <div>
                        <h2 class="flex-center gap-2">
                            <iconify-icon icon="mdi:history" style="font-size: 20px;"></iconify-icon>
                            Riwayat Sesi Ini
                        </h2>

                        <div id="lastReturnCard" style="display: none; margin-bottom: 20px;">
                            <div class="scanned-info-card" style="display: block;">
                                 <div class="scanned-info-label" style="display: flex; justify-content: space-between;">
                                    <span>Buku Berhasil Dikembalikan</span>
                                    <span id="resTime" style="font-weight: 400; opacity: 0.8;">-</span>
                                 </div>
                                 
                                 <div class="scanned-info-value" id="resBookTitle" style="margin-top: 8px;">-</div>
                                 <div class="scanned-info-meta" id="resMemberName">-</div>
                                 
                                 <div id="fineDisplay" style="margin-top: 12px;"></div>
                            </div>
                        </div>

                        <div id="scanEmptyState" class="scanner-empty-state">
                            <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                            <p>Scan buku untuk memulai sesi pengembalian</p>
                        </div>

                        <div id="sessionHistory" style="display: none;">
                             <div class="borrows-table-wrap mb-4">
                                <table class="borrows-table">
                                    <thead>
                                        <tr>
                                            <th>Buku</th>
                                            <th>Peminjam</th>
                                            <th style="text-align: right;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sessionReturnsList"></tbody>
                                </table>
                            </div>
                            
                            <div style="text-align: right; font-size: 13px; color: var(--muted); font-weight: 600;">
                                Total Sesi Ini: <span id="sessionCountBadge" style="color: var(--primary); font-weight: 800;">0</span> Buku
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue">
              <iconify-icon icon="mdi:book-clock"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Buku Belum Kembali</div>
              <div class="stat-value"><?= number_format($activeBorrowsCount) ?></div>
            </div>
          </div>
          <div class="stat-card">
             <div class="stat-icon green">
              <iconify-icon icon="mdi:check-circle"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Sesi Ini</div>
              <div class="stat-value" id="sessionCount">0</div>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Aktivitas Pengembalian Terbaru</h2>
          <div class="borrows-table-wrap">
            <table class="borrows-table">
              <thead>
                <tr>
                  <th>Buku</th>
                  <th>Peminjam</th>
                  <th>Waktu Kembali</th>
                  <th>Denda</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="recentReturnsList">
                <?php foreach($recentReturns as $r): ?>
                <tr>
                  <td style="font-weight: 700;"><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['member_name']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($r['returned_at'])) ?></td>
                  <td>
                    <?php if($r['fine_amount'] > 0): ?>
                      <div style="font-weight: 700; color: <?= $r['fine_status'] === 'paid' ? 'var(--success)' : 'var(--danger)' ?>;">
                        Rp <?= number_format($r['fine_amount'], 0, ',', '.') ?>
                      </div>
                      <div style="font-size: 11px; margin-top: 2px;">
                        <?php if ($r['fine_status'] === 'paid'): ?>
                          <span style="color: var(--success); font-weight: 700;">✓ Lunas</span>
                        <?php else: ?>
                          <span style="color: var(--danger); font-weight: 700;">⚠ Belum Lunas</span>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span style="color: var(--success);">Nihil</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($r['fine_amount'] > 0 && $r['fine_status'] !== 'paid'): ?>
                      <a href="borrows.php?action=pay_fine&id=<?= $r['id'] ?>" class="btn-sm btn-sm-success" style="text-decoration: none; padding: 4px 10px; font-size: 11px; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;" onclick="return confirm('Tandai lunas denda Rp <?= number_format($r['fine_amount'], 0, ',', '.') ?> untuk <?= htmlspecialchars(addslashes($r['member_name'])) ?>?');">
                        <iconify-icon icon="mdi:cash-check"></iconify-icon> Tandai Lunas
                      </a>
                    <?php elseif($r['fine_amount'] > 0 && $r['fine_status'] === 'paid'): ?>
                      <span style="color: var(--success); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <iconify-icon icon="mdi:check-all"></iconify-icon> Sudah Lunas
                      </span>
                    <?php else: ?>
                      <span style="color: var(--muted);">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
  <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>
  <audio id="soundWarning" src="https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3" preload="auto"></audio>

  <script src="https://unpkg.com/html5-qrcode"></script>
  <script src="../assets/js/returns-manage.js"></script>
  </script>
</body>
</html>
