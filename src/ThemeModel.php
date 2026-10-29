<?php
/**
 * ThemeModel
 * Mengelola tema sekolah untuk multi-tenant system
 * Fetch tema berdasarkan school_id
 */

class ThemeModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ambil tema sekolah berdasarkan school_id
     * @param int $school_id
     * @return array|null
     */
    public function getSchoolTheme($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT id, theme_name, custom_colors, typography
            FROM school_themes
            WHERE school_id = :school_id
            LIMIT 1
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();

        // Default theme jika belum ada
        if (!$result) {
            return [
                'theme_name' => 'light',
                'custom_colors' => null,
                'typography' => null
            ];
        }

        return $result;
    }

    /**
     * Ambil tema by school_id dengan format siap pakai
     * @param int $school_id
     * @return array
     */
    public function getThemeData($school_id)
    {
        $theme = $this->getSchoolTheme($school_id);

        return [
            'theme_name' => $theme['theme_name'] ?? 'light',
            'custom_colors' => json_decode($theme['custom_colors'] ?? '{}', true),
            'typography' => json_decode($theme['typography'] ?? '{}', true)
        ];
    }

    /**
     * Simpan tema untuk sekolah
     * @param int $school_id
     * @param string $theme_name
     * @param array|null $custom_colors
     * @param array|null $typography
     * @return bool
     */
    public function saveSchoolTheme($school_id, $theme_name, $custom_colors = null, $typography = null)
    {
        // Mutlak: saat tema dasar diaktifkan/disimpan, semua tema event otomatis dimatikan!
        $this->deactivateAllSpecialThemes($school_id);

        // Fetch current values to preserve if null passed
        $current = $this->getSchoolTheme($school_id);
        
        $colors_json = ($custom_colors !== null) ? json_encode($custom_colors) : ($current['custom_colors'] ?? null);
        $typo_json = ($typography !== null) ? json_encode($typography) : ($current['typography'] ?? null);

        if ($current && isset($current['id'])) {
            // Update
            $stmt = $this->pdo->prepare('
                UPDATE school_themes 
                SET theme_name = :theme_name, 
                    custom_colors = :colors, 
                    typography = :typography,
                    updated_at = NOW()
                WHERE school_id = :school_id
            ');
            return $stmt->execute([
                'theme_name' => $theme_name,
                'colors' => $colors_json,
                'typography' => $typo_json,
                'school_id' => $school_id
            ]);
        } else {
            // Insert
            $stmt = $this->pdo->prepare('
                INSERT INTO school_themes 
                (school_id, theme_name, custom_colors, typography)
                VALUES (:school_id, :theme_name, :colors, :typography)
            ');
            return $stmt->execute([
                'school_id' => $school_id,
                'theme_name' => $theme_name,
                'colors' => $colors_json,
                'typography' => $typo_json
            ]);
        }
    }
    /**
     * Cek apakah ada tema khusus yang aktif untuk hari ini
     * @param int $school_id
     * @return string|null theme_key atau null
     */
    public function checkSpecialTheme($school_id)
    {
        if (!$school_id) return null;
        try {
            // Jika ada tema event yang diaktifkan (is_active = 1), langsung terapkan
            // Mutlak hanya 1 tema event yang aktif di sistem
            $stmt = $this->pdo->prepare("
                SELECT theme_key 
                FROM special_themes 
                WHERE school_id = :school_id 
                AND is_active = 1 
                ORDER BY (date = CURRENT_DATE() OR DATE_FORMAT(date, '%m-%d') = DATE_FORMAT(CURRENT_DATE(), '%m-%d')) DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute(['school_id' => $school_id]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Ambil semua daftar hari penting untuk sekolah tertentu
     * @param int $school_id
     * @return array
     */
    public function getSpecialThemes($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM special_themes 
            WHERE school_id = :school_id 
            ORDER BY date ASC
        ');
        $stmt->execute(['school_id' => $school_id]);
        $themes = $stmt->fetchAll();

        // Auto-seed standard prestigious event presets if empty
        if (empty($themes) && $school_id) {
            $year = date('Y');
            $presets = [
                ['name' => 'Peringatan Hari Kemerdekaan RI', 'date' => "$year-08-17", 'theme_key' => 'kemerdekaan', 'description' => 'Dirgahayu Republik Indonesia (17 Agustus)'],
                ['name' => 'Hari Raya Idul Fitri', 'date' => "$year-03-21", 'theme_key' => 'idulfitri', 'description' => 'Perayaan Hari Raya Idul Fitri 1 Syawal'],
                ['name' => 'Hari Guru Nasional', 'date' => "$year-11-25", 'theme_key' => 'hariguru', 'description' => 'Penghormatan Guru & Tenaga Kependidikan (25 November)'],
                ['name' => 'Perayaan Tahun Baru Masehi', 'date' => "$year-01-01", 'theme_key' => 'tahunbaru', 'description' => 'Menyambut Tahun Baru & Harapan Baru'],
                ['name' => 'Perayaan Hari Raya Imlek', 'date' => "$year-02-10", 'theme_key' => 'imlek', 'description' => 'Gong Xi Fa Cai — Tahun Baru Imlek Penuh Berkah'],
                ['name' => 'Hari Raya Natal & Tahun Baru', 'date' => "$year-12-25", 'theme_key' => 'natal', 'description' => 'Perayaan Hari Natal & Libur Akhir Tahun (25 Desember)']
            ];
            foreach ($presets as $p) {
                $this->addSpecialTheme([
                    'school_id' => $school_id,
                    'name' => $p['name'],
                    'date' => $p['date'],
                    'theme_key' => $p['theme_key'],
                    'description' => $p['description'],
                    'is_active' => 0
                ]);
            }
            $stmt->execute(['school_id' => $school_id]);
            $themes = $stmt->fetchAll();
        }

        return $themes;
    }

    /**
     * Matikan semua tema event untuk sekolah (mencegah double activate)
     * @param int $school_id
     * @return bool
     */
    public function deactivateAllSpecialThemes($school_id)
    {
        if (!$school_id) return false;
        try {
            $stmt = $this->pdo->prepare('UPDATE special_themes SET is_active = 0 WHERE school_id = :school_id');
            return $stmt->execute(['school_id' => $school_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Tambah hari penting baru
     */
    public function addSpecialTheme($data)
    {
        if (!empty($data['is_active']) && !empty($data['school_id'])) {
            $this->deactivateAllSpecialThemes($data['school_id']);
        }
        $stmt = $this->pdo->prepare('
            INSERT INTO special_themes (school_id, name, date, theme_key, description, is_active)
            VALUES (:school_id, :name, :date, :theme_key, :description, :is_active)
        ');
        return $stmt->execute($data);
    }

    /**
     * Hapus hari penting
     */
    public function deleteSpecialTheme($id, $school_id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM special_themes WHERE id = :id AND school_id = :school_id');
        return $stmt->execute(['id' => $id, 'school_id' => $school_id]);
    }

    /**
     * Toggle status aktif hari penting
     * Jika diaktifkan (status = 1), semua tema event lain otomatis dimatikan!
     */
    public function toggleSpecialTheme($id, $school_id, $status)
    {
        if ($status == 1) {
            // Matikan semua event lain terlebih dahulu
            $this->deactivateAllSpecialThemes($school_id);
            $stmt = $this->pdo->prepare('
                UPDATE special_themes 
                SET is_active = 1, date = CURRENT_DATE() 
                WHERE id = :id AND school_id = :school_id
            ');
            return $stmt->execute(['id' => $id, 'school_id' => $school_id]);
        } else {
            $stmt = $this->pdo->prepare('
                UPDATE special_themes 
                SET is_active = 0 
                WHERE id = :id AND school_id = :school_id
            ');
            return $stmt->execute(['id' => $id, 'school_id' => $school_id]);
        }
    }

    /**
     * Uji aktifkan hari penting langsung untuk hari ini
     * Mutlak hanya 1 event yang aktif
     */
    public function testActivateSpecialTheme($id, $school_id)
    {
        // Matikan event lain agar tidak tumpang tindih
        $this->deactivateAllSpecialThemes($school_id);

        // Aktifkan event ini dan set tanggalnya ke hari ini
        $stmtActivate = $this->pdo->prepare('
            UPDATE special_themes 
            SET is_active = 1, date = CURRENT_DATE() 
            WHERE id = :id AND school_id = :school_id
        ');
        return $stmtActivate->execute(['id' => $id, 'school_id' => $school_id]);
    }
}
?>