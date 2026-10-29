<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: index.php', true, 302);
    exit;
}

require_once __DIR__ . '/../src/auth.php';

try {
    $pdo = require __DIR__ . '/../src/db.php';
} catch (Exception $e) {
    http_response_code(500);
    die('Database connection failed');
}

require_once __DIR__ . '/../src/MemberHelper.php';
require_once __DIR__ . '/../src/FineHelper.php';

$userId = (int) $_SESSION['user']['id'];
$schoolId = (int) $_SESSION['user']['school_id'];

// Get school info
try {
    $schoolStmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
    $schoolStmt->execute(['sid' => $schoolId]);
    $school = $schoolStmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching school: " . $e->getMessage());
    $school = null;
}

$success_message = '';
$error_message = '';
$isEditing = false;

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']) && isset($_POST['upload_photo'])) {
    try {
        $file = $_FILES['foto'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed: ' . $file['error']);
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar (max 5MB)');
        }

        // Check MIME type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Format file harus JPG, PNG, atau WEBP');
        }

        // Create upload directory
        $upload_dir = __DIR__ . '/uploads/anggota';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'anggota_' . $userId . '_' . time() . '_' . uniqid() . '.' . strtolower($ext);
        $filepath = $upload_dir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Gagal menyimpan file');
        }

        $photo_path = 'uploads/anggota/' . $filename;
        $update = $pdo->prepare("UPDATE siswa SET foto = ?, updated_at = NOW() WHERE id_siswa = ?");
        $update->execute([$photo_path, $userId]);

        $success_message = 'Foto berhasil diubah!';

        // Refresh anggota data to show new photo
        $stmt = $pdo->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$userId]);
        $siswa['foto'] = $stmt->fetch(PDO::FETCH_ASSOC)['foto'];
    } catch (Exception $e) {
        $error_message = '❌ Error upload: ' . htmlspecialchars($e->getMessage());
        error_log('Photo upload error: ' . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    try {
        // Validate and sanitize input
        $kelas = trim($_POST['kelas'] ?? '');
        $jurusan = trim($_POST['jurusan'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');

        // Update custom fields in anggota table
        $update = $pdo->prepare("
            UPDATE siswa 
            SET 
                kelas = ?,
                jurusan = ?,
                tanggal_lahir = ?,
                jenis_kelamin = ?,
                alamat = ?,
                no_hp = ?,
                updated_at = NOW()
            WHERE id_siswa = ?
        ");
        $update->execute([
            $kelas ?: null,
            $jurusan ?: null,
            $tanggal_lahir ?: null,
            $jenis_kelamin ?: null,
            $alamat ?: null,
            $no_hp ?: null,
            $userId
        ]);

        $success_message = 'Profil berhasil diperbarui!';
    } catch (Exception $e) {
        $error_message = '❌ Error: ' . htmlspecialchars($e->getMessage());
        error_log('Profile update error: ' . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT id, school_id, name, nisn, email, role, is_verified, created_at
        FROM users 
        WHERE id = ? AND school_id = ?
    ");
    $stmt->execute([$userId, $schoolId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("Anggota tidak ditemukan. Hubungi administrator.");
    }
} catch (Exception $e) {
    error_log('Error fetching user: ' . $e->getMessage());
    die("Terjadi kesalahan saat memuat data pengguna.");
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id_siswa, nama_lengkap, nisn, kelas, jurusan,
            tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto,
            created_at, updated_at
        FROM siswa
        WHERE id_siswa = ?
    ");
    $stmt->execute([$userId]);
    $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        try {
            $insert = $pdo->prepare("
                INSERT INTO siswa 
                (id_siswa, nama_lengkap, nisn, email, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $insert->execute([
                $userId,
                $userData['name'],
                $userData['nisn'],
                $userData['email']
            ]);

            $stmt = $pdo->prepare("
                SELECT 
                    id_siswa, nama_lengkap, nisn, kelas, jurusan,
                    tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto,
                    created_at, updated_at
                FROM siswa
                WHERE id_siswa = ?
            ");
            $stmt->execute([$userId]);
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error creating anggota record: ' . $e->getMessage());
            $siswa = [
                'id_siswa' => $userData['id'],
                'nama_lengkap' => $userData['name'],
                'nisn' => $userData['nisn'],
                'email' => $userData['email'],
                'kelas' => null,
                'jurusan' => null,
                'tanggal_lahir' => null,
                'jenis_kelamin' => null,
                'alamat' => null,
                'no_hp' => null,
                'foto' => null,
                'created_at' => $userData['created_at'],
                'updated_at' => null
            ];
        }
    }
} catch (Exception $e) {
    error_log('Error fetching anggota data: ' . $e->getMessage());
    die("Terjadi kesalahan saat memuat profil.");
}

$tanggalLahir = !empty($siswa['tanggal_lahir']) ? date('d M Y', strtotime($siswa['tanggal_lahir'])) : '-';
$createdAt = !empty($siswa['created_at']) ? date('d M Y, H:i', strtotime($siswa['created_at'])) : '-';
$updatedAt = !empty($siswa['updated_at']) ? date('d M Y, H:i', strtotime($siswa['updated_at'])) : '-';

$genderDisplay = match ($siswa['jenis_kelamin'] ?? null) {
    'L', 'M' => 'Laki-laki',
    'P', 'F' => 'Perempuan',
    default => '-'
};

$memberHelper = new MemberHelper($pdo);
$member_id = $memberHelper->getMemberId($userData);

$fineHelper = new FineHelper($pdo);
$pendingMemberDenda = $fineHelper->getTotalUnpaidFine($member_id);

$photoUrl = $siswa['foto'] ? '/perpustakaan-online/public/' . htmlspecialchars($siswa['foto']) : '/perpustakaan-online/assets/img/default-avatar.png';

$pageTitle = 'Profil Saya';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <!-- JsBarcode for client-side barcode generation -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/profil-style.css?v=<?= time() ?>">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <div class="container-main">
        <div class="page-header">
            <div class="topbar-title">
                <iconify-icon icon="mdi:account-circle" width="28" height="28" style="color: var(--accent);"></iconify-icon>
                <h1>Profil Saya</h1>
            </div>
            <p>Kelola informasi pribadi dan lihat kartu perpustakaan Anda</p>
        </div>

        <div class="card">
            <div class="profile-header">
                <?php
                $photoExists = $siswa['foto'] && file_exists(__DIR__ . '/' . $siswa['foto']);
                if ($photoExists):
                    ?>
                    <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto" class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo-placeholder">
                        <?php echo strtoupper(substr($siswa['nama_lengkap'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h2>
                    <p><?php echo htmlspecialchars($siswa['nisn'] ?? 'NISN: -'); ?> -
                        <?php echo htmlspecialchars($siswa['status'] ?? 'active'); ?>
                    </p>
                    <button type="button" onclick="showLibraryCard()" class="btn-view-card" style="margin-top: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                        <iconify-icon icon="mdi:card-account" width="16" height="16"></iconify-icon>
                        Lihat Kartu Perpustakaan
                    </button>
                </div>
            </div>

            <!-- Photo Upload Section -->
            <div class="photo-upload-section" id="uploadSection">
                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-area">
                        <div class="upload-icon">
                            <iconify-icon icon="mdi:cloud-upload-outline" width="32" height="32"></iconify-icon>
                        </div>
                        <div class="upload-text">
                            <h4>Ubah Foto Profil</h4>
                            <p>Drag dan drop foto atau klik untuk memilih</p>
                        </div>
                        <div class="file-input-wrapper">
                            <label class="file-input-label" for="fotoInput">
                                <iconify-icon icon="mdi:image-plus" width="18" height="18"></iconify-icon>
                                Pilih Foto
                            </label>
                            <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/webp"
                                required>
                        </div>
                        <p class="file-info">JPG, PNG, atau WEBP • Max 5MB</p>
                    </div>
                    <input type="hidden" name="upload_photo" value="1">
                </form>
            </div>
            <div class="divider"></div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Total Denda Section -->
            <div
                style="animation: fadeInSlideUp 0.4s ease-out; margin-bottom: 24px; padding: 16px; background-color: <?php echo $pendingMemberDenda > 0 ? 'rgba(239, 68, 68, 0.05)' : 'rgba(16, 185, 129, 0.05)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? '#ef4444' : '#10b981'; ?>; display: flex; align-items: center; gap: 16px;">
                <div style="font-size: 24px; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                    <iconify-icon
                        icon="<?php echo $pendingMemberDenda > 0 ? 'mdi:alert-circle' : 'mdi:check-circle'; ?>"
                        width="24" height="24"></iconify-icon>
                </div>
                <div style="flex: 1;">
                    <div
                        style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                        Denda Tertunda</div>
                    <div
                        style="font-size: 24px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                        Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                    <?php if ($pendingMemberDenda > 0): ?>
                        <p style="font-size: 12px; color: var(--text-muted); margin: 4px 0 0 0; line-height: 1.5;">Total denda penalti (keterlambatan & kerusakan). Hubungi admin untuk detail lebih lanjut.</p>
                    <?php else: ?>
                        <p style="font-size: 12px; color: #10b981; margin: 4px 0 0 0;">✓ Tidak ada denda tertunda</p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" class="profile-form" id="form-profile">
                <div style="margin-bottom: 24px;">
                    <h3
                        style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; margin-bottom: 12px; font-weight: 600;">
                        Informasi dari Registrasi (Tidak dapat diubah)</h3>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nama Lengkap</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['nama_lengkap'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">NISN</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['nisn'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['email'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Nomor Telepon</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['no_hp'] ?? '-'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Editable Fields -->
                <div style="margin-bottom: 24px;">
                    <h3
                        style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; margin-bottom: 12px; font-weight: 600;">
                        Data Pribadi (Dapat diubah)</h3>

                    <div class="info-grid">
                        <div class="form-group">
                            <label class="form-label">Kelas</label>
                            <input type="text" name="kelas" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['kelas'] ?? ''); ?>"
                                placeholder="Contoh: XII RPL">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jurusan</label>
                            <input type="text" name="jurusan" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['jurusan'] ?? ''); ?>"
                                placeholder="Contoh: Rekayasa Perangkat Lunak">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-input">
                                <option value="">-- Pilih --</option>
                                <option value="L" <?php echo ($siswa['jenis_kelamin'] === 'L') ? 'selected' : ''; ?>>
                                    Laki-laki</option>
                                <option value="P" <?php echo ($siswa['jenis_kelamin'] === 'P') ? 'selected' : ''; ?>>
                                    Perempuan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-input" rows="3"
                                placeholder="Jalan, No., Kelurahan, Kecamatan, Kota"><?php echo htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor HP</label>
                            <input type="tel" name="no_hp" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['no_hp'] ?? ''); ?>"
                                placeholder="Contoh: 081234567890">
                        </div>
                    </div>
                </div>

                <input type="hidden" name="save_profile" value="1">
            </form>

            <div class="meta-section">
                <div class="meta-item">
                    <strong>Terdaftar:</strong>
                    <span><?php echo htmlspecialchars($createdAt); ?></span>
                </div>
                <div class="meta-item">
                    <strong>Diperbarui:</strong>
                    <span><?php echo htmlspecialchars($updatedAt); ?></span>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" form="form-profile" class="btn primary">Simpan Perubahan</button>
                <a href="student-dashboard.php" class="btn secondary">Kembali</a>
            </div>
        </div>
    </div>

    <!-- Library Card Modal -->
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
                          <div class="id-card-photo">
                              <?php if ($photoExists): ?>
                                  <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto">
                              <?php else: ?>
                                  <iconify-icon icon="mdi:account" style="font-size: 48px; opacity: 0.8;"></iconify-icon>
                              <?php endif; ?>
                          </div>
                          
                          <div class="id-card-details" style="text-align: left !important; align-items: flex-start !important;">
                              <p class="id-card-label" style="text-align: left !important;">Nama Anggota</p>
                              <h3 id="modal-name" style="text-align: left !important; margin: 0 0 6px 0; width: 100%;"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h3>
                              <p class="id-card-nisn" style="text-align: left !important; margin: 0; width: 100%;">NISN: <?php echo htmlspecialchars($siswa['nisn']); ?></p>
                          </div>
                     </div>

                     <div class="id-card-barcode-area">
                         <svg id="card-barcode" style="width: 100%; height: 60px;"></svg>
                     </div>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; gap: 12px; margin-top: 24px;">
                <button onclick="window.print()" class="btn primary" style="flex: 1;">
                    <iconify-icon icon="mdi:printer" style="font-size: 18px;"></iconify-icon>
                    Cetak Kartu
                </button>
                <button onclick="closeLibraryCardModal()" class="btn secondary" style="flex: 1;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        window.appConfig = {
            userNisn: "<?php echo $siswa['nisn']; ?>"
        };
    </script>
    <script src="../assets/js/profil-manage.js"></script>
</body>

</html>