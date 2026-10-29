<?php

class DamageFineModel
{
    private $pdo;

    // Predefined damage types and their fines
    const DAMAGE_TYPES = [
        'minor_tear' => ['name' => 'Robekan Kecil', 'fine' => 25000],
        'major_tear' => ['name' => 'Robekan Besar', 'fine' => 50000],
        'water_damage' => ['name' => 'Rusak Terkena Air', 'fine' => 100000],
        'stain' => ['name' => 'Noda/Kotoran', 'fine' => 30000],
        'cover_damage' => ['name' => 'Kerusakan Sampul', 'fine' => 40000],
        'spine_damage' => ['name' => 'Kerusakan Tulang Punggung', 'fine' => 35000],
        'missing_pages' => ['name' => 'Halaman Hilang', 'fine' => 150000],
        'other' => ['name' => 'Lainnya', 'fine' => 0]
    ];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get damage types for dropdown (static default)
     */
    public static function getDamageTypes()
    {
        return self::DAMAGE_TYPES;
    }

    /**
     * Get damage types with customized school fines if configured
     */
    public function getSchoolDamageTypes($school_id)
    {
        $types = self::DAMAGE_TYPES;
        if (!$school_id) {
            return $types;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT damage_fines_config FROM schools WHERE id = :id');
            $stmt->execute(['id' => (int) $school_id]);
            $json = $stmt->fetchColumn();
            if ($json) {
                $customConfig = json_decode($json, true);
                if (is_array($customConfig)) {
                    foreach ($types as $key => &$info) {
                        if (isset($customConfig[$key]) && is_numeric($customConfig[$key])) {
                            $info['fine'] = (float) $customConfig[$key];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Fallback gracefully to default
        }

        return $types;
    }

    /**
     * Get fine amount for damage type
     */
    public static function getFineAmount($damage_type)
    {
        return self::DAMAGE_TYPES[$damage_type]['fine'] ?? 0;
    }

    /**
     * Add new damage fine record
     */
    public function addRecord($school_id, $borrow_id, $member_id, $book_id, $damage_type, $damage_description = null, $fine_amount = null)
    {
        // Validate damage type
        if (!array_key_exists($damage_type, self::DAMAGE_TYPES)) {
            throw new Exception("Tipe kerusakan '{$damage_type}' tidak valid");
        }

        // Use provided fine amount or calculate from school's custom configuration or default
        if ($fine_amount === null || $fine_amount === '') {
            $schoolTypes = $this->getSchoolDamageTypes($school_id);
            $fine_amount = $schoolTypes[$damage_type]['fine'] ?? self::getFineAmount($damage_type);
        }

        // Validate borrow record exists and belongs to this school
        $checkStmt = $this->pdo->prepare("SELECT id FROM borrows WHERE id = :borrow_id AND school_id = :school_id");
        $checkStmt->execute(['borrow_id' => (int) $borrow_id, 'school_id' => (int) $school_id]);
        if (!$checkStmt->fetch()) {
            throw new Exception("Peminjaman tidak ditemukan");
        }

        $sql = "INSERT INTO book_damage_fines 
            (school_id, borrow_id, member_id, book_id, damage_type, damage_description, fine_amount, status) 
            VALUES 
            (:school_id, :borrow_id, :member_id, :book_id, :damage_type, :damage_description, :fine_amount, 'pending')";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':school_id' => (int) $school_id,
                ':borrow_id' => (int) $borrow_id,
                ':member_id' => (int) $member_id,
                ':book_id' => (int) $book_id,
                ':damage_type' => $damage_type,
                ':damage_description' => $damage_description ?: null,
                ':fine_amount' => (float) $fine_amount
            ]);
            return $result ? $this->pdo->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("DamageFineModel::addRecord() Error: " . $e->getMessage());
            throw new Exception("Gagal menambah laporan kerusakan: " . $e->getMessage());
        }
    }

    /**
     * Get all damage fine records for a school
     */
    public function getAll($school_id, $limit = null, $offset = 0)
    {
        $sql = "SELECT 
              d.id,
              d.borrow_id,
              d.member_id,
              d.book_id,
              d.damage_type,
              d.damage_description,
              d.fine_amount,
              d.status,
              d.created_at,
              d.updated_at,
              b.title as book_title,
              b.author as book_author,
              m.name as member_name,
              bw.borrowed_at,
              bw.due_at,
              bw.returned_at
            FROM book_damage_fines d
            JOIN books b ON d.book_id = b.id
            JOIN members m ON d.member_id = m.id
            JOIN borrows bw ON d.borrow_id = bw.id
            WHERE d.school_id = :school_id
            ORDER BY d.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['school_id' => (int) $school_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DamageFineModel::getAll() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single damage fine record by ID for a school
     */
    public function getById($school_id, $id)
    {
        $sql = "SELECT 
              d.id,
              d.borrow_id,
              d.member_id,
              d.book_id,
              d.damage_type,
              d.damage_description,
              d.fine_amount,
              d.status,
              d.created_at,
              d.updated_at,
              b.title as book_title,
              b.author as book_author,
              m.name as member_name,
              bw.borrowed_at,
              bw.due_at,
              bw.returned_at
            FROM book_damage_fines d
            JOIN books b ON d.book_id = b.id
            JOIN members m ON d.member_id = m.id
            JOIN borrows bw ON d.borrow_id = bw.id
            WHERE d.id = :id AND d.school_id = :school_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => (int) $id, 'school_id' => (int) $school_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DamageFineModel::getById() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get damage fines for a specific member
     */
    public function getByMember($school_id, $member_id)
    {
        $sql = "SELECT 
              d.id,
              d.borrow_id,
              d.member_id,
              d.book_id,
              d.damage_type,
              d.damage_description,
              d.fine_amount,
              d.status,
              d.created_at,
              b.title as book_title,
              b.author as book_author,
              m.name as member_name
            FROM book_damage_fines d
            JOIN books b ON d.book_id = b.id
            JOIN members m ON d.member_id = m.id
            WHERE d.school_id = :school_id AND d.member_id = :member_id
            ORDER BY d.created_at DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['school_id' => (int) $school_id, 'member_id' => (int) $member_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DamageFineModel::getByMember() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update damage fine record status
     */
    public function updateStatus($school_id, $id, $status)
    {
        // Validate status
        if (!in_array($status, ['pending', 'paid'])) {
            throw new Exception("Status tidak valid. Pilih: pending, paid");
        }

        // Check that record belongs to this school
        $checkStmt = $this->pdo->prepare("SELECT id FROM book_damage_fines WHERE id = :id AND school_id = :school_id");
        $checkStmt->execute(['id' => (int) $id, 'school_id' => (int) $school_id]);
        if (!$checkStmt->fetch()) {
            throw new Exception("Catatan denda tidak ditemukan");
        }

        $sql = "UPDATE book_damage_fines SET status = :status, updated_at = NOW() WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':status' => $status,
                ':id' => (int) $id
            ]);
            return $result ? $stmt->rowCount() : false;
        } catch (Exception $e) {
            error_log("DamageFineModel::updateStatus() Error: " . $e->getMessage());
            throw new Exception("Gagal update status denda: " . $e->getMessage());
        }
    }

    /**
     * Delete damage fine record
     */
    public function deleteRecord($school_id, $id)
    {
        // Check that record belongs to this school
        $checkStmt = $this->pdo->prepare("SELECT id FROM book_damage_fines WHERE id = :id AND school_id = :school_id");
        $checkStmt->execute(['id' => (int) $id, 'school_id' => (int) $school_id]);
        if (!$checkStmt->fetch()) {
            throw new Exception("Catatan denda tidak ditemukan");
        }

        $sql = "DELETE FROM book_damage_fines WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id' => (int) $id]);
            return $result ? $stmt->rowCount() : false;
        } catch (Exception $e) {
            error_log("DamageFineModel::deleteRecord() Error: " . $e->getMessage());
            throw new Exception("Gagal hapus catatan denda: " . $e->getMessage());
        }
    }

    /**
     * Get count of records for a school
     */
    public function getCount($school_id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM book_damage_fines WHERE school_id = :school_id");
            $stmt->execute(['school_id' => (int) $school_id]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("DamageFineModel::getCount() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate total fines for a school
     */
    public function getTotalFines($school_id, $status = null)
    {
        $sql = "SELECT SUM(fine_amount) as total FROM book_damage_fines WHERE school_id = :school_id";

        if ($status) {
            $sql .= " AND status = :status";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $params = ['school_id' => (int) $school_id];
            if ($status) {
                $params['status'] = $status;
            }
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("DamageFineModel::getTotalFines() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate total fines by member for a school
     */
    public function getFinesByMember($school_id)
    {
        $sql = "SELECT 
              m.id,
              m.name,
              COUNT(d.id) as damage_count,
              SUM(d.fine_amount) as total_fine,
              SUM(CASE WHEN d.status = 'paid' THEN d.fine_amount ELSE 0 END) as paid_amount,
              SUM(CASE WHEN d.status = 'pending' THEN d.fine_amount ELSE 0 END) as pending_amount
            FROM book_damage_fines d
            RIGHT JOIN members m ON d.member_id = m.id
            WHERE m.school_id = :school_id
            GROUP BY m.id, m.name
            ORDER BY total_fine DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['school_id' => (int) $school_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DamageFineModel::getFinesByMember() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active borrows for damage reporting
     */
    public function getActiveBorrows($school_id, $member_id = null)
    {
        $sql = "SELECT 
              b.id,
              b.member_id,
              b.book_id,
              b.borrowed_at,
              b.due_at,
              b.status,
              bk.title as book_title,
              bk.author as book_author,
              m.name as member_name
            FROM borrows b
            JOIN books bk ON b.book_id = bk.id
            JOIN members m ON b.member_id = m.id
            WHERE bk.school_id = :school_id AND (
                b.status IN ('borrowed', 'pending_return') 
                OR (b.status = 'returned' AND b.returned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))
            )";

        if ($member_id) {
            $sql .= " AND b.member_id = :member_id";
        }

        $sql .= " ORDER BY b.borrowed_at DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $params = ['school_id' => (int) $school_id];
            if ($member_id) {
                $params['member_id'] = (int) $member_id;
            }
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DamageFineModel::getActiveBorrows() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if damage already reported for a borrow
     */
    public function damageExists($school_id, $borrow_id)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM book_damage_fines WHERE school_id = :school_id AND borrow_id = :borrow_id");
        $stmt->execute(['school_id' => (int) $school_id, 'borrow_id' => (int) $borrow_id]);
        return $stmt->fetch() ? true : false;
    }
}
?>