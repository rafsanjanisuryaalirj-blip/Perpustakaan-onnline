<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$pdo = require __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$nisn = trim($_POST['nisn'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$user_type = trim($_POST['user_type'] ?? '');
$school_id = !empty($_POST['school_id']) ? (int)$_POST['school_id'] : null;

// Determine if login is for student/member or school admin
if ($user_type === 'student') {
    // Student login with NISN + Password
    if (empty($nisn) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'NISN / ID Anggota dan password harus diisi']);
        exit;
    }

    try {
        if ($school_id) {
            // Isolasi multi-tenant ketat: cari user dengan NISN di sekolah yang spesifik
            $stmt = $pdo->prepare("
                SELECT u.*, s.name as school_name, s.status as school_status 
                FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                WHERE u.school_id = :school_id 
                  AND u.nisn = :nisn 
                  AND u.role IN ('student', 'teacher', 'employee') 
                LIMIT 1
            ");
            $stmt->execute(['school_id' => $school_id, 'nisn' => $nisn]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Fallback jika tidak ada school_id: cek apakah ada satu atau lebih sekolah dengan NISN ini
            $stmt = $pdo->prepare("
                SELECT u.*, s.name as school_name, s.status as school_status 
                FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                WHERE u.nisn = :nisn 
                  AND u.role IN ('student', 'teacher', 'employee')
            ");
            $stmt->execute(['nisn' => $nisn]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($users) > 1) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'NISN/ID ini terdaftar di beberapa sekolah. Harap masuk melalui portal khusus sekolah Anda.'
                ]);
                exit;
            }

            $user = $users[0] ?? null;
        }

        if (!$user) {
            // Cek apakah user tidak sengaja memasukkan email admin di form NISN portal siswa
            if (strpos($nisn, '@') !== false) {
                $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = :email AND role IN ('admin', 'librarian') LIMIT 1");
                $checkAdmin->execute(['email' => $nisn]);
                if ($checkAdmin->fetch()) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Email ini adalah akun Admin/Pengelola Sekolah. Silakan masuk melalui Halaman Login Pengelola.',
                        'is_admin' => true,
                        'admin_url' => 'admin-login.php'
                    ]);
                    exit;
                }
            }

            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'NISN / ID Anggota atau password salah']);
            exit;
        }

        // Cek status sekolah
        if (isset($user['school_status']) && $user['school_status'] === 'rejected') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses perpustakaan sekolah ini dinonaktifkan']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'NISN / ID Anggota atau password salah']);
            exit;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'school_id' => (int)$user['school_id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'nisn' => $user['nisn'],
            'school_name' => $user['school_name'] ?? ''
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect_url' => 'public/student-dashboard.php'
        ]);
        exit;

    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
        exit;
    }
} else {
    // School admin login with email + password
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.*, s.name as school_name, s.status as school_status 
            FROM users u 
            LEFT JOIN schools s ON u.school_id = s.id 
            WHERE u.email = :email 
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
            exit;
        }

        // Cek role: Hanya admin / librarian yang boleh masuk via admin gate
        if (!in_array($user['role'], ['admin', 'librarian'])) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Akun ini adalah akun Siswa/Anggota. Silakan login melalui portal perpustakaan sekolah Anda.'
            ]);
            exit;
        }

        // Check if email verified
        if (isset($user['is_verified']) && (int)$user['is_verified'] === 0) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Silakan verifikasi email Anda terlebih dahulu.',
                'needs_verification' => true,
                'email' => $email,
                'user_id' => $user['id']
            ]);
            exit;
        }

        // Check if school is rejected
        if (isset($user['school_status']) && $user['school_status'] === 'rejected') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akun sekolah Anda dinonaktifkan. Hubungi bantuan teknis.']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
            exit;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'school_id' => (int)$user['school_id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'school_name' => $user['school_name'] ?? ''
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect_url' => 'public/index.php'
        ]);
        exit;

    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
        exit;
    }
}