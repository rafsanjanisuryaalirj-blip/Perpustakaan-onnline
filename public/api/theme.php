<?php
// Theme API - Per-school theme management
session_start();

// Check authentication
if (empty($_SESSION['user']) || empty($_SESSION['user']['school_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../../src/db.php';

$school_id = $_SESSION['user']['school_id'];
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get school's theme
        $stmt = $pdo->prepare('SELECT * FROM school_themes WHERE school_id = ?');
        $stmt->execute([$school_id]);
        $result = $stmt->fetch();

        require_once __DIR__ . '/../../src/ThemeModel.php';
        $themeModel = new ThemeModel($pdo);
        $specialTheme = $themeModel->checkSpecialTheme($school_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'theme_name' => $result['theme_name'],
                'custom_colors' => json_decode($result['custom_colors'] ?? '{}', true),
                'typography' => json_decode($result['typography'] ?? '{}', true),
                'special_theme' => $specialTheme
            ]);
        } else {
            // Return default if not set
            echo json_encode([
                'success' => true,
                'theme_name' => 'light',
                'custom_colors' => [],
                'typography' => [],
                'special_theme' => $specialTheme
            ]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save school's theme
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['theme_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'theme_name is required']);
            exit;
        }

        // Fetch current to preserve typography if not provided
        $stmt = $pdo->prepare('SELECT typography FROM school_themes WHERE school_id = ?');
        $stmt->execute([$school_id]);
        $current = $stmt->fetch();
        
        $theme_name = $data['theme_name'];
        $custom_colors = $data['custom_colors'] ?? [];
        
        // Store display name inside custom_colors metadata
        if (isset($data['theme_display_name'])) {
            $custom_colors['metadata']['display_name'] = $data['theme_display_name'];
        }
        
        $custom_colors_json = json_encode($custom_colors);
        $typography = isset($data['typography']) ? json_encode($data['typography']) : ($current['typography'] ?? null);

        // Check if exists
        $stmt = $pdo->prepare('SELECT id FROM school_themes WHERE school_id = ?');
        $stmt->execute([$school_id]);
        $exists = $stmt->fetchColumn();

        require_once __DIR__ . '/../../src/ThemeModel.php';
        $themeModel = new ThemeModel($pdo);
        $themeModel->deactivateAllSpecialThemes($school_id);

        if ($exists) {
            $stmt = $pdo->prepare('UPDATE school_themes SET theme_name = ?, custom_colors = ?, typography = ? WHERE school_id = ?');
            $stmt->execute([$theme_name, $custom_colors_json, $typography, $school_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO school_themes (school_id, theme_name, custom_colors, typography) VALUES (?, ?, ?, ?)');
            $stmt->execute([$school_id, $theme_name, $custom_colors_json, $typography]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Theme saved successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
