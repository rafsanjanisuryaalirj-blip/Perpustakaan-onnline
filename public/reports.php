<?php
require_once __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';
requireAuth();

$schoolId = (int) $_SESSION['user']['school_id'];

$tot_books = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE school_id = $schoolId")->fetchColumn();
$tot_copies = (int) $pdo->query("SELECT SUM(copies) FROM books WHERE school_id = $schoolId")->fetchColumn();
$tot_borrows_month = (int) $pdo->query("SELECT COUNT(*) FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = $schoolId AND MONTH(br.borrowed_at) = MONTH(CURRENT_DATE()) AND YEAR(br.borrowed_at)=YEAR(CURRENT_DATE())")->fetchColumn();
$tot_returns_month = (int) $pdo->query("SELECT COUNT(*) FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = $schoolId AND br.returned_at IS NOT NULL AND MONTH(br.returned_at)=MONTH(CURRENT_DATE()) AND YEAR(br.returned_at)=YEAR(CURRENT_DATE())")->fetchColumn();
$tot_borrows_today = (int) $pdo->query("SELECT COUNT(*) FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = $schoolId AND DATE(br.borrowed_at) = CURRENT_DATE()")->fetchColumn();
$tot_returns_today = (int) $pdo->query("SELECT COUNT(*) FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = $schoolId AND br.returned_at IS NOT NULL AND DATE(br.returned_at) = CURRENT_DATE()")->fetchColumn();
$active_members = (int) $pdo->query("SELECT COUNT(DISTINCT br.member_id) FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = $schoolId AND br.borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)")->fetchColumn();
$tot_categories = (int) $pdo->query("SELECT COUNT(DISTINCT category) FROM books WHERE school_id = $schoolId")->fetchColumn();

$schoolLateFineStmt = $pdo->prepare("SELECT late_fine FROM schools WHERE id = ?");
$schoolLateFineStmt->execute([$schoolId]);
$lateFineVal = $schoolLateFineStmt->fetchColumn();
$per_day = ($lateFineVal !== false && $lateFineVal !== null) ? (float) $lateFineVal : 1000;

$fines = 0;
$unpaidLateFines = 0;
$paidLateFines = 0;
$rows = $pdo->query("SELECT br.due_at, br.returned_at, br.fine_amount, br.fine_status FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = $schoolId AND br.due_at IS NOT NULL AND (br.returned_at IS NOT NULL OR CURRENT_DATE() > br.due_at)")->fetchAll();
foreach ($rows as $r) {
  $amt = 0;
  if (isset($r['fine_amount']) && (float)$r['fine_amount'] > 0) {
    $amt = (float)$r['fine_amount'];
  } else {
    $due = new DateTime($r['due_at']);
    $returned = $r['returned_at'] ? new DateTime($r['returned_at']) : new DateTime();
    $diff = (int) $due->diff($returned)->format('%r%a');
    if ($diff > 0)
      $amt = $diff * $per_day;
  }
  if ($amt > 0) {
    $fines += $amt;
    if (!empty($r['fine_status']) && $r['fine_status'] === 'paid') {
      $paidLateFines += $amt;
    } else {
      $unpaidLateFines += $amt;
    }
  }
}

$damageController = new DamageController($pdo, $schoolId);
$damageRecords = $damageController->getAll();
$totalDamageFines = $damageController->getTotalFines();
$pendingDamageFines = $damageController->getTotalFines('pending');
$paidDamageFines = $damageController->getTotalFines('paid');
$finesByMember = $damageController->getFinesByMember();

// Trend
$trendStmt = $pdo->prepare("SELECT DATE(br.borrowed_at) as d, COUNT(*) as c FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = ? AND br.borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY) GROUP BY DATE(br.borrowed_at) ORDER BY d ASC");
$trendStmt->execute([$schoolId]);
$trend = $trendStmt->fetchAll();
$trend_labels = [];
$trend_data = [];
$start = new DateTime('-29 days');
$period = new DatePeriod($start, new DateInterval('P1D'), 30);
$map = [];
foreach ($trend as $t)
  $map[$t['d']] = (int) $t['c'];
