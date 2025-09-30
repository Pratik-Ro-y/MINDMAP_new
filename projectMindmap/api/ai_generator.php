<?php
// api/ai_generator.php

// --- Enhanced Error Reporting for Debugging ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ---

require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

// ... (rest of the file remains the same) ...

// Enhanced AI Simulation Function
function generateMindmapFromText($text) {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    
    if (empty($lines)) {
        return ['title' => 'Empty Document', 'nodes' => []];
    }
    
    $nodes = [];
    $nodeIdCounter = 1;
    
    // First line becomes the root/central node
    $rootTitle = array_shift($lines);
    $rootNodeId = $nodeIdCounter++;
    
    $nodes[] = [
        'nodeId' => $rootNodeId,
        'parentId' => null,
        'content' => $rootTitle,
        'x' => 400,
        'y' => 300,
        'color' => '#667eea'
    ];
    
    // Process remaining lines
    $angle = 0;
    $angleStep = 360 / max(1, count($lines));
    $radius = 200;
    
    foreach ($lines as $index => $line) {
        // Skip empty lines
        if (empty($line)) continue;
        
        // Calculate position in a circle around the root
        $x = 400 + $radius * cos(deg2rad($angle));
        $y = 300 + $radius * sin(deg2rad($angle));
        
        // Check for sub-items (lines starting with - or *)
        $isSubItem = preg_match('/^[-*]\s+/', $line);
        $content = preg_replace('/^[-*]\s+/', '', $line);
        
        // Determine parent
        $parentId = $rootNodeId;
        if ($isSubItem && count($nodes) > 1) {
            // Make it a child of the last main node
            for ($i = count($nodes) - 1; $i >= 0; $i--) {
                if ($nodes[$i]['parentId'] === $rootNodeId) {
                    $parentId = $nodes[$i]['nodeId'];
                    // Adjust position relative to parent
                    $x = $nodes[$i]['x'] + 100;
                    $y = $nodes[$i]['y'] + 80;
                    break;
                }
            }
        }
        
        $nodes[] = [
            'nodeId' => $nodeIdCounter++,
            'parentId' => $parentId,
            'content' => $content,
            'x' => intval($x),
            'y' => intval($y),
            'color' => $isSubItem ? '#34a853' : '#4285f4'
        ];
        
        if (!$isSubItem) {
            $angle += $angleStep;
        }
    }
    
    return [
        'title' => $rootTitle ?: 'AI Generated Map',
        'nodes' => $nodes
    ];
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['document'])) {
            throw new Exception('No file uploaded');
        }
        
        $file = $_FILES['document'];
        
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File too large',
                UPLOAD_ERR_PARTIAL => 'File partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: missing temp directory',
                UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
            ];
            $message = $errorMessages[$file['error']] ?? 'Unknown upload error';
            throw new Exception($message);
        }
        
        // Removed file type validation to allow any file type
        
        // Check file size (limit to 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 5MB');
        }
        
        // Read and process file
        $fileContent = file_get_contents($file['tmp_name']);
        
        if ($fileContent === false) {
            throw new Exception('Failed to read file');
        }
        
        // Detect encoding and convert to UTF-8 if needed
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
        }
        
        // Generate mindmap
        $mindmapData = generateMindmapFromText($fileContent);
        
        // Store in session
        $_SESSION['ai_generated_map'] = $mindmapData;
        
        echo json_encode([
            'success' => true,
            'message' => 'Mindmap generated successfully!',
            'nodeCount' => count($mindmapData['nodes'])
        ]);
        
    } catch (Exception $e) {
        http_response_code(400); // Set a bad request status code
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>