<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo = require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/SchoolProfileModel.php';
require __DIR__ . '/../src/ThemeModel.php';
require_once __DIR__ . '/../src/maintenance/DamageFineModel.php';

// Ensure user is not a student
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
    header('Location: student-dashboard.php');
    exit;
}

$user = $_SESSION['user'];
$sid = $user['school_id'];
$schoolProfileModel = new SchoolProfileModel($pdo);
$themeModel = new ThemeModel($pdo);
$damageFineModel = new DamageFineModel($pdo);

// Fetch school data BEFORE POST processing so we have current values for fallbacks
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();

if (!$school) {
    die('Error: School data not found');
}

$error = null;
$success = null;
$profile_error = null;
$profile_success = null;
$theme_success = null;
$theme_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_identity') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $npsn = trim($_POST['school_npsn'] ?? '') ?: null;
        $email = trim($_POST['school_email'] ?? '') ?: null;

        if ($name === '') {
            $error = 'Nama sekolah wajib diisi.';
        } else {
            // Auto-generate slug from name
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s-]/', '', $name)));
                $slug = preg_replace('/\s+/', '-', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
            }

            // ensure slug unique
            $stmt = $pdo->prepare('SELECT id FROM schools WHERE slug = :slug AND id != :id');
            $stmt->execute(['slug' => $slug, 'id' => $sid]);
            $exists = $stmt->fetchColumn();
            if ($exists) {
                $error = 'Slug sudah digunakan oleh sekolah lain.';
            } else {
                try {
                    $schoolProfileModel->updateSchoolProfile($sid, [
                        'name' => $name,
                        'slug' => $slug,
                        'npsn' => $npsn,
                        'email' => $email
                    ]);
                    $success = 'Data identitas sekolah berhasil diperbarui.';
                } catch (Exception $e) {
                    $error = 'Gagal menyimpan data: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'update_theme') {
        // Update school theme
        try {
            $theme_name = trim($_POST['theme_name'] ?? 'light');
            if (!$theme_name) {
                $theme_name = 'light';
            }
            // Mutlak: Memilih tema dasar otomatis mematikan semua tema event
            $themeModel->deactivateAllSpecialThemes($sid);
            $themeModel->saveSchoolTheme($sid, $theme_name);
            $theme_success = "Tema dasar '{$theme_name}' berhasil diaktifkan. Semua tema event otomatis dinonaktifkan.";
        } catch (Exception $e) {
            $theme_error = 'Gagal menyimpan tema: ' . $e->getMessage();
        }
    } elseif ($action === 'deactivate_all_special_themes') {
        try {
            $themeModel->deactivateAllSpecialThemes($sid);
            $theme_success = 'Semua tema event berhasil dinonaktifkan. Sistem kembali menggunakan tema dasar sekolah.';
        } catch (Exception $e) {
            $theme_error = 'Gagal menonaktifkan tema event: ' . $e->getMessage();
        }
    } elseif ($action === 'update_borrows') {
        try {
            $borrow_duration = max(1, (int)($_POST['borrow_duration'] ?? ($school['borrow_duration'] ?: 7)));
            $late_fine = max(0, (float)($_POST['late_fine'] ?? ($school['late_fine'] ?? 1000)));

            // Tangkap damage_fines jika ada
            $damage_fines = $_POST['damage_fines'] ?? [];
            $damage_fines_config = null;
            if (is_array($damage_fines)) {
                $sanitized = [];
                foreach ($damage_fines as $dtype => $dfine) {
                    $sanitized[$dtype] = max(0, (float)$dfine);
                }
                $damage_fines_config = json_encode($sanitized);
            }

            $updateData = [
                'borrow_duration' => $borrow_duration,
                'late_fine' => $late_fine,
                'max_books_student' => max(1, (int)($_POST['max_books_student'] ?? $school['max_books_student'])),
                'max_books_teacher' => max(1, (int)($_POST['max_books_teacher'] ?? $school['max_books_teacher'])),
                'max_books_employee' => max(1, (int)($_POST['max_books_employee'] ?? $school['max_books_employee']))
            ];

            if ($damage_fines_config !== null) {
                $updateData['damage_fines_config'] = $damage_fines_config;
            }

            $schoolProfileModel->updateSchoolProfile($sid, $updateData);

            // Refresh data $school
            $school = $schoolProfileModel->getSchoolProfile($sid);

            $profile_success = 'Peraturan peminjaman dan tarif denda otomatis berhasil diperbarui.';
        } catch (Exception $e) {
            $profile_error = 'Gagal memperbarui peraturan: ' . $e->getMessage();
        }
    } elseif ($action === 'update_password') {
        // Update user (admin) password
        try {
            $password_old = $_POST['school_password_old'] ?? '';
            $password_new = $_POST['school_password_new'] ?? '';
            $password_confirm = $_POST['school_password_confirm'] ?? '';

            // Validate inputs
            if (empty($password_old) || empty($password_new) || empty($password_confirm)) {
                throw new Exception('Semua field password harus diisi.');
            }

            if ($password_new !== $password_confirm) {
                throw new Exception('Password baru dan konfirmasi password tidak cocok.');
            }

            if (strlen($password_new) < 6) {
                throw new Exception('Password baru minimal 6 karakter.');
            }

            // Verify old password
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
            $stmt->execute(['id' => $user['id']]);
            $user_data = $stmt->fetch();

            if (!$user_data || !password_verify($password_old, $user_data['password'])) {
                throw new Exception('Password lama tidak sesuai.');
            }

            // Update password
            $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->execute(['password' => $hashed_password, 'id' => $user['id']]);

            $profile_success = 'Password berhasil diubah.';
        } catch (Exception $e) {
            $profile_error = 'Gagal mengubah password: ' . $e->getMessage();
        }
    } elseif ($action === 'upload_photo') {
        // Handle photo upload
        try {
            if (!isset($_FILES['school_photo']) || $_FILES['school_photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File tidak ditemukan');
            }

            // Validate
            $schoolProfileModel->validatePhotoFile($_FILES['school_photo']);

            // Delete old photo
            $old_photo = $schoolProfileModel->getSchoolPhoto($sid);
            if ($old_photo) {
                $old_path = __DIR__ . '/' . $old_photo;
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }

            // Save new photo
            $filename = $schoolProfileModel->savePhotoFile($_FILES['school_photo']);
            $photo_path = 'public/uploads/school-photos/' . $filename;
            $schoolProfileModel->updateSchoolPhoto($sid, $photo_path);

            $profile_success = 'Foto profil sekolah berhasil diunggah.';
        } catch (Exception $e) {
            $profile_error = 'Gagal mengunggah foto: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_photo') {
        // Handle photo deletion
        try {
            $schoolProfileModel->deleteSchoolPhoto($sid);
            $profile_success = 'Foto profil sekolah berhasil dihapus.';
        } catch (Exception $e) {
            $profile_error = 'Gagal menghapus foto: ' . $e->getMessage();
        }

    } elseif ($action === 'add_special_theme') {
        try {
            $themeModel->addSpecialTheme([
                'school_id' => $sid,
                'name' => $_POST['theme_name_label'] ?? '',
                'date' => $_POST['theme_date'] ?? '',
                'theme_key' => $_POST['theme_key'] ?? '',
                'description' => $_POST['theme_description'] ?? '',
                'is_active' => 1
            ]);
            $theme_success = 'Tema hari penting berhasil ditambahkan.';
        } catch (Exception $e) {
            $theme_error = 'Gagal menambah tema: ' . $e->getMessage();
        }
    } elseif ($action === 'toggle_special_theme') {
        try {
            $id = (int)$_POST['theme_id'];
            $status = (int)$_POST['status'];
            $themeModel->toggleSpecialTheme($id, $sid, $status);
            if ($status == 1) {
                $theme_success = 'Tema event berhasil diaktifkan. Tema event lain otomatis dinonaktifkan.';
            } else {
                $theme_success = 'Tema event berhasil dinonaktifkan. Sistem kembali menggunakan tema dasar sekolah.';
            }
        } catch (Exception $e) {
            $theme_error = 'Gagal mengubah status tema: ' . $e->getMessage();
        }
    } elseif ($action === 'test_activate_special_theme') {
        try {
            $id = (int)($_POST['theme_id'] ?? 0);
            $themeModel->testActivateSpecialTheme($id, $sid);
            $theme_success = 'Tema event berhasil diaktifkan untuk hari ini. Semua tema lain otomatis dinonaktifkan.';
        } catch (Exception $e) {
            $theme_error = 'Gagal mengaktifkan tema event: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_special_theme') {
        try {
            $id = (int)$_POST['theme_id'];
            $themeModel->deleteSpecialTheme($id, $sid);
            $theme_success = 'Tema hari penting berhasil dihapus.';
        } catch (Exception $e) {
            $theme_error = 'Gagal menghapus tema: ' . $e->getMessage();
        }

    } elseif ($action === 'reset_scan_key') {
        try {
            $schoolProfileModel->resetScanAccessKey($sid);
            $profile_success = 'Scan Access Key berhasil direset. Silakan bagikan link baru ke staff.';
        } catch (Exception $e) {
            $profile_error = 'Gagal mereset key: ' . $e->getMessage();
        }
    } elseif ($action === 'update_scanner_settings') {
        try {
            $custom_url = trim($_POST['custom_base_url'] ?? '');
            $schoolProfileModel->updateSchoolProfile($sid, ['custom_base_url' => $custom_url ?: null]);
            $profile_success = 'Pengaturan scanner berhasil diperbarui.';
        } catch (Exception $e) {
            $profile_error = 'Gagal memperbarui pengaturan: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();

$currentTheme = $themeModel->getSchoolTheme($sid);
$customColors = json_decode($currentTheme['custom_colors'] ?? '{}', true);
$themeDisplayName = $customColors['metadata']['display_name'] ?? 'Kustomisasi Saya';

$specialThemes = $themeModel->getSpecialThemes($sid);
$activeSpecialTheme = $themeModel->checkSpecialTheme($sid);
$eventConfigPath = __DIR__ . '/../theme-config.json';
$eventConfig = file_exists($eventConfigPath) ? json_decode(file_get_contents($eventConfigPath), true) : [];

$schoolDamageTypes = $damageFineModel->getSchoolDamageTypes($sid);
$defaultDamageTypes = DamageFineModel::getDamageTypes();

if (!$school) {
    die('Error: School data not found');
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar">
            <strong>Pengaturan Sekolah</strong>
        </div>

        <div class="content">
            <!-- Navigation -->
            <div class="settings-tabs">
                <div class="tab-link active" data-tab="identity">
                    <iconify-icon icon="mdi:bank-outline"></iconify-icon> Identitas
                </div>
                <div class="tab-link" data-tab="security">
                    <iconify-icon icon="mdi:shield-lock-outline"></iconify-icon> Keamanan
                </div>
                <div class="tab-link" data-tab="borrows">
                    <iconify-icon icon="mdi:book-clock-outline"></iconify-icon> Peminjaman & Denda
                </div>
                <div class="tab-link" data-tab="themes">
                    <iconify-icon icon="mdi:palette-outline"></iconify-icon> Tema
                </div>
                <div class="tab-link" data-tab="scanner">
                    <iconify-icon icon="mdi:barcode-scan"></iconify-icon> Scanner Mobile
                </div>
                <div class="tab-link" data-tab="faq">
                    <iconify-icon icon="mdi:help-circle-outline"></iconify-icon> Bantuan
                </div>
            </div>

            <!-- Identity -->
            <div class="tab-content active" id="identity">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:card-account-details-outline"></iconify-icon> Informasi Sekolah
                        </h2>
                        <p class="card-subtitle">Kelola informasi dasar dan kontak resmi sekolah Anda.</p>
                        
                        <?php if (!empty($success) && $action === 'update_identity'): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div>Tersimpan: <?php echo htmlspecialchars($success); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error) && $action === 'update_identity'): ?>
                            <div class="alert alert-danger">
                                <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_identity">
                            <div class="form-group">
                                <label for="name">Nama Lengkap Sekolah</label>
                                <input id="name" name="name" type="text" required
                                    value="<?php echo htmlspecialchars($school['name'] ?? ''); ?>"
                                    placeholder="Contoh: SMA Negeri 1 Jakarta">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="slug">URL Pendek (Slug)</label>
                                    <input id="slug" name="slug" type="text"
                                        value="<?php echo htmlspecialchars($school['slug'] ?? ''); ?>"
                                        placeholder="Contoh: sman1-jkt">
                                </div>
                                <div class="form-group">
                                    <label for="school_npsn">NPSN</label>
                                    <input id="school_npsn" name="school_npsn" type="text"
                                        value="<?php echo htmlspecialchars($school['npsn'] ?? ''); ?>"
                                        placeholder="Contoh: 20102012">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="school_email">Email Sekolah</label>
                                <input id="school_email" name="school_email" type="email"
                                    value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>"
                                    placeholder="sekolah@example.com">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="mdi:content-save-outline"></iconify-icon>
                                Simpan Identitas
                            </button>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h2 class="card-title" style="font-size: 15px;">Logo Sekolah</h2>
                            <div class="profile-photo-container">
                                <div class="profile-photo-wrapper">
                                    <?php if ($school['photo_path'] && file_exists(__DIR__ . '/../' . $school['photo_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($school['photo_path']); ?>" 
                                             class="profile-photo" alt="Logo Sekolah" id="preview-img">
                                    <?php else: ?>
                                        <div class="school-photo-placeholder" id="preview-placeholder">
                                            <iconify-icon icon="mdi:school-outline"></iconify-icon>
                                        </div>
                                        <img src="" class="profile-photo" alt="Logo Sekolah" id="preview-img" style="display: none;">
                                    <?php endif; ?>
                                    <label for="school_photo" class="photo-overlay">
                                        <iconify-icon icon="mdi:camera-outline"></iconify-icon>
                                    </label>
                                </div>
                                
                                <form method="post" enctype="multipart/form-data" id="photo-form">
                                    <input type="hidden" name="action" value="upload_photo">
                                    <input type="file" name="school_photo" id="school_photo" hidden accept="image/*">
                                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                                        <button type="button" onclick="document.getElementById('school_photo').click()" class="btn" style="padding: 8px 12px; font-size: 12px;">
                                            Pilih
                                        </button>
                                        <button type="submit" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px;">
                                            Unggah
                                        </button>
                                    </div>
                                </form>

                                <?php if ($school['photo_path']): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="delete_photo">
                                        <button type="submit" class="btn btn-danger" style="margin-top: 8px; border: none; font-size: 12px; background: transparent;">
                                            <iconify-icon icon="mdi:trash-can-outline"></iconify-icon> Hapus Foto
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Status Kelengkapan</h3>
                            <div class="checklist-item done">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon> Nama & Slug
                            </div>
                            <div class="checklist-item <?php echo !empty($school['npsn']) ? 'done' : 'todo'; ?>">
                                <iconify-icon icon="<?php echo !empty($school['npsn']) ? 'mdi:check-circle' : 'mdi:circle-outline'; ?>"></iconify-icon> NPSN Terdaftar
                            </div>
                            <div class="checklist-item <?php echo !empty($school['photo_path']) ? 'done' : 'todo'; ?>">
                                <iconify-icon icon="<?php echo !empty($school['photo_path']) ? 'mdi:check-circle' : 'mdi:circle-outline'; ?>"></iconify-icon> Logo Sekolah
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="tab-content" id="security">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:lock-reset"></iconify-icon> Ganti Password Admin
                        </h2>
                        
                        <?php if (!empty($profile_success) && $action === 'update_password'): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_success); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile_error) && $action === 'update_password'): ?>
                            <div class="alert alert-danger">
                                <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-group">
                                <label for="school_password_old">Password Saat Ini</label>
                                <input id="school_password_old" name="school_password_old" type="password" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="school_password_new">Password Baru</label>
                                    <input id="school_password_new" name="school_password_new" type="password" required>
                                </div>
                                <div class="form-group">
                                    <label for="school_password_confirm">Konfirmasi Password Baru</label>
                                    <input id="school_password_confirm" name="school_password_confirm" type="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="mdi:shield-check-outline"></iconify-icon>
                                Update Password
                            </button>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Keamanan Akun</h3>
                            <div class="info-block">
                                <div class="info-icon"><iconify-icon icon="mdi:shield-key"></iconify-icon></div>
                                <div class="info-text">
                                    <h4>Encryption</h4>
                                    <p>Password Anda dienkripsi dengan algoritma standar industri (BCrypt).</p>
                                </div>
                            </div>
                            <div class="info-block">
                                <div class="info-icon"><iconify-icon icon="mdi:history"></iconify-icon></div>
                                <div class="info-text">
                                    <h4>Sesi Aktif</h4>
                                    <p>Pastikan Anda Logout jika mengakses dari komputer publik.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peminjaman -->
            <!-- Peminjaman & Denda -->
            <div class="tab-content" id="borrows">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:book-cog-outline"></iconify-icon> Peraturan Peminjaman & Denda Otomatis
                        </h2>
                        <p class="card-subtitle">Atur durasi pinjam, tarif denda keterlambatan otomatis, tarif denda kerusakan buku, dan kuota peminjaman per anggota di sekolah Anda.</p>

                        <?php if (!empty($profile_success) && $action === 'update_borrows'): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_success); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile_error) && $action === 'update_borrows'): ?>
                            <div class="alert alert-danger">
                                <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_borrows">

                            <!-- Bagian 1: Durasi Peminjaman & Denda Keterlambatan Otomatis -->
                            <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
                                <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                    <iconify-icon icon="mdi:clock-alert-outline" style="color: #f59e0b; font-size: 18px;"></iconify-icon> Durasi Pinjam & Denda Telat Otomatis
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="borrow_duration" style="font-weight: 500; font-size: 13px;">Durasi Peminjaman Standar</label>
                                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
                                            <input type="number" id="borrow_duration" name="borrow_duration" class="form-control" value="<?= htmlspecialchars($school['borrow_duration'] ?: 7) ?>" min="1" max="90" required>
                                            <span style="font-size: 13px; color: var(--muted); white-space: nowrap;">Hari</span>
                                        </div>
                                        <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">Masa pinjam buku sebelum dinyatakan jatuh tempo (due date).</small>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="late_fine" style="font-weight: 500; font-size: 13px;">Tarif Denda Telat per Hari</label>
                                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
                                            <span style="font-size: 13px; font-weight: 600; color: var(--text);">Rp</span>
                                            <input type="number" id="late_fine" name="late_fine" class="form-control" value="<?= htmlspecialchars((int)($school['late_fine'] ?? 1000)) ?>" min="0" step="500" required>
                                            <span style="font-size: 13px; color: var(--muted); white-space: nowrap;">/ Hari / Buku</span>
                                        </div>
                                        <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">Dihitung otomatis saat terlambat (cth: Rp 1.000, Rp 3.000, atau Rp 0 untuk bebas denda).</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Bagian 2: Tarif Denda Kerusakan Buku -->
                            <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                                    <h3 style="font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin: 0;">
                                        <iconify-icon icon="mdi:book-alert-outline" style="color: #ef4444; font-size: 18px;"></iconify-icon> Tarif Denda Kerusakan Buku (Custom Bebas)
                                    </h3>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="resetDamageFinesToDefault()" style="font-size: 11px; padding: 4px 10px;">
                                        <iconify-icon icon="mdi:restore" style="vertical-align: middle; margin-right: 4px;"></iconify-icon> Reset Default Standar
                                    </button>
                                </div>
                                <p style="font-size: 12px; color: var(--muted); margin-bottom: 14px;">
                                    Tarif denda otomatis yang otomatis terisi saat petugas mencatat laporan kerusakan buku di menu Pemeliharaan. Petugas tetap dapat mengubah nominal secara manual saat pencatatan jika diperlukan.
                                </p>

                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                                    <?php 
                                    $typeIcons = [
                                        'minor_tear' => 'mdi:book-open-page-variant-outline',
                                        'major_tear' => 'mdi:book-remove-outline',
                                        'water_damage' => 'mdi:water-alert-outline',
                                        'stain' => 'mdi:brush',
                                        'cover_damage' => 'mdi:book-outline',
                                        'spine_damage' => 'mdi:format-line-spacing',
                                        'missing_pages' => 'mdi:file-document-remove-outline',
                                        'other' => 'mdi:dots-horizontal-circle-outline'
                                    ];
                                    foreach ($schoolDamageTypes as $dtype => $info): 
                                        $defaultFine = DamageFineModel::DAMAGE_TYPES[$dtype]['fine'] ?? 0;
                                        $currentFine = (float)$info['fine'];
                                    ?>
                                        <div style="background: var(--bg-surface, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                                <span style="font-size: 12px; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 6px;">
                                                    <iconify-icon icon="<?= $typeIcons[$dtype] ?? 'mdi:alert-circle-outline' ?>" style="color: #64748b; font-size: 14px;"></iconify-icon>
                                                    <?= htmlspecialchars($info['name']) ?>
                                                </span>
                                                <span style="font-size: 10px; color: var(--muted);" title="Standar bawaan sistem: Rp <?= number_format($defaultFine, 0, ',', '.') ?>">
                                                    Def: Rp <?= number_format($defaultFine, 0, ',', '.') ?>
                                                </span>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span style="font-size: 12px; font-weight: 600; color: var(--muted);">Rp</span>
                                                <input type="number" 
                                                       name="damage_fines[<?= htmlspecialchars($dtype) ?>]" 
                                                       id="damage_fine_<?= htmlspecialchars($dtype) ?>" 
                                                       class="form-control damage-fine-input" 
                                                       data-default="<?= $defaultFine ?>" 
                                                       value="<?= $currentFine ?>" 
                                                       min="0" 
                                                       step="1000" 
                                                       style="padding: 6px 10px; font-size: 13px;" 
                                                       required>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Bagian 3: Batas Jumlah Pinjaman Buku -->
                            <div class="form-group" style="margin-bottom: 24px;">
                                <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                    <iconify-icon icon="mdi:account-group-outline" style="color: #3b82f6; font-size: 18px;"></iconify-icon> Maksimum Buku Dipinjam (Berdasarkan Role)
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-top: 5px;">
                                    <div>
                                        <label style="font-size: 12px; font-weight: 500; color: var(--muted);">Anggota / Siswa</label>
                                        <div style="display: flex; align-items: center; gap: 5px; margin-top: 4px;">
                                            <input type="number" name="max_books_student" class="form-control" value="<?= htmlspecialchars($school['max_books_student'] ?? 3) ?>" min="1" max="50">
                                            <span style="font-size: 12px;">Buku</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="font-size: 12px; font-weight: 500; color: var(--muted);">Guru</label>
                                        <div style="display: flex; align-items: center; gap: 5px; margin-top: 4px;">
                                            <input type="number" name="max_books_teacher" class="form-control" value="<?= htmlspecialchars($school['max_books_teacher'] ?? 10) ?>" min="1" max="50">
                                            <span style="font-size: 12px;">Buku</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="font-size: 12px; font-weight: 500; color: var(--muted);">Karyawan</label>
                                        <div style="display: flex; align-items: center; gap: 5px; margin-top: 4px;">
                                            <input type="number" name="max_books_employee" class="form-control" value="<?= htmlspecialchars($school['max_books_employee'] ?? 5) ?>" min="1" max="50">
                                            <span style="font-size: 12px;">Buku</span>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted" style="display: block; margin-top: 6px;">Tentukan jatah pinjam buku maksimal per role. Ini akan menjadi nilai bawaan saat menambah anggota baru.</small>
                            </div>

                            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 600;">
                                <iconify-icon icon="mdi:content-save-check" style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Simpan Peraturan & Denda
                            </button>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <div class="info-block">
                                <div class="info-icon warning">
                                    <iconify-icon icon="mdi:information-outline"></iconify-icon>
                                </div>
                                <div class="info-text">
                                    <h4 style="font-size: 14px;">Fleksibilitas Denda</h4>
                                    <p style="font-size: 13px;">Anda dapat mengatur tarif denda keterlambatan per hari maupun denda tiap kategori kerusakan buku sesuai kebijakan resmi sekolah Anda.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <h3 style="font-size: 14px; margin-bottom: 12px;">Ringkasan Denda Sekolah</h3>
                            <div class="checklist-item done" style="margin-bottom: 8px;">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon>
                                <span style="font-size: 13px;">Denda Telat: <strong>Rp <?= number_format((int)($school['late_fine'] ?? 1000), 0, ',', '.') ?></strong> / hari</span>
                            </div>
                            <div class="checklist-item done" style="margin-bottom: 8px;">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon>
                                <span style="font-size: 13px;">Durasi Standar: <strong><?= (int)($school['borrow_duration'] ?: 7) ?></strong> hari</span>
                            </div>
                            <div class="checklist-item done">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon>
                                <span style="font-size: 13px;">Kategori Kerusakan: <strong>8 Tipe Aktif</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Themes -->
            <div class="tab-content" id="themes">
                <div class="settings-grid">
                    <div class="main-column">
                        <div class="card">
                            <h2 class="card-title">
                                <iconify-icon icon="mdi:palette-swatch-outline"></iconify-icon> Tema Dasar
                            </h2>
                            <p class="card-subtitle">Pilih warna dasar aplikasi yang paling sesuai dengan mata Anda.</p>

                            <?php if (!empty($theme_success)): ?>
                                <div class="alert alert-success">
                                    <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                    <div><?php echo htmlspecialchars($theme_success); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($activeSpecialTheme)): ?>
                                <div class="alert" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; padding: 16px 20px; border-radius: 14px; border: 1.5px solid #f59e0b; background: rgba(245, 158, 11, 0.08); color: var(--text);">
                                    <div style="display: flex; align-items: center; gap: 14px;">
                                        <iconify-icon icon="mdi:calendar-star" style="font-size: 32px; color: #f59e0b; flex-shrink: 0;"></iconify-icon>
                                        <div>
                                            <div style="font-size: 14px; font-weight: 700; color: #d97706;">
                                                Tema Event '<?php echo htmlspecialchars($eventConfig[$activeSpecialTheme]['name'] ?? ucfirst($activeSpecialTheme)); ?>' Sedang Aktif Hari Ini
                                            </div>
                                            <p style="margin: 4px 0 0; font-size: 12.5px; opacity: 0.9; line-height: 1.5;">
                                                Tema dasar saat ini dinonaktifkan sementara agar tampilan tidak bertabrakan. Klik salah satu tema dasar di bawah ini atau tombol di samping untuk mematikan tema event dan kembali ke tema dasar.
                                            </p>
                                        </div>
                                    </div>
                                    <form method="post" style="margin: 0; flex-shrink: 0;">
                                        <input type="hidden" name="action" value="deactivate_all_special_themes">
                                        <button type="submit" class="btn" style="background: #ef4444; color: #ffffff; border: none; font-size: 12px; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);">
                                            <iconify-icon icon="mdi:power" style="font-size: 16px;"></iconify-icon> 
                                            <span>Matikan Event & Kembali ke Tema Dasar</span>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <input type="hidden" name="action" value="update_theme">
                                <div class="theme-grid">
                                    <?php 
                                    $themes = [
                                        'light' => [
                                            'title' => 'Terang (Default)',
                                            'desc' => 'Tampilan Standar Bersih',
                                            'bg' => '#f8fafc',
                                            'surface' => '#ffffff',
                                            'sidebar_bg' => '#0f172a',
                                            'primary' => '#2563eb',
                                            'text' => '#0f172a',
                                            'border' => '#e2e8f0'
                                        ],
                                        'dark' => [
                                            'title' => 'Gelap',
                                            'desc' => 'Kontras Tinggi & Ramah Mata',
                                            'bg' => '#090d16',
                                            'surface' => '#131b2e',
                                            'sidebar_bg' => '#070a12',
                                            'primary' => '#38bdf8',
                                            'text' => '#f1f5f9',
                                            'border' => '#293548'
                                        ],
                                        'blue' => [
                                            'title' => 'Biru',
                                            'desc' => 'Biru Samudera Berwibawa',
                                            'bg' => '#0a192f',
                                            'surface' => '#112240',
                                            'sidebar_bg' => '#071324',
                                            'primary' => '#3b82f6',
                                            'text' => '#e6f1ff',
                                            'border' => '#233554'
                                        ],
                                        'monochrome' => [
                                            'title' => 'Hitam Putih',
                                            'desc' => 'Monokrom Kontras Tajam',
                                            'bg' => '#121212',
                                            'surface' => '#1e1e1e',
                                            'sidebar_bg' => '#0a0a0a',
                                            'primary' => '#ffffff',
                                            'text' => '#ffffff',
                                            'border' => '#383838'
                                        ],
                                        'custom' => [
                                            'title' => $themeDisplayName,
                                            'desc' => 'Palet Kustom Sekolah',
                                            'bg' => $customColors['color-bg'] ?? '#f8fafc',
                                            'surface' => $customColors['color-surface'] ?? '#ffffff',
                                            'sidebar_bg' => $customColors['color-sidebar-bg'] ?? '#0b3d61',
                                            'primary' => $customColors['color-accent'] ?? '#2563eb',
                                            'text' => $customColors['color-text'] ?? '#0f172a',
                                            'border' => $customColors['color-border'] ?? '#e2e8f0'
                                        ]
                                    ];
                                    $activeTheme = $currentTheme['theme_name'] ?? 'light';
                                    $isEventOverriding = !empty($activeSpecialTheme);
                                    foreach ($themes as $val => $t): 
                                        $isSelectedBase = ($activeTheme === $val);
                                        $isVisuallyActive = (!$isEventOverriding && $isSelectedBase);
                                    ?>
                                    <label class="theme-option <?php echo ($isEventOverriding && $isSelectedBase) ? 'theme-option-standby' : ''; ?>">
                                        <input type="radio" name="theme_name" value="<?php echo $val; ?>" 
                                            <?php echo $isVisuallyActive ? 'checked' : ''; ?>
                                            onchange="sessionStorage.setItem('theme_cache', this.value); this.form.submit();">
                                        <div class="theme-card">
                                            <?php if ($isVisuallyActive): ?>
                                                <div class="theme-badge-check" title="Tema Sedang Aktif">
                                                    <iconify-icon icon="mdi:check-bold"></iconify-icon>
                                                </div>
                                            <?php elseif ($isEventOverriding && $isSelectedBase): ?>
                                                <div class="theme-badge-check" style="background: #f59e0b;" title="Tema Standar Sekolah (Tertunda selama Event berlangsung)">
                                                    <iconify-icon icon="mdi:pause"></iconify-icon>
                                                </div>
                                            <?php endif; ?>
                                            <div class="theme-preview-box" style="background-color: <?php echo $t['bg']; ?>;">
                                                <div class="preview-mock-sidebar" style="background-color: <?php echo $t['sidebar_bg']; ?>;">
                                                    <div class="preview-mock-navdot active" style="background-color: <?php echo $t['primary']; ?>;"></div>
                                                    <div class="preview-mock-navdot" style="background-color: <?php echo $t['bg']; ?>;"></div>
                                                    <div class="preview-mock-navdot" style="background-color: <?php echo $t['bg']; ?>;"></div>
                                                </div>
                                                <div class="preview-mock-main">
                                                    <div class="preview-mock-topline" style="background-color: <?php echo $t['text']; ?>;"></div>
                                                    <div class="preview-mock-card" style="background-color: <?php echo $t['surface']; ?>; border: 1px solid <?php echo $t['border']; ?>;">
                                                        <div class="preview-mock-line" style="background-color: <?php echo $t['text']; ?>;"></div>
                                                        <div class="preview-mock-btn" style="background-color: <?php echo $t['primary']; ?>;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="theme-info-box">
                                                <span class="theme-name"><?php echo htmlspecialchars($t['title']); ?></span>
                                                <span class="theme-desc">
                                                    <?php if ($isEventOverriding && $isSelectedBase): ?>
                                                        <span style="color: #f59e0b; font-weight: 600;">(Standar Sekolah - Tertunda)</span>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($t['desc']); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        </div>

                        <!-- Section Tema Hari Penting -->
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                                <div>
                                    <h2 class="card-title">
                                        <iconify-icon icon="mdi:calendar-heart"></iconify-icon> Event & Hari Penting
                                    </h2>
                                    <p class="card-subtitle">Atur tema otomatis untuk memeriahkan hari-hari besar sekolah.</p>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="openAddSpecialThemeModal()">
                                    <iconify-icon icon="mdi:plus"></iconify-icon> Tambah Event
                                </button>
                            </div>

                            <?php if (!empty($theme_error)): ?>
                                <div class="alert alert-danger" style="margin-bottom: 24px;">
                                    <iconify-icon icon="mdi:alert-circle-outline" style="font-size: 20px;"></iconify-icon>
                                    <div><?php echo htmlspecialchars($theme_error); ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="special-theme-list-premium">
                                <?php if (empty($specialThemes)): ?>
                                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--muted); background: var(--bg); border: 2px dashed var(--border); border-radius: 20px;">
                                        <iconify-icon icon="mdi:calendar-multiple-check" style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;"></iconify-icon>
                                        <p style="font-weight: 600;">Belum ada event hari besar yang dikonfigurasi.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php foreach ($specialThemes as $st): 
                                    $cfg = $eventConfig[$st['theme_key']] ?? null;
                                    $evIcon = $cfg['icon'] ?? 'mdi:calendar-star';
                                    $evPrimary = $cfg['primary_color'] ?? '#2563eb';
                                    $evBadge = $cfg['badge'] ?? strtoupper($st['theme_key']);
                                    $evTagline = $cfg['tagline'] ?? ($st['description'] ?: 'Peringatan Hari Besar');
                                    $isLiveToday = ($activeSpecialTheme === $st['theme_key']) && $st['is_active'];
                                ?>
                                <div class="special-theme-card-enhanced <?php echo $isLiveToday ? 'is-live-today' : ($st['is_active'] ? 'active' : ''); ?>" style="--card-event-color: <?php echo $evPrimary; ?>;">
                                    <div class="event-card-top">
                                        <div class="event-icon-badge" style="background: <?php echo $evPrimary; ?>; color: #ffffff;">
                                            <iconify-icon icon="<?php echo htmlspecialchars($evIcon); ?>" style="font-size: 22px;"></iconify-icon>
                                        </div>
                                        <div class="event-header-info">
                                            <div class="event-title-row">
                                                <h3 class="event-title"><?php echo htmlspecialchars($st['name']); ?></h3>
                                                <?php if ($isLiveToday): ?>
                                                    <span class="live-status-pill">
                                                        <span class="live-dot"></span> LIVE HARI INI
                                                    </span>
                                                <?php elseif ($st['is_active']): ?>
                                                    <span class="scheduled-status-pill">TERJADWAL</span>
                                                <?php else: ?>
                                                    <span class="inactive-status-pill">NONAKTIF</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="event-official-badge"><?php echo htmlspecialchars($evBadge); ?></span>
                                        </div>
                                    </div>

                                    <p class="event-tagline-quote">"<?php echo htmlspecialchars($evTagline); ?>"</p>

                                    <div class="event-meta-chips">
                                        <div class="meta-chip">
                                            <iconify-icon icon="mdi:calendar-month-outline"></iconify-icon>
                                            <span><?php echo date('d M Y', strtotime($st['date'])); ?></span>
                                        </div>
                                        <div class="meta-chip">
                                            <iconify-icon icon="mdi:palette-swatch-outline"></iconify-icon>
                                            <code><?php echo htmlspecialchars($st['theme_key']); ?>.css</code>
                                        </div>
                                        <?php if (!empty($cfg['effect_type'])): ?>
                                        <div class="meta-chip">
                                            <iconify-icon icon="mdi:sparkles"></iconify-icon>
                                            <span style="text-transform: capitalize;"><?php echo htmlspecialchars($cfg['effect_type']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="event-actions-bar">
                                        <?php if ($isLiveToday): ?>
                                            <form method="post" style="flex: 1;">
                                                <input type="hidden" name="action" value="toggle_special_theme">
                                                <input type="hidden" name="theme_id" value="<?php echo $st['id']; ?>">
                                                <input type="hidden" name="status" value="0">
                                                <button type="submit" class="btn btn-test-live is-active-btn" style="background: #ef4444; color: #ffffff; border-color: #ef4444;" title="Klik untuk mematikan tema event ini dan kembali ke tema dasar">
                                                    <iconify-icon icon="mdi:power"></iconify-icon>
                                                    <span>Sedang Aktif (Klik Matikan)</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="flex: 1;">
                                                <input type="hidden" name="action" value="test_activate_special_theme">
                                                <input type="hidden" name="theme_id" value="<?php echo $st['id']; ?>">
                                                <button type="submit" class="btn btn-test-live" title="Aktifkan dan terapkan tema ini sekarang juga untuk hari ini">
                                                    <iconify-icon icon="mdi:play-circle-outline"></iconify-icon>
                                                    <span>Uji Coba Hari Ini</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post">
                                            <input type="hidden" name="action" value="toggle_special_theme">
                                            <input type="hidden" name="theme_id" value="<?php echo $st['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $st['is_active'] ? 0 : 1; ?>">
                                            <button type="submit" class="btn btn-toggle-switch <?php echo $st['is_active'] ? 'on' : 'off'; ?>" title="<?php echo $st['is_active'] ? 'Nonaktifkan tema ini' : 'Aktifkan jadwal tema ini'; ?>">
                                                <?php echo $st['is_active'] ? 'ON' : 'OFF'; ?>
                                            </button>
                                        </form>

                                        <form method="post" onsubmit="return confirm('Hapus konfigurasi event & hari besar ini?')">
                                            <input type="hidden" name="action" value="delete_special_theme">
                                            <input type="hidden" name="theme_id" value="<?php echo $st['id']; ?>">
                                            <button type="submit" class="btn-icon-danger" title="Hapus Event">
                                                <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Panduan Desain</h3>
                            <p style="font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 16px;">
                                • Pastikan <strong>Kontras Teks</strong> cukup tinggi agar mudah dibaca.<br>
                                • Gunakan <strong>Warna Aksen</strong> yang mewakili identitas sekolah.<br>
                                • Simpan perubahan setelah Anda puas dengan hasilnya.
                            </p>
                            <button type="button" class="btn btn-primary btn-full" onclick="openThemeCustomizerModal()" style="font-size: 12px;">
                                <iconify-icon icon="mdi:palette-outline"></iconify-icon> Buka Panel Kustomisasi
                            </button>

                            <?php if (!empty($customColors)): ?>
                            <div class="sidebar-palette">
                                <span class="sidebar-palette-title">Palet Kustom Aktif</span>
                                <div class="palette-grid">
                                    <?php foreach (['color-bg', 'color-surface', 'color-text', 'color-accent', 'color-muted', 'color-border'] as $id): ?>
                                        <div class="palette-swatch" style="background: <?php echo htmlspecialchars($customColors[$id] ?? '#eee'); ?>"></div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="sidebar-mini-preview">
                                    <div class="mini-mock-avatar"></div>
                                    <div class="mini-mock-lines">
                                        <div class="mini-mock-line"></div>
                                        <div class="mini-mock-line short"></div>
                                    </div>
                                    <iconify-icon icon="mdi:check-decagram" style="color: var(--success); font-size: 18px;"></iconify-icon>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="sidebar-palette" style="opacity: 0.5;">
                                <p style="font-size: 11px; text-align: center; color: var(--muted); margin: 0;">Belum ada kustomisasi aktif.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


            <div id="themeCustomizerModal" class="modal-overlay theme-customizer-overlay" style="display: none;">
                <div class="theme-customizer-card glass-modal">
                    <div class="theme-customizer-header">
                        <div class="title-group">
                            <h3 class="modal-title">
                                <iconify-icon icon="mdi:palette-swatch-variant"></iconify-icon> 
                                Kustomisasi Tema
                            </h3>
                            <p class="modal-subtitle">Personalisasi identitas visual sekolah Anda secara real-time.</p>
                        </div>
                        
                        <div class="theme-name-input-group">
                            <label>Nama Tema</label>
                            <input type="text" id="customThemeDisplayName" 
                                value="<?= htmlspecialchars($themeDisplayName) ?>" 
                                placeholder="Misal: Warna Sekolah Kami">
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-icon-text" onclick="closeThemeCustomizerModal()">
                                <iconify-icon icon="mdi:close"></iconify-icon> Tutup
                            </button>
                            <button type="button" id="resetCustomThemeBtn" class="btn-icon-text danger-hover">
                                <iconify-icon icon="mdi:refresh"></iconify-icon> Reset
                            </button>
                            <button type="button" id="saveCustomThemeBtn" class="btn btn-primary btn-save-theme">
                                <iconify-icon icon="mdi:content-save-check"></iconify-icon> Simpan Tema
                            </button>
                        </div>
                    </div>

                    <div class="theme-customizer-body">
                        <div class="theme-customizer-controls">
                            <div class="customizer-group">
                                <div class="group-header">
                                    <iconify-icon icon="mdi:application-settings-outline"></iconify-icon>
                                    <h4>Warna Aplikasi</h4>
                                    <span class="badge-info">Khusus Tema Kustom</span>
                                </div>
                                <div class="color-picker-grid-compact">
                                    <?php
                                    $mainColors = [
                                        '--bg' => 'Latar Belakang',
                                        '--surface' => 'Kartu & Panel',
                                        '--text' => 'Teks Utama',
                                        '--accent' => 'Aksen Utama',
                                        '--muted' => 'Teks Sekunder',
                                        '--border' => 'Garis Pemisah'
                                    ];

                                    foreach ($mainColors as $cssVar => $label):
                                        $colorId = str_replace('--', 'color-', $cssVar);
                                        $currentVal = $customColors[$colorId] ?? ''; 
                                        if (!$currentVal) {
                                            $fallbacks = [
                                                '--bg' => '#f8f9fa', '--surface' => '#ffffff', '--text' => '#1a202c', 
                                                '--accent' => '#007bff', '--muted' => '#718096', '--border' => '#e2e8f0'
                                            ];
                                            $currentVal = $fallbacks[$cssVar] ?? '#000000';
                                        }
                                    ?>
                                    <div class="color-picker-item-minimal">
                                        <div class="color-info">
                                            <span class="color-label"><?= $label ?></span>
                                            <span class="color-code-display"><?= strtoupper($currentVal) ?></span>
                                        </div>
                                        <div class="picker-control">
                                            <input type="color" class="color-box-input" data-color-id="<?= $colorId ?>" value="<?= $currentVal ?>">
                                            <input type="text" class="color-hex-input" value="<?= strtoupper($currentVal) ?>" spellcheck="false" maxlength="7">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Sidebar Group -->
                            <div class="customizer-group">
                                <div class="group-header">
                                    <iconify-icon icon="mdi:sidebar-outline"></iconify-icon>
                                    <h4>Branding Sidebar</h4>
                                    <span class="badge-info">Semua Tema</span>
                                </div>
                                <div class="color-picker-grid-compact">
                                    <?php
                                    $sidebarColors = [
                                        '--sidebar-bg' => 'Latar Sidebar',
                                        '--sidebar-text' => 'Teks Sidebar',
                                        '--sidebar-accent' => 'Aksen Sidebar'
                                    ];

                                    foreach ($sidebarColors as $cssVar => $label):
                                        $colorId = str_replace('--', 'color-', $cssVar);
                                        $currentVal = $customColors[$colorId] ?? ''; 
                                        if (!$currentVal) {
                                            $fallbacks = [
                                                '--sidebar-bg' => '#0b3d61', '--sidebar-text' => '#ffffff', '--sidebar-accent' => 'rgba(255,255,255,0.1)'
                                            ];
                                            $currentVal = $fallbacks[$cssVar] ?? '#000000';
                                        }
                                    ?>
                                    <div class="color-picker-item-minimal">
                                        <div class="color-info">
                                            <span class="color-label"><?= $label ?></span>
                                            <span class="color-code-display"><?= strtoupper($currentVal) ?></span>
                                        </div>
                                        <div class="picker-control">
                                            <input type="color" class="color-box-input" data-color-id="<?= $colorId ?>" value="<?= $currentVal ?>">
                                            <input type="text" class="color-hex-input" value="<?= strtoupper($currentVal) ?>" spellcheck="false" maxlength="7">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="theme-customizer-preview">
                            <div class="sticky-preview-wrapper">
                                <div class="preview-card-header">
                                    <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                                    <span>Pratinjau Langsung</span>
                                </div>
                                
                                <div class="preview-mock-app">
                                    <div class="mock-sidebar">
                                        <div class="mock-logo"></div>
                                        <div class="mock-nav">
                                            <div class="mock-nav-item active"></div>
                                            <div class="mock-nav-item"></div>
                                            <div class="mock-nav-item"></div>
                                        </div>
                                    </div>
                                    <div class="mock-content">
                                        <div class="mock-topbar"></div>
                                        <div class="mock-inner">
                                            <div class="mock-stats">
                                                <div class="mock-stat"></div>
                                                <div class="mock-stat"></div>
                                            </div>
                                            <div class="mock-main-card">
                                                <div class="mock-avatar"></div>
                                                <div class="mock-lines">
                                                    <div class="mock-line title"></div>
                                                    <div class="mock-line"></div>
                                                    <div class="mock-line short"></div>
                                                </div>
                                                <div class="mock-button">DEMO TOMBOL</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="preview-tips">
                                    <iconify-icon icon="mdi:lightbulb-on-outline"></iconify-icon>
                                    <p>Perubahan warna dapat langsung terlihat pada aplikasi di sistem real-time.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="addSpecialThemeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(6px); padding: 16px;">
                <div class="card" style="width: 520px; max-width: 100%; padding: 28px; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.35); max-height: 90vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 class="card-title" style="margin: 0; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                            <iconify-icon icon="mdi:calendar-star" style="color: var(--primary);"></iconify-icon> Tambah Event & Hari Besar
                        </h2>
                        <button type="button" onclick="closeAddSpecialThemeModal()" style="background: none; border: none; font-size: 20px; color: var(--muted); cursor: pointer;">
                            <iconify-icon icon="mdi:close"></iconify-icon>
                        </button>
                    </div>

                    <!-- Quick Preset Selector -->
                    <div style="margin-bottom: 20px; background: var(--bg); padding: 14px; border-radius: 12px; border: 1px solid var(--border);">
                        <label style="font-weight: 700; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">
                            ⚡ Pilih Cepat Dari Preset Resmi Nasional:
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                            <button type="button" class="btn-preset-chip" onclick="applySpecialPreset('kemerdekaan')">
                                <iconify-icon icon="mdi:flag-variant" style="color: #dc2626;"></iconify-icon> HUT RI (17 Ags)
                            </button>
                            <button type="button" class="btn-preset-chip" onclick="applySpecialPreset('idulfitri')">
                                <iconify-icon icon="mdi:moon-waning-crescent" style="color: #059669;"></iconify-icon> Idul Fitri (21 Mar)
                            </button>
                            <button type="button" class="btn-preset-chip" onclick="applySpecialPreset('hariguru')">
                                <iconify-icon icon="mdi:school" style="color: #1e3a8a;"></iconify-icon> Hari Guru (25 Nov)
                            </button>
                            <button type="button" class="btn-preset-chip" onclick="applySpecialPreset('tahunbaru')">
                                <iconify-icon icon="mdi:party-popper" style="color: #4f46e5;"></iconify-icon> Tahun Baru (1 Jan)
                            </button>
                            <button type="button" class="btn-preset-chip" onclick="applySpecialPreset('imlek')">
                                <iconify-icon icon="mingcute:lantern-fill" style="color: #dc2626;"></iconify-icon> Imlek (Gong Xi Fa Cai)
                            </button>
                            <button type="button" class="btn-preset-chip" onclick="applySpecialPreset('natal')">
                                <iconify-icon icon="mdi:pine-tree" style="color: #15803d;"></iconify-icon> Natal & Libur Akhir Tahun (25 Des)
                            </button>
                        </div>
                    </div>

                    <form method="post">
                        <input type="hidden" name="action" value="add_special_theme">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label style="font-weight: 700; font-size: 13px; margin-bottom: 6px; display: block;">Nama Hari / Acara</label>
                            <input type="text" id="special_name_input" name="theme_name_label" placeholder="Contoh: Peringatan Hari Kemerdekaan RI" required style="width: 100%; padding: 11px 14px; border-radius: 10px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);">
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 13px; margin-bottom: 6px; display: block;">Tanggal Pelaksanaan</label>
                                <input type="date" id="special_date_input" name="theme_date" required style="width: 100%; padding: 11px 14px; border-radius: 10px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);">
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 13px; margin-bottom: 6px; display: block;">Preset Desain & Efek</label>
                                <select id="special_key_select" name="theme_key" required style="width: 100%; padding: 11px 14px; border-radius: 10px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);">
                                    <option value="kemerdekaan">🇮🇩 Kemerdekaan RI (Sang Saka Merah Putih)</option>
                                    <option value="idulfitri">🌙 Idul Fitri (Emerald & Gold)</option>
                                    <option value="hariguru">🎓 Hari Guru Nasional (Sapphire & Gold)</option>
                                    <option value="tahunbaru">🎆 Tahun Baru Masehi (Midnight Indigo Gala)</option>
                                    <option value="imlek">🏮 Tahun Baru Imlek (Crimson & Gold)</option>
                                    <option value="natal">🎄 Hari Natal (Evergreen & Crimson)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 22px;">
                            <label style="font-weight: 700; font-size: 13px; margin-bottom: 6px; display: block;">Tagline / Deskripsi</label>
                            <textarea id="special_desc_input" name="theme_description" rows="2" placeholder="Kutipan ucapan atau catatan..." style="width: 100%; padding: 11px 14px; border-radius: 10px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 2; padding: 12px; font-weight: 700;">
                                <iconify-icon icon="mdi:content-save-check"></iconify-icon> Simpan Event
                            </button>
                            <button type="button" class="btn" style="flex: 1; padding: 12px; background: var(--bg); border: 1px solid var(--border); color: var(--text);" onclick="closeAddSpecialThemeModal()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function applySpecialPreset(key) {
                const year = new Date().getFullYear();
                const presets = {
                    kemerdekaan: {
                        name: 'Peringatan Hari Kemerdekaan RI',
                        date: year + '-08-17',
                        key: 'kemerdekaan',
                        desc: 'Dirgahayu Republik Indonesia — Terus Melaju Menuju Indonesia Maju'
                    },
                    idulfitri: {
                        name: 'Selamat Hari Raya Idul Fitri',
                        date: year + '-03-21',
                        key: 'idulfitri',
                        desc: 'Taqabbalallahu Minna Wa Minkum — Mohon Maaf Lahir dan Batin'
                    },
                    hariguru: {
                        name: 'Peringatan Hari Guru Nasional',
                        date: year + '-11-25',
                        key: 'hariguru',
                        desc: 'Terima Kasih Guru — Pahlawan Tanpa Tanda Jasa, Pelita Pembangun Peradaban'
                    },
                    tahunbaru: {
                        name: 'Perayaan Tahun Baru Masehi',
                        date: year + '-01-01',
                        key: 'tahunbaru',
                        desc: 'Selamat Tahun Baru — Awali Langkah Baru dengan Semangat dan Prestasi Gemilang'
                    },
                    imlek: {
                        name: 'Perayaan Hari Raya Imlek (Gong Xi Fa Cai)',
                        date: year + '-02-10',
                        key: 'imlek',
                        desc: 'Gong Xi Fa Cai — Semoga Kebahagiaan, Kemakmuran, dan Kesehatan Selalu Menyertai Kita'
                    },
                    natal: {
                        name: 'Perayaan Hari Raya Natal & Tahun Baru',
                        date: year + '-12-25',
                        key: 'natal',
                        desc: 'Selamat Hari Natal & Tahun Baru — Damai, Sukacita, dan Berkah bagi Kita Semua'
                    }
                };
                const p = presets[key];
                if (p) {
                    const nameInp = document.getElementById('special_name_input');
                    const dateInp = document.getElementById('special_date_input');
                    const keySel = document.getElementById('special_key_select');
                    const descInp = document.getElementById('special_desc_input');
                    if (nameInp) nameInp.value = p.name;
                    if (dateInp) dateInp.value = p.date;
                    if (keySel) keySel.value = p.key;
                    if (descInp) descInp.value = p.desc;
                }
            }
            </script>

            <div class="tab-content" id="scanner">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:cellphone-wireless"></iconify-icon> Scanner
                        </h2>
                        <p class="card-subtitle">Gunakan fitur ini untuk memberikan akses scan buku ke staff lewat handphone tanpa harus memberitahukan password admin.</p>

                        <?php 
                        $scanKey = $school['scan_access_key']; 
                        
                        $protocol = 'http';
                        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
                        } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                            $protocol = 'https';
                        }
                        
                        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $currentDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                        
                        $detectedBaseUrl = $protocol . "://" . $host . $currentDir;
                        
                        $displayBaseUrl = $detectedBaseUrl;
                        if (!empty($school['custom_base_url'])) {
                            $custom = rtrim($school['custom_base_url'], '/');
                            $parsed = parse_url($custom);
                            
                            if (empty($parsed['path']) || $parsed['path'] === '/') {
                                $displayBaseUrl = $custom . $currentDir;
                            } else {
                                $displayBaseUrl = $custom;
                            }
                        }
                        
                        $scanUrl = $displayBaseUrl . "/scan-mobile.php" . ($scanKey ? "?key=$scanKey" : "");
                        $returnUrl = $displayBaseUrl . "/scan-return-mobile.php" . ($scanKey ? "?key=$scanKey" : "");
                        ?>

                        <div class="card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); margin: 16px 0;">
                            <h3 class="card-title" style="font-size: 14px;">Domain / Base URL</h3>
                            <p style="font-size: 12px; color: var(--muted); margin-bottom: 12px;">Jika Anda menggunakan <strong>ngrok</strong> atau akses IP Lokal, masukkan URL dasarnya di sini agar link yang dihasilkan benar.</p>
                            <form method="post" style="display: flex; gap: 8px;">
                                <input type="hidden" name="action" value="update_scanner_settings">
                                <input type="text" name="custom_base_url" placeholder="Contoh: https://abcd-123.ngrok-free.app" 
                                       value="<?= htmlspecialchars($school['custom_base_url'] ?? '') ?>" style="flex: 1;">
                                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Simpan</button>
                            </form>
                            <?php if (!empty($school['custom_base_url'])): ?>
                                <small style="color: var(--success); margin-top: 4px; display: block;">
                                    <iconify-icon icon="mdi:check-circle"></iconify-icon> URL Custom Aktif
                                </small>
                            <?php endif; ?>
                        </div>

                        <?php if (!$scanKey): ?>
                            <div class="alert alert-warning">
                                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
                                <div>Akses Scan Mobile belum diaktifkan. Klik tombol di bawah untuk membuat kunci akses pertama kali.</div>
                            </div>
                        <?php else: ?>
                            <div class="info-block" style="background: var(--bg-secondary); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                                <div style="margin-bottom: 16px;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 8px;">1. Link Scanner Peminjaman</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" readonly value="<?= $scanUrl ?>" style="flex: 1; font-family: monospace; font-size: 11px;">
                                        <button class="btn" onclick="copyToClipboard('<?= $scanUrl ?>')">Salin</button>
                                    </div>
                                </div>
                                <div style="margin-bottom: 16px;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 8px;">2. Link Scanner Pengembalian</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" readonly value="<?= $returnUrl ?>" style="flex: 1; font-family: monospace; font-size: 11px;">
                                        <button class="btn" onclick="copyToClipboard('<?= $returnUrl ?>')">Salin</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="reset_scan_key">
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="mdi:key-refresh"></iconify-icon>
                                <?= $scanKey ? 'Reset & Generate Kunci Baru' : 'Aktifkan Akses Scanner' ?>
                            </button>
                        </form>
                        
                        <?php if ($scanKey): ?>
                            <p class="text-muted" style="margin-top: 16px; font-size: 12px; color: var(--danger);">
                                <iconify-icon icon="mdi:alert-outline"></iconify-icon> 
                                <strong>Peringatan:</strong> Jika Anda mereset kunci, link yang lama tidak akan bisa digunakan lagi.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Cara Penggunaan</h3>
                            <ul style="font-size: 13px; color: var(--muted); padding-left: 20px; line-height: 1.6;">
                                <li>Klik aktifkan untuk mendapatkan kunci unik.</li>
                                <li>Salin link scanner (Peminjaman atau Pengembalian).</li>
                                <li>Kirim link tersebut ke WhatsApp atau buka di Browser handphone staff.</li>
                                <li>Staff bisa langsung scan barcode buku/anggota tanpa perlu login.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="tab-content" id="faq">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:frequently-asked-questions"></iconify-icon> Pertanyaan Umum
                        </h2>
                        <div class="faq-list">
                            <?php
                            $faqs = [
                                [
                                    'q' => 'Bagaimana cara mengubah tema aplikasi?',
                                    'a' => 'Masuk ke tab "Tema", lalu klik pada salah satu pilihan tema yang tersedia. Tema akan langsung disimpan dan diterapkan untuk semua pengguna.'
                                ],
                                [
                                    'q' => 'Apa itu Slug?',
                                    'a' => 'Slug adalah teks unik yang akan muncul di URL pencarian atau identitas sekolah Anda. Misalnya jika slug diisi "sman1", maka link akses sekolah Anda akan lebih mudah diingat.'
                                ],
                                [
                                    'q' => 'Mengapa foto profil tidak berubah?',
                                    'a' => 'Pastikan ukuran foto tidak melebihi 5MB dan memiliki format JPG, PNG, atau WEBP. Jika sudah sesuai namun tidak berubah, coba segarkan (refresh) halaman browser Anda.'
                                ],
                                [
                                    'q' => 'Siapa yang bisa mengakses pengaturan ini?',
                                    'a' => 'Hanya pengguna dengan peran Admin yang dapat mengakses dan mengubah pengaturan sekolah.'
                                ]
                            ];
                            foreach ($faqs as $i => $item):
                            ?>
                            <div class="faq-item">
                                <div class="faq-question">
                                    <?php echo $item['q']; ?>
                                    <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                                </div>
                                <div class="faq-answer">
                                    <?php echo $item['a']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card card-accent">
                            <h3 class="card-title" style="font-size: 14px;">Butuh Bantuan?</h3>
                            <p style="font-size: 12px; margin-bottom: 12px;">Hubungi tim IT sekolah Anda jika mengalami kendala teknis yang berat.</p>
                            <a href="mailto:support@sekolah.id" class="btn btn-full" style="font-size: 12px;">Email Support</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script src="../assets/js/settings-manage.js"></script>
</body>

</html>
