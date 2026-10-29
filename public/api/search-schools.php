<?php
/**
 * API Pencarian Sekolah untuk Siswa & Pengunjung
 * Digunakan untuk menemukan portal sekolah
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = require __DIR__ . '/../../src/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

$query = trim($_GET['q'] ?? '');

try {
    if (empty($query)) {
        // Ambil beberapa sekolah terbaru jika belum mengetik
        $stmt = $pdo->prepare("
            SELECT id, name, slug, npsn, address, photo_path, logo 
            FROM schools 
            WHERE status != 'rejected'
            ORDER BY id DESC 
            LIMIT 6
        ");
        $stmt->execute();
    } else {
        $searchTerm = '%' . $query . '%';
        $exactName = $query . '%';

        // Gunakan parameter unik untuk kompatibilitas PDO native prepare (EMULATE_PREPARES = false)
        $stmt = $pdo->prepare("
            SELECT id, name, slug, npsn, address, photo_path, logo 
            FROM schools 
            WHERE status != 'rejected' 
              AND (name LIKE :q_name OR npsn LIKE :q_npsn OR slug LIKE :q_slug OR address LIKE :q_addr)
            ORDER BY 
              CASE 
                WHEN npsn = :exact_npsn THEN 1
                WHEN name LIKE :exact_name THEN 2 
                ELSE 3 
              END,
              name ASC 
            LIMIT 10
        ");
        
        $stmt->execute([
            'q_name' => $searchTerm,
            'q_npsn' => $searchTerm,
            'q_slug' => $searchTerm,
            'q_addr' => $searchTerm,
            'exact_npsn' => $query,
            'exact_name' => $exactName
        ]);
    }

    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format output dan pastikan slug valid
    $results = array_map(function($school) {
        $slug = !empty($school['slug']) ? $school['slug'] : strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school['name'])));
        return [
            'id' => (int)$school['id'],
            'name' => $school['name'],
            'slug' => $slug,
            'npsn' => $school['npsn'] ?? '-',
            'address' => $school['address'] ?? '',
            'photo' => $school['photo_path'] ?? null,
            'logo' => $school['logo'] ?? null,
            'url' => 'school.php?s=' . urlencode($slug)
        ];
    }, $schools);

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'schools' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal memuat data sekolah: ' . $e->getMessage()
    ]);
}
