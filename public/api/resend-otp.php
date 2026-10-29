<?php
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

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? ($_POST['email'] ?? ''));

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email harus diisi']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, u.is_verified, s.name as school_name 
        FROM users u 
        LEFT JOIN schools s ON u.school_id = s.id 
        WHERE u.email = :email 
        LIMIT 1
    ');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Akun dengan email ini tidak ditemukan']);
        exit;
    }

    if ($user['is_verified']) {
        echo json_encode([
            'success' => false,
            'message' => 'Akun sudah terverifikasi. Silakan langsung login.'
        ]);
        exit;
    }

    // Generate new verification code
    $verification_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $pdo->prepare('UPDATE users SET verification_code = :code, code_expires_at = :expires WHERE id = :id');
    $stmt->execute([
        'code' => $verification_code,
        'expires' => $expires_at,
        'id' => $user['id']
    ]);

    // Send email
    sendVerificationEmail($email, $user['school_name'] ?? 'Sekolah', $user['name'], $verification_code);

    echo json_encode([
        'success' => true,
        'message' => 'Kode OTP baru telah berhasil dikirim ke email Anda.'
    ]);

} catch (Exception $e) {
    error_log("Resend OTP Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
