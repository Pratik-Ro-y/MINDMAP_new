<?php
// Save as: projectMindmap/debug_session.php

require_once 'config.php';
requireLogin();

echo "<h1>Debug Session Data</h1>";

if (isset($_SESSION['ai_generated_map'])) {
    echo "<h2>✅ AI Generated Map Found in Session</h2>";
    echo "<pre>";
    print_r($_SESSION['ai_generated_map']);
    echo "</pre>";
    
    $map = $_SESSION['ai_generated_map'];
    echo "<h3>Summary:</h3>";
    echo "Title: " . htmlspecialchars($map['title'] ?? 'N/A') . "<br>";
    echo "Node Count: " . count($map['nodes'] ?? []) . "<br><br>";
    
    if (!empty($map['nodes'])) {
        echo "<h3>Nodes:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>NodeId</th><th>ParentId</th><th>Content</th><th>X</th><th>Y</th><th>Color</th></tr>";
        foreach ($map['nodes'] as $node) {
            echo "<tr>";
            echo "<td>" . ($node['nodeId'] ?? 'N/A') . "</td>";
            echo "<td>" . ($node['parentId'] ?? 'null') . "</td>";
            echo "<td>" . htmlspecialchars($node['content'] ?? '') . "</td>";
            echo "<td>" . ($node['x'] ?? 'N/A') . "</td>";
            echo "<td>" . ($node['y'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($node['color'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h2>❌ No AI Generated Map in Session</h2>";
    echo "<p>Upload a file first from the dashboard.</p>";
}

echo "<hr>";
echo "<h2>All Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<a href='dashboard.php'>Go to Dashboard</a> | ";
echo "<a href='editor.php?ai=true'>Go to Editor (AI)</a>";
?>