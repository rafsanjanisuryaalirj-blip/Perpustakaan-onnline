<?php
/**
 * Login Khusus Admin & Petugas Perpustakaan Sekolah
 * Dedicated Admin & Librarian Gate
 */
session_start();

// Jika sudah login sebagai admin, langsung arahkan ke dashboard
if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', ['admin', 'librarian'])) {
    header('Location: public/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Login Pengelola Perpustakaan — Platform Sekolah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        :root {
            --primary: #0B3D61;
            --primary-dark: #07273E;
            --primary-light: #1E5C8A;
            --accent: #F59E0B;
            --bg: #0F172A;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --radius-lg: 24px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient glow background */
        .ambient-glow-1 {
            position: absolute;
            top: -100px;
            left: -100px;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Top Header */
        .admin-nav {
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .brand-link img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: white;
            padding: 4px;
        }

        .nav-back a {
            color: #94A3B8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .nav-back a:hover {
            color: white;
        }

        /* Login Card */
        .admin-card-container {
            max-width: 460px;
            width: 90%;
            margin: 20px auto 40px;
            position: relative;
            z-index: 2;
        }

        .admin-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 40px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .admin-icon-badge {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0B3D61 0%, #1E40AF 100%);
            color: white;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
            box-shadow: 0 10px 20px -5px rgba(11, 61, 97, 0.4);
        }

        .card-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .card-header p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
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
            padding: 14px 14px 14px 44px;
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
            padding: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px rgba(11, 61, 97, 0.35);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -4px rgba(11, 61, 97, 0.45);
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
        }

        .alert-error {
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }

        .card-footer-links {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.8;
        }

        .card-footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .card-footer-links a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .site-footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #64748B;
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>

    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <header class="admin-nav">
        <a href="index.php" class="brand-link">
            <img src="img/logo.png" alt="Logo">
            <span>Perpustakaan<span style="color:var(--accent);">Digital</span></span>
        </a>
        <div class="nav-back">
            <a href="index.php"><iconify-icon icon="solar:arrow-left-linear"></iconify-icon> Kembali ke Beranda</a>
        </div>
    </header>

    <main class="admin-card-container">
        <div class="admin-card">
            <div class="card-header">
                <div class="admin-icon-badge">
                    <iconify-icon icon="solar:shield-keyhole-bold"></iconify-icon>
                </div>
                <h1>Login Pengelola</h1>
                <p>Khusus Administrator dan Petugas Perpustakaan Sekolah</p>
            </div>

            <div id="loginAlert" class="alert-box alert-error">
                <iconify-icon icon="solar:danger-circle-bold" style="font-size: 18px;"></iconify-icon>
                <span id="loginAlertText"></span>
            </div>

            <form id="adminLoginForm" onsubmit="handleAdminLogin(event)">
                <input type="hidden" name="user_type" value="school">

                <div class="input-group">
                    <label for="emailInput">Email Administrator / Sekolah</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><iconify-icon icon="solar:letter-linear"></iconify-icon></span>
                        <input type="email" id="emailInput" name="email" required placeholder="admin@sekolah.sch.id" autocomplete="email">
                    </div>
                </div>

                <div class="input-group">
                    <label for="passwordInput">Kata Sandi</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><iconify-icon icon="solar:lock-password-linear"></iconify-icon></span>
                        <input type="password" id="passwordInput" name="password" required placeholder="Masukkan kata sandi akun" autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" aria-label="Toggle password">
                            <iconify-icon id="eyeIcon" icon="solar:eye-linear"></iconify-icon>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit">
                    <span>Masuk ke Dashboard</span>
                    <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>
                </button>
            </form>

            <div class="card-footer-links">
                <p>Bukan pengelola? <a href="school.php">Masuk sebagai Siswa / Anggota &rarr;</a></p>
                <p style="margin-top: 4px;">Ingin mendaftarkan sekolah baru? <a href="register-school.php">Daftar Sekolah &rarr;</a></p>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Perpustakaan Digital. Keamanan Terintegrasi Multi-Tenant.</p>
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

        async function handleAdminLogin(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('loginAlert');
            const alertText = document.getElementById('loginAlertText');

            alertBox.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Memeriksa Akun...</span>';

            const formData = new FormData(form);

            try {
                const response = await fetch('public/api/login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    btn.innerHTML = '<span>Login Berhasil! Mengalihkan...</span>';
                    window.location.href = result.redirect_url || 'public/index.php';
                } else {
                    if (result.needs_verification) {
                        alertBox.style.display = 'flex';
                        alertText.innerHTML = `${result.message} <a href="register-school.php?verify=1&email=${encodeURIComponent(result.email)}&user_id=${result.user_id}" style="color: #B91C1C; text-decoration: underline; font-weight: bold; margin-left: 5px;">Verifikasi di sini &rarr;</a>`;
                    } else {
                        throw new Error(result.message || 'Login gagal.');
                    }
                }
            } catch (err) {
                alertBox.style.display = 'flex';
                alertText.innerText = err.message || 'Terjadi kesalahan saat menghubungi server.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Masuk ke Dashboard</span> <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>';
            }
        }
    </script>
</body>
</html>
