<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
$user = $_SESSION['user'] ?? null;
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/perpustakaan-online/public';

// Load school profile data
$sidebarSchool = null;
$school_photo = null;
$school_email = null;
$school_npsn = null;

if ($user) {
    try {
        $pdo = require __DIR__ . '/../../src/db.php';
        $stmt = $pdo->prepare('SELECT name, photo_path, email, npsn FROM schools WHERE id = :id');
        $stmt->execute(['id' => $user['school_id']]);
        $sidebarSchool = $stmt->fetch();

        if ($sidebarSchool) {
            $school_photo = $sidebarSchool['photo_path'] ?? null;
            $school_email = $sidebarSchool['email'] ?? null;
            $school_npsn = $sidebarSchool['npsn'] ?? null;
        }
    } catch (Exception $e) {
        // Fallback if database query fails
        error_log('Sidebar error: ' . $e->getMessage());
    }
}

if (!function_exists('_is_active_sidebar')) {
    function _is_active_sidebar($path, $current)
    {
        $current = rtrim(str_replace('/perpustakaan-online/public', '', $current), '/') ?: '/';
        $path = rtrim(str_replace('/perpustakaan-online/public', '', $path), '/') ?: '/';
        return $current === $path ? ' active' : '';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="<?php echo $base; ?>/../assets/css/school-profile.css">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f1724;
            --muted: #6b7280;
            --accent: #0b3d61;
            --accent-light: #e0f2fe;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Navigation Sidebar */
        .nav-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 240px;
            background: var(--sidebar-bg, linear-gradient(135deg, #0b3d61 0%, #062d4a 100%)) !important; 
            color: var(--sidebar-text, white);
            padding: 24px 0;
            z-index: 1002;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }

        .nav-sidebar-header {
            padding: 0 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            cursor: pointer;
        }

        .nav-sidebar-header-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        .nav-sidebar-header-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: white;
        }

        .nav-sidebar-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: var(--sidebar-text, white);
        }

        .nav-sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-sidebar-menu li {
            margin: 0;
        }

        .nav-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: var(--sidebar-text, rgba(255, 255, 255, 0.8));
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sidebar-menu a:hover {
            background: var(--sidebar-accent, rgba(255, 255, 255, 0.1));
            color: var(--sidebar-text, white);
        }

        .nav-sidebar-menu a.active {
            background: var(--sidebar-accent, rgba(255, 255, 255, 0.15));
            color: var(--sidebar-text, white);
            border-left-color: var(--sidebar-text, white);
            font-weight: 600;
        }

        .nav-sidebar-menu-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        iconify-icon {
            display: inline-block;
            vertical-align: middle;
        }

        .nav-sidebar-menu iconify-icon,
        .nav-sidebar-menu-icon iconify-icon {
            font-size: 18px;
            width: 24px;
            height: 24px;
            color: currentColor;
            opacity: 0.95;
            transition: all 0.2s ease;
        }

        .nav-sidebar-menu a:hover iconify-icon,
        .nav-sidebar-menu a:hover .nav-sidebar-menu-icon iconify-icon {
            color: currentColor;
            opacity: 1;
        }

        .nav-sidebar-menu a.active iconify-icon,
        .nav-sidebar-menu a.active .nav-sidebar-menu-icon iconify-icon {
            color: currentColor;
            opacity: 1;
        }

        .nav-sidebar-divider {
            height: 1px;
            background: var(--sidebar-accent, rgba(255, 255, 255, 0.1));
            margin: 16px 0;
        }

        /* Adjust main content to account for sidebar */
        .app {
            margin-left: 240px;
        }

        @media (max-width: 768px) {
            .nav-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                flex-direction: row;
                padding: 12px 16px;
                display: flex;
                align-items: center;
            }

            .nav-sidebar-header {
                margin-bottom: 0;
                padding: 0;
            }

            .nav-sidebar-menu {
                display: flex;
                flex-direction: row;
                gap: 4px;
                flex: 1;
            }

            .nav-sidebar-menu a {
                padding: 8px 12px;
                font-size: 12px;
            }

            .nav-sidebar-menu span {
                display: none;
            }

            .app {
                margin-left: 0;
            }
        }
    </style>
</head>

<body></body>

</html>