foreach ($period as $day) {
  $k = $day->format('Y-m-d');
  $trend_labels[] = $k;
  $trend_data[] = $map[$k] ?? 0;
}

// Category
$category_labels = [];
$category_data = [];
$hasCategory = (bool) $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='books' AND COLUMN_NAME='category'")->fetchColumn();
if ($hasCategory) {
  $catStmt = $pdo->prepare("SELECT b.category, COUNT(*) as c FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = ? GROUP BY b.category ORDER BY c DESC LIMIT 10");
  $catStmt->execute([$schoolId]);
  foreach ($catStmt->fetchAll() as $r) {
    $category_labels[] = $r['category'] ?: 'Uncategorized';
    $category_data[] = (int) $r['c'];
  }
}

// Members
$memStmt = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') month, COUNT(*) c FROM members WHERE school_id = ? AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH) GROUP BY month ORDER BY month ASC");
$memStmt->execute([$schoolId]);
$mem = $memStmt->fetchAll();
$mem_labels = [];
$mem_data = [];
$start = new DateTime('first day of -11 months');
$period = new DatePeriod($start, new DateInterval('P1M'), 12);
$map = [];
foreach ($mem as $m)
  $map[$m['month']] = (int) $m['c'];
foreach ($period as $d) {
  $k = $d->format('Y-m');
  $mem_labels[] = $k;
  $mem_data[] = $map[$k] ?? 0;
}

// Heatmap
$hourStmt = $pdo->prepare("SELECT HOUR(br.borrowed_at) h, COUNT(*) c FROM borrows br JOIN books b ON br.book_id = b.id WHERE b.school_id = ? AND br.borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY) GROUP BY h");
$hourStmt->execute([$schoolId]);
$hours = array_fill(0, 24, 0);
foreach ($hourStmt->fetchAll() as $r)
  $hours[(int) $r['h']] = (int) $r['c'];

// Tables
$borrowTable = $pdo->query("SELECT br.id, br.borrowed_at, b.title as book_title, m.name as member_name, br.status, br.due_at, br.returned_at FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id WHERE b.school_id = $schoolId ORDER BY br.borrowed_at DESC LIMIT 500")->fetchAll();
$returnsTable = $pdo->query("SELECT br.id, br.borrowed_at, br.returned_at, DATEDIFF(br.returned_at, br.due_at) as days_late, b.title as book_title, m.name as member_name FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id WHERE b.school_id = $schoolId AND br.returned_at IS NOT NULL ORDER BY br.returned_at DESC LIMIT 500")->fetchAll();
$booksTable = $pdo->query("SELECT id, title, author, copies, created_at FROM books WHERE school_id = $schoolId ORDER BY title LIMIT 1000")->fetchAll();

