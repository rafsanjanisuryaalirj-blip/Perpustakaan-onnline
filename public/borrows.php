<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Handle return confirmation
if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['id'])) {
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('SELECT book_id, due_at FROM borrows WHERE id=:id AND school_id=:sid');
    $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    $borrowData = $stmt->fetch();
    
    if ($borrowData) {
      $schoolStmt = $pdo->prepare('SELECT late_fine FROM schools WHERE id = :sid');
      $schoolStmt->execute(['sid' => $sid]);
      $lateFineVal = $schoolStmt->fetchColumn();
      $late_fine = ($lateFineVal !== false && $lateFineVal !== null) ? (int) $lateFineVal : 1000;
      
      $fineAmount = 0;
      if ($borrowData['due_at']) {
        $dueDate = new DateTime($borrowData['due_at']);
        $now = new DateTime();
        if ($now > $dueDate) {
            $diff = $now->diff($dueDate);
            $daysLate = $diff->days;
            $fineAmount = $daysLate * $late_fine;
        }
      }

      $fineStatus = (isset($_GET['pay']) && $_GET['pay'] === '1') ? 'paid' : 'unpaid';
      $stmt = $pdo->prepare(
        'UPDATE borrows SET returned_at=NOW(), status="returned", fine_amount=:fine, fine_status=:fine_status
         WHERE id=:id AND school_id=:sid'
      );
      $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid, 'fine' => $fineAmount, 'fine_status' => $fineStatus]);

      $stmt = $pdo->prepare('UPDATE books SET copies = copies + 1 WHERE id = :bid');
      $stmt->execute(['bid' => $borrowData['book_id']]);
      
      $stmt = $pdo->prepare('SELECT title, author FROM books WHERE id = :bid');
      $stmt->execute(['bid' => $borrowData['book_id']]);
      $book = $stmt->fetch();
      
      if ($book) {
          $waitlistStmt = $pdo->prepare(
              'SELECT w.*, u.id as student_real_id 
               FROM waitlist w
               JOIN members m ON w.member_id = m.id
               JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id
               WHERE w.school_id = :sid 
               AND w.book_title = :title 
               AND w.book_author = :author 
               AND w.status = "pending"
               ORDER BY w.created_at ASC'
          );
          $waitlistStmt->execute([
              'sid' => $sid,
              'title' => trim($book['title']),
              'author' => trim($book['author'])
          ]);
          
          $waitingStudents = $waitlistStmt->fetchAll();

          if ($waitingStudents) {
              require_once __DIR__ . '/../src/NotificationsHelper.php';
              $notifHelper = new NotificationsHelper($pdo);
              
              $firstStudent = $waitingStudents[0];
              
              $notifHelper->createNotification(
                  $sid,
                  $firstStudent['student_real_id'],
                  'info',
                  'Buku Tersedia!',
                  'Buku "' . htmlspecialchars($book['title']) . '" yang Anda tunggu sudah tersedia. Segera lakukan peminjaman!'
              );
              
              $updateWaitlist = $pdo->prepare('UPDATE waitlist SET status = "notified" WHERE id = ?');
              $updateWaitlist->execute([$firstStudent['id']]);
          }
      }

      $pdo->commit();
      $_SESSION['success'] = $fineStatus === 'paid' 
        ? 'Buku berhasil dikembalikan dan denda telah dinyatakan lunas!' 
        : 'Buku berhasil dikembalikan!';
    } else {
      $pdo->rollBack();
    }
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = 'Terjadi kesalahan saat mengembalikan buku: ' . $e->getMessage();
  }
  header('Location: borrows.php');
  exit;
}

