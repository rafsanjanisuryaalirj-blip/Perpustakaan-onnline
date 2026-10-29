<?php
/**
 * Pendaftaran Sekolah Mandiri (Self-Serve Onboarding)
 * Halaman Registrasi Khusus & Terpisah untuk Pengelola Sekolah
 */
session_start();

$verify_mode = isset($_GET['verify']) && $_GET['verify'] === '1';
$pending_email = htmlspecialchars($_GET['email'] ?? '');
$pending_user_id = htmlspecialchars($_GET['user_id'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Pendaftaran Sekolah — Platform Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        :root {
            --primary: #0B3D61;
            --primary-light: #1E5C8A;
            --accent: #F59E0B;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
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

        .decor-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 340px;
            background: linear-gradient(135deg, var(--primary) 0%, #172554 100%);
            clip-path: polygon(0 0, 100% 0, 100% 75%, 0 100%);
            z-index: 0;
        }

        .reg-nav {
            position: relative;
            z-index: 2;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1100px;
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
        }

        .brand-link img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: white;
            padding: 4px;
        }

        .nav-link-btn {
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

        .nav-link-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            color: white;
        }

        .reg-container {
            position: relative;
            z-index: 2;
            max-width: 580px;
            width: 92%;
            margin: 10px auto 40px;
        }

        .reg-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            padding: 40px 36px;
        }

        .card-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .header-icon {
            width: 64px;
            height: 64px;
            background: #EFF6FF;
            color: var(--primary);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
        }

        .card-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .card-header p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #CBD5E1;
            transition: all 0.3s;
        }

        .step-dot.active {
            width: 28px;
            border-radius: 10px;
            background: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .full-col {
            grid-column: 1 / -1;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
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

        .input-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
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

        .alert-success {
            background: #DCFCE7;
            color: #15803D;
            border: 1px solid #86EFAC;
        }

        /* OTP Screen Styling */
        .otp-box {
            text-align: center;
            padding: 10px 0;
        }

        .otp-inputs {
            margin: 24px 0;
        }

        .otp-input-field {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 12px;
            text-align: center;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            width: 100%;
            background: #F8FAFC;
            transition: all 0.2s;
        }

        .otp-input-field:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(11, 61, 97, 0.1);
        }

        .resend-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            text-decoration: underline;
        }

        .card-footer {
            margin-top: 24px;
            padding-top: 20px;
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

        .site-footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #64748B;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .reg-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="decor-bg"></div>

    <header class="reg-nav">
        <a href="index.php" class="brand-link">
            <img src="img/logo.png" alt="Logo">
            <span>Perpustakaan<span style="color:var(--accent);">Digital</span></span>
        </a>
        <a href="admin-login.php" class="nav-link-btn">
            <iconify-icon icon="solar:lock-keyhole-linear"></iconify-icon> Sudah Punya Akun? Masuk
        </a>
    </header>

    <main class="reg-container">
        <div class="reg-card">

            <!-- STEP 1: Registration Form -->
            <div id="stepRegistration" style="<?php echo $verify_mode ? 'display: none;' : ''; ?>">
                <div class="card-header">
                    <div class="header-icon">
                        <iconify-icon icon="solar:buildings-bold"></iconify-icon>
                    </div>
                    <h1>Daftarkan Sekolah Baru</h1>
                    <p>Sistem akan mengonfigurasi perpustakaan digital khusus untuk instansi sekolah Anda secara otomatis.</p>
                </div>

                <div class="step-indicator">
                    <div class="step-dot active"></div>
                    <div class="step-dot"></div>
                </div>

                <div id="regAlert" class="alert-box alert-error">
                    <iconify-icon icon="solar:danger-circle-bold" style="font-size: 18px;"></iconify-icon>
                    <span id="regAlertText"></span>
                </div>

                <form id="schoolRegForm" onsubmit="handleSchoolRegistration(event)">
                    <div class="form-grid">
                        <div class="full-col input-group">
                            <label for="schoolName">Nama Resmi Sekolah</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:buildings-2-linear"></iconify-icon></span>
                                <input type="text" id="schoolName" name="school_name" required placeholder="Contoh: SMA Negeri 1 Jakarta">
                            </div>
                        </div>

                        <div class="full-col input-group">
                            <label for="npsn">Nomor Pokok Sekolah Nasional (NPSN)</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:diploma-linear"></iconify-icon></span>
                                <input type="text" id="npsn" name="npsn" required maxlength="8" pattern="[0-9]{8}" placeholder="8 digit angka (contoh: 20104567)">
                            </div>
                            <div class="input-hint">Digunakan untuk validasi keaslian instansi pendidikan</div>
                        </div>

                        <div class="full-col input-group">
                            <label for="adminName">Nama Lengkap Penanggung Jawab / Pustakawan</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:user-circle-linear"></iconify-icon></span>
                                <input type="text" id="adminName" name="admin_name" required placeholder="Nama dan gelar administrator sekolah">
                            </div>
                        </div>

                        <div class="full-col input-group">
                            <label for="adminEmail">Email Resmi Administrator (@sch.id)</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:letter-linear"></iconify-icon></span>
                                <input type="email" id="adminEmail" name="admin_email" required 
                                       pattern="^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?sch\.id$" 
                                       placeholder="contoh: admin@namasekolah.sch.id"
                                       title="Email wajib berakhiran @sch.id atau @namasekolah.sch.id">
                            </div>
                            <div class="input-hint">Wajib menggunakan email instansi sekolah berdomain <strong>@sch.id</strong>. Kode OTP akan dikirim ke email ini.</div>
                        </div>

                        <div class="input-group">
                            <label for="adminPassword">Kata Sandi</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:lock-password-linear"></iconify-icon></span>
                                <input type="password" id="adminPassword" name="admin_password" required minlength="6" placeholder="Min. 6 karakter">
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirmPassword">Konfirmasi Sandi</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><iconify-icon icon="solar:check-circle-linear"></iconify-icon></span>
                                <input type="password" id="confirmPassword" required minlength="6" placeholder="Ulangi kata sandi">
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="regSubmitBtn" class="btn-submit">
                        <span>Lanjutkan Verifikasi</span>
                        <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>
                    </button>
                </form>

                <div class="card-footer">
                    Sudah pernah mendaftarkan sekolah? <a href="admin-login.php">Masuk di sini &rarr;</a>
                </div>
            </div>

            <!-- STEP 2: OTP Verification Screen -->
            <div id="stepOTP" style="<?php echo $verify_mode ? 'display: block;' : 'display: none;'; ?>">
                <div class="card-header">
                    <div class="header-icon" style="background: #FEF3C7; color: #D97706;">
                        <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
                    </div>
                    <h1>Verifikasi Email Sekolah</h1>
                    <p>Masukkan 6 digit kode OTP yang telah dikirim ke alamat email:<br><strong id="displayTargetEmail" style="color: var(--primary);"><?php echo $pending_email ?: 'email Anda'; ?></strong></p>
                </div>

                <div class="step-indicator">
                    <div class="step-dot"></div>
                    <div class="step-dot active"></div>
                </div>

                <div id="otpAlert" class="alert-box alert-error">
                    <iconify-icon icon="solar:danger-circle-bold" style="font-size: 18px;"></iconify-icon>
                    <span id="otpAlertText"></span>
                </div>

                <form id="otpVerifyForm" onsubmit="handleOTPVerification(event)" class="otp-box">
                    <input type="hidden" id="otpUserId" name="user_id" value="<?php echo $pending_user_id; ?>">

                    <div class="otp-inputs">
                        <input type="text" id="otpCode" name="verification_code" required maxlength="6" pattern="[0-9]{6}" placeholder="000000" class="otp-input-field" autocomplete="one-time-code">
                    </div>

                    <button type="submit" id="otpSubmitBtn" class="btn-submit">
                        <span>Verifikasi & Aktifkan Sekolah</span>
                        <iconify-icon icon="solar:verified-check-linear"></iconify-icon>
                    </button>

                    <p style="margin-top: 20px; font-size: 13px; color: var(--text-muted);">
                        Belum menerima email? 
                        <button type="button" class="resend-btn" onclick="handleResendOTP()">Kirim Ulang Kode</button>
                    </p>
                </form>

                <div class="card-footer">
                    Salah memasukkan email? <a href="#" onclick="backToRegistration(event)">&larr; Kembali dan ubah data</a>
                </div>
            </div>

        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Perpustakaan Digital. Solusi Otomatisasi Perpustakaan Sekolah.</p>
    </footer>

    <script>
        let registeredEmail = '<?php echo $pending_email; ?>';

        async function handleSchoolRegistration(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('regSubmitBtn');
            const alertBox = document.getElementById('regAlert');
            const alertText = document.getElementById('regAlertText');

            const pwd = document.getElementById('adminPassword').value;
            const confirmPwd = document.getElementById('confirmPassword').value;

            const email = document.getElementById('adminEmail').value.trim();
            const schIdRegex = /^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?sch\.id$/i;

            if (!schIdRegex.test(email)) {
                alertBox.style.display = 'flex';
                alertText.innerText = 'Pendaftaran sekolah wajib menggunakan email resmi berdomain @sch.id (contoh: admin@namasekolah.sch.id).';
                document.getElementById('adminEmail').focus();
                return;
            }

            if (pwd !== confirmPwd) {
                alertBox.style.display = 'flex';
                alertText.innerText = 'Kata sandi dan konfirmasi sandi tidak cocok.';
                return;
            }

            alertBox.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Mendaftarkan Sekolah...</span>';

            const formData = new FormData(form);

            try {
                const response = await fetch('public/api/register.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    registeredEmail = result.email;
                    document.getElementById('displayTargetEmail').innerText = result.email;
                    document.getElementById('otpUserId').value = result.user_id;

                    // Switch to OTP Step
                    document.getElementById('stepRegistration').style.display = 'none';
                    document.getElementById('stepOTP').style.display = 'block';
                    document.getElementById('otpCode').focus();
                } else {
                    throw new Error(result.message || 'Pendaftaran gagal.');
                }
            } catch (err) {
                alertBox.style.display = 'flex';
                alertText.innerText = err.message || 'Terjadi kesalahan sistem.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Lanjutkan Verifikasi</span> <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>';
            }
        }

        async function handleOTPVerification(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('otpSubmitBtn');
            const alertBox = document.getElementById('otpAlert');
            const alertText = document.getElementById('otpAlertText');

            alertBox.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Memverifikasi Kode...</span>';

            const formData = new FormData(form);

            try {
                const response = await fetch('public/api/verify-and-login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.style.display = 'flex';
                    alertText.innerText = result.message || 'Akun sekolah berhasil diverifikasi! Mengalihkan...';
                    
                    setTimeout(() => {
                        window.location.href = result.redirect_url || 'public/index.php';
                    }, 1200);
                } else {
                    throw new Error(result.message || 'Kode OTP salah.');
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.style.display = 'flex';
                alertText.innerText = err.message || 'Gagal memverifikasi kode OTP.';
                btn.disabled = false;
                btn.innerHTML = '<span>Verifikasi & Aktifkan Sekolah</span> <iconify-icon icon="solar:verified-check-linear"></iconify-icon>';
            }
        }

        async function handleResendOTP() {
            if (!registeredEmail) {
                alert('Email tidak valid. Silakan ulangi pendaftaran.');
                return;
            }

            try {
                const res = await fetch('public/api/resend-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: registeredEmail })
                });

                const data = await res.json();
                if (data.success) {
                    alert('✓ ' + (data.message || 'Kode OTP baru telah dikirim ke email Anda.'));
                } else {
                    alert('Gagal: ' + (data.message || 'Tidak dapat mengirim ulang kode.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        function backToRegistration(e) {
            e.preventDefault();
            document.getElementById('stepOTP').style.display = 'none';
            document.getElementById('stepRegistration').style.display = 'block';
        }
    </script>
</body>
</html>
