<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$is_authenticated = !empty($_SESSION['user']);

if ($is_authenticated) {
    $pdo = require __DIR__ . '/../src/db.php';
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];

    // Recent borrows
    $stmt = $pdo->prepare("SELECT b.title, m.name, br.borrowed_at as timestamp, 'borrow' as type FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        JOIN members m ON br.member_id = m.id 
        WHERE br.school_id = :sid AND br.returned_at IS NULL 
        ORDER BY br.borrowed_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $recent_borrows = $stmt->fetchAll();

    // Recent returns
    $stmt = $pdo->prepare("SELECT b.title, m.name, br.returned_at as timestamp, 'return' as type FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        JOIN members m ON br.member_id = m.id 
        WHERE br.school_id = :sid AND br.returned_at IS NOT NULL 
        ORDER BY br.returned_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $recent_returns = $stmt->fetchAll();

    // New members
    $stmt = $pdo->prepare("SELECT name as title, '' as name, created_at as timestamp, 'member' as type FROM members 
        WHERE school_id = :sid 
        ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $new_members = $stmt->fetchAll();

    // New books
    $stmt = $pdo->prepare("SELECT title, '' as name, created_at as timestamp, 'book' as type FROM books 
        WHERE school_id = :sid 
        ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $new_books = $stmt->fetchAll();

    // Merge and sort all activities
    $all_activities = array_merge($recent_borrows, $recent_returns, $new_members, $new_books);
    usort($all_activities, function ($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Special Theme Check
    require_once __DIR__ . '/../src/ThemeModel.php';
    $themeModel = new ThemeModel($pdo);
    $specialTheme = $themeModel->checkSpecialTheme($school_id);
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Perpustakaan</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>

    <?php if ($is_authenticated): ?>
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <div class="app">

            <div class="topbar">
                <strong>Dashboard Perpustakaan</strong>
            </div>

            <div class="content">
                <div class="dashboard-grid">
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="borrows.php" class="q-action">
                            <div class="q-icon" style="background: var(--accent-soft); color: var(--accent);">
                                <iconify-icon icon="mdi:book-plus-outline"></iconify-icon>
                            </div>
                            <span>Pinjam Buku</span>
                        </a>
                        <a href="borrows.php" class="q-action">
                            <div class="q-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <iconify-icon icon="mdi:book-check-outline"></iconify-icon>
                            </div>
                            <span>Pengembalian</span>
                        </a>
                        <a href="books.php" class="q-action">
                            <div class="q-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                <iconify-icon icon="mdi:library-plus"></iconify-icon>
                            </div>
                            <span>Tambah Buku</span>
                        </a>
                        <a href="members.php" class="q-action">
                            <div class="q-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                <iconify-icon icon="mdi:account-plus-outline"></iconify-icon>
                            </div>
                            <span>Tambah Anggota</span>
                        </a>
                    </div>

                    <div class="stats">
                        <div class="stat stat-books" data-stat-type="books" data-tooltip="Total seluruh buku yang sudah terdaftar di perpustakaan">
                            <div class="stat-icon">
                                <iconify-icon icon="mdi:library-books"></iconify-icon>
                            </div>
                            <div class="stat-info">
                                <small>Total Buku</small>
                                <strong class="stat-value" id="stat-books">-</strong>
                            </div>
                        </div>
                        <div class="stat stat-members" data-stat-type="members" data-tooltip="Total seluruh anggota perpustakaan yang terdaftar">
                            <div class="stat-icon">
                                <iconify-icon icon="mdi:account-group"></iconify-icon>
                            </div>
                            <div class="stat-info">
                                <small>Total Anggota</small>
                                <strong class="stat-value" id="stat-members">-</strong>
                            </div>
                        </div>
                        <div class="stat stat-borrowed" data-stat-type="borrowed" data-tooltip="Total buku yang sedang dipinjam oleh anggota">
                            <div class="stat-icon">
                                <iconify-icon icon="mdi:book-clock"></iconify-icon>
                            </div>
                            <div class="stat-info">
                                <small>Dipinjam</small>
                                <strong class="stat-value" id="stat-borrowed">-</strong>
                            </div>
                        </div>
                        <div class="stat stat-overdue alert" data-stat-type="overdue" data-tooltip="Total peminjaman yang sudah melewati batas waktu pengembalian">
                            <div class="stat-icon">
                                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
                            </div>
                            <div class="stat-info">
                                <small>Terlambat</small>
                                <strong class="stat-value" id="stat-overdue">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="charts">
                        <div class="chart-box">
                            <h2>Peminjaman per Bulan</h2>
                            <div class="chart-container">
                                <canvas id="borrowChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-box">
                            <h2>Status Buku</h2>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-box">
                            <h2>Tren 7 Hari Terakhir</h2>
                            <div class="chart-container">
                                <canvas id="weeklyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="insights-row">
                        <div class="chart-box">
                            <h2><iconify-icon icon="mdi:fire" class="section-header-icon" style="color: #f59e0b;"></iconify-icon>Buku Terpopuler</h2>
                            <div class="chart-container">
                                <canvas id="topBooksChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-box">
                            <h2><iconify-icon icon="mdi:star" class="section-header-icon" style="color: #f59e0b;"></iconify-icon>Anggota Teraktif</h2>
                            <div class="top-list" id="top-members-list">
                                <div class="modal-loading">Memuat data...</div>
                            </div>
                        </div>
                    </div>

                    <div class="activity-section">
                        <div class="section-header-flex">
                            <div style="flex: 1;">
                                <h2><iconify-icon icon="mdi:clipboard-list" class="section-header-icon"></iconify-icon>Aktivitas Terbaru</h2>
                                <p style="color: var(--muted); font-size: 13px; margin-top: 4px;">Pantau sirkulasi data terbaru secara real-time</p>
                            </div>
                            <div class="search-wrapper">
                                <input type="text" id="searchActivityList" class="search-input" placeholder="Cari aktivitas...">
                                <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                                <div class="search-kbd">
                                    <span style="font-size: 8px;">Ctrl</span>
                                    <span>K</span>
                                </div>
                                <button class="search-clear" id="clearActivitySearch"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
                            </div>
                        </div>

                        <div class="activity-tabs">
                            <button class="activity-tab active btn-sm" data-tab="all"><iconify-icon
                                    icon="mdi:shuffle-variant" style="vertical-align: middle;"></iconify-icon>
                                Semua</button>
                            <button class="activity-tab btn-sm" data-tab="borrows"><iconify-icon icon="mdi:book-open"
                                    style="vertical-align: middle;"></iconify-icon> Peminjaman</button>
                            <button class="activity-tab btn-sm" data-tab="returns"><iconify-icon icon="mdi:inbox"
                                    style="vertical-align: middle;"></iconify-icon> Pengembalian</button>
                            <button class="activity-tab btn-sm" data-tab="members"><iconify-icon icon="mdi:account-multiple"
                                    style="vertical-align: middle;"></iconify-icon> Anggota Baru</button>
                            <button class="activity-tab btn-sm" data-tab="books"><iconify-icon icon="mdi:library"
                                    style="vertical-align: middle;"></iconify-icon> Buku Baru</button>
                        </div>

                        <!-- All Activities Tab -->
                        <div class="activity-content active" id="all-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($all_activities)): ?>
                                        <?php foreach ($all_activities as $activity): ?>
                                            <div class="activity-item">
                                                <div class="activity-marker">
                                                    <?php
                                                    switch ($activity['type']) {
                                                        case 'borrow': echo '<iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>'; break;
                                                        case 'return': echo '<iconify-icon icon="mdi:keyboard-return"></iconify-icon>'; break;
                                                        case 'member': echo '<iconify-icon icon="mdi:account-plus"></iconify-icon>'; break;
                                                        case 'book': echo '<iconify-icon icon="mdi:plus-circle"></iconify-icon>'; break;
                                                    }
                                                    ?>
                                                </div>
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <?php
                                                        switch ($activity['type']) {
                                                            case 'borrow':
                                                                echo 'Dipinjam oleh ' . htmlspecialchars($activity['name']);
                                                                break;
                                                            case 'return':
                                                                echo 'Dikembalikan oleh ' . htmlspecialchars($activity['name']);
                                                                break;
                                                            case 'member':
                                                                echo 'Anggota baru terdaftar';
                                                                break;
                                                            case 'book':
                                                                echo 'Buku baru ditambahkan';
                                                                break;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada aktivitas terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Borrows Tab -->
                        <div class="activity-content" id="borrows-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($recent_borrows)): ?>
                                        <?php foreach ($recent_borrows as $activity): ?>
                                            <div class="activity-item">
                                                <div class="activity-marker" style="border-color: var(--accent); color: var(--accent);">
                                                    <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
                                                </div>
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        Dipinjam oleh <?= htmlspecialchars($activity['name']) ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada peminjaman terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Returns Tab -->
                        <div class="activity-content" id="returns-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($recent_returns)): ?>
                                        <?php foreach ($recent_returns as $activity): ?>
                                            <div class="activity-item">
                                                <div class="activity-marker" style="border-color: var(--success); color: var(--success);">
                                                    <iconify-icon icon="mdi:keyboard-return"></iconify-icon>
                                                </div>
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        Dikembalikan oleh <?= htmlspecialchars($activity['name']) ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada pengembalian terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Members Tab -->
                        <div class="activity-content" id="members-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($new_members)): ?>
                                        <?php foreach ($new_members as $activity): ?>
                                            <div class="activity-item">
                                                <div class="activity-marker" style="border-color: var(--success); color: var(--success);">
                                                    <iconify-icon icon="mdi:account-plus"></iconify-icon>
                                                </div>
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        Anggota baru terdaftar
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada anggota baru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Books Tab -->
                        <div class="activity-content" id="books-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($new_books)): ?>
                                        <?php foreach ($new_books as $activity): ?>
                                            <div class="activity-item">
                                                <div class="activity-marker" style="border-color: var(--accent); color: var(--accent);">
                                                    <iconify-icon icon="mdi:plus-circle"></iconify-icon>
                                                </div>
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        Buku baru ditambahkan
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada buku baru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <script src="../assets/js/stats-modal.js"></script>
        <script src="../assets/js/index.js"></script>

        <!-- Modal untuk Stats -->
        <div class="modal-overlay" id="statsModal">
            <div class="modal-container">
                <div class="modal-header">
                    <div style="flex: 1;">
                        <h2>Detail Data</h2>
                    </div>
                    <div class="search-wrapper">
                        <input type="text" id="searchModalTab" class="search-input" placeholder="Cari data...">
                        <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                        <div class="search-kbd">
                            <span style="font-size: 8px;">Ctrl</span>
                            <span>K</span>
                        </div>
                        <button class="search-clear" id="clearModalTabSearch"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
                    </div>
                    <button class="modal-close" type="button" style="margin-left: 20px;">×</button>
                </div>
                <div class="modal-body">
                    <div class="modal-loading">Memuat data...</div>
                </div>
            </div>
        </div>

        <script src="../assets/js/index-manage.js"></script>
    <?php endif; ?>

</body>

</html>