<?php
// api/delete_mindmap.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mapId = $input['mapId'] ?? null;

if (!$mapId || !is_numeric($mapId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mindmap ID']);
    exit;
}

try {
    // Verify ownership before deleting
    $stmt = $pdo->prepare("SELECT userId FROM mindmaps WHERE mapId = ?");
    $stmt->execute([$mapId]);
    $mindmap = $stmt->fetch();
    
    if (!$mindmap) {
        echo json_encode(['success' => false, 'message' => 'Mindmap not found']);
        exit;
    }
    
    if ($mindmap['userId'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Delete mindmap (nodes will be deleted automatically due to CASCADE)
    $stmt = $pdo->prepare("DELETE FROM mindmaps WHERE mapId = ?");
    $stmt->execute([$mapId]);
    
    echo json_encode(['success' => true, 'message' => 'Mindmap deleted successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>