<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

$action = $_GET['action'] ?? 'list';

$schoolStmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$schoolStmt->execute(['sid' => $sid]);
$school = $schoolStmt->fetch();

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Insert into members table
    $stmt = $pdo->prepare(
      'INSERT INTO members (school_id,name,email,nisn,role,max_pinjam)
       VALUES (:sid,:name,:email,:nisn,:role,:max_pinjam)'
    );
    $stmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn'],
      'role' => $_POST['role'] ?? 'student',
      'max_pinjam' => (int) ($_POST['max_pinjam'] ?? $school['max_books_' . ($_POST['role'] ?? 'student')] ?? 3)
    ]);

    // Get the inserted NISN for password generation
    $nisn = $_POST['nisn'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Create account in users table
    $userStmt = $pdo->prepare(
      'INSERT INTO users (school_id, name, email, password, role, nisn)
       VALUES (:sid, :name, :email, :password, :role, :nisn)'
    );
    $userStmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => $hashed_password,
      'role' => $_POST['role'] ?? 'student',
      'nisn' => $nisn
    ]);

    // Success message
    $_SESSION['success'] = 'Anggota berhasil ditambahkan. Akun otomatis terbuat dengan ' . ($_POST['role'] === 'student' ? 'NISN' : 'ID') . ': ' . $nisn;
    header('Location: members.php');
    exit;
  } catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, '1062') !== false && strpos($msg, 'nisn') !== false) {
        $_SESSION['error'] = 'Gagal menambahkan: Nomor NISN/ID ini sudah digunakan oleh anggota lain.';
    } else {
        $_SESSION['error'] = 'Gagal menambahkan anggota: ' . $msg;
    }
    header('Location: members.php');
    exit;
  }
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldMemberStmt = $pdo->prepare('SELECT nisn FROM members WHERE id=:id AND school_id=:sid');
    $oldMemberStmt->execute(['id' => $id, 'sid' => $sid]);
    $oldMember = $oldMemberStmt->fetch();
    $oldNisn = $oldMember['nisn'] ?? $_POST['nisn'];

    $stmt = $pdo->prepare(
      'UPDATE members SET name=:name,email=:email,nisn=:nisn,role=:role,max_pinjam=:max_pinjam
       WHERE id=:id AND school_id=:sid'
    );
    $stmt->execute([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn'],
      'role' => $_POST['role'] ?? 'student',
      'max_pinjam' => (int) ($_POST['max_pinjam'] ?? 2),
      'id' => $id,
      'sid' => $sid
    ]);

    $getUserStmt = $pdo->prepare('SELECT id FROM users WHERE school_id = :sid AND nisn = :nisn AND (role = "student" OR role = "teacher" OR role = "employee")');
    $getUserStmt->execute(['sid' => $sid, 'nisn' => $oldNisn]);
    $user = $getUserStmt->fetch();

    if ($user) {
        $userId = $user['id'];
        
        $updateUserSql = 'UPDATE users SET name=:name, email=:email, nisn=:new_nisn';
        $updateUserParams = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'new_nisn' => $_POST['nisn'],
            'id' => $userId
        ];

        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $updateUserSql .= ', password=:password';
            $updateUserParams['password'] = $hashed_password;
        }

        $updateUserSql .= ' WHERE id=:id';
        $updateUserStmt = $pdo->prepare($updateUserSql);
        $updateUserStmt->execute($updateUserParams);

        $checkSiswa = $pdo->prepare('SELECT id_siswa FROM siswa WHERE id_siswa = :id');
        $checkSiswa->execute(['id' => $userId]);
        
        if ($checkSiswa->fetch()) {
            $updateSiswaStmt = $pdo->prepare('UPDATE siswa SET nama_lengkap = :name, email = :email, nisn = :nisn WHERE id_siswa = :id');
            $updateSiswaStmt->execute([
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'nisn' => $_POST['nisn'],
                'id' => $userId
            ]);
        } else {
            $insertSiswa = $pdo->prepare('INSERT INTO siswa (id_siswa, nama_lengkap, email, nisn) VALUES (:id, :name, :email, :nisn)');
            $insertSiswa->execute([
                'id' => $userId,
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'nisn' => $_POST['nisn']
            ]);
        }
    }

    header('Location: members.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM members WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $member = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  try {
    $getMemberStmt = $pdo->prepare('SELECT email, nisn FROM members WHERE id=:id AND school_id=:sid');
    $getMemberStmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    $member = $getMemberStmt->fetch();

    if ($member) {
      $deleteUserStmt = $pdo->prepare('DELETE FROM users WHERE school_id=:sid AND nisn=:nisn');
      $deleteUserStmt->execute(['sid' => $sid, 'nisn' => $member['nisn']]);

      $stmt = $pdo->prepare('DELETE FROM members WHERE id=:id AND school_id=:sid');
      $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    }

    $_SESSION['success'] = 'Anggota dan akun berhasil dihapus';
    header('Location: members.php');
    exit;
  } catch (Exception $e) {
    $_SESSION['error'] = 'Gagal menghapus anggota: ' . $e->getMessage();
    header('Location: members.php');
    exit;
  }
}