<!-- Navigation Sidebar -->
<nav class="nav-sidebar" id="navSidebar">
    <!-- School Profile Header -->
    <div class="school-profile-header">
        <!-- School Photo -->
        <div class="school-photo-wrapper">
            <?php if ($school_photo && file_exists(__DIR__ . '/../../' . $school_photo)): ?>
                <img src="<?php echo $base; ?>/../<?php echo htmlspecialchars($school_photo); ?>"
                    alt="<?php echo htmlspecialchars($sidebarSchool['name'] ?? 'School Logo'); ?>" class="school-photo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="school-photo-placeholder" style="display: none;">
                    <iconify-icon icon="mdi:school-outline"></iconify-icon>
                </div>
            <?php else: ?>
                <div class="school-photo-placeholder">
                    <iconify-icon icon="mdi:school-outline"></iconify-icon>
                </div>
            <?php endif; ?>
        </div>

        <!-- School Name -->
        <?php if ($sidebarSchool): ?>
            <h3 class="school-name"><?php echo htmlspecialchars($sidebarSchool['name']); ?></h3>

            <!-- School Info -->
            <div class="school-info">
                <?php if ($school_email): ?>
                    <div class="school-info-item">
                        <iconify-icon icon="mdi:email-outline"></iconify-icon>
                        <span><?php echo htmlspecialchars($school_email); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($school_npsn): ?>
                    <div class="school-info-item">
                        <iconify-icon icon="mdi:identifier"></iconify-icon>
                        <span><?php echo htmlspecialchars($school_npsn); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Profile Button (Admin only) -->
            <?php if ($user && $user['role'] === 'admin'): ?>
                <a href="<?php echo $base; ?>/settings.php#school-profile" class="edit-profile-btn">
                    <iconify-icon icon="mdi:pencil"></iconify-icon>
                    Edit
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Navigation Menu -->
    <a href="<?php echo $base; ?>/" class="nav-sidebar-header" style="display: none;">
        <div class="nav-sidebar-header-icon">
            <iconify-icon icon="mdi:library"></iconify-icon>
        </div>
        <h2>Perpustakaan</h2>
    </a>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="<?php echo $base; ?>/" class="nav-link<?php echo _is_active_sidebar($base . '/', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:view-dashboard"></iconify-icon>
                </span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/books.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/books.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:book-multiple"></iconify-icon>
                </span>
                <span>Buku</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/book-maintenance.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/book-maintenance.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:wrench"></iconify-icon>
                </span>
                <span>Pemeliharaan</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/members.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/members.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:account-multiple"></iconify-icon>
                </span>
                <span>Anggota</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/student-barcodes.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/student-barcodes.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:qrcode"></iconify-icon>
                </span>
                <span>Barcode Anggota</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/generate-barcode.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/generate-barcode.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                </span>
                <span>Barcode Buku</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/borrows.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/borrows.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                </span>
                <span>Peminjaman</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/returns.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/returns.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:keyboard-return"></iconify-icon>
                </span>
                <span>Pengembalian</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/reports.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/reports.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:chart-line"></iconify-icon>
                </span>
                <span>Laporan</span>
            </a>
        </li>
        <?php if ($user): ?>
            <li>
                <div class="nav-sidebar-divider"></div>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li>
                <a href="<?php echo $base; ?>/settings.php"
                    class="nav-link<?php echo _is_active_sidebar($base . '/settings.php', $current); ?>">
                    <span class="nav-sidebar-menu-icon">
                        <iconify-icon icon="mdi:cog"></iconify-icon>
                    </span>
                    <span>Pengaturan</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="<?php echo $base; ?>/logout.php" class="nav-link">
                    <span class="nav-sidebar-menu-icon">
                        <iconify-icon icon="mdi:logout"></iconify-icon>
                    </span>
                    <span>Logout</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<script>
    // Tampilkan animasi sidebar hanya di kunjungan pertama
    document.addEventListener('DOMContentLoaded', function () {
        const navSidebar = document.getElementById('navSidebar');
        const isFirstVisit = !sessionStorage.getItem('adminSidebarAnimated');

        if (!isFirstVisit) {
            // Jika bukan kunjungan pertama, hapus animasi
            const allElements = navSidebar.querySelectorAll('*');
            navSidebar.style.animation = 'none';
            allElements.forEach(el => {
                el.style.animation = 'none';
            });
        }

        // Tandai bahwa sidebar sudah ditampilkan
        sessionStorage.setItem('adminSidebarAnimated', 'true');
    });

    // Make sidebar links respond to active page
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.classList.contains('active')) {
            link.setAttribute('aria-current', 'page');
        }
    });
</script>