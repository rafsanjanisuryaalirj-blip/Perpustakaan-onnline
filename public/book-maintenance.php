<?php
require_once __DIR__ . '/../src/auth.php';
requireAuth();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';

// Get school_id from session
$school_id = $_SESSION['user']['school_id'];

// Pass school_id to controller
$controller = new DamageController($pdo, $school_id);

// Get all damage records and active borrows
$records = $controller->getAll();
$activeBorrows = $controller->getActiveBorrows();
$totalRecords = $controller->getCount();
$totalFines = $controller->getTotalFines();
$pendingFines = $controller->getTotalFines('pending');
$damageTypes = $controller->getDamageTypes();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
  $controller->handleAjax();
  exit;
}

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: application/vnd.ms-excel; charset=utf-8');
  header('Content-Disposition: attachment; filename="damage-fines-' . date('Y-m-d-H-i-s') . '.xls"');

  echo '<!DOCTYPE html>';
  echo '<html>';
  echo '<head>';
  echo '<meta charset="UTF-8">';
  echo '<style>';
  echo 'table { border-collapse: collapse; width: 100%; }';
  echo 'th { background-color: #2563eb; color: white; padding: 12px; text-align: left; border: 1px solid #ddd; font-weight: bold; }';
  echo 'td { padding: 10px; border: 1px solid #ddd; }';
  echo 'tr:nth-child(even) { background-color: #f9fafb; }';
  echo 'col.id { width: 50px; }';
  echo 'col.member { width: 150px; }';
  echo 'col.book { width: 200px; }';
  echo 'col.damage { width: 120px; }';
  echo 'col.fine { width: 100px; }';
  echo 'col.status { width: 100px; }';
  echo 'col.date { width: 120px; }';
  echo '</style>';
  echo '</head>';
  echo '<body>';
  echo '<table>';
  echo '<colgroup>';
  echo '<col class="id">';
  echo '<col class="member">';
  echo '<col class="book">';
  echo '<col class="damage">';
  echo '<col class="fine">';
  echo '<col class="status">';
  echo '<col class="date">';
  echo '</colgroup>';
  echo '<thead>';
  echo '<tr>';
  echo '<th>ID</th>';
  echo '<th>Anggota</th>';
  echo '<th>Judul Buku</th>';
  echo '<th>Tipe Kerusakan</th>';
  echo '<th>Denda</th>';
  echo '<th>Status</th>';
  echo '<th>Tanggal</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';

  $no = 1;
  foreach ($records as $r) {
    echo '<tr>';
    echo '<td>' . $no . '</td>';
    echo '<td>' . htmlspecialchars($r['member_name']) . '</td>';
    echo '<td>' . htmlspecialchars($r['book_title']) . '</td>';
    echo '<td>' . htmlspecialchars($damageTypes[$r['damage_type']]['name'] ?? $r['damage_type']) . '</td>';
    echo '<td>Rp ' . number_format($r['fine_amount'], 0, ',', '.') . '</td>';
    echo '<td>' . htmlspecialchars($r['status']) . '</td>';
    echo '<td>' . date('d-m-Y H:i', strtotime($r['created_at'])) . '</td>';
    echo '</tr>';
    $no++;
  }

  echo '</tbody>';
  echo '</table>';
  echo '</body>';
  echo '</html>';

  exit;
}

