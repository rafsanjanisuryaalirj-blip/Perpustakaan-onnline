<?php

require_once __DIR__ . '/DamageFineModel.php';

class DamageController
{
    private $model;
    private $school_id;

    public function __construct($pdo, $school_id)
    {
        $this->model = new DamageFineModel($pdo);
        $this->school_id = (int) $school_id;
    }

    /**
     * Get all damage fine records for the school
     */
    public function getAll($limit = null, $offset = 0)
    {
        return $this->model->getAll($this->school_id, $limit, $offset);
    }

    /**
     * Get a single damage fine record by ID for this school
     */
    public function getById($id)
    {
        return $this->model->getById($this->school_id, $id);
    }

    /**
     * Get damage fines for a specific member
     */
    public function getByMember($member_id)
    {
        return $this->model->getByMember($this->school_id, $member_id);
    }

    /**
     * Add new damage fine report for this school
     */
    public function addRecord($borrow_id, $member_id, $book_id, $damage_type, $damage_description = null, $fine_amount = null)
    {
        try {
            // Check if damage already reported
            if ($this->model->damageExists($this->school_id, $borrow_id)) {
                return [
                    'success' => false,
                    'message' => 'Kerusakan untuk peminjaman ini sudah dilaporkan sebelumnya'
                ];
            }

            $id = $this->model->addRecord(
                $this->school_id,
                $borrow_id,
                $member_id,
                $book_id,
                $damage_type,
                $damage_description,
                $fine_amount
            );
            return [
                'success' => true,
                'message' => 'Laporan kerusakan berhasil ditambahkan',
                'id' => $id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update damage fine status
     */
    public function updateStatus($id, $status)
    {
        try {
            $result = $this->model->updateStatus($this->school_id, $id, $status);
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Status denda berhasil diupdate'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data yang diupdate'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete damage fine record for this school
     */
    public function deleteRecord($id)
    {
        try {
            $result = $this->model->deleteRecord($this->school_id, $id);
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Catatan denda berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Catatan tidak ditemukan'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get count of records for this school
     */
    public function getCount()
    {
        return $this->model->getCount($this->school_id);
    }

    /**
     * Get total fines for this school
     */
    public function getTotalFines($status = null)
    {
        return $this->model->getTotalFines($this->school_id, $status);
    }

    /**
     * Get total fines by member
     */
    public function getFinesByMember()
    {
        return $this->model->getFinesByMember($this->school_id);
    }

    /**
     * Get active borrows for damage reporting
     */
    public function getActiveBorrows($member_id = null)
    {
        return $this->model->getActiveBorrows($this->school_id, $member_id);
    }

    /**
     * Get damage types with fine amounts (taking into account school customizations)
     */
    public function getDamageTypes()
    {
        return $this->model->getSchoolDamageTypes($this->school_id);
    }

    /**
     * Handle AJAX requests
     */
    public function handleAjax()
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $action = $_POST['action'] ?? $_GET['action'] ?? null;

        if (!$action) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak didefinisikan']);
            exit;
        }

        try {
            switch ($action) {
                case 'add':
                    $borrow_id = $_POST['borrow_id'] ?? null;
                    $member_id = $_POST['member_id'] ?? null;
                    $book_id = $_POST['book_id'] ?? null;
                    $damage_type = $_POST['damage_type'] ?? null;
                    $damage_description = $_POST['damage_description'] ?? null;
                    $fine_amount = $_POST['fine_amount'] ?? null;

                    if (!$borrow_id || !$member_id || !$book_id || !$damage_type) {
                        throw new Exception('Borrow ID, Member ID, Book ID, dan Tipe Kerusakan harus diisi');
                    }

                    $result = $this->addRecord($borrow_id, $member_id, $book_id, $damage_type, $damage_description, $fine_amount);
                    echo json_encode($result);
                    break;

                case 'update_status':
                    $id = $_POST['id'] ?? null;
                    $status = $_POST['status'] ?? null;

                    if (!$id || !$status) {
                        throw new Exception('ID dan Status harus diisi');
                    }

                    $result = $this->updateStatus($id, $status);
                    echo json_encode($result);
                    break;

                case 'delete':
                    $id = $_POST['id'] ?? null;

                    if (!$id) {
                        throw new Exception('ID harus diisi');
                    }

                    $result = $this->deleteRecord($id);
                    echo json_encode($result);
                    break;

                case 'get':
                    $id = $_GET['id'] ?? null;
                    $member_id = $_GET['member_id'] ?? null;

                    if ($id) {
                        $data = $this->getById($id);
                    } elseif ($member_id) {
                        $data = $this->getByMember($member_id);
                    } else {
                        $data = $this->getAll();
                    }

                    echo json_encode(['success' => true, 'data' => $data]);
                    break;

                case 'get_active_borrows':
                    $member_id = $_GET['member_id'] ?? null;
                    $borrows = $this->getActiveBorrows($member_id);
                    echo json_encode(['success' => true, 'data' => $borrows]);
                    break;

                case 'get_damage_types':
                    $types = $this->getDamageTypes();
                    echo json_encode(['success' => true, 'data' => $types]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }
}
?>