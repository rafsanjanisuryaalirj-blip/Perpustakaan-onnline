<?php
session_start();
header('Content-Type: application/json');

try {
    $pdo = require __DIR__ . '/../../src/db.php';
    require __DIR__ . '/../../src/EmailHelper.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = trim($_POST['user_id'] ?? '');
$verification_code = trim($_POST['verification_code'] ?? '');

if (empty($user_id) || empty($verification_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID dan kode verifikasi harus diisi']);
    exit;
}

try {
    // 1. Get user data
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Akun tidak ditemukan']);
        exit;
    }

    // 2. Check if already verified
    if ($user['is_verified']) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'school_id' => (int)$user['school_id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Email sudah diverifikasi. Mengalihkan ke dashboard...',
            'redirect_url' => 'public/index.php'
        ]);
        exit;
    }

    // 3. Verify code
    if ($user['verification_code'] !== $verification_code) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kode verifikasi tidak sesuai']);
        exit;
    }

    // 4. Check expiry
    if (isVerificationCodeExpired($user['code_expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kode verifikasi sudah kadaluarsa. Silakan klik kirim ulang.']);
        exit;
    }

    // 5. Activate user and school
    $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verified_at = NOW(), verification_code = NULL WHERE id = :id');
    $stmt->execute(['id' => $user_id]);

    $stmtSchool = $pdo->prepare('UPDATE schools SET status = "active" WHERE id = :school_id');
    $stmtSchool->execute(['school_id' => $user['school_id']]);

    // 6. Set Session (Auto Login)
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'school_id' => (int)$user['school_id'],
        'name' => $user['name'],
        'role' => $user['role']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Verifikasi berhasil! Selamat datang di Perpustakaan Digital.',
        'redirect_url' => 'public/index.php'
    ]);

} catch (Exception $e) {
    error_log("Verification Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}
