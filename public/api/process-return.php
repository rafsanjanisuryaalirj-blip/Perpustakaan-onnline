<?php
/**
 * Process Return
 * Menangani pengembalian buku: update status borrows, kembalikan stok buku, hitung denda.
 */

header('Content-Type: application/json');

require __DIR__ . '/../../src/auth.php';
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$barcode = trim($input['barcode'] ?? '');

if (!$barcode) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Barcode tidak boleh kosong']);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';
    $user = $_SESSION['user'];
    $sid = $user['school_id'];

    $pdo->beginTransaction();

    // 1. Cari buku by barcode (ISBN) atau ID
    $bookStmt = $pdo->prepare(
        'SELECT id, title, author, isbn, copies FROM books 
         WHERE (isbn = :isbn OR id = :id) AND school_id = :sid'
    );
    $bookStmt->execute(['isbn' => $barcode, 'id' => $barcode, 'sid' => $sid]);
    $book = $bookStmt->fetch();

    if (!$book) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan di database sekolah ini']);
        exit;
    }

    // 2. Cari peminjaman aktif untuk buku tsb
    // Ambil yang paling lama dipinjam (DESC by borrowed_at ASC/LIMIT 1)
    $borrowStmt = $pdo->prepare(
        'SELECT b.*, m.name as member_name, m.nisn
         FROM borrows b
         JOIN members m ON b.member_id = m.id
         WHERE b.book_id = :book_id AND b.school_id = :sid 
         AND b.status IN ("borrowed", "overdue", "pending_return")
         ORDER BY b.borrowed_at ASC LIMIT 1'
    );
    $borrowStmt->execute(['book_id' => $book['id'], 'sid' => $sid]);
    $borrow = $borrowStmt->fetch();

    if (!$borrow) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Buku ini tidak tercatat sedang dipinjam']);
        exit;
    }

    // 3. Hitung Denda (jika telat)
    $fineAmount = 0;
    $lateDays = 0;
    
    if ($borrow['due_at']) {
        $dueDate = strtotime($borrow['due_at']);
        $today = time();
        
        if ($today > $dueDate) {
            $lateSeconds = $today - $dueDate;
            $lateDays = ceil($lateSeconds / (60 * 60 * 24));
            
            // Ambil late_fine dari sekolah
            $schoolStmt = $pdo->prepare('SELECT late_fine FROM schools WHERE id = ?');
            $schoolStmt->execute([$sid]);
            $school = $schoolStmt->fetch();
            $finePerDay = isset($school['late_fine']) ? (float) $school['late_fine'] : 1000;
            $fineAmount = $lateDays * $finePerDay;
        }
    }

    // 4. Update Borrows
    $updateBorrow = $pdo->prepare(
        'UPDATE borrows SET 
            status = "returned", 
            returned_at = NOW(), 
            fine_amount = :fine,
            fine_status = "unpaid" 
         WHERE id = :id'
    );
    $updateBorrow->execute([
        'fine' => $fineAmount,
        'id' => $borrow['id']
    ]);

    // 5. Update Stock (Increase copies)
    $updateStock = $pdo->prepare('UPDATE books SET copies = copies + 1 WHERE id = ?');
    $updateStock->execute([$book['id']]);

    // 6. Waitlist Notification Logic
    // Check if there are students waiting for this book title/author
    $waitlistStmt = $pdo->prepare(
        'SELECT w.*, u.id as student_real_id 
         FROM waitlist w
         JOIN members m ON w.member_id = m.id
         JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id
         WHERE w.school_id = :sid 
         AND w.book_title = :title 
         AND w.book_author = :author 
         AND w.status = "pending"
         ORDER BY w.created_at ASC'
    );
    $waitlistStmt->execute([
        'sid' => $sid,
        'title' => trim($book['title']),
        'author' => trim($book['author'])
    ]);
    
    $waitingStudents = $waitlistStmt->fetchAll();

    if ($waitingStudents) {
        require_once __DIR__ . '/../../src/NotificationsHelper.php';
        $notifHelper = new NotificationsHelper($pdo);
        
        // Notify the first person in line
        $firstStudent = $waitingStudents[0];
        
        $notifHelper->createNotification(
            $sid,
            $firstStudent['student_real_id'],
            'info',
            'Buku Tersedia!',
            'Buku "' . htmlspecialchars($book['title']) . '" yang Anda tunggu sudah tersedia. Segera lakukan peminjaman!'
        );
        
        // Mark as notified
        $updateWaitlist = $pdo->prepare('UPDATE waitlist SET status = "notified" WHERE id = ?');
        $updateWaitlist->execute([$firstStudent['id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Buku berhasil dikembalikan',
        'data' => [
            'book_title' => $book['title'],
            'member_name' => $borrow['member_name'],
            'member_nisn' => $borrow['nisn'],
            'fine_amount' => $fineAmount,
            'late_days' => $lateDays,
            'returned_at' => date('Y-m-d H:i:s'),
            'borrow_id' => $borrow['id'],
            'member_id' => $borrow['member_id'],
            'book_id' => $book['id']
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