$stmt = $pdo->prepare('
    SELECT m.*, s.foto,
    (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND returned_at IS NULL) as active_borrows
    FROM members m
    LEFT JOIN users u ON u.nisn = m.nisn AND u.school_id = m.school_id AND (u.role = "student" OR u.role = "teacher" OR u.role = "employee")
    LEFT JOIN siswa s ON s.id_siswa = u.id
    WHERE m.school_id = :sid 
    ORDER BY m.id DESC
');
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Anggota</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/index.css">
  <link rel="stylesheet" href="../assets/css/members.css">
  <link rel="stylesheet" href="../assets/css/members-style.css?v=<?= time() ?>">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Anggota</strong>
    </div>

    <div class="content">
      <div class="main">

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>

        <div class="card">
          <h2><?= $action === 'edit' ? 'Edit Anggota' : 'Tambah Anggota' ?></h2>
          <?php if ($action === 'add'): ?>
            <div
              style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 12px; color: #0c4a6e;">
              <strong>ℹ️ Info:</strong> Ketika anggota ditambahkan, akun akan otomatis terbuat. <strong>Anggota login
                dengan NISN/ID sebagai username and password yang Anda buat</strong>.
            </div>
          <?php endif; ?>
          <form method="post" action="<?= $action === 'edit' ? '' : 'members.php?action=add' ?>" autocomplete="off"
            id="member-form">
            <div class="form-group">
              <label>Role Anggota</label>
              <select name="role" id="role-select" required onchange="updateMemberLabels()">
                <option value="student" <?= ($action === 'edit' && isset($member['role']) && $member['role'] === 'student') ? 'selected' : '' ?>>Anggota</option>
                <option value="teacher" <?= ($action === 'edit' && isset($member['role']) && $member['role'] === 'teacher') ? 'selected' : '' ?>>Guru</option>
                <option value="employee" <?= ($action === 'edit' && isset($member['role']) && $member['role'] === 'employee') ? 'selected' : '' ?>>Karyawan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="name" required autocomplete="off"
                value="<?= $action === 'edit' && isset($member['name']) ? htmlspecialchars($member['name']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required autocomplete="off"
                value="<?= $action === 'edit' && isset($member['email']) ? htmlspecialchars($member['email']) : '' ?>">
            </div>
            <div class="form-group">
              <label id="id-label">NISN Anggota</label>
              <input type="text" name="nisn" id="id-input" required placeholder="Nomor Induk Siswa Nasional" autocomplete="off"
                value="<?= $action === 'edit' && isset($member['nisn']) ? htmlspecialchars($member['nisn']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Batas Pinjam Buku (Maksimal)</label>
              <input type="number" name="max_pinjam" min="1" required 
                placeholder="Default: <?= (int)($school['max_books_student'] ?? 3) ?>" autocomplete="off"
                value="<?= $action === 'edit' && isset($member['max_pinjam']) 
                  ? (int)$member['max_pinjam'] 
                  : (int)($school['max_books_' . ($member['role'] ?? 'student')] ?? 3) ?>">
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" autocomplete="new-password" <?= $action === 'edit' ? '' : 'required' ?>
                placeholder="<?= $action === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : 'Buat password untuk anggota' ?>"
                value="">
            </div>
            <button class="btn" type="submit">
              <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
            </button>
          </form>
        </div>

        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <h2 style="margin: 0;">Daftar Anggota (<?= count($members) ?>)</h2>
            <div style="display: flex; gap: 12px; align-items: center;">
              <div class="search-box" style="position: relative;">
                <iconify-icon icon="mdi:magnify" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b;"></iconify-icon>
                <input type="text" id="memberSearch" placeholder="Cari nama, NISN..." style="padding: 10px 12px 10px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; width: 250px;">
              </div>
              <button class="btn btn-sm btn-secondary" onclick="printAllCards()">
                <iconify-icon icon="mdi:card-multiple-outline" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                Cetak Semua Kartu
              </button>
            </div>
          </div>
          
          <div class="table-wrap" style="max-height: 600px; overflow-y: auto; overflow-x: auto; position: relative; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
              <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 10;">
                <tr style="text-align: left;">
                  <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; width: 300px; min-width: 250px;">Anggota</th>
                  <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; width: 120px; white-space: nowrap;">Role</th>
                  <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; width: 120px; white-space: nowrap;">Pinjaman</th>
                  <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; width: 150px; white-space: nowrap;">Status Akun</th>
                  <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; text-align: right; width: 140px; white-space: nowrap;">Aksi</th>
                </tr>
              </thead>
              <tbody id="membersTableBody">
                <?php foreach ($members as $m):
                  $checkUserStmt = $pdo->prepare('SELECT id FROM users WHERE nisn = :nisn AND (role = "student" OR role = "teacher" OR role = "employee")');
                  $checkUserStmt->execute(['nisn' => $m['nisn']]);
                  $userExists = $checkUserStmt->fetch() ? true : false;
                  
                  $photoSrc = !empty($m['foto']) ? htmlspecialchars($m['foto']) : null;
                  if ($photoSrc && strpos($photoSrc, 'http') !== 0) {
                      $photoSrc = str_replace(['./', '../'], '', $photoSrc);
                      if (file_exists(__DIR__ . '/' . $photoSrc)) {
                          $photoSrc = $photoSrc; 
                      } else {
                           if (file_exists(__DIR__ . '/uploads/siswa/' . basename($photoSrc))) {
                               $photoSrc = 'uploads/siswa/' . basename($photoSrc);
                           } else {
                               $photoSrc = null;
                           }
                      }
                  }
                  $initial = strtoupper(substr($m['name'], 0, 1));
                  $colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#d946ef', '#f43f5e'];
                  $bg = $colors[strlen($m['name']) % count($colors)];
                  $modalData = $m;
                  $modalData['foto'] = $photoSrc;
                  ?>
                  <tr class="member-row" style="transition: background 0.2s; border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 16px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: <?= $bg ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px;">
                                <?php if($photoSrc): ?>
                                    <img src="<?= $photoSrc ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?= $initial ?>
                                <?php endif; ?>
                            </div>
                            <div style="overflow: hidden;">
                                <div style="font-weight: 600; color: #0f172a; font-size: 14px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;" class="member-name" title="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></div>
                                <div style="font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;">
                                    <span style="font-family: monospace; background: #f1f5f9; padding: 2px 4px; border-radius: 4px; color: #475569;"><?= htmlspecialchars($m['nisn']) ?></span>
                                    • <span class="member-email" title="<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 16px; border-bottom: 1px solid #f1f5f9;">
                      <span style="display: inline-block; background: rgba(59, 130, 246, 0.1); color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize;">
                        <?= htmlspecialchars($m['role'] ?? 'student') ?>
                      </span>
                    </td>
                    <td style="padding: 16px; border-bottom: 1px solid #f1f5f9;">
                        <?php 
                            $active = $m['active_borrows'] ?? 0;
                            $max = $m['max_pinjam'] ?? 3;
                            $ratio = $active / $max;
                            $color = $ratio >= 1 ? '#ef4444' : ($ratio > 0.6 ? '#f59e0b' : '#10b981');
                        ?>
                        <div style="font-size: 13px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 6px;">
                            <span style="color: <?= $color ?>"><?= $active ?></span> / <?= $max ?>
                            <?php if($active > 0): ?>
                                <span style="font-size: 10px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #64748b;">Aktif</span>
                            <?php endif; ?>
                        </div>
                        <div style="width: 60px; height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 4px; overflow: hidden;">
                            <div style="width: <?= min(100, ($active/$max)*100) ?>%; height: 100%; background: <?= $color ?>;"></div>
                        </div>
                    </td>
                    <td style="padding: 16px; border-bottom: 1px solid #f1f5f9;">
                      <?php if ($userExists): ?>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #059669; font-size: 13px; font-weight: 500;">
                            <iconify-icon icon="mdi:check-circle" style="color: #10b981;"></iconify-icon> Terdaftar
                        </span>
                      <?php else: ?>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #94a3b8; font-size: 13px;">
                            <iconify-icon icon="mdi:account-alert-outline"></iconify-icon> Belum Ada Akun
                        </span>
                      <?php endif; ?>
                    </td>
                    <td style="padding: 16px; border-bottom: 1px solid #f1f5f9; text-align: right;">
                      <div style="display: flex; gap: 8px; justify-content: flex-end;">
                          <button onclick="showLibraryCard(<?= htmlspecialchars(json_encode($modalData)) ?>)" 
                                  title="Lihat Kartu"
                                  style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #3b82f6; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                            <iconify-icon icon="mdi:card-account-details-outline" style="font-size: 18px;"></iconify-icon>
                          </button>
                        <a href="members.php?action=edit&id=<?= $m['id'] ?>" title="Edit Anggota"
                          style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #f97316; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                          <iconify-icon icon="mdi:pencil" style="font-size: 18px;"></iconify-icon>
                        </a>
                        <a href="members.php?action=delete&id=<?= $m['id'] ?>" title="Hapus Anggota"
                          onclick="return confirm('Hapus anggota ini? Akun juga akan dihapus.')"
                          style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fee2e2; background: #fff1f2; color: #ef4444; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none;">
                          <iconify-icon icon="mdi:trash-can" style="font-size: 18px;"></iconify-icon>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php
          $totalMembers = count($members);
          $students = array_values(array_filter($members, fn($m) => ($m['role'] ?? 'student') === 'student'));
          $staffMembers = array_values(array_filter($members, fn($m) => in_array($m['role'] ?? '', ['teacher', 'employee'])));
          $activeMembers = array_values(array_filter($members, fn($m) => ($m['active_borrows'] ?? 0) > 0));
          $hasAccountMembers = [];
          foreach ($members as $m) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE nisn = :nisn AND (role = "student" OR role = "teacher" OR role = "employee")');
            $chk->execute(['nisn' => $m['nisn']]);
            if ($chk->fetch()) $hasAccountMembers[] = $m;
          }
          $statCards = [
            [
              'label'   => 'Total Anggota',
              'value'   => $totalMembers,
              'icon'    => 'mdi:account-group',
              'color'   => '#3b82f6',
              'bg'      => 'rgba(59,130,246,0.1)',
              'key'     => 'semua',
            ],
            [
              'label'   => 'Siswa/Pelajar',
              'value'   => count($students),
              'icon'    => 'mdi:account-school',
              'color'   => '#10b981',
              'bg'      => 'rgba(16,185,129,0.1)',
              'key'     => 'siswa',
            ],
            [
              'label'   => 'Guru & Karyawan',
              'value'   => count($staffMembers),
              'icon'    => 'mdi:account-tie',
              'color'   => '#f59e0b',
              'bg'      => 'rgba(245,158,11,0.1)',
              'key'     => 'staf',
            ],
            [
              'label'   => 'Sedang Meminjam',
              'value'   => count($activeMembers),
              'icon'    => 'mdi:book-open-variant',
              'color'   => '#ef4444',
              'bg'      => 'rgba(239,68,68,0.1)',
              'key'     => 'aktif',
            ],
          ];
        ?>
        <div class="card" style="grid-column: 1/-1">
          <h2 style="margin-bottom: 20px;">Statistik Anggota</h2>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <?php foreach ($statCards as $sc): ?>
            <div class="stat-card-clickable" onclick="showMembersStatModal('<?= $sc['key'] ?>')" 
                 style="background: var(--card, #fff); border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px 18px; cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= $sc['bg'] ?>; display: flex; align-items: center; justify-content: center;">
                  <iconify-icon icon="<?= $sc['icon'] ?>" style="font-size: 22px; color: <?= $sc['color'] ?>;"></iconify-icon>
                </div>
                <iconify-icon icon="mdi:chevron-right" style="color: #cbd5e1; font-size: 18px;"></iconify-icon>
              </div>
              <div style="font-size: 32px; font-weight: 800; color: <?= $sc['color'] ?>; line-height: 1; margin-bottom: 6px;"><?= $sc['value'] ?></div>
              <div style="font-size: 13px; color: #64748b; font-weight: 500;"><?= $sc['label'] ?></div>
              <div style="position: absolute; bottom: -20px; right: -10px; width: 80px; height: 80px; border-radius: 50%; background: <?= $sc['bg'] ?>; pointer-events: none;"></div>
            </div>
            <?php endforeach; ?>
          </div>
          <p style="margin-top: 14px; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
            <iconify-icon icon="mdi:information-outline" style="font-size: 14px;"></iconify-icon>
            Klik kartu untuk melihat daftar anggota
          </p>
        </div>

          <?php
          $statsData = [
            'semua' => $members,
            'siswa' => $students,
            'staf'  => $staffMembers,
            'aktif' => $activeMembers,
          ];
          ?>

        <div class="card" style="grid-column: 1/-1">
          <h2>Pertanyaan Umum</h2>
          <div class="faq-item">
            <div class="faq-question">Bagaimana cara menambah anggota baru? <span>+</span></div>
            <div class="faq-answer">Pilih Role (Anggota/Guru/Karyawan), isi nama lengkap, email, dan ID (NISN/NIP/NUPTK), lalu klik "Tambah Anggota". Akun akan otomatis terbuat dengan ID tersebut sebagai username.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa yang dimaksud dengan ID Anggota? <span>+</span></div>
            <div class="faq-answer"><strong>ID Anggota</strong> bisa berupa NISN untuk Anggota, NUPTK untuk Guru, atau NIP untuk Karyawan. ID ini digunakan sebagai username login.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa itu "Status Akun"? <span>+</span></div>
            <div class="faq-answer">Status Akun menunjukkan apakah kredensial login sudah aktif. Secara default, password awal sama dengan ID Anggota.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bagaimana anggota login? <span>+</span></div>
            <div class="faq-answer">Anggota login menggunakan <strong>ID (NISN/NIP/NUPTK) sebagai username</strong> and 
              <strong>Password = ID</strong>. Kami sarankan untuk segera mengubah password setelah login pertama kali.
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bisakah saya mengedit data anggota? <span>+</span></div>
            <div class="faq-answer">Ya, klik "Edit" pada baris anggota yang ingin diubah. Anda bisa mengubah nama, email,
              no anggota, dan NISN. Perubahan NISN juga akan mengubah kredensial login anggota.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa yang terjadi jika saya menghapus anggota? <span>+</span></div>
            <div class="faq-answer">Anggota dan akun akan dihapus dari sistem. Anggota tidak bisa login lagi. Pastikan
              anggota tidak memiliki peminjaman aktif sebelum menghapus.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apakah NISN harus unik? <span>+</span></div>
            <div class="faq-answer">Ya, NISN harus unik karena digunakan sebagai identitas login anggota. Setiap anggota
              hanya memiliki satu NISN yang valid.</div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Stats Modal -->
  <div id="membersStatModal" class="modal-overlay" style="display:none;">
    <div class="stat-modal-card">
      <div class="stat-modal-header">
        <div style="display:flex; align-items:center; gap:12px;">
          <div id="statModalIconWrap" style="width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <iconify-icon id="statModalIcon" icon="mdi:account-group" style="font-size:22px;"></iconify-icon>
          </div>
          <div>
            <h3 id="statModalTitle" style="margin:0; font-size:18px; font-weight:700; color:#0f172a;">-</h3>
            <p id="statModalCount" style="margin:0; font-size:13px; color:#64748b;"></p>
          </div>
        </div>
        <button class="modal-close-btn" onclick="closeMembersStatModal()" style="position:static; flex-shrink:0;">
          <iconify-icon icon="mdi:close"></iconify-icon>
        </button>
      </div>

      <!-- Search in modal -->
      <div style="padding: 12px 24px; border-bottom: 1px solid #f1f5f9;">
        <div style="position: relative;">
          <iconify-icon icon="mdi:magnify" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8;"></iconify-icon>
          <input type="text" id="statModalSearch" placeholder="Cari nama, NISN..." 
                 style="width:100%; padding:9px 12px 9px 36px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; box-sizing:border-box; outline:none;"
                 oninput="filterStatModal(this.value)">
        </div>
      </div>

      <!-- Member List -->
      <div class="stat-modal-body" id="statModalBody">
      </div>

      <!-- Footer -->
      <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; text-align:right;">
        <button onclick="closeMembersStatModal()" class="btn btn-secondary" style="padding: 9px 20px;">Tutup</button>
      </div>
    </div>
  </div>

  <div id="libraryCardModal" class="modal-overlay">
    <div class="modal-card">
      <button class="modal-close-btn" onclick="closeLibraryCardModal()">
        <iconify-icon icon="mdi:close"></iconify-icon>
      </button>
      
      <h3 style="margin-bottom: 20px; font-size: 18px;">Pratinjau Kartu Perpustakaan</h3>

      <div class="library-card-wrapper">
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
                     <p style="font-size: 10px; margin-bottom: 4px; opacity: 0.6; text-transform: uppercase; text-align: left !important;">Nama Anggota</p>
                     <h3 id="modal-name" style="text-align: left !important; margin: 0 0 6px 0; width: 100%;">-</h3>
                     <p id="modal-nisn" style="text-align: left !important; margin: 0; width: 100%;">NISN: -</p>
                 </div>
             </div>

             <div class="id-card-barcode-area">
                 <svg id="card-barcode" style="width: 100%; height: 60px;"></svg>
             </div>
        </div>
      </div>

      <div class="modal-footer" style="display: flex; gap: 12px; margin-top: 24px;">
        <button onclick="printLibraryCard()" class="btn btn-primary" style="flex: 1;">
          <iconify-icon icon="mdi:printer" style="font-size: 18px;"></iconify-icon>
          Cetak Kartu
        </button>
        <button onclick="closeLibraryCardModal()" class="btn btn-secondary" style="flex: 1;">
          Tutup
        </button>
      </div>
    </div>
  </div>

  <script src="../assets/js/members.js"></script>
  <script>
    const allMembersData = <?= json_encode($members) ?>;
    const schoolData = <?= json_encode($school) ?>;
    let currentMemberData = null;
    const membersStatData = <?= json_encode($statsData) ?>;
  </script>
  <script src="../assets/js/members-manage.js"></script>


</body>

</html>