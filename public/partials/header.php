<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();
$user = $_SESSION['user'] ?? null;
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/perpustakaan-online/public';

// Special Theme Check
$specialTheme = null;
if ($user && isset($user['school_id'])) {
    require_once __DIR__ . '/../../src/ThemeModel.php';
    $themeModel = new ThemeModel($pdo ?? (require __DIR__ . '/../../src/db.php'));
    $specialTheme = $themeModel->checkSpecialTheme($user['school_id']);
}

function _is_active($path, $current)
{
  $current = rtrim(str_replace('/perpustakaan-online/public', '', $current), '/') ?: '/';
  $path = rtrim(str_replace('/perpustakaan-online/public', '', $path), '/') ?: '/';
  return $current === $path ? ' active' : '';
}
?>
<!DOCTYPE html>
<html>

<head>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <?php require_once __DIR__ . '/../../theme-loader.php'; ?>
</head>

<body></body>

</html>

<!-- Header -->
<header class="header">
  <div class="header-container">
    <div class="header-brand">
      <div class="header-brand-icon">
        <iconify-icon icon="mdi:library"></iconify-icon>
      </div>
      <div class="header-brand-text">
        <h2>Perpustakaan</h2>
        <p>Admin Dashboard</p>
      </div>
    </div>

    <div class="header-user">
      <div class="header-user-info">
        <p class="name"><?php echo htmlspecialchars($user['name'] ?? 'Admin'); ?></p>
        <p class="role">Administrator</p>
      </div>
      <div class="header-user-avatar">
        <?php echo htmlspecialchars(strtoupper(substr($user['name'] ?? 'A', 0, 1))); ?>
      </div>
      <a href="<?php echo $base; ?>/logout.php" class="header-logout">
        <iconify-icon icon="mdi:logout"></iconify-icon>
        Logout
      </a>
    </div>
  </div>
</header>

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

  /* Header */
  .header {
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 16px 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    animation: slideDown 0.6s ease-out;
    margin-left: 240px;
  }

  .header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
  }

  .header-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--text);
  }

  .header-brand-icon {
    font-size: 32px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--accent-light);
    border-radius: 8px;
  }

  .header-brand-icon iconify-icon {
    width: 32px;
    height: 32px;
    color: var(--accent);
  }

  .header-brand-text h2 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
  }

  .header-brand-text p {
    font-size: 12px;
    color: var(--muted);
    margin: 2px 0 0 0;
  }

  .header-user {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .header-user-info {
    text-align: right;
  }

  .header-user-info p {
    font-size: 13px;
    margin: 0;
  }

  .header-user-info .name {
    font-weight: 600;
    color: var(--text);
  }

  .header-user-info .role {
    color: var(--muted);
    font-size: 12px;
  }

  .header-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 16px;
  }

  .header-logout {
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--bg);
    color: var(--danger);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .header-logout:hover {
    background: rgba(239, 68, 68, 0.08);
    border-color: var(--danger);
    color: var(--danger);
  }

  .header-logout:active {
    transform: scale(0.98);
  }

  .header-logout iconify-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }
</style>

<script>
  // Tampilkan animasi header hanya di kunjungan pertama
  document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('.header');
    const isFirstVisit = !sessionStorage.getItem('adminHeaderAnimated');

    if (!isFirstVisit) {
      // Jika bukan kunjungan pertama, hapus animasi
      const allElements = header.querySelectorAll('*');
      header.style.animation = 'none';
      allElements.forEach(el => {
        el.style.animation = 'none';
      });
    }

    // Tandai bahwa header sudah ditampilkan
    sessionStorage.setItem('adminHeaderAnimated', 'true');
  });
</script>

<style>
  @media (max-width: 768px) {
    .header {
      margin-left: 0;
    }

    .header-container {
      flex-wrap: wrap;
      gap: 12px;
    }

    .header-user-info {
      display: none;
    }

    .header-logout {
      padding: 6px 12px;
      font-size: 12px;
    }
  }
</style>