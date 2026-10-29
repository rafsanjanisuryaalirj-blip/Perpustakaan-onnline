<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

ob_start();

try {
    $pdo = require __DIR__ . '/../../src/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    require __DIR__ . '/../../src/EmailHelper.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'EmailHelper load failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$school_name = trim($_POST['school_name'] ?? '');
$npsn = trim($_POST['npsn'] ?? '');
$admin_name = trim($_POST['admin_name'] ?? '');
$admin_email = trim($_POST['admin_email'] ?? '');
$admin_password = $_POST['admin_password'] ?? '';

if (empty($school_name) || empty($admin_name) || empty($admin_email) || empty($admin_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

if (strlen($school_name) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nama sekolah minimal 3 karakter']);
    exit;
}

// Validasi NPSN jika diisi (harus 8 digit angka)
if (!empty($npsn)) {
    if (!preg_match('/^[0-9]{8}$/', $npsn)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'NPSN harus berupa 8 digit angka']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM schools WHERE npsn = :npsn');
    $stmt->execute(['npsn' => $npsn]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'NPSN ini sudah terdaftar']);
        exit;
    }
}

if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?sch\.id$/i', $admin_email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Pendaftaran sekolah wajib menggunakan email resmi berdomain @sch.id (contoh: admin@namasekolah.sch.id)'
    ]);
    exit;
}

if (strlen($admin_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $admin_email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email ini sudah terdaftar sebagai pengguna']);
        exit;
    }

    // Generate smart unique slug
    $base_slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school_name)));
    $base_slug = trim($base_slug, '-');
    if (empty($base_slug)) {
        $base_slug = 'sekolah-' . time();
    }

    $slug = $base_slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM schools WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        $counter++;
        $slug = $base_slug . '-' . $counter;
    }

    // Create school
    $stmt = $pdo->prepare('INSERT INTO schools (name, slug, npsn, status) VALUES (:name, :slug, :npsn, "pending")');
    $stmt->execute([
        'name' => $school_name, 
        'slug' => $slug,
        'npsn' => !empty($npsn) ? $npsn : null
    ]);
    $school_id = $pdo->lastInsertId();

    // Generate verification code
    $verification_code = generateVerificationCode();
    $code_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Create admin user dengan status is_verified = 0
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (school_id, name, email, password, verification_code, code_expires_at, is_verified, role) 
         VALUES (:school_id, :name, :email, :password, :verification_code, :code_expires_at, 0, "admin")'
    );
    $stmt->execute([
        'school_id' => $school_id,
        'name' => $admin_name,
        'email' => $admin_email,
        'password' => $password_hash,
        'verification_code' => $verification_code,
        'code_expires_at' => $code_expires_at
    ]);
    $user_id = $pdo->lastInsertId();

    // Send verification email via SMTP (and auto-logs to logs/email_debug.log)
    sendVerificationEmail($admin_email, $school_name, $admin_name, $verification_code);

    echo json_encode([
        'success' => true,
        'message' => 'Pendaftaran berhasil! Kode verifikasi telah dikirim ke email Anda (' . htmlspecialchars($admin_email) . ').',
        'user_id' => $user_id,
        'email' => $admin_email,
        'slug' => $slug
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    $message = $e->getMessage();
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $message]);
    exit;
}