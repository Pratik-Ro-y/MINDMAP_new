<?php
// editor.php
require_once 'config.php';
requireLogin();

$mapId = null;
$mindmapData = null;
$nodes = [];
$isAIMap = false;
$isTemplate = false;

// ... (PHP logic for loading data remains the same) ...
if (isset($_GET['template_id']) && is_numeric($_GET['template_id'])) {
    $isTemplate = true;
    $templateId = intval($_GET['template_id']);
    try {
        $stmt = $pdo->prepare("SELECT title, nodeStructure FROM templates WHERE templateId = ?");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();
        if ($template) {
            $mindmapData = ['title' => $template['title']];
            $nodes = json_decode($template['nodeStructure'], true);
        } else {
            redirect('templates.php');
        }
    } catch (PDOException $e) {
        redirect('templates.php');
    }
}
elseif (isset($_GET['ai']) && isset($_SESSION['ai_generated_map'])) {
    $isAIMap = true;
    $ai_map = $_SESSION['ai_generated_map'];
    $mindmapData = ['title' => $ai_map['title']];
    $nodes = $ai_map['nodes'];
    unset($_SESSION['ai_generated_map']);
}
elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $mapId = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM mindmaps WHERE mapId = ? AND userId = ?");
        $stmt->execute([$mapId, $_SESSION['user_id']]);
        $mindmapData = $stmt->fetch();
        if (!$mindmapData) redirect('dashboard.php');

        $stmt = $pdo->prepare("SELECT * FROM nodes WHERE mapId = ? ORDER BY nodeId");
        $stmt->execute([$mapId]);
        $nodes = $stmt->fetchAll();
    } catch (PDOException $e) {
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindMap Editor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; height: 100vh; overflow: hidden; }
        .editor-header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; z-index: 1000; }
        .mindmap-title { font-size: 1.2rem; border: 1px solid transparent; padding: 0.5rem; border-radius: 5px; }
        .toolbar { display: flex; align-items: center; gap: 1rem; }
        .btn { padding: 0.7rem 1.2rem; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: background 0.2s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .color-palette { display: flex; gap: 0.5rem; }
        .color-swatch { width: 24px; height: 24px; border-radius: 50%; cursor: pointer; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
        .canvas-container { 
            height: calc(100vh - 70px); 
            position: relative; 
            overflow: hidden;
            background-color: #f7f9fc;
            background-image:
                linear-gradient(rgba(17, 17, 34, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(17, 17, 34, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .mindmap-canvas { width: 100%; height: 100%; position: relative; }
        .node { 
            position: absolute; 
            background: white; 
            border-left: 5px solid;
            border-radius: 8px;
            padding: 12px 18px; 
            cursor: move; 
            user-select: none; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: left;
            min-width: 150px;
            transition: box-shadow 0.2s, border-color 0.2s, background 0.2s;
        }
        .node.root { 
            background: #667eea; 
            color: white;
            border-left: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .node.selected { box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.6); }
        .node-content { background: transparent; border: none; outline: none; width: 100%; text-align: left; font: inherit; color: inherit; }
        .connection-line { stroke: #adb5bd; stroke-width: 3; fill: none; stroke-linecap: round; }
    </style>
</head>
<body>
    <div class="editor-header">
        <div class="header-left">
            <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i></a>
            <input type="text" class="mindmap-title" placeholder="Enter mindmap title..." value="<?php echo htmlspecialchars($mindmapData['title'] ?? 'New MindMap'); ?>">
        </div>
        <div class="toolbar">
            <button class="btn btn-primary" onclick="addChildNode()"><i class="fas fa-plus"></i> Add Node</button>
            <div class="color-palette">
                <div class="color-swatch" style="background: #667eea;" onclick="applyNodeColor('#667eea')"></div>
                <div class="color-swatch" style="background: #4285f4;" onclick="applyNodeColor('#4285f4')"></div>
                <div class="color-swatch" style="background: #34a853;" onclick="applyNodeColor('#34a853')"></div>
                <div class="color-swatch" style="background: #ffc107;" onclick="applyNodeColor('#ffc107')"></div>
                <div class="color-swatch" style="background: #dc3545;" onclick="applyNodeColor('#dc3545')"></div>
            </div>
            <button class="btn btn-success" onclick="saveMindMap()"><i class="fas fa-save"></i> Save</button>
            <div class="export-dropdown">
                 <button class="btn btn-secondary"><i class="fas fa-download"></i> Export</button>
                 <div class="export-options">
                    <a href="#" onclick="exportMindMap('png')">PNG</a>
                    <a href="#" onclick="exportMindMap('json')">JSON</a>
                    <a href="#" onclick="exportMindMap('pdf')">PDF</a>
                </div>
            </div>
        </div>
    </div>
    <div class="canvas-container" id="canvas-container">
        <div class="mindmap-canvas" id="canvas">
            <svg id="connections" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none;"></svg>
        </div>
    </div>
    <div id="statusIndicator" class="status-indicator"></div>

    <script>
        let nodes = <?php echo json_encode($nodes, JSON_NUMERIC_CHECK); ?>;
        let mapId = <?php echo $mapId ? $mapId : 'null'; ?>;
        
        let selectedNode = null;
        let draggedNode = null;
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };
        let nodeIdCounter = nodes.length > 0 ? Math.max(...nodes.map(n => n.nodeId)) + 1 : 1;
        const HORIZONTAL_SPACING = 120;
        const VERTICAL_SPACING = 25;

        document.addEventListener('DOMContentLoaded', () => {
            if (nodes.length === 0) createRootNode();
            else renderAllNodes();
            setupEventListeners();
            autoLayout();
        });

        function createRootNode() {
            const node = { nodeId: nodeIdCounter++, parentId: null, content: 'Central Idea', x: 100, y: 300, color: '#667eea' };
            nodes.push(node);
            renderNode(node);
            selectNode(node);
        }

        function renderNode(node, shouldFocus = false) {
            let el = document.getElementById('node-' + node.nodeId);
            if (!el) {
                el = document.createElement('div');
                el.id = 'node-' + node.nodeId;
                el.innerHTML = `<input type="text" class="node-content">`;
                document.getElementById('canvas').appendChild(el);
            }
            
            el.className = 'node' + (node.parentId === null ? ' root' : '') + (selectedNode && node.nodeId === selectedNode.nodeId ? ' selected' : '');
            el.style.left = `${node.x}px`;
            el.style.top = `${node.y}px`;
            el.style.borderColor = node.color;
            el.style.background = (node.parentId === null) ? node.color : 'white';
            
            const input = el.querySelector('.node-content');
            input.value = node.content;
            input.style.color = (node.parentId === null) ? 'white' : 'black';
            
            input.oninput = () => autoLayout();
            el.onmousedown = (e) => onNodeMouseDown(e, node);

            if (shouldFocus) {
                input.focus();
                input.select();
            }
        }
        
        function renderAllNodes() {
            nodes.forEach(node => renderNode(node));
        }

        function selectNode(node) {
            selectedNode = node;
            document.querySelectorAll('.node').forEach(n => n.classList.remove('selected'));
            document.getElementById('node-' + node.nodeId)?.classList.add('selected');
        }

        function addChildNode() {
            if (!selectedNode) {
                const root = nodes.find(n => n.parentId === null);
                if (root) selectNode(root);
                else return;
            }
            const node = { nodeId: nodeIdCounter++, parentId: selectedNode.nodeId, content: 'New Idea', x: 0, y: 0, color: '#4285f4' };
            nodes.push(node);
            renderNode(node, true);
            autoLayout();
            selectNode(node);
        }

        function addSiblingNode() {
            if (!selectedNode || selectedNode.parentId === null) return;
            const node = { nodeId: nodeIdCounter++, parentId: selectedNode.parentId, content: 'New Idea', x: 0, y: 0, color: '#4285f4' };
            nodes.push(node);
            renderNode(node, true);
            autoLayout();
            selectNode(node);
        }
        
        function autoLayout() {
            const root = nodes.find(n => n.parentId === null);
            if (!root) return;
            
            nodes.forEach(n => {
                const el = document.getElementById('node-' + n.nodeId);
                if (el) { n.width = el.offsetWidth; n.height = el.offsetHeight; }
            });

            calculateSubtreeHeights(root);
            positionNodes(root);
            
            nodes.forEach(n => renderNode(n));
            updateConnections();
        }

        function calculateSubtreeHeights(node) {
            const children = nodes.filter(n => n.parentId === node.nodeId);
            if (children.length === 0) {
                node.subtreeHeight = node.height;
                return;
            }
            let totalChildHeight = 0;
            children.forEach(child => {
                calculateSubtreeHeights(child);
                totalChildHeight += child.subtreeHeight;
            });
            totalChildHeight += (children.length - 1) * VERTICAL_SPACING;
            node.subtreeHeight = Math.max(node.height, totalChildHeight);
        }

        function positionNodes(node) {
            const children = nodes.filter(n => n.parentId === node.nodeId);
            let currentY = node.y + (node.height / 2) - (node.subtreeHeight / 2);
            
            children.forEach(child => {
                child.x = node.x + node.width + HORIZONTAL_SPACING;
                child.y = currentY + (child.subtreeHeight / 2) - (child.height / 2);
                positionNodes(child);
                currentY += child.subtreeHeight + VERTICAL_SPACING;
            });
        }

        function updateConnections() {
            const svg = document.getElementById('connections');
            if(!svg) return;
            svg.innerHTML = '';
            nodes.forEach(node => {
                const parent = nodes.find(p => p.nodeId === node.parentId);
                if (parent) drawConnection(parent, node);
            });
        }

        function drawConnection(p, c) {
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const pX = p.x + p.width;
            const pY = p.y + p.height / 2;
            const cX = c.x;
            const cY = c.y + c.height / 2;
            const midX = pX + HORIZONTAL_SPACING / 2;
            const d = `M ${pX} ${pY} H ${midX} V ${cY} L ${cX} ${cY}`;
            line.setAttribute('d', d);
            line.classList.add('connection-line');
            svg.appendChild(line);
        }

        function onNodeMouseDown(e, node) {
            e.stopPropagation();
            selectNode(node);
            draggedNode = node;
            isDragging = true;
            const rect = e.currentTarget.getBoundingClientRect();
            dragOffset = { x: e.clientX - rect.left, y: e.clientY - rect.top };
        }
        
        function onCanvasMouseMove(e) {
            if (!isDragging || !draggedNode) return;
            const canvas = document.getElementById('canvas-container').getBoundingClientRect();
            draggedNode.x = e.clientX - canvas.left - dragOffset.x;
            draggedNode.y = e.clientY - canvas.top - dragOffset.y;
            renderNode(draggedNode);
            updateConnections();
        }

        function onCanvasMouseUp() {
            if (isDragging) {
                isDragging = false;
                draggedNode = null;
                autoLayout();
            }
        }

        function setupEventListeners() {
            const canvasContainer = document.getElementById('canvas-container');
            canvasContainer.addEventListener('mousemove', onCanvasMouseMove);
            canvasContainer.addEventListener('mouseup', onCanvasMouseUp);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') { e.preventDefault(); addChildNode(); }
                if (e.key === 'Enter') { e.preventDefault(); addSiblingNode(); }
            });
        }
        
        function applyNodeColor(color) {
            if(selectedNode) {
                selectedNode.color = color;
                renderNode(selectedNode);
            }
        }
        
        // --- SAVE AND EXPORT FUNCTIONS (UNCHANGED) ---
        function saveMindMap() {
            // Unchanged
        }
        function exportMindMap(format) {
            // Unchanged
        }
        function showStatus(message) {
            // Unchanged
        }
    </script>
</body>
</html>