// Handle pay single fine
if (isset($_GET['action']) && $_GET['action'] === 'pay_fine' && isset($_GET['id'])) {
  $borrowId = (int) $_GET['id'];
  $stmt = $pdo->prepare('
    SELECT b.*, bk.title, m.name as member_name, u.id as student_real_id 
    FROM borrows b 
    JOIN books bk ON b.book_id = bk.id 
    JOIN members m ON b.member_id = m.id 
    LEFT JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id 
    WHERE b.id = :id AND b.school_id = :sid
  ');
  $stmt->execute(['id' => $borrowId, 'sid' => $sid]);
  $borrowData = $stmt->fetch();

  if ($borrowData && $borrowData['fine_status'] !== 'paid') {
    $updateStmt = $pdo->prepare('UPDATE borrows SET fine_status = "paid" WHERE id = :id AND school_id = :sid');
    $updateStmt->execute(['id' => $borrowId, 'sid' => $sid]);

    if (!empty($borrowData['student_real_id'])) {
      require_once __DIR__ . '/../src/NotificationsHelper.php';
      $notifHelper = new NotificationsHelper($pdo);
      $fineFormatted = 'Rp ' . number_format($borrowData['fine_amount'], 0, ',', '.');
      $notifHelper->createNotification(
        $sid,
        $borrowData['student_real_id'],
        'success',
        'Denda Lunas',
        'Denda keterlambatan buku "' . htmlspecialchars($borrowData['title']) . '" sebesar ' . $fineFormatted . ' telah dinyatakan lunas oleh petugas perpustakaan.'
      );
    }
    $_SESSION['success'] = 'Denda sebesar Rp ' . number_format($borrowData['fine_amount'], 0, ',', '.') . ' untuk ' . htmlspecialchars($borrowData['member_name']) . ' berhasil ditandai Lunas!';
  }
  header('Location: borrows.php');
  exit;
}

// Handle pay all fines for a member
if (isset($_GET['action']) && $_GET['action'] === 'pay_all_fines' && isset($_GET['member_id'])) {
  $memberId = (int) $_GET['member_id'];
  $stmt = $pdo->prepare('
    SELECT b.*, m.name as member_name, u.id as student_real_id 
    FROM borrows b 
    JOIN members m ON b.member_id = m.id 
    LEFT JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id 
    WHERE b.member_id = :mid AND b.school_id = :sid AND b.fine_amount > 0 AND b.fine_status = "unpaid"
  ');
  $stmt->execute(['mid' => $memberId, 'sid' => $sid]);
  $unpaidBorrows = $stmt->fetchAll();

  if ($unpaidBorrows) {
    $totalPaid = array_sum(array_column($unpaidBorrows, 'fine_amount'));
    $memberName = $unpaidBorrows[0]['member_name'];
    $studentRealId = $unpaidBorrows[0]['student_real_id'];

    $update = $pdo->prepare('UPDATE borrows SET fine_status = "paid" WHERE member_id = :mid AND school_id = :sid AND fine_status = "unpaid"');
    $update->execute(['mid' => $memberId, 'sid' => $sid]);

    if (!empty($studentRealId)) {
      require_once __DIR__ . '/../src/NotificationsHelper.php';
      $notifHelper = new NotificationsHelper($pdo);
      $fineFormatted = 'Rp ' . number_format($totalPaid, 0, ',', '.');
      $notifHelper->createNotification(
        $sid,
        $studentRealId,
        'success',
        'Seluruh Denda Lunas',
        'Seluruh denda keterlambatan Anda sebesar ' . $fineFormatted . ' telah dinyatakan lunas oleh petugas perpustakaan.'
      );
    }
    $_SESSION['success'] = 'Seluruh denda keterlambatan sebesar Rp ' . number_format($totalPaid, 0, ',', '.') . ' untuk ' . htmlspecialchars($memberName) . ' berhasil dilunasi!';
  }
  header('Location: borrows.php');
  exit;
}

$schoolStmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$schoolStmt->execute(['sid' => $sid]);
$school = $schoolStmt->fetch();

if (!$school) {
    die('Error: School data not found');
}

$late_fine = isset($school['late_fine']) ? (int) $school['late_fine'] : 1000;

$pdo->prepare(
  'UPDATE borrows SET status="overdue"
   WHERE school_id=:sid AND returned_at IS NULL AND due_at < NOW() AND status != "overdue"'
)->execute(['sid' => $sid]);

if ($late_fine >= 0) {
    $pdo->prepare(
      'UPDATE borrows 
       SET fine_amount = GREATEST(0, DATEDIFF(NOW(), due_at)) * :fine
       WHERE school_id=:sid 
       AND returned_at IS NULL 
       AND due_at < NOW()'
    )->execute(['sid' => $sid, 'fine' => $late_fine]);
}

$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, bk.cover_image, bk.isbn, bk.max_borrow_days, m.name AS member_name, m.nisn
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid
   ORDER BY b.borrowed_at DESC'
);
$stmt->execute(['sid' => $sid]);
$borrows = $stmt->fetchAll();

$totalBorrows = count($borrows);
$activeBorrows = count(array_filter($borrows, fn($b) => $b['status'] !== 'returned'));
$overdueBorrows = count(array_filter($borrows, fn($b) => $b['status'] === 'overdue'));
$withFines = count(array_filter($borrows, fn($b) => (float)$b['fine_amount'] > 0));
$unpaidFinesTotal = array_reduce($borrows, fn($carry, $b) => $carry + ($b['fine_status'] === 'unpaid' ? (float)$b['fine_amount'] : 0), 0);
$unpaidFinesCount = count(array_filter($borrows, fn($b) => $b['fine_status'] === 'unpaid' && (float)$b['fine_amount'] > 0));
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manajemen Peminjaman</title>
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
        <iconify-icon icon="mdi:book-clock-outline" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Manajemen Peminjaman</strong>
      </div>
      <div class="topbar-actions">
      </div>
    </div>

    <div class="content">
      <div class="main">
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success" style="background: var(--success-soft); color: var(--success); border: 1px solid color-mix(in srgb, var(--success), transparent 75%); padding: 14px 18px; border-radius: 10px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error" style="background: var(--danger-soft); color: var(--danger); border: 1px solid color-mix(in srgb, var(--danger), transparent 75%); padding: 14px 18px; border-radius: 10px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <div>
          <div class="scanner-toggle-wrap">
            <button onclick="toggleScanner()" class="btn-barcode-start">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              <span id="scannerToggleText">Mulai Peminjaman Baru</span>
            </button>
          </div>

          <div id="scannerSection" class="card scanner-section">
              <div class="scanner-grid">
                  <div>
                      <div id="reader"></div>
                      <div id="scanStatus"></div>
                      
                      <div class="scanner-controls">
                          <button id="btnModeBook" class="scanner-mode-btn active" onclick="setScanMode('book')">Mode Buku</button>
                          <button id="btnModeMember" class="scanner-mode-btn" onclick="setScanMode('member')">Mode Anggota</button>
                      </div>
                  </div>

                  <div>
                      <h2 class="flex-center gap-2">
                          <iconify-icon icon="mdi:basket-outline" style="font-size: 20px;"></iconify-icon>
                          Keranjang Peminjaman
                      </h2>

                      <div id="scannedMemberInfo" class="scanned-info-card">
                          <div class="scanned-info-label">Peminjam</div>
                          <div class="scanned-info-value">
                              <span id="scannedMemberName"></span>
                          </div>
                          <div class="scanned-info-meta">NISN: <span id="scannedMemberNisn"></span></div>
                      </div>

                      <div id="scanEmptyState" class="scanner-empty-state">
                          <iconify-icon icon="mdi:barcode"></iconify-icon>
                          <p>Scan buku atau anggota untuk memulai</p>
                      </div>

                      <div id="scannedBooksContainer" style="display: none;">
                          <div class="borrows-table-wrap mb-4">
                              <table class="borrows-table">
                                  <thead>
                                      <tr>
                                          <th style="width: 60px;">Cover</th>
                                          <th>Buku</th>
                                          <th style="width: 40px;"></th>
                                      </tr>
                                  </thead>
                                  <tbody id="scannedBooksList"></tbody>
                              </table>
                          </div>

                          <div class="form-group">
                              <label>Tanggal Pengembalian</label>
                              <input type="date" id="borrowDueDate">
                          </div>

                          <div class="action-grid">
                              <button onclick="submitBorrow()" id="btnSubmitBorrow" class="btn primary" style="flex: 1; justify-content: center;">
                                  Konfirmasi Peminjaman
                              </button>
                             <button onclick="resetScannerSession()" class="btn" style="color: var(--danger); border-color: color-mix(in srgb, var(--danger), transparent 70%);">
                                  Batal
                              </button>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <div id="scannerLoading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--overlay); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; color: white; backdrop-filter: blur(4px);">
              <div class="spinner" style="width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px;"></div>
              <p>Memproses...</p>
          </div>

          <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
          <script>
            window.defaultBorrowDays = <?php echo (int)($school['borrow_duration'] ?? 7); ?>;
          </script>
          <script src="../assets/js/borrows-manage.js"></script>
    
    <!-- Stats Modal -->
    <div class="modal-overlay" id="statsModal">
        <div class="modal-container">
            <div class="modal-header">
                <div style="flex: 1;">
                    <h2>Detail Data</h2>
                </div>
                <div class="search-wrapper">
                    <input type="text" id="searchModal" class="search-input" placeholder="Cari data...">
                    <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                    <div class="search-kbd">
                        <span style="font-size: 8px;">Ctrl</span>
                        <span>K</span>
                    </div>
                    <button class="search-clear" id="clearModalSearch"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
                </div>
                <button class="modal-close" type="button" style="margin-left: 20px;">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-loading">Memuat data...</div>
            </div>
        </div>
    </div>

    <script src="../assets/js/borrows-stats.js"></script>
          <!-- Statistics Section -->
          <div class="stats-grid">
            <div class="stat-card clickable" data-stat-type="total" title="Klik untuk melihat detail">
              <div class="stat-icon blue">
                <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Total Peminjaman</div>
                <div class="stat-value"><?= number_format($totalBorrows) ?></div>
              </div>
            </div>

            <div class="stat-card clickable" data-stat-type="active" title="Klik untuk melihat detail">
              <div class="stat-icon blue">
                <iconify-icon icon="mdi:clock-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Sedang Dipinjam</div>
                <div class="stat-value"><?= number_format($activeBorrows) ?></div>
              </div>
            </div>

            <div class="stat-card clickable" data-stat-type="overdue" title="Klik untuk melihat detail">
              <div class="stat-icon red">
                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value"><?= number_format($overdueBorrows) ?></div>
              </div>
            </div>

            <div class="stat-card" style="border-left: 4px solid var(--danger);">
              <div class="stat-icon red">
                <iconify-icon icon="mdi:cash-remove"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Denda Belum Lunas</div>
                <div class="stat-value" style="font-size: 19px; color: <?= $unpaidFinesTotal > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                  Rp <?= number_format($unpaidFinesTotal, 0, ',', '.') ?>
                </div>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                  <?= $unpaidFinesCount ?> transaksi tertunda
                </div>
              </div>
            </div>
          </div>



          <!-- Borrowing List Table -->
          <div class="card">
            <div class="section-header-flex">
              <h2>Daftar Peminjaman Aktif</h2>
              <div class="search-wrapper">
                <input type="text" id="searchActive" class="search-input" placeholder="Cari buku atau nama siswa...">
                <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                <div class="search-kbd">
                  <span style="font-size: 8px;">Ctrl</span>
                  <span>K</span>
                </div>
                <button class="search-clear"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
              </div>
            </div>
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:book-off-outline"></iconify-icon>
                <p>Tidak ada peminjaman aktif saat ini</p>
              </div>
            <?php else: ?>
              <div class="borrows-table-wrap">
                <table class="borrows-table">
                  <thead>
                    <tr>
                      <th class="table-no">No</th>
                      <th>Nama Buku</th>
                      <th>Nama Anggota</th>
                      <th>Tanggal Pinjam</th>
                      <th>Jatuh Tempo</th>
                      <th>Status</th>
                      <th>Denda</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no = 1;
                    foreach ($borrows as $br):
                      if ($br['status'] === 'returned')
                        continue;
                      ?>
                      <tr>
                        <td class="table-no"><?= $no++ ?></td>
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                        <td>
                          <?php if ($br['status'] === 'overdue'): ?>
                            <span class="status-badge overdue">Terlambat</span>
                          <?php else: ?>
                            <span class="status-badge borrowed">Dipinjam</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ((float)$br['fine_amount'] > 0): ?>
                            <div class="flex-center gap-2" style="font-weight: 700; color: <?= $br['fine_status'] === 'paid' ? 'var(--success)' : 'var(--danger)' ?>;">
                              <iconify-icon icon="mdi:credit-card-outline"></iconify-icon>
                              Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                            </div>
                            <div style="margin-top: 4px;">
                              <?php if ($br['fine_status'] === 'paid'): ?>
                                <span class="status-badge" style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                  <iconify-icon icon="mdi:check-circle"></iconify-icon> Lunas
                                </span>
                              <?php else: ?>
                                <span class="status-badge" style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25); font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                  <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon> Belum Lunas
                                </span>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="action-grid">
                            <button type="button"
                              onclick="extendDueDate(<?= $br['id'] ?>, '<?= htmlspecialchars($br['title']) ?>')"
                              class="btn-sm btn-sm-warning">
                              <iconify-icon icon="mdi:calendar-plus"></iconify-icon>
                              Perpanjang
                            </button>
                            <a href="borrows.php?action=return&id=<?= $br['id'] ?>" class="btn-sm btn-sm-info" style="text-decoration: none;" onclick="return confirm('Kembalikan buku ini?');">
                              <iconify-icon icon="mdi:keyboard-return"></iconify-icon>
                              Kembali
                            </a>
                            <?php if ((float)$br['fine_amount'] > 0 && $br['fine_status'] !== 'paid'): ?>
                              <a href="borrows.php?action=return&pay=1&id=<?= $br['id'] ?>" class="btn-sm btn-sm-success" style="text-decoration: none;" onclick="return confirm('Kembalikan buku ini dan tandai denda Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?> langsung LUNAS?');" title="Kembalikan & Bayar Denda Langsung">
                                <iconify-icon icon="mdi:cash-check"></iconify-icon>
                                Kembali & Lunas
                              </a>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Returned Books History -->
          <div class="card">
            <div class="section-header-flex">
              <h2>Riwayat Pengembalian Buku</h2>
              <div class="search-wrapper">
                <input type="text" id="searchHistory" class="search-input" placeholder="Cari buku atau nama siswa...">
                <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                <div class="search-kbd">
                  <span style="font-size: 8px;">Ctrl</span>
                  <span>K</span>
                </div>
                <button class="search-clear"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
              </div>
            </div>
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] === 'returned'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:history"></iconify-icon>
                <p>Belum ada riwayat pengembalian</p>
              </div>
            <?php else: ?>
              <div class="borrows-table-wrap">
                <table class="borrows-table">
                  <thead>
                    <tr>
                      <th class="table-no">No</th>
                      <th>Nama Buku</th>
                      <th>Nama Anggota</th>
                      <th>Tanggal Pinjam</th>
                      <th>Tanggal Kembali</th>
                      <th>Denda</th>
                      <th>Status Buku</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no = 1;
                    foreach ($borrows as $br):
                      if ($br['status'] !== 'returned')
                        continue;
                      ?>
                      <tr>
                        <td class="table-no"><?= $no++ ?></td>
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <td><?= $br['returned_at'] ? date('d/m/Y', strtotime($br['returned_at'])) : '-' ?></td>
                        <td>
                          <?php if ((float)$br['fine_amount'] > 0): ?>
                            <div class="flex-center gap-2" style="font-weight: 700; color: <?= $br['fine_status'] === 'paid' ? 'var(--success)' : 'var(--danger)' ?>;">
                              <iconify-icon icon="mdi:credit-card-outline"></iconify-icon>
                              Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                            </div>
                            <div style="margin-top: 4px;">
                              <?php if ($br['fine_status'] === 'paid'): ?>
                                <span class="status-badge" style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                  <iconify-icon icon="mdi:check-circle"></iconify-icon> Lunas
                                </span>
                              <?php else: ?>
                                <span class="status-badge" style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25); font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                  <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon> Belum Lunas
                                </span>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="status-badge returned">Dikembalikan</span>
                        </td>
                        <td>
                          <?php if ((float)$br['fine_amount'] > 0 && $br['fine_status'] !== 'paid'): ?>
                            <a href="borrows.php?action=pay_fine&id=<?= $br['id'] ?>" 
                               class="btn-sm btn-sm-success" 
                               onclick="return confirm('Konfirmasi pelunasan denda Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?> untuk <?= htmlspecialchars(addslashes($br['member_name'])) ?>?');"
                               style="text-decoration: none; font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);">
                              <iconify-icon icon="mdi:cash-check" style="font-size: 16px;"></iconify-icon>
                              Tandai Lunas
                            </a>
                          <?php elseif ((float)$br['fine_amount'] > 0 && $br['fine_status'] === 'paid'): ?>
                            <span style="color: var(--success); font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                              <iconify-icon icon="mdi:check-all" style="font-size: 16px;"></iconify-icon> Sudah Lunas
                            </span>
                          <?php else: ?>
                            <span style="color: var(--muted); font-size: 12px;">-</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <script>

    function extendDueDate(borrowId, bookTitle) {
      const days = prompt(`Perpanjang tenggat untuk "${bookTitle}":\n\nMasukkan jumlah hari perpanjangan (1-365):`, '7');

      if (days === null) {
        return;
      }

      const daysInt = parseInt(days, 10);

      if (isNaN(daysInt) || daysInt < 1 || daysInt > 365) {
        alert('Jumlah hari harus antara 1-365');
        return;
      }

      fetch('api/extend-due-date.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId + '&extend_days=' + daysInt
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(`✓ Tenggat waktu diperpanjang!\n\nBuku: ${data.book_title}\nAnggota: ${data.member_name}\nTenggat Baru: ${data.new_due_date}`);
            location.reload();
          } else {
            alert('❌ Gagal memperpanjang tenggat:\n' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan saat memperpanjang tenggat');
        });
    }

    function confirmReturn(borrowId) {
      if (!confirm('Konfirmasi pengembalian buku ini?')) {
        return;
      }

      fetch('api/admin-confirm-return.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Pengembalian buku telah dikonfirmasi!');
            location.reload();
          } else {
            alert(data.message || 'Gagal mengkonfirmasi pengembalian');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan');
        });
    }
  </script>

  <!-- Live Scan Sync Script -->
  <audio id="scanNotificationSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
</body>

</html>