<?php
/**
 * Portal Khusus Perpustakaan Sekolah
 * Dedicated School Portal untuk Siswa, Guru, dan Anggota
 */
session_start();

// Jika sudah login sebagai siswa, langsung arahkan ke student dashboard
if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', ['student', 'teacher', 'employee'])) {
    header('Location: public/student-dashboard.php');
    exit;
}

$pdo = null;
try {
    $pdo = require __DIR__ . '/src/db.php';
} catch (Exception $e) {
    // Database fallback
}

$school_slug = trim($_GET['s'] ?? ($_GET['slug'] ?? ''));
$school_id = !empty($_GET['id']) ? (int)$_GET['id'] : null;
$school = null;

if ($pdo && (!empty($school_slug) || $school_id)) {
    try {
        if ($school_id) {
            $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $school_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM schools WHERE slug = :slug OR name = :slug_name OR npsn = :slug_npsn LIMIT 1");
            $stmt->execute(['slug' => $school_slug, 'slug_name' => $school_slug, 'slug_npsn' => $school_slug]);
        }
        $school = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika ditemukan, ambil ringkasan koleksi buku
        if ($school) {
            $stmtBooks = $pdo->prepare("SELECT COUNT(*) FROM books WHERE school_id = :sid");
            $stmtBooks->execute(['sid' => $school['id']]);
            $school['total_books'] = $stmtBooks->fetchColumn() ?: 0;
        }
    } catch (Exception $e) {
        $school = null;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo $school ? htmlspecialchars($school['name']) . ' — Perpustakaan Digital' : 'Pencarian Perpustakaan Sekolah'; ?></title>
    <meta name="description" content="Portal Akses Perpustakaan Digital Sekolah">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        :root {
            --primary: #0B3D61;
            --primary-light: #1E5C8A;
            --accent: #F59E0B;
            --accent-soft: rgba(245, 158, 11, 0.12);
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --danger: #EF4444;
            --success: #10B981;
            --radius-lg: 20px;
            --radius-md: 12px;
            --shadow-card: 0 20px 40px -15px rgba(11, 61, 97, 0.12), 0 0 1px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #F0F4F8 0%, #E2E8F0 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow-x: hidden;
        }

        /* Top Decorative Wave */
        .decor-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 380px;
            background: linear-gradient(135deg, var(--primary) 0%, #172554 100%);
            clip-path: polygon(0 0, 100% 0, 100% 75%, 0 100%);
            z-index: 0;
        }

        .decor-circle-1 {
            position: absolute;
            top: -60px;
            right: 10%;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            filter: blur(40px);
            pointer-events: none;
        }

        .decor-circle-2 {
            position: absolute;
            top: 80px;
            left: 5%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.08);
            filter: blur(30px);
            pointer-events: none;
        }

        /* Nav Header */
        .portal-nav {
            position: relative;
            z-index: 2;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: -0.3px;
        }

        .brand-link img {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: white;
            padding: 4px;
        }

        .nav-actions a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.12);
            transition: all 0.2s;
        }

        .nav-actions a:hover {
            background: rgba(255, 255, 255, 0.22);
            color: white;
        }

        /* Main Container */
        .portal-container {
            position: relative;
            z-index: 2;
            max-width: 520px;
            width: 92%;
            margin: 10px auto 40px;
        }

        /* School Card */
        .school-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* School Header banner inside card */
        .school-header {
            padding: 32px 32px 24px;
            text-align: center;
            background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .school-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #EFF6FF;
            color: #1D4ED8;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 50px;
            margin-bottom: 16px;
        }

        .school-logo-wrap {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            border-radius: 20px;
            background: #FFFFFF;
            border: 3px solid #FFFFFF;
            box-shadow: 0 10px 25px -5px rgba(11, 61, 97, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .school-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .school-logo-wrap iconify-icon {
            font-size: 40px;
            color: var(--primary);
        }

        .school-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 6px;
            letter-spacing: -0.4px;
            line-height: 1.3;
        }

        .school-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        /* School Meta Badges */
        .school-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 14px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .school-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #F1F5F9;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Login Form */
        .login-body {
            padding: 32px;
        }

        .form-intro {
            margin-bottom: 24px;
            text-align: center;
        }

        .form-intro h3 {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .form-intro p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            font-size: 20px;
            color: #94A3B8;
            pointer-events: none;
            display: flex;
        }

        .input-wrapper input {
            width: 100%;
            padding: 13px 14px 13px 44px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: inherit;
            color: var(--text-main);
            background: #F8FAFC;
            transition: all 0.2s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(11, 61, 97, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: #94A3B8;
            cursor: pointer;
            display: flex;
            padding: 4px;
            font-size: 18px;
        }

        .toggle-password:hover {
            color: #475569;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px rgba(11, 61, 97, 0.3);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -4px rgba(11, 61, 97, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.2s ease;
        }

        .alert-error {
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }

        .alert-success {
            background: #DCFCE7;
            color: #15803D;
            border: 1px solid #86EFAC;
        }

        .card-footer {
            padding: 18px 32px 24px;
            background: #F8FAFC;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }

        .card-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .card-footer a:hover {
            text-decoration: underline;
        }

        /* Search School Layout (When no school or school not found) */
        .search-box-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 36px 32px;
            text-align: center;
        }

        .search-icon-hero {
            width: 72px;
            height: 72px;
            background: #EFF6FF;
            color: var(--primary);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 20px;
        }

        .search-results-list {
            margin-top: 20px;
            text-align: left;
            max-height: 280px;
            overflow-y: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: none;
        }

        .school-item-result {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s;
        }

        .school-item-result:last-child {
            border-bottom: none;
        }

        .school-item-result:hover {
            background: #F1F5F9;
        }

        .school-item-title {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .school-item-sub {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Site footer */
        .site-footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #64748B;
            position: relative;
            z-index: 2;
        }

        .site-footer a {
            color: #475569;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="decor-bg"></div>
    <div class="decor-circle-1"></div>
    <div class="decor-circle-2"></div>

    <!-- Header -->
    <header class="portal-nav">
        <a href="index.php" class="brand-link">
            <img src="img/logo.png" alt="Logo">
            <span>Perpustakaan<span style="color:var(--accent);">Digital</span></span>
        </a>
        <div class="nav-actions">
            <?php if ($school): ?>
                <a href="school.php"><iconify-icon icon="solar:magnifer-linear"></iconify-icon> Cari Sekolah Lain</a>
            <?php else: ?>
                <a href="index.php"><iconify-icon icon="solar:home-2-linear"></iconify-icon> Beranda</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Container -->
    <main class="portal-container">

        <?php if ($school): ?>
            <!-- School Exists: Login Form -->
            <div class="school-card">
                <div class="school-header">
                    <div class="school-badge-pill">
                        <iconify-icon icon="solar:verified-check-bold"></iconify-icon> Perpustakaan Resmi
                    </div>

                    <div class="school-logo-wrap">
                        <?php if (!empty($school['photo_path']) && file_exists(__DIR__ . '/' . $school['photo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($school['photo_path']); ?>" alt="<?php echo htmlspecialchars($school['name']); ?>">
                        <?php elseif (!empty($school['logo']) && file_exists(__DIR__ . '/' . $school['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($school['logo']); ?>" alt="<?php echo htmlspecialchars($school['name']); ?>">
                        <?php else: ?>
                            <iconify-icon icon="solar:buildings-bold"></iconify-icon>
                        <?php endif; ?>
                    </div>

                    <h1 class="school-title"><?php echo htmlspecialchars($school['name']); ?></h1>
                    
                    <?php if (!empty($school['address'])): ?>
                        <div class="school-subtitle">
                            <iconify-icon icon="solar:map-point-linear"></iconify-icon>
                            <span><?php echo htmlspecialchars($school['address']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="school-meta">
                        <?php if (!empty($school['npsn'])): ?>
                            <div class="school-meta-item">
                                <iconify-icon icon="solar:diploma-bold"></iconify-icon>
                                <span>NPSN: <?php echo htmlspecialchars($school['npsn']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="school-meta-item">
                            <iconify-icon icon="solar:book-bookmark-bold"></iconify-icon>
                            <span><?php echo number_format($school['total_books'] ?? 0); ?> Koleksi Buku</span>
                        </div>
                    </div>
                </div>

                <div class="login-body">
                    <div class="form-intro">
                        <h3>Masuk Anggota Perpustakaan</h3>
                        <p>Silakan gunakan NISN, NIP, atau ID Anggota Anda</p>
                    </div>

                    <div id="loginAlert" class="alert-box alert-error">
                        <iconify-icon icon="solar:danger-circle-bold" style="font-size: 18px;"></iconify-icon>
                        <span id="loginAlertText"></span>
                    </div>

                    <form id="studentPortalForm" onsubmit="handlePortalLogin(event)">
                        <input type="hidden" name="user_type" value="student">
                        <input type="hidden" name="school_id" value="<?php echo (int)$school['id']; ?>">

                        <div class="input-group">
                            <label for="nisnInput">NISN / NIP / ID Anggota</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:user-id-linear"></iconify-icon></span>
                                <input type="text" id="nisnInput" name="nisn" required placeholder="Masukkan nomor identitas Anda" autocomplete="username">
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="passwordInput">Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:lock-password-linear"></iconify-icon></span>
                                <input type="password" id="passwordInput" name="password" required placeholder="Masukkan kata sandi" autocomplete="current-password">
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" aria-label="Toggle password">
                                    <iconify-icon id="eyeIcon" icon="solar:eye-linear"></iconify-icon>
                                </button>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="btn-submit">
                            <span>Masuk ke Perpustakaan</span>
                            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>
                        </button>
                    </form>
                </div>

                <div class="card-footer">
                    <p>Belum memiliki akun? Hubungi petugas perpustakaan sekolah untuk dibuatkan kartu anggota digital.</p>
                </div>
            </div>

        <?php else: ?>
            <!-- School Search / Finder Mode -->
            <div class="search-box-card">
                <div class="search-icon-hero">
                    <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                </div>

                <h1 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">Cari Perpustakaan Sekolah</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.5;">
                    Ketik nama sekolah atau NPSN untuk masuk ke portal perpustakaan digital resmi sekolah Anda.
                </p>

                <div class="input-wrapper" style="margin-bottom: 12px;">
                    <span class="input-icon"><iconify-icon icon="solar:magnifer-linear"></iconify-icon></span>
                    <input type="text" id="schoolSearchInput" placeholder="Contoh: SMK Bina Mandiri atau 12345678" oninput="debounceSearchSchools()" autocomplete="off">
                </div>

                <div id="searchResultsList" class="search-results-list"></div>

                <div id="searchNotice" style="margin-top: 16px; font-size: 13px; color: var(--text-muted);">
                    Mulai ketik untuk melihat daftar sekolah yang terdaftar.
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Perpustakaan Digital. Sistem Manajemen Perpustakaan Sekolah Terintegrasi.</p>
    </footer>

    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.setAttribute('icon', 'solar:eye-closed-linear');
            } else {
                pwd.type = 'password';
                icon.setAttribute('icon', 'solar:eye-linear');
            }
        }

        async function handlePortalLogin(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('loginAlert');
            const alertText = document.getElementById('loginAlertText');

            alertBox.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Memverifikasi...</span>';

            const formData = new FormData(form);

            try {
                const response = await fetch('public/api/login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    btn.innerHTML = '<span>Berhasil! Mengalihkan...</span>';
                    window.location.href = result.redirect_url || 'public/student-dashboard.php';
                } else {
                    if (result.is_admin) {
                        alertBox.style.display = 'flex';
                        alertText.innerHTML = `${result.message} <a href="${result.admin_url || 'admin-login.php'}" style="color: #0B3D61; font-weight: 700; text-decoration: underline; margin-left: 6px;">Login Admin di Sini &rarr;</a>`;
                        btn.disabled = false;
                        btn.innerHTML = '<span>Masuk ke Perpustakaan</span> <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>';
                        return;
                    }
                    throw new Error(result.message || 'Login gagal.');
                }
            } catch (err) {
                alertBox.style.display = 'flex';
                alertText.innerText = err.message || 'Terjadi kesalahan jaringan.';
                btn.disabled = false;
                btn.innerHTML = '<span>Masuk ke Perpustakaan</span> <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>';
            }
        }

        // Live search schools functionality
        let searchTimeout = null;
        function debounceSearchSchools() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchSchools, 300);
        }

        async function searchSchools() {
            const input = document.getElementById('schoolSearchInput');
            if (!input) return;
            const query = input.value.trim();
            const resultsBox = document.getElementById('searchResultsList');
            const notice = document.getElementById('searchNotice');

            if (query.length === 0) {
                loadInitialSchools();
                return;
            }

            if (query.length < 2) {
                resultsBox.style.display = 'none';
                resultsBox.innerHTML = '';
                notice.innerText = 'Ketik minimal 2 karakter untuk mencari.';
                return;
            }

            notice.innerText = 'Mencari sekolah...';

            try {
                const res = await fetch(`public/api/search-schools.php?q=${encodeURIComponent(query)}`);
                const data = await res.json();

                if (data.success && data.schools && data.schools.length > 0) {
                    resultsBox.innerHTML = data.schools.map(s => `
                        <a href="${s.url}" class="school-item-result">
                            <div>
                                <div class="school-item-title">${escapeHtml(s.name)}</div>
                                <div class="school-item-sub">NPSN: ${escapeHtml(s.npsn)} ${s.address ? '• ' + escapeHtml(s.address) : ''}</div>
                            </div>
                            <iconify-icon icon="solar:arrow-right-linear" style="color: #64748B;"></iconify-icon>
                        </a>
                    `).join('');
                    resultsBox.style.display = 'block';
                    notice.innerText = `Ditemukan ${data.schools.length} sekolah. Klik untuk masuk ke portal.`;
                } else {
                    resultsBox.style.display = 'none';
                    resultsBox.innerHTML = '';
                    notice.innerText = 'Sekolah tidak ditemukan. Pastikan nama atau NPSN sudah benar.';
                }
            } catch (err) {
                notice.innerText = 'Gagal memuat data pencarian.';
            }
        }

        async function loadInitialSchools() {
            const resultsBox = document.getElementById('searchResultsList');
            const notice = document.getElementById('searchNotice');
            if (!resultsBox) return;

            try {
                const res = await fetch('public/api/search-schools.php');
                const data = await res.json();
                if (data.success && data.schools && data.schools.length > 0) {
                    resultsBox.innerHTML = data.schools.map(s => `
                        <a href="${s.url}" class="school-item-result">
                            <div>
                                <div class="school-item-title">${escapeHtml(s.name)}</div>
                                <div class="school-item-sub">NPSN: ${escapeHtml(s.npsn)} ${s.address ? '• ' + escapeHtml(s.address) : ''}</div>
                            </div>
                            <iconify-icon icon="solar:arrow-right-linear" style="color: #64748B;"></iconify-icon>
                        </a>
                    `).join('');
                    resultsBox.style.display = 'block';
                    notice.innerText = 'Pilih sekolah Anda atau ketik nama/NPSN di atas.';
                }
            } catch (err) {}
        }

        if (document.getElementById('schoolSearchInput')) {
            document.addEventListener('DOMContentLoaded', loadInitialSchools);
            document.getElementById('schoolSearchInput').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimeout);
                    searchSchools();
                }
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>
</html>
