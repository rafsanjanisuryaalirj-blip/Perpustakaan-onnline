<?php
require_once __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$schoolId = (int)$_SESSION['user']['school_id'];
$schoolLateFineStmt = $pdo->prepare("SELECT late_fine FROM schools WHERE id = ?");
$schoolLateFineStmt->execute([$schoolId]);
$lateFineVal = $schoolLateFineStmt->fetchColumn();
$per_day = ($lateFineVal !== false && $lateFineVal !== null) ? (float) $lateFineVal : 1000;

try {
    $statusFilter = $_GET['status'] ?? null;
    $sql = "
        SELECT br.id, br.borrowed_at, br.due_at, br.returned_at, br.fine_amount, br.fine_status,
               b.title, m.name as member_name, m.nisn
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE b.school_id = ? 
          AND br.due_at IS NOT NULL 
          AND (br.returned_at IS NOT NULL OR CURRENT_DATE() > br.due_at)
    ";
    if ($statusFilter === 'paid') {
        $sql .= " AND br.fine_status = 'paid'";
    } elseif ($statusFilter === 'pending' || $statusFilter === 'unpaid') {
        $sql .= " AND (br.fine_status = 'unpaid' OR br.fine_status IS NULL)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schoolId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $due = new DateTime($r['due_at']);
        $returned = $r['returned_at'] ? new DateTime($r['returned_at']) : new DateTime();
        $diff = (int) $due->diff($returned)->format('%r%a');
        if ($diff > 0) {
            $r['fine_amount'] = (isset($r['fine_amount']) && (float)$r['fine_amount'] > 0) 
                                ? (float)$r['fine_amount'] 
                                : ($diff * $per_day);
            $r['days_late'] = $diff;
            $r['fine_status'] = (!empty($r['fine_status']) && $r['fine_status'] === 'paid') ? 'paid' : 'unpaid';
            $data[] = $r;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
