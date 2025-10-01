<?php
// Save this as: projectMindmap/test_phi.php

echo "<h1>Testing Phi Integration</h1>";

// Test 1: Check if Ollama is accessible
echo "<h2>Test 1: Ollama Connection</h2>";
$ch = curl_init('http://localhost:11434');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Ollama is running!<br>";
} else {
    echo "❌ Cannot connect to Ollama. Make sure it's running.<br>";
    exit;
}

// Test 2: Send a simple request to Phi
echo "<h2>Test 2: Phi Model Response</h2>";
echo "Asking Phi to create a simple mindmap...<br><br>";

$testText = "Artificial Intelligence is transforming technology. Machine Learning enables computers to learn. Neural Networks mimic human brains. Deep Learning processes complex data.";

$prompt = "Analyze this text and create a mindmap structure. Extract the main topic and subtopics.\n\n"
        . "Respond ONLY with valid JSON in this format:\n"
        . '{"title":"Main Topic","nodes":[{"content":"Topic","level":0},{"content":"Subtopic","level":1,"parent":0}]}'
        . "\n\nText:\n" . $testText;

$data = [
    'model' => 'phi',
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'temperature' => 0.3,
        'top_p' => 0.9
    ]
];

$startTime = microtime(true);

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($error) {
    echo "❌ Error: $error<br>";
    exit;
}

if ($httpCode !== 200) {
    echo "❌ HTTP Error: $httpCode<br>";
    echo "<pre>$response</pre>";
    exit;
}

$result = json_decode($response, true);

if (!isset($result['response'])) {
    echo "❌ Invalid response format<br>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    exit;
}

echo "✅ Phi responded in {$duration} seconds!<br><br>";

echo "<h3>Raw AI Response:</h3>";
echo "<pre>" . htmlspecialchars($result['response']) . "</pre>";

// Try to extract JSON
echo "<h3>Parsing JSON:</h3>";
preg_match('/\{.*\}/s', $result['response'], $matches);

if (!empty($matches)) {
    $mindmapData = json_decode($matches[0], true);
    if ($mindmapData) {
        echo "✅ Valid JSON structure found!<br><br>";
        echo "<strong>Title:</strong> " . htmlspecialchars($mindmapData['title'] ?? 'N/A') . "<br>";
        echo "<strong>Nodes:</strong> " . count($mindmapData['nodes'] ?? []) . "<br><br>";
        echo "<pre>" . print_r($mindmapData, true) . "</pre>";
    } else {
        echo "⚠️ Found JSON but couldn't parse it<br>";
    }
} else {
    echo "⚠️ No JSON found in response. Phi might need better prompting.<br>";
    echo "This is okay - we have a fallback pattern-based generator!<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "✅ Ollama is connected<br>";
echo "✅ Phi model is responding<br>";
echo "⏱️ Response time: {$duration}s<br>";
echo "<br><strong>Next step:</strong> Try uploading a file in your mindmap app!<br>";
?>