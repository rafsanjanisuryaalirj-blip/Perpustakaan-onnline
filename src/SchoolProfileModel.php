<?php
/**
 * SchoolProfileModel
 * Mengelola data profil sekolah dan foto profil
 */

class SchoolProfileModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get school profile by school_id
     */
    public function getSchoolProfile($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT id, name, slug, email, phone, address, 
                   npsn, website, founded_year, photo_path, status,
                   borrow_duration, late_fine, max_books,
                   max_books_student, max_books_teacher, max_books_employee,
                   scan_access_key, custom_base_url, damage_fines_config
            FROM schools 
            WHERE id = :id
        ');
        $stmt->execute(['id' => $school_id]);
        return $stmt->fetch();
    }

    /**
     * Get scan access key
     */
    public function getScanAccessKey($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT scan_access_key FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Generate or Reset scan access key
     */
    public function resetScanAccessKey($school_id)
    {
        $new_key = bin2hex(random_bytes(16)); // 32 chars
        $stmt = $this->pdo->prepare('UPDATE schools SET scan_access_key = :key WHERE id = :id');
        $stmt->execute(['key' => $new_key, 'id' => $school_id]);
        return $new_key;
    }

    /**
     * Get damage fines configuration for a school
     */
    public function getDamageFinesConfig($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT damage_fines_config FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $configJson = $stmt->fetchColumn();
        if ($configJson) {
            $decoded = json_decode($configJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Update school profile data
     */
    public function updateSchoolProfile($school_id, $data)
    {
        // Allowed fields to update
        $allowed = [
            'name', 'email', 'phone', 'address', 'npsn', 'website', 'founded_year',
            'borrow_duration', 'late_fine', 'max_books',
            'max_books_student', 'max_books_teacher', 'max_books_employee',
            'custom_base_url', 'damage_fines_config'
        ];
        $updates = [];
        $params = ['id' => $school_id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $updates[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = 'UPDATE schools SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update school photo path
     */
    public function updateSchoolPhoto($school_id, $photo_path)
    {
        $stmt = $this->pdo->prepare('UPDATE schools SET photo_path = :path WHERE id = :id');
        return $stmt->execute(['path' => $photo_path, 'id' => $school_id]);
    }

    /**
     * Get school photo path
     */
    public function getSchoolPhoto($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT photo_path FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $result = $stmt->fetch();
        return $result['photo_path'] ?? null;
    }

    /**
     * Delete school photo
     */
    public function deleteSchoolPhoto($school_id)
    {
        // Get current photo path
        $photo_path = $this->getSchoolPhoto($school_id);

        if ($photo_path) {
            // Delete file from server
            $file_path = __DIR__ . '/../public/uploads/school-photos/' . basename($photo_path);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Update database
        $stmt = $this->pdo->prepare('UPDATE schools SET photo_path = NULL WHERE id = :id');
        return $stmt->execute(['id' => $school_id]);
    }

    /**
     * Validate photo file
     * Returns array with 'valid' => bool, 'error' => string (if invalid)
     */
    public function validatePhotoFile($file)
    {
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        // Check if file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'File tidak ditemukan'];
        }

        // Check file size
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'error' => 'Ukuran file terlalu besar (maksimal 5MB)'];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mimes)) {
            return ['valid' => false, 'error' => 'Format file tidak didukung (JPG, PNG, WEBP)'];
        }

        // Check extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            return ['valid' => false, 'error' => 'Ekstensi file tidak valid'];
        }

        return ['valid' => true];
    }

    /**
     * Save photo file
     * Returns filename or false if failed
     */
    public function savePhotoFile($file)
    {
        // Validate file
        $validation = $this->validatePhotoFile($file);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        // Create uploads directory if not exists
        $upload_dir = __DIR__ . '/../public/uploads/school-photos';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'school_' . time() . '_' . uniqid() . '.' . $ext;
        $file_path = $upload_dir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception('Gagal mengunggah file');
        }

        // Optimize image (resize if needed)
        $this->optimizeImage($file_path);

        return $filename;
    }

    /**
     * Optimize image file
     * Resize and compress image
     */
    private function optimizeImage($file_path)
    {
        // This is a simple implementation
        // For production, use ImageMagick or GD library to optimize
        // For now, just ensure the image is readable
        if (!is_readable($file_path)) {
            throw new Exception('File tidak dapat dibaca');
        }
    }
}
