<?php
// api/save_mindmap.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$title = sanitize($input['title'] ?? '');
$nodes = $input['nodes'] ?? [];
$mapId = $input['mapId'] ?? null;

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($mapId) {
        // Update existing mindmap title
        $stmt = $pdo->prepare("UPDATE mindmaps SET title = ? WHERE mapId = ? AND userId = ?");
        $stmt->execute([$title, $mapId, $_SESSION['user_id']]);
        // Delete all old nodes to replace them
        $stmt = $pdo->prepare("DELETE FROM nodes WHERE mapId = ?");
        $stmt->execute([$mapId]);
    } else {
        // Create new mindmap
        $stmt = $pdo->prepare("INSERT INTO mindmaps (title, userId) VALUES (?, ?)");
        $stmt->execute([$title, $_SESSION['user_id']]);
        $mapId = $pdo->lastInsertId();
    }

    // Save nodes if any exist
    if (!empty($nodes)) {
        $nodeIdMap = []; // Maps client-side temporary ID to new database ID
        
        // Prepare statement for inserting nodes
        $stmt = $pdo->prepare("INSERT INTO nodes (mapId, parentId, content, x, y, color) VALUES (?, ?, ?, ?, ?, ?)");
        
        // Make a mutable copy of the nodes array to process
        $nodesToProcess = $nodes;
        
        // Loop until all nodes are processed. This handles parent-child dependencies.
        while (count($nodesToProcess) > 0) {
            $processedThisLoop = 0;
            foreach ($nodesToProcess as $index => $node) {
                $parentId = $node['parentId'];
                
                // A node can be inserted if it's a root node OR if its parent has already been inserted.
                if ($parentId === null || isset($nodeIdMap[$parentId])) {
                    $dbParentId = $parentId === null ? null : $nodeIdMap[$parentId];
                    
                    $stmt->execute([
                        $mapId,
                        $dbParentId,
                        $node['content'],
                        intval($node['x']),
                        intval($node['y']),
                        $node['color'] ?? '#4285f4'
                    ]);
                    
                    // Store the new DB ID, mapping it from the original client-side ID
                    $newNodeId = $pdo->lastInsertId();
                    $nodeIdMap[$node['nodeId']] = $newNodeId;
                    
                    // Remove the processed node from the array
                    unset($nodesToProcess[$index]);
                    $processedThisLoop++;
                }
            }
            
            // If a loop completes without processing any nodes, there's a circular dependency or orphaned node.
            if ($processedThisLoop === 0) {
                throw new Exception("Mindmap structure error: Could not save nodes due to an orphan or circular dependency.");
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Mindmap saved successfully!', 'mapId' => $mapId]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>