?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laporan Kerusakan Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/index.css">
  <link rel="stylesheet" href="../assets/css/book-maintenance.css">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar" style="margin-left: -20px;">
      <strong>Laporan Kerusakan Buku</strong>
      <div class="topbar-actions">
        <button class="btn btn-secondary" onclick="exportCSV()"><iconify-icon icon="mdi:file-excel"
            style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Export Excel</button>
        <button class="btn" onclick="openAddModal()" style="margin-left: 8px;"><iconify-icon icon="mdi:plus"
            style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Lapor Kerusakan</button>
      </div>
    </div>

    <div class="content">

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card" data-stat-type="reports" style="cursor: pointer;" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:alert"></iconify-icon></div>
          <div>
            <div class="kpi-title">Total Laporan</div>
            <div class="kpi-value"><?= $totalRecords ?></div>
          </div>
        </div>

        <div class="kpi-card" data-stat-type="fines" style="cursor: pointer;" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:cash-multiple"></iconify-icon></div>
          <div>
            <div class="kpi-title">Total Denda</div>
            <div class="kpi-value">Rp <?= number_format($totalFines, 0, ',', '.') ?></div>
          </div>
        </div>

        <div class="kpi-card" data-stat-type="pending" style="cursor: pointer;" title="Klik untuk melihat detail">
          <div class="kpi-icon"><iconify-icon icon="mdi:clock-alert"></iconify-icon></div>
          <div>
            <div class="kpi-title">Denda Tertunda</div>
            <div class="kpi-value">Rp <?= number_format($pendingFines, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="card">
        <h2>Filter & Pencarian</h2>
        <div class="filter-bar">
          <input type="text" id="searchInput" placeholder="Cari anggota atau buku...">
          <select id="statusFilter">
            <option value="">Semua Status</option>
            <option value="pending">Tertunda</option>
            <option value="paid">Lunas</option>
          </select>
          <select id="damageTypeFilter">
            <option value="">Semua Tipe Kerusakan</option>
            <?php foreach ($damageTypes as $key => $type): ?>
              <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($type['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-danger" onclick="resetFilter();"><iconify-icon icon="mdi:redo"></iconify-icon>
            Reset</button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="card">
        <h2>Daftar Laporan (<?= count($records) ?>)</h2>
        <?php if (empty($records)): ?>
          <p style="text-align: center; color: var(--muted); padding: 32px 0;">
            Belum ada laporan kerusakan.
          </p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <colgroup>
                <col class="member">
                <col class="book">
                <col class="damage">
                <col class="description">
                <col class="fine">
                <col class="status">
                <col class="date">
                <col class="action">
              </colgroup>

              <thead>
                <tr>
                  <th>Anggota</th>
                  <th>Buku</th>
                  <th>Tipe Kerusakan</th>
                  <th>Deskripsi</th>
                  <th>Denda</th>
                  <th>Status</th>
                  <th>Tanggal</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>

              <tbody>
                <?php foreach ($records as $r): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($r['member_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['book_title']) ?></td>
                    <td data-damage-type="<?= htmlspecialchars($r['damage_type']) ?>">
                      <span class="damage-badge" style="background-color: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <?= htmlspecialchars($damageTypes[$r['damage_type']]['name'] ?? $r['damage_type']) ?>
                      </span>
                    </td>
                    <td style="font-size: 12px;">
                      <?= $r['damage_description'] ? htmlspecialchars(substr($r['damage_description'], 0, 30)) . (strlen($r['damage_description']) > 30 ? '...' : '') : '-' ?>
                    </td>
                    <td style="font-weight: 600; color: #dc2626;">Rp <?= number_format($r['fine_amount'], 0, ',', '.') ?>
                    </td>
                    <td>
                      <span class="status-badge status-<?= strtolower($r['status']) ?>">
                        <?= $r['status'] === 'paid' ? 'Lunas' : 'Tertunda' ?>
                      </span>
                    </td>
                    <td style="font-size: 12px;"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                    <td class="text-center">
                      <div class="actions">
                        <?php if ($r['status'] === 'pending'): ?>
                          <button class="btn btn-sm btn-success" onclick="markAsPaid(<?= $r['id'] ?>)"
                            title="Tandai Sebagai Lunas"><iconify-icon icon="mdi:check"></iconify-icon></button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?= $r['id'] ?>)"
                          title="Hapus"><iconify-icon icon="mdi:trash-can"></iconify-icon></button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Modal Add Damage Report -->
    <div id="damageModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">Lapor Kerusakan Buku</div>
        <div class="modal-body">
          <form id="damageForm">
            <div class="form-group">
              <label for="borrowId">Pilih Peminjaman</label>
              <select id="borrowId" name="borrow_id" required onchange="onBorrowSelected()">
                <option value="">-- Pilih Peminjaman --</option>
                <?php foreach ($activeBorrows as $b): ?>
                  <option value="<?= $b['id'] ?>" data-member-id="<?= $b['member_id'] ?>"
                    data-book-id="<?= $b['book_id'] ?>">
                    <?= htmlspecialchars($b['member_name']) ?> - <?= htmlspecialchars($b['book_title']) ?>
                    (<?= $b['status'] === 'returned' ? 'Dikembalikan' : 'Dipinjam' ?> - <?= date('d M Y', strtotime($b['borrowed_at'])) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="damageType">Tipe Kerusakan</label>
              <select id="damageType" name="damage_type" required onchange="onDamageTypeChanged()">
                <option value="">-- Pilih Tipe Kerusakan --</option>
                <?php foreach ($damageTypes as $key => $type): ?>
                  <option value="<?= htmlspecialchars($key) ?>" data-fine="<?= $type['fine'] ?>">
                    <?= htmlspecialchars($type['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="damageDescription">Deskripsi Kerusakan (Opsional)</label>
              <textarea id="damageDescription" name="damage_description"
                placeholder="Jelaskan detail kerusakan..."></textarea>
            </div>

            <div class="form-group">
              <label for="fineAmountInput">Nominal Denda (Rp)</label>
              <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 14px; font-weight: 600; color: #dc2626; z-index: 1;">Rp</span>
                <input type="number" id="fineAmountInput" name="fine_amount" class="form-control" min="0" step="1000" value="0" required style="padding-left: 42px; font-size: 16px; font-weight: 600; color: #dc2626;">
              </div>
              <small class="text-muted" style="display: block; margin-top: 5px; font-size: 11px;">
                Tarif standar tipe ini: <strong id="fineAmount">Rp 0</strong>. Anda dapat mengubah nominal secara bebas jika ada pertimbangan khusus.
              </small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" onclick="closeModal()"><iconify-icon icon="mdi:close"
              style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Batal</button>
          <button class="btn" onclick="saveDamageReport()"><iconify-icon icon="mdi:content-save"
              style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Lapor Kerusakan</button>
        </div>
      </div>
    </div>

    <!-- Stats Modal -->
    <div class="modal-overlay" id="statsModal">
      <div class="modal-container">
        <div class="modal-header">
          <div style="flex: 1;">
            <h2 id="statsModalTitle">Detail Data</h2>
          </div>
          <div class="search-wrapper">
              <input type="text" id="searchStatsModal" class="search-input" placeholder="Cari data...">
              <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
              <div class="search-kbd">
                  <span style="font-size: 8px;">Ctrl</span>
                  <span>K</span>
              </div>
              <button class="search-clear" id="clearStatsSearch"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
          </div>
          <button class="modal-close" type="button" style="margin-left: 20px;">×</button>
        </div>
        <div class="modal-body">
          <div class="modal-loading">Memuat data...</div>
        </div>
      </div>
    </div>

    <script>
      // Data untuk digunakan di book-maintenance.js
      window.recordsData = <?php echo json_encode($records); ?>;
      window.damageTypesData = <?php echo json_encode($damageTypes); ?>;
    </script>
    <script src="../assets/js/book-maintenance.js"></script>
    <script src="../assets/js/maintenance-stats.js"></script>

  </div>

</body>

</html>