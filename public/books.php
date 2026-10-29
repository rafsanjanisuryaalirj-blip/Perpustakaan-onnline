<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsHelper.php';

$user = $_SESSION['user'];
$sid = $user['school_id'];
$action = $_GET['action'] ?? 'list';

// Create uploads directory
$uploadsDir = __DIR__ . '/../img/covers';
if (!is_dir($uploadsDir)) {
  mkdir($uploadsDir, 0755, true);
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $coverImage = '';

  // Handle image upload
  if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff'];
    $filename = basename($_FILES['cover_image']['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
      $newFilename = 'book_' . time() . '_' . uniqid() . '.' . $ext;
      $uploadPath = $uploadsDir . '/' . $newFilename;

      if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
        $coverImage = $newFilename;
      }
    }
  }

  $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
  if ($quantity < 1) $quantity = 1;

  $stmt = $pdo->prepare(
    'INSERT INTO books (school_id,title,author,isbn,category,access_level,shelf,row_number,lokasi_rak,copies,max_borrow_days,cover_image)
     VALUES (:sid,:title,:author,:isbn,:category,:access_level,:shelf,:row,:lokasi_rak,:copies,:max_borrow_days,:cover_image)'
  );

  for ($i = 0; $i < $quantity; $i++) {
      $stmt->execute([
            'sid' => $sid,
            'title' => $_POST['title'],
            'author' => $_POST['author'],
            'isbn' => $_POST['isbn'],
            'category' => $_POST['category'],
            'access_level' => $_POST['access_level'] ?? 'all',
            'shelf' => $_POST['shelf'],
            'row' => $_POST['row_number'],
            'lokasi_rak' => $_POST['lokasi_rak'],
            'copies' => 1,
            'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
            'cover_image' => $coverImage
      ]);
  }
  
  // notification
  $studentsStmt = $pdo->prepare(
    'SELECT id FROM users WHERE school_id = :school_id AND role = "student"'
  );
  $studentsStmt->execute(['school_id' => $sid]);
  $students = $studentsStmt->fetchAll(PDO::FETCH_COLUMN);
  
  // Broadcast notification to all students
  if (!empty($students)) {
    $helper = new NotificationsHelper($pdo);
    $bookTitle = $_POST['title'];
    $notificationMessage = 'Buku "' . htmlspecialchars($bookTitle) . '" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!';
    
    $helper->broadcastNotification(
      $sid,
      $students,
      'new_book',
      'Buku Baru Tersedia',
      $notificationMessage
    );
  }
  
  header('Location: books.php');
  exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM books WHERE id=:id AND school_id=:sid');
    $stmt->execute(['id' => $id, 'sid' => $sid]);
    $oldBook = $stmt->fetch();
    $coverImage = $oldBook['cover_image'] ?? '';

    // Handle new image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      $filename = basename($_FILES['cover_image']['name']);
      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      if (in_array($ext, $allowed)) {
        $newFilename = 'book_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadsDir . '/' . $newFilename;

        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
          // Delete old image if exists
          if ($coverImage && file_exists($uploadsDir . '/' . $coverImage)) {
            unlink($uploadsDir . '/' . $coverImage);
          }
          $coverImage = $newFilename;
        }
      }
    }

    $pdo->prepare(
      'UPDATE books SET title=:title,author=:author,isbn=:isbn,category=:category,access_level=:access_level,shelf=:shelf,row_number=:row,lokasi_rak=:lokasi_rak,max_borrow_days=:max_borrow_days,cover_image=:cover_image
       WHERE title=:old_title AND isbn <=> :old_isbn AND school_id=:sid'
    )->execute([
          'title' => $_POST['title'],
          'author' => $_POST['author'],
          'isbn' => empty($_POST['isbn']) ? null : $_POST['isbn'],
          'category' => $_POST['category'],
          'access_level' => $_POST['access_level'] ?? 'all',
          'shelf' => $_POST['shelf'],
          'row' => $_POST['row_number'],
          'lokasi_rak' => $_POST['lokasi_rak'],
          'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
          'cover_image' => $coverImage,
          'old_title' => $oldBook['title'],
          'old_isbn' => $oldBook['isbn'],
          'sid' => $sid
        ]);
        
    $quantityAdd = isset($_POST['quantity_add']) ? (int)$_POST['quantity_add'] : 0;
    if ($quantityAdd > 0) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO books (school_id,title,author,isbn,category,access_level,shelf,row_number,lokasi_rak,copies,max_borrow_days,cover_image)
             VALUES (:sid,:title,:author,:isbn,:category,:access_level,:shelf,:row,:lokasi_rak,:copies,:max_borrow_days,:cover_image)'
        );
        for ($i = 0; $i < $quantityAdd; $i++) {
            $insertStmt->execute([
                'sid' => $sid,
                'title' => $_POST['title'],
                'author' => $_POST['author'],
                'isbn' => empty($_POST['isbn']) ? null : $_POST['isbn'],
                'category' => $_POST['category'],
                'access_level' => $_POST['access_level'] ?? 'all',
                'shelf' => $_POST['shelf'],
                'row' => $_POST['row_number'],
                'lokasi_rak' => $_POST['lokasi_rak'],
                'copies' => 1,
                'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
                'cover_image' => $coverImage
            ]);
        }
    }
    
    header('Location: books.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM books WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $book = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  $stmt = $pdo->prepare('SELECT title, isbn FROM books WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
  $deleteTarget = $stmt->fetch();
  if ($deleteTarget) {
      $pdo->prepare('DELETE FROM books WHERE title=:old_title AND isbn <=> :old_isbn AND school_id=:sid')
        ->execute(['old_title' => $deleteTarget['title'], 'old_isbn' => $deleteTarget['isbn'], 'sid' => $sid]);
  }
  header('Location: books.php');
  exit;
}

if ($action === 'delete_single' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM books WHERE id=:id AND school_id=:sid')
    ->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
  header('Location: books.php');
  exit;
}

if ($action === 'delete_single_ajax' && isset($_GET['id'])) {
  $stmt = $pdo->prepare('DELETE FROM books WHERE id=:id AND school_id=:sid');
  $success = $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// Get school info for default settings
$stmt = $pdo->prepare('SELECT borrow_duration FROM schools WHERE id = :sid');
$stmt->execute(['sid' => $sid]);
$school = $stmt->fetch();
$defaultDuration = $school['borrow_duration'] ?? 7;

$stmt = $pdo->prepare('
  SELECT bk.*, 
         curr_b.id as current_borrow_id, 
         curr_b.status as borrow_status,
         m.name as borrower_name
  FROM books bk
  -- Join with the MOST RECENT active borrow record
  LEFT JOIN borrows curr_b ON curr_b.id = (
      SELECT id FROM borrows 
      WHERE book_id = bk.id 
      AND status NOT IN ("returned", "rejected")
      ORDER BY id DESC LIMIT 1
  )
  LEFT JOIN members m ON curr_b.member_id = m.id
  WHERE bk.school_id = :sid 
  ORDER BY bk.id DESC
');
$stmt->execute(['sid' => $sid]);
$allBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groupedBooks = [];
foreach ($allBooks as $b) {
    $groupKey = (!empty($b['isbn']) ? $b['isbn'] : '') . '|' . $b['title'];
    if (!isset($groupedBooks[$groupKey])) {
        $groupedBooks[$groupKey] = [
            'id' => $b['id'],
            'title' => $b['title'],
            'author' => $b['author'],
            'isbn' => $b['isbn'],
            'category' => $b['category'],
            'cover_image' => $b['cover_image'],
            'shelf' => $b['shelf'],
            'row_number' => $b['row_number'],
            'lokasi_rak' => $b['lokasi_rak'],
            'access_level' => $b['access_level'],
            'max_borrow_days' => $b['max_borrow_days'],
            'total_copies' => 0,
            'available_copies' => 0,
            'copies_detail' => []
        ];
    }
    
    $groupedBooks[$groupKey]['total_copies']++;
    $isBorrowed = !empty($b['current_borrow_id']);
    
    if (!$isBorrowed && $b['copies'] > 0) {
        $groupedBooks[$groupKey]['available_copies']++;
    }

    $groupedBooks[$groupKey]['copies_detail'][] = [
        'id' => $b['id'],
        'barcode' => 'B-' . $b['id'],
        'is_borrowed' => $isBorrowed,
        'borrow_status' => $b['borrow_status'],
        'borrower_name' => $b['borrower_name'],
        'copies' => $b['copies']
    ];
}

$books = array_values($groupedBooks);

$categories = [
  'Fiksi',
  'Non-Fiksi',
  'Referensi',
  'Biografi',
  'Sejarah',
  'Seni & Budaya',
  'Teknologi',
  'Pendidikan',
  'Anak-anak',
  'Komik',
  'Majalah',
  'Lainnya'
];
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/books.css">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Buku</strong>
    </div>

    <div class="content">
      <div class="main">

        <!-- ADD/EDIT FORM -->
        <div class="card form-card">
          <div class="card-header-flex" style="border-bottom: none; margin-bottom: 24px; padding-bottom: 0;">
             <div style="flex: 1;">
                 <h2 style="border: none; padding: 0; margin-bottom: 4px;">
                    <iconify-icon icon="mdi:book-plus-outline" style="color: var(--accent);"></iconify-icon>
                    <?= $action === 'edit' ? 'Edit Detail Buku' : 'Tambah Koleksi Baru' ?>
                 </h2>
                 <p style="color: var(--muted); font-size: 13px;">Lengkapi informasi detail buku di bawah ini</p>
             </div>
          </div>

          <form method="post" action="<?= $action === 'edit' ? '' : 'books.php?action=add' ?>"
            enctype="multipart/form-data">
            
            <div class="form-group-wrapper">
                <div class="form-subheader">
                    <iconify-icon icon="mdi:information-outline"></iconify-icon>
                    Informasi Utama
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group"><label>Judul Buku</label>
                            <input name="title" required value="<?= $book['title'] ?? '' ?>" placeholder="Contoh: Laskar Pelangi">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group"><label>Pengarang</label>
                            <input name="author" required value="<?= $book['author'] ?? '' ?>" placeholder="Nama Penulis">
                        </div>
                    </div>
                </div>
                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-col">
                        <div class="form-group"><label>ISBN / Kode Buku</label>
                            <input name="isbn" value="<?= $book['isbn'] ?? '' ?>" placeholder="Contoh: 978-602-...">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group"><label>Kategori</label>
                            <select name="category" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($book['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?>
                                </option>
                            <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-col" style="max-width: 150px;">
                        <?php if ($action !== 'edit'): ?>
                        <div class="form-group"><label>Jumlah Pcs</label>
                            <input type="number" name="quantity" min="1" value="1" required>
                        </div>
                        <?php else: ?>
                        <div class="form-group"><label>Tambah Stok (+)</label>
                            <input type="number" name="quantity_add" min="0" value="0" placeholder="0">
                        </div>
                        <p style="font-size: 10px; color: var(--muted); margin-top: -12px;">Isi > 0 untuk injek copy baru</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col wide">
                    <div class="form-group-wrapper">
                        <div class="form-subheader">
                            <iconify-icon icon="mdi:map-marker-outline"></iconify-icon>
                            Lokasi & Pengaturan
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group"><label>Target Peminjam</label>
                                    <select name="access_level">
                                        <option value="all" <?= ($book['access_level'] ?? '') === 'all' ? 'selected' : '' ?>>Semua (Umum)</option>
                                        <option value="teacher_only" <?= ($book['access_level'] ?? '') === 'teacher_only' ? 'selected' : '' ?>>Khusus Guru/Staf</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group"><label>Batas Pinjam (Hari)</label>
                                    <input type="number" name="max_borrow_days" placeholder="<?= (int)$defaultDuration ?> hari (Default)" 
                                           value="<?= $action === 'edit' && isset($book['max_borrow_days']) ? (int)$book['max_borrow_days'] : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-row" style="margin-top: 16px;">
                            <div class="form-col" style="flex: 1;">
                                <div class="form-group">
                                    <label>Penempatan Rak (Rak, Baris, Kolom)</label>
                                    <div class="book-location-input">
                                        <input name="shelf" value="<?= $book['shelf'] ?? '' ?>" placeholder="Rak A" style="flex: 1.5;">
                                        <input type="number" min="1" name="row_number" value="<?= $book['row_number'] ?? '' ?>" placeholder="Brs 1" style="flex: 1; max-width: 80px;">
                                        <input type="number" min="1" name="lokasi_rak" value="<?= $book['lokasi_rak'] ?? '' ?>" placeholder="Klm 1" style="flex: 1; max-width: 80px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-col wide">
                    <div class="form-group-wrapper" style="margin-bottom: 0;">
                        <div class="form-subheader">
                            <iconify-icon icon="mdi:image-outline"></iconify-icon>
                            Sampul Buku (Opsional)
                        </div>
                        <div class="form-row" style="align-items: center; margin-bottom: 0; gap: 32px;">
                            <div class="form-col" style="flex: 0 0 160px;">
                                <div id="imagePreview" class="image-preview-mini" style="height: 220px; width: 160px; border: 2px dashed var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: var(--bg); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); padding: 8px; box-sizing: border-box;">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../img/covers/<?= htmlspecialchars($book['cover_image']) ?>" alt="Preview" style="max-height: 100%; max-width: 100%; object-fit: contain; border-radius: 4px; box-shadow: 0 8px 15px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div style="text-align: center;">
                                            <iconify-icon icon="mdi:camera-outline" style="font-size: 32px; color: var(--muted); opacity: 0.5;"></iconify-icon>
                                            <div style="font-size: 11px; color: var(--muted); margin-top: 4px; font-weight: 600;">PREVIEW</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-col" style="flex: 1;">
                                <div class="form-group">
                                    <label>Pilih File Gambar</label>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/gif" id="imageInput"
                                        onchange="previewImage(event)">
                                    </div>
                                    <p style="color: var(--muted); font-size: 13px; margin-top: 12px; line-height: 1.5;">
                                        Unggah gambar sampul untuk memudahkan identifikasi buku. 
                                        <br>
                                        <iconify-icon icon="mdi:information-outline" style="vertical-align: middle;"></iconify-icon>
                                        Format: JPG, PNG, GIF. Maks: 5MB. 
                                        <br>
                                        <iconify-icon icon="mdi:aspect-ratio" style="vertical-align: middle;"></iconify-icon>
                                        Rasio ideal: 2:3 (Tegak).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="copies" value="1">

            <div class="form-actions">
                <button class="btn" type="submit"><?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Buku Baru' ?></button>
                <?php if($action === 'edit'): ?>
                    <a href="books.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- BOOK LIST -->
        <div class="card" style="padding-top: 0;">
          <div class="card-header-flex" style="border-bottom: 3px solid var(--accent-soft); margin-bottom: 24px; padding: 24px 0 16px 0;">
             <div style="flex: 1;">
                 <h2 style="border: none; padding: 0; margin-bottom: 4px;">
                    <iconify-icon icon="mdi:bookshelf" style="color: var(--accent);"></iconify-icon>
                    Daftar Koleksi Buku (<?= count($books) ?>)
                 </h2>
                 <p style="color: var(--muted); font-size: 13px;">Kelola dan cari koleksi buku perpustakaan anda secara real-time</p>
             </div>
             <div class="search-wrapper">
                 <input type="text" id="searchBooksList" class="search-input" placeholder="Cari judul, penulis, atau ISBN...">
                 <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                 <div class="search-kbd">
                     <span style="font-size: 8px;">Ctrl</span>
                     <span>K</span>
                 </div>
                 <button class="search-clear" id="clearBooksSearch"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
             </div>
          </div>
          
          <div class="books-grid">
            <?php foreach ($books as $idx => $b): ?>
              <div class="book-card-vertical">
                <div class="book-cover-container">
                  <?php if (!empty($b['cover_image']) && file_exists(__DIR__ . '/../img/covers/' . $b['cover_image'])): ?>
                    <img src="../img/covers/<?= htmlspecialchars($b['cover_image']) ?>"
                      alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="no-image-placeholder">
                        <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px; color: var(--accent); opacity: 0.5;"></iconify-icon>
                    </div>
                  <?php endif; ?>
                  
                    <?php 
                    $badgeText = 'Tersedia ' . $b['available_copies'] . '/' . $b['total_copies'];
                    $badgeColor = '#059669';
                    $badgeBg = 'rgba(16, 185, 129, 0.15)';
                    $badgeBorder = 'rgba(16, 185, 129, 0.3)';
                    $badgeIcon = 'mdi:check-circle';
                    $badgeTooltip = 'Buku Tersedia';

                    if ($b['available_copies'] == 0) {
                        $badgeText = 'Tersedia 0/' . $b['total_copies'];
                        $badgeColor = '#dc2626';
                        $badgeBg = 'rgba(239, 68, 68, 0.15)';
                        $badgeBorder = 'rgba(239, 68, 68, 0.3)';
                        $badgeIcon = 'mdi:clock-alert';
                        $badgeTooltip = 'Sedang dipinjam / tidak ada stok';
                    }
                  ?>
                  <div class="stock-badge-overlay" style="
                      background: <?= $badgeBg ?>;
                      color: <?= $badgeColor ?>;
                      border: 1px solid <?= $badgeBorder ?>;
                  " title="<?= $badgeTooltip ?>">
                      <iconify-icon icon="<?= $badgeIcon ?>" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                      <?= $badgeText ?>
                  </div>
                </div>
                
                <div class="book-card-body">
                  <div class="book-category"><?= htmlspecialchars($b['category'] ?? 'Umum') ?></div>
                  <div class="book-title" title="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?></div>
                  <div class="book-author"><?= htmlspecialchars($b['author']) ?></div>
                  
                  <div class="book-card-footer">
                      <div class="shelf-info">
                          <iconify-icon icon="mdi:bookshelf" style="color: var(--accent);"></iconify-icon>
                          <span title="<?= !empty($b['lokasi_rak']) ? 'Detail: ' . htmlspecialchars($b['lokasi_rak']) : '' ?>">
                            Rak <?= htmlspecialchars($b['shelf'] ?? '-') ?> / <?= htmlspecialchars($b['row_number'] ?? '-') ?> / <?= htmlspecialchars($b['lokasi_rak'] ?? '-') ?>
                          </span>
                      </div>
                      
                      <div class="action-buttons">
                        <button class="btn-icon-sm" onclick="openDetailModal(<?= $idx ?>)" title="Lihat Detail">
                           <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                        </button>
                        <a href="books.php?action=edit&id=<?= $b['id'] ?>" class="btn-icon-sm" title="Ubah Data">
                           <iconify-icon icon="mdi:pencil-outline"></iconify-icon>
                        </a>
                        <a href="books.php?action=delete&id=<?= $b['id'] ?>" class="btn-icon-sm btn-icon-danger" 
                           onclick="return confirm('Hapus SEMUA copy dari buku ini dari database? (Berjaga-jaga: menghapus ini akan membuang semua <?= $b['total_copies'] ?> salinan. Gunakan Lihat Detail untuk menghapus satu copy saja.)')" title="Hapus Semua Salinan">
                           <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                        </a>
                      </div>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        </div>

        <!-- BOTTOM INFo -->
        <div class="bottom-grid">
            <div class="card">
                <h2>Pertanyaan Umum</h2>
                <div class="faq-container">
                    <div class="faq-item" onclick="toggleFaq(this)">
                        <div class="faq-question">Bagaimana cara menambah buku? <iconify-icon icon="mdi:chevron-down"></iconify-icon></div>
                        <div class="faq-answer">Isi formulir "Tambah Buku" di bagian atas halaman dengan lengkap, lalu klik tombol simpan.</div>
                    </div>
                    <div class="faq-item" onclick="toggleFaq(this)">
                        <div class="faq-question">Bagaimana edit stok? <iconify-icon icon="mdi:chevron-down"></iconify-icon></div>
                        <div class="faq-answer">Cari buku di daftar, klik tombol pensil (edit), lalu ubah jumlah stok dan simpan.</div>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="card">
                <h2>Statistik Perpustakaan</h2>
                <div class="stats-grid-modern">
                    
                    <div class="stat-card-modern" onclick="showStatDetail('books')">
                        <div class="stat-icon blue">
                            <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count($books) ?></div>
                            <div class="stat-label">Total Judul Buku</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>

                    <div class="stat-card-modern" onclick="showStatDetail('categories')">
                        <div class="stat-icon teal">
                            <iconify-icon icon="mdi:shape"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count(array_unique(array_column($books, 'category'))) ?></div>
                            <div class="stat-label">Kategori Buku</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>

                </div>
            </div>
        </div>

      </div>

    </div>
  </div>

  <div id="statModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="statModalTitle">Statistik Detail</h2>
        <button class="modal-close" onclick="closeStatModal()">&times;</button>
      </div>
      <div class="modal-body" id="statModalBody">
      </div>
    </div>
  </div>

  <div id="detailModal" class="modal">
    <div class="modal-content" style="max-width: 900px; width: 95%;">
      <div class="modal-header">
        <h2>Detail Buku & Stok Fisik</h2>
        <button class="modal-close" onclick="closeDetail()">&times;</button>
      </div>
      <div class="modal-body">
        
        <div style="display: flex; gap: 32px; flex-wrap: wrap; align-items: stretch;">
          
          <!-- LEFT PANEL: Info -->
          <div style="flex: 1; min-width: 400px;">
            <div class="detail-layout">
              <div class="detail-image">
                <img id="detailCover" src="" alt="Book Cover">
              </div>
              <div class="detail-info">
                <div class="detail-field">
                  <label>Judul</label>
                  <div id="detailTitle"></div>
                </div>
                <div class="detail-field">
                  <label>Pengarang</label>
                  <div id="detailAuthor"></div>
                </div>
                <div class="detail-field">
                  <label>ISBN</label>
                  <div id="detailISBN"></div>
                </div>
                <div class="detail-field">
                  <label>Kategori</label>
                  <div id="detailCategory"></div>
                </div>
                <div class="detail-field">
                  <label>Lokasi</label>
                  <div id="detailLocation"></div>
                </div>
                <div class="detail-field">
                  <label>Batas Pinjam</label>
                  <div id="detailMaxBorrow"></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- RIGHT PANEL: Barcodes -->
          <div style="flex: 0 0 240px; background: rgba(0,0,0,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 20px; display: flex; flex-direction: column;">
            <label style="font-size: 16px; font-weight: 600; color: var(--text); border-bottom: 2px solid var(--accent-light); padding-bottom: 12px; margin-bottom: 16px; display: block; flex-shrink: 0; display:flex; align-items:center; gap:8px;">
              <iconify-icon icon="mdi:barcode-scan" style="color: var(--accent); font-size: 20px;"></iconify-icon>Daftar Copy (Item Fisik)
            </label>
            <div id="detailCopiesList" style="flex: 1; max-height: 400px; overflow-y: auto; padding-right: 8px;">
               <!-- Injected via JavaScript -->
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>

  <script>
    window.booksData = <?= json_encode(array_values($books)) ?>;
  </script>
  <script src="../assets/js/books-manage.js"></script>
</body>

</html>