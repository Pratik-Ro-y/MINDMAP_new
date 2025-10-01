<?php
// api/ai_generator.php - Optimized for Low RAM (Pattern-Based)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(60);

require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

// Enhanced pattern-based generation (NO AI REQUIRED)
function generateMindmapFromText($text) {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    
    if (empty($lines)) {
        return ['title' => 'Empty Document', 'nodes' => []];
    }
    
    // Use frequency analysis to identify key topics
    $words = str_word_count(strtolower($text), 1);
    $stopWords = ['the','a','an','and','or','but','in','on','at','to','for','of','with',
                  'by','is','are','was','were','be','been','being','have','has','had',
                  'do','does','did','will','would','should','could','may','might','must',
                  'can','this','that','these','those','i','you','he','she','it','we','they'];
    $words = array_diff($words, $stopWords);
    
    if (empty($words)) {
        // Fallback if no words found
        $rootTitle = array_shift($lines) ?: 'Document';
        return [
            'title' => $rootTitle,
            'nodes' => [
                [
                    'nodeId' => 1,
                    'parentId' => null,
                    'content' => $rootTitle,
                    'x' => 400,
                    'y' => 300,
                    'color' => '#667eea'
                ]
            ]
        ];
    }
    
    $wordFreq = array_count_values($words);
    arsort($wordFreq);
    $keywords = array_slice(array_keys($wordFreq), 0, 6);
    
    $nodes = [];
    $nodeIdCounter = 1;
    
    // Create root from first line or most common keyword
    $rootTitle = array_shift($lines) ?: ucfirst($keywords[0] ?? 'Document');
    $rootTitle = preg_replace('/^#+\s*/', '', $rootTitle); // Remove markdown headers
    $rootTitle = substr($rootTitle, 0, 50); // Limit length
    
    $rootNodeId = $nodeIdCounter++;
    $nodes[] = [
        'nodeId' => $rootNodeId,
        'parentId' => null,
        'content' => $rootTitle,
        'x' => 400,
        'y' => 300,
        'color' => '#667eea'
    ];
    
    // Group lines by keyword presence
    $branches = [];
    foreach ($keywords as $keyword) {
        $branches[$keyword] = [];
    }
    
    // Add unmatched lines to a general category
    $branches['other'] = [];
    
    foreach ($lines as $line) {
        if (empty($line)) continue;
        
        $lineLower = strtolower($line);
        $matched = false;
        
        foreach ($keywords as $keyword) {
            if (strpos($lineLower, $keyword) !== false) {
                $branches[$keyword][] = $line;
                $matched = true;
                break;
            }
        }
        
        if (!$matched && count($branches['other']) < 5) {
            $branches['other'][] = $line;
        }
    }
    
    // Remove empty branches
    $branches = array_filter($branches, function($items) {
        return !empty($items);
    });
    
    // Limit to 6 main branches max
    $branches = array_slice($branches, 0, 6, true);
    
    // Create nodes from branches
    $angle = 0;
    $angleStep = 360 / max(1, count($branches));
    
    foreach ($branches as $keyword => $branchLines) {
        if (empty($branchLines)) continue;
        
        // Main branch node
        $x = 400 + 200 * cos(deg2rad($angle));
        $y = 300 + 200 * sin(deg2rad($angle));
        
        $branchNodeId = $nodeIdCounter++;
        $branchTitle = $keyword === 'other' ? 'Additional Points' : ucfirst($keyword);
        
        $nodes[] = [
            'nodeId' => $branchNodeId,
            'parentId' => $rootNodeId,
            'content' => $branchTitle,
            'x' => intval($x),
            'y' => intval($y),
            'color' => '#4285f4'
        ];
        
        // Add up to 4 details per branch
        $details = array_slice($branchLines, 0, 4);
        foreach ($details as $i => $detail) {
            // Clean up the detail text
            $detail = preg_replace('/^[-*•]\s*/', '', $detail); // Remove bullets
            $detail = preg_replace('/^\d+\.\s*/', '', $detail); // Remove numbers
            $detail = substr($detail, 0, 60); // Limit length
            
            $nodes[] = [
                'nodeId' => $nodeIdCounter++,
                'parentId' => $branchNodeId,
                'content' => $detail,
                'x' => intval($x + 150),
                'y' => intval($y + ($i * 70) - 105),
                'color' => '#34a853'
            ];
        }
        
        $angle += $angleStep;
    }
    
    return [
        'title' => $rootTitle,
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
            ];
            throw new Exception($errorMessages[$file['error']] ?? 'Upload error');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 5MB');
        }
        
        // Read file content
        $fileContent = file_get_contents($file['tmp_name']);
        
        if ($fileContent === false) {
            throw new Exception('Failed to read file');
        }
        
        // Convert encoding if needed
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
        }
        
        // Generate mindmap using pattern-based approach
        $mindmapData = generateMindmapFromText($fileContent);
        
        // Store in session
        $_SESSION['ai_generated_map'] = $mindmapData;
        
        echo json_encode([
            'success' => true,
            'message' => 'Mindmap generated successfully!',
            'nodeCount' => count($mindmapData['nodes'])
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>