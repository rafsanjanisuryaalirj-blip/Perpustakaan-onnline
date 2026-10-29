<?php
/**
 * API / Action to mark late fine as paid
 */
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/NotificationsHelper.php';

$user = $_SESSION['user'];
$sid = (int) ($user['school_id'] ?? 0);

$borrowId = (int) ($_POST['borrow_id'] ?? $_GET['id'] ?? 0);
$memberId = (int) ($_POST['member_id'] ?? $_GET['member_id'] ?? 0);
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
          ($_SERVER['REQUEST_METHOD'] === 'POST');

try {
    if ($borrowId > 0) {
        $stmt = $pdo->prepare('
            SELECT b.*, bk.title, m.name as member_name, u.id as student_real_id 
            FROM borrows b 
            JOIN books bk ON b.book_id = bk.id 
            JOIN members m ON b.member_id = m.id 
            LEFT JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id 
            WHERE b.id = :id AND b.school_id = :sid
        ');
        $stmt->execute(['id' => $borrowId, 'sid' => $sid]);
        $borrow = $stmt->fetch();

        if (!$borrow) {
            throw new Exception('Data peminjaman tidak ditemukan');
        }

        $stmt = $pdo->prepare('UPDATE borrows SET fine_status = "paid" WHERE id = :id AND school_id = :sid');
        $stmt->execute(['id' => $borrowId, 'sid' => $sid]);

        if (!empty($borrow['student_real_id'])) {
            $notifHelper = new NotificationsHelper($pdo);
            $fineFormatted = 'Rp ' . number_format($borrow['fine_amount'], 0, ',', '.');
            $notifHelper->createNotification(
                $sid,
                $borrow['student_real_id'],
                'success',
                'Denda Lunas',
                'Denda keterlambatan buku "' . htmlspecialchars($borrow['title']) . '" sebesar ' . $fineFormatted . ' telah dinyatakan lunas oleh petugas perpustakaan.'
            );
        }

        $msg = 'Denda sebesar Rp ' . number_format($borrow['fine_amount'], 0, ',', '.') . ' untuk ' . htmlspecialchars($borrow['member_name']) . ' berhasil ditandai Lunas!';
        $_SESSION['success'] = $msg;

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }

        $redirect = $_GET['redirect'] ?? '../borrows.php';
        header("Location: $redirect");
        exit;
    } elseif ($memberId > 0) {
        $stmt = $pdo->prepare('
            SELECT b.*, m.name as member_name, u.id as student_real_id 
            FROM borrows b 
            JOIN members m ON b.member_id = m.id 
            LEFT JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id 
            WHERE b.member_id = :mid AND b.school_id = :sid AND b.fine_amount > 0 AND b.fine_status = "unpaid"
        ');
        $stmt->execute(['mid' => $memberId, 'sid' => $sid]);
        $unpaidBorrows = $stmt->fetchAll();

        if (!$unpaidBorrows) {
            throw new Exception('Tidak ada denda tertunda untuk anggota ini');
        }

        $totalPaid = array_sum(array_column($unpaidBorrows, 'fine_amount'));
        $memberName = $unpaidBorrows[0]['member_name'];
        $studentRealId = $unpaidBorrows[0]['student_real_id'];

        $update = $pdo->prepare('UPDATE borrows SET fine_status = "paid" WHERE member_id = :mid AND school_id = :sid AND fine_status = "unpaid"');
        $update->execute(['mid' => $memberId, 'sid' => $sid]);

        if (!empty($studentRealId)) {
            $notifHelper = new NotificationsHelper($pdo);
            $fineFormatted = 'Rp ' . number_format($totalPaid, 0, ',', '.');
            $notifHelper->createNotification(
                $sid,
                $studentRealId,
                'success',
                'Seluruh Denda Lunas',
                'Seluruh denda keterlambatan Anda sebesar ' . $fineFormatted . ' telah dinyatakan lunas oleh petugas perpustakaan.'
            );
        }

        $msg = 'Seluruh denda keterlambatan sebesar Rp ' . number_format($totalPaid, 0, ',', '.') . ' untuk ' . htmlspecialchars($memberName) . ' berhasil dilunasi!';
        $_SESSION['success'] = $msg;

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }

        $redirect = $_GET['redirect'] ?? '../borrows.php';
        header("Location: $redirect");
        exit;
    } else {
        throw new Exception('Parameter ID tidak diberikan');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    $redirect = $_GET['redirect'] ?? '../borrows.php';
    header("Location: $redirect");
    exit;
}