$new_members_30 = (int) $pdo->query("SELECT COUNT(*) FROM members WHERE school_id = $schoolId AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();
$new_books_30 = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE school_id = $schoolId AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan - Perpustakaan Online</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/reports.css">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <div class="topbar">
      <div class="topbar-title">
        <iconify-icon icon="mdi:chart-box-outline" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Laporan Perpustakaan</strong>
      </div>
      <div class="topbar-actions">
      </div>
    </div>

    <div class="content">
      <!-- Filter Panel -->
      <div class="card">
        <h3>Filter Data</h3>
        <div class="filter-panel">
          <div class="form-group">
            <label for="filter-start">Tanggal Mulai</label>
            <input id="filter-start" type="date" />
          </div>
          <div class="form-group">
            <label for="filter-end">Tanggal Akhir</label>
            <input id="filter-end" type="date" />
          </div>
          <div class="form-group">
            <label for="filter-category">Kategori</label>
            <select id="filter-category">
              <option value="">Semua Kategori</option>
              <?php if ($hasCategory): ?>
                <?php foreach ($category_labels as $c): ?>
                  <option><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option disabled>-- kategori tidak tersedia --</option>
              <?php endif; ?>
            </select>
          </div>
          <div>
            <button id="btn-apply" class="btn"><iconify-icon icon="mdi:filter"
                style="vertical-align: middle;"></iconify-icon> Filter</button>
            <button id="btn-export-excel" class="btn btn-secondary"><iconify-icon
                icon="mdi:file-excel" style="vertical-align: middle;"></iconify-icon> Export Excel</button>
          </div>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card clickable" data-stat-type="total_books" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:library"></iconify-icon></div>
          <div>
            <div class="kpi-title">Total Judul Buku</div>
            <div class="kpi-value"><?php echo number_format($tot_books); ?></div>
          </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="total_copies">
           <div class="kpi-icon"><iconify-icon icon="mdi:book-multiple"></iconify-icon></div>
           <div>
             <div class="kpi-title">Total Eksemplar</div>
             <div class="kpi-value"><?php echo number_format($tot_copies); ?></div>
           </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="borrows_month" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:sync"></iconify-icon></div>
          <div>
            <div class="kpi-title">Peminjaman Bulan Ini</div>
            <div class="kpi-value"><?php echo number_format($tot_borrows_month); ?></div>
          </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="borrows_today">
           <div class="kpi-icon"><iconify-icon icon="mdi:calendar-today"></iconify-icon></div>
           <div>
             <div class="kpi-title">Peminjaman Hari Ini</div>
             <div class="kpi-value"><?php echo number_format($tot_borrows_today); ?></div>
           </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="returns_month" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:inbox"></iconify-icon></div>
          <div>
            <div class="kpi-title">Pengembalian Bulan Ini</div>
            <div class="kpi-value"><?php echo number_format($tot_returns_month); ?></div>
          </div>
        </div>
        
        <div class="kpi-card clickable" data-stat-type="returns_today">
            <div class="kpi-icon"><iconify-icon icon="mdi:check-circle"></iconify-icon></div>
            <div>
              <div class="kpi-title">Pengembalian Hari Ini</div>
              <div class="kpi-value"><?php echo number_format($tot_returns_today); ?></div>
            </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="active_members" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:account-multiple"></iconify-icon></div>
          <div>
            <div class="kpi-title">Anggota Aktif (90 hari)</div>
            <div class="kpi-value"><?php echo number_format($active_members); ?></div>
          </div>
        </div>
        
        <div class="kpi-card clickable" data-stat-type="total_categories">
           <div class="kpi-icon"><iconify-icon icon="mdi:bookmark-multiple"></iconify-icon></div>
           <div>
             <div class="kpi-title">Total Kategori</div>
             <div class="kpi-value"><?php echo number_format($tot_categories); ?></div>
           </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="late_fines" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:cash-multiple"></iconify-icon></div>
          <div>
            <div class="kpi-title">Denda Keterlambatan</div>
            <div class="kpi-value">Rp <?php echo number_format($fines); ?></div>
            <div style="font-size: 11px; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
              <span style="color: #dc2626; font-weight: 600;">Rp <?= number_format($unpaidLateFines); ?> Tertunda</span>
              <span style="opacity: 0.4;">•</span>
              <span style="color: #16a34a; font-weight: 600;">Rp <?= number_format($paidLateFines); ?> Lunas</span>
            </div>
          </div>
        </div>

        <div class="kpi-card clickable" data-stat-type="damage_fines" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:alert-circle"></iconify-icon></div>
          <div>
            <div class="kpi-title">Denda Kerusakan Buku</div>
            <div class="kpi-value">Rp <?php echo number_format($totalDamageFines); ?></div>
            <div style="font-size: 11px; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
              <span style="color: #dc2626; font-weight: 600;">Rp <?= number_format($pendingDamageFines); ?> Tertunda</span>
              <span style="opacity: 0.4;">•</span>
              <span style="color: #16a34a; font-weight: 600;">Rp <?= number_format($paidDamageFines); ?> Lunas</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="chart-grid">
        <div class="chart-box">
          <h2>Tren Peminjaman (30 hari)</h2>
          <div class="chart-container">
            <canvas id="chart-trend"></canvas>
          </div>
        </div>

        <div class="chart-box">
          <h2>Kategori Populer</h2>
          <div class="chart-container">
            <?php if ($hasCategory): ?>
              <canvas id="chart-category"></canvas>
            <?php else: ?>
              <div class="chart-empty">Kolom kategori tidak tersedia</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="chart-box">
          <h2>Pertumbuhan Anggota</h2>
          <div class="chart-container">
            <canvas id="chart-members"></canvas>
          </div>
        </div>
      </div>

      <!-- Info Section -->
      <div class="info-section">
        <div class="card clickable" data-stat-type="new_members_30" title="Klik untuk melihat detail">
          <div class="kpi-title">Anggota Baru (30 hari)</div>
          <div class="kpi-value"><?php echo number_format($new_members_30); ?></div>
        </div>
        <div class="card clickable" data-stat-type="new_books_30" title="Klik untuk melihat detail">
          <div class="kpi-title">Buku Baru (30 hari)</div>
          <div class="kpi-value"><?php echo number_format($new_books_30); ?></div>
        </div>
      </div>

      <!-- Heatmap -->
      <div class="card">
        <h3>Heatmap Jam Peminjaman (30 hari terakhir)</h3>
        <div id="heatmap" class="heatmap-grid">
          <?php for ($h = 0; $h < 24; $h++): ?>
            <?php $v = $hours[$h];
            $intensity = min(1, $v / max(1, max($hours))); ?>
            <div class="heatmap-cell" style="background: color-mix(in srgb, var(--info), transparent <?php echo (1 - (0.12 + $intensity * 0.6)) * 100; ?>%);">
              <div style="font-size:12px; color:var(--muted);"><?php echo sprintf('%02d:00', $h); ?></div>
            </div>
          <?php endfor; ?>
        </div>
        <small style="display: block; margin-top: 12px; color: var(--muted);">Warna lebih gelap menunjukkan volume peminjaman lebih tinggi</small>
      </div>

      <!-- Tables Section -->
      <div class="card">
        <h3>Laporan Detail</h3>

        <h4 style="margin-top: 24px;">Laporan Peminjaman</h4>
        <table id="tbl-borrows" class="datatable">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Buku</th>
              <th>Anggota</th>
              <th>Status</th>
              <th>Due</th>
              <th>Kembali</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($borrowTable as $r): ?>
              <tr>
                <td><?php echo $r['borrowed_at']; ?></td>
                <td><?php echo htmlspecialchars($r['book_title']); ?></td>
                <td><?php echo htmlspecialchars($r['member_name']); ?></td>
                <td><?php echo $r['status']; ?></td>
                <td><?php echo $r['due_at']; ?></td>
                <td><?php echo $r['returned_at']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <h4 style="margin-top: 24px;">Laporan Pengembalian</h4>
        <table id="tbl-returns" class="datatable">
          <thead>
            <tr>
              <th>Pinjam</th>
              <th>Kembali</th>
              <th>Terlambat (hari)</th>
              <th>Buku</th>
              <th>Anggota</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($returnsTable as $r): ?>
              <tr>
                <td><?php echo $r['borrowed_at']; ?></td>
                <td><?php echo $r['returned_at']; ?></td>
                <td><?php echo max(0, (int) $r['days_late']); ?></td>
                <td><?php echo htmlspecialchars($r['book_title']); ?></td>
                <td><?php echo htmlspecialchars($r['member_name']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <h4 style="margin-top: 24px;">Laporan Buku</h4>
        <table id="tbl-books" class="datatable">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Penulis</th>
              <th>Stok</th>
              <th>Ditambahkan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($booksTable as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['title']); ?></td>
                <td><?php echo htmlspecialchars($r['author']); ?></td>
                <td><?php echo (int) $r['copies']; ?></td>
                <td><?php echo $r['created_at']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Fine Reports -->
        <h4 style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px;">Laporan Denda Kerusakan Buku</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
          <div class="fine-card info clickable" data-stat-type="damage_fines">
            <div class="fine-card-label">Total Denda</div>
            <div class="fine-card-value info">Rp <?php echo number_format($totalDamageFines); ?></div>
          </div>
          <div class="fine-card danger clickable" data-stat-type="damage_fines" data-status="pending">
            <div class="fine-card-label">Denda Tertunda</div>
            <div class="fine-card-value danger">Rp <?php echo number_format($pendingDamageFines); ?></div>
          </div>
          <div class="fine-card success clickable" data-stat-type="damage_fines" data-status="paid">
            <div class="fine-card-label">Denda Terbayar</div>
            <div class="fine-card-value success">Rp <?php echo number_format($paidDamageFines); ?></div>
          </div>
        </div>

        <h5 style="margin-bottom: 12px;">Daftar Denda Per Anggota</h5>
        <table id="tbl-fines-by-member" class="datatable">
          <thead>
            <tr>
              <th>Nama Anggota</th>
              <th>Jumlah Kerusakan</th>
              <th>Total Denda</th>
              <th>Terbayar</th>
              <th>Tertunda</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($finesByMember)): ?>
              <?php foreach ($finesByMember as $member): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($member['name']); ?></strong></td>
                  <td><?php echo (int) $member['damage_count']; ?></td>
                  <td class="fine-card-value danger" style="font-size: 14px;">Rp <?php echo number_format($member['total_fine']); ?></td>
                  <td class="fine-card-value success" style="font-size: 14px;">Rp <?php echo number_format($member['paid_amount']); ?></td>
                  <td class="fine-card-value danger" style="font-size: 14px;">Rp <?php echo number_format($member['pending_amount']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <h5 style="margin-top: 24px; margin-bottom: 12px;">Riwayat Denda Kerusakan</h5>
        <table id="tbl-damage-fines" class="datatable">
          <thead>
            <tr>
              <th>Anggota</th>
              <th>Buku</th>
              <th>Tipe</th>
              <th>Denda</th>
              <th>Status</th>
              <th>Tanggal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($damageRecords as $record): ?>
              <tr>
                <td><?php echo htmlspecialchars($record['member_name']); ?></td>
                <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                <td><?php echo htmlspecialchars($record['damage_type']); ?></td>
                <td class="fine-card-value danger" style="font-size: 14px;">Rp <?php echo number_format($record['fine_amount']); ?></td>
                <td>
                  <span class="status-pill <?php echo $record['status'] === 'paid' ? 'paid' : 'pending'; ?>">
                    <?php echo $record['status'] === 'paid' ? 'Lunas' : 'Tertunda'; ?>
                  </span>
                </td>
                <td><?php echo date('d M Y', strtotime($record['created_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <!-- Stats Modal -->
  <div class="modal-overlay" id="statsModal">
    <div class="modal-container">
      <div class="modal-header">
        <h2>Detail Data</h2>
        <button class="modal-close" type="button">×</button>
      </div>
      <div class="modal-body">
        <div class="modal-loading">Memuat data...</div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <script>
    window.chartData = {
      trendLabels: <?php echo json_encode($trend_labels); ?>,
      trendData: <?php echo json_encode($trend_data); ?>,
      categoryLabels: <?php echo json_encode($category_labels); ?>,
      categoryData: <?php echo json_encode($category_data); ?>,
      memLabels: <?php echo json_encode($mem_labels); ?>,
      memData: <?php echo json_encode($mem_data); ?>
    };
  </script>
  <script src="../assets/js/reports.js?v=<?php echo time(); ?>"></script>
  <script src="../assets/js/reports-stats.js?v=<?php echo time(); ?>"></script>
</body>

</html>
