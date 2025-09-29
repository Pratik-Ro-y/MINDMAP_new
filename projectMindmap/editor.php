<?php
// editor.php
require_once 'config.php';
requireLogin();

$mapId = null;
$mindmapData = null;
$nodes = [];
$isAIMap = false;
$isTemplate = false;

// Check if we are loading a template
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
    <title><?php echo $mapId ? 'Edit' : 'Create'; ?> MindMap</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; height: 100vh; overflow: hidden; }
        .editor-header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; z-index: 1000; }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .mindmap-title { font-size: 1.2rem; border: 1px solid transparent; padding: 0.5rem; border-radius: 5px; }
        .mindmap-title:focus { border-color: #ddd; }
        .toolbar { display: flex; align-items: center; gap: 1rem; }
        .btn { padding: 0.7rem 1.2rem; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: background 0.2s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .color-palette { display: flex; gap: 0.5rem; }
        .color-swatch { width: 24px; height: 24px; border-radius: 50%; cursor: pointer; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
        .canvas-container { height: calc(100vh - 70px); position: relative; overflow: hidden; }
        .mindmap-canvas { width: 100%; height: 100%; position: relative; transform-origin: top left; }
        .node { position: absolute; background: white; border: 3px solid #667eea; border-radius: 25px; padding: 15px 20px; cursor: move; user-select: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; transition: box-shadow 0.2s, border-color 0.2s; }
        .node.root { background: #667eea; color: white; }
        .node.selected { box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.5); }
        .node-content { background: transparent; border: none; outline: none; width: 100%; text-align: center; font: inherit; color: inherit; }
        .connection-line { stroke: #aaa; stroke-width: 2; fill: none; }
        .status-indicator { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.8); color: white; padding: 0.8rem 1.5rem; border-radius: 25px; display: none; z-index: 3000; }
        .export-dropdown { position: relative; display: inline-block; }
        .export-options { display: none; position: absolute; right:0; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; }
        .export-options a { color: black; padding: 12px 16px; text-decoration: none; display: block; }
        .export-options a:hover { background-color: #f1f1f1; }
        .export-dropdown:hover .export-options { display: block; }
    </style>
</head>
<body>
    <div class="editor-header">
        <div class="header-left">
            <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i></a>
            <input type="text" class="mindmap-title" placeholder="Enter mindmap title..." value="<?php echo htmlspecialchars($mindmapData['title'] ?? 'New MindMap'); ?>">
        </div>
        <div class="toolbar">
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
        
        let selectedNode = null, draggedNode = null, isDragging = false;
        let dragOffset = { x: 0, y: 0 };
        let nodeIdCounter = nodes.length > 0 ? Math.max(...nodes.map(n => n.nodeId)) + 1 : 1;
        const HORIZONTAL_SPACING = 180;
        const VERTICAL_SPACING = 40;

        document.addEventListener('DOMContentLoaded', () => {
            if (nodes.length === 0) createRootNode();
            else renderExistingNodes();
            setupEventListeners();
            autoLayout(); 
            setInterval(saveMindMap, 30000); // Auto-save
        });

        function createRootNode() {
            const node = { nodeId: nodeIdCounter++, parentId: null, content: 'Central Idea', x: 100, y: 300, color: '#667eea' };
            nodes.push(node);
            renderNode(node);
            selectNode(node);
        }

        function renderExistingNodes() {
            nodes.forEach(node => renderNode(node));
            updateConnections();
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
            el.style.left = node.x + 'px';
            el.style.top = node.y + 'px';
            el.style.borderColor = node.color;
            el.style.background = (node.parentId === null) ? node.color : 'white';
            
            const input = el.querySelector('.node-content');
            input.value = node.content;
            input.style.color = (node.parentId === null) ? 'white' : 'black';
            
            input.onblur = (e) => { node.content = e.target.value; autoLayout(); };
            el.onmousedown = (e) => onNodeMouseDown(e, node);

            if (shouldFocus) {
                input.focus();
                input.select();
            }
        }

        function selectNode(node) {
            selectedNode = node;
            document.querySelectorAll('.node').forEach(n => n.classList.remove('selected'));
            document.getElementById('node-' + node.nodeId)?.classList.add('selected');
        }

        function addChildNode() {
            if (!selectedNode) return;
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

        function deleteNode() {
            if (!selectedNode || selectedNode.parentId === null) return;
            let toDelete = [selectedNode.nodeId];
            let queue = [selectedNode.nodeId];
            while (queue.length > 0) {
                const parentId = queue.shift();
                nodes.forEach(n => {
                    if (n.parentId === parentId) { toDelete.push(n.nodeId); queue.push(n.nodeId); }
                });
            }
            toDelete.forEach(id => document.getElementById('node-' + id)?.remove());
            nodes = nodes.filter(n => !toDelete.includes(n.nodeId));
            selectNode(nodes.find(n => n.nodeId === selectedNode.parentId));
            autoLayout();
        }
        
        function autoLayout() {
            const root = nodes.find(n => n.parentId === null);
            if (!root) return;
            
            // First, render all nodes to ensure their dimensions are available
            nodes.forEach(n => renderNode(n));

            // Then, calculate the layout based on those dimensions
            calculateTreeLayout(root);

            // Finally, re-render the nodes in their new positions and update connections
            nodes.forEach(n => renderNode(n));
            updateConnections();
        }

        function calculateTreeLayout(node) {
            const children = nodes.filter(n => n.parentId === node.nodeId);
            if (children.length === 0) {
                const el = document.getElementById('node-' + node.nodeId);
                node.subtreeHeight = el ? el.offsetHeight : 0;
                return;
            }

            let totalChildHeight = 0;
            children.forEach(child => {
                calculateTreeLayout(child);
                totalChildHeight += child.subtreeHeight;
            });
            
            const el = document.getElementById('node-' + node.nodeId);
            const nodeHeight = el ? el.offsetHeight : 0;
            totalChildHeight += (children.length - 1) * VERTICAL_SPACING;
            node.subtreeHeight = Math.max(nodeHeight, totalChildHeight);

            let currentY = node.y - (totalChildHeight / 2) + (nodeHeight / 2);
            
            children.forEach(child => {
                child.x = node.x + (el ? el.offsetWidth : 0) + HORIZONTAL_SPACING;
                child.y = currentY - (child.subtreeHeight / 2) + (child.height / 2);
                currentY += child.subtreeHeight + VERTICAL_SPACING;
            });
        }
        
        function updateConnections() {
            const svg = document.getElementById('connections');
            svg.innerHTML = '';
            nodes.forEach(node => {
                if (node.parentId !== null) {
                    const parent = nodes.find(n => n.nodeId === node.parentId);
                    if (parent) drawConnection(parent, node);
                }
            });
        }

        function drawConnection(p, c) {
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const pEl = document.getElementById('node-' + p.nodeId);
            const cEl = document.getElementById('node-' + c.nodeId);
            if (!pEl || !cEl) return;
            
            const pX = p.x + pEl.offsetWidth;
            const pY = p.y + pEl.offsetHeight / 2;
            const cX = c.x;
            const cY = c.y + cEl.offsetHeight / 2;

            const d = `M ${pX} ${pY} C ${pX + 60} ${pY}, ${cX - 60} ${cY}, ${cX} ${cY}`;
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
                autoLayout();
            }
        }
        
        function applyNodeColor(color) {
            if (selectedNode) {
                selectedNode.color = color;
                renderNode(selectedNode);
            }
        }

        function setupEventListeners() {
            const canvasContainer = document.getElementById('canvas-container');
            canvasContainer.addEventListener('mousemove', onCanvasMouseMove);
            canvasContainer.addEventListener('mouseup', onCanvasMouseUp);
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') { e.preventDefault(); addChildNode(); return; }
                if (e.key === 'Enter') { e.preventDefault(); addSiblingNode(); return; }
                
                // Only block other shortcuts if an input is active
                const activeEl = document.activeElement;
                if (activeEl.tagName === 'INPUT' && activeEl.closest('.node')) return;
                
                switch (e.key) {
                    case 'Delete': case 'Backspace': e.preventDefault(); deleteNode(); break;
                    case 's': if (e.ctrlKey) { e.preventDefault(); saveMindMap(); } break;
                }
            });
        }
        
        function saveMindMap() {
            const title = document.querySelector('.mindmap-title').value.trim();
            if (!title) { showStatus('Title is required!'); return; }
            showStatus('Saving...');
            fetch('api/save_mindmap.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, nodes, mapId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (!mapId) {
                        window.history.replaceState({}, '', `editor.php?id=${data.mapId}`);
                        mapId = data.mapId;
                    }
                    showStatus('Mindmap saved!');
                } else { showStatus('Error: ' + data.message); }
            })
            .catch(() => showStatus('Save failed!'));
        }

        function exportMindMap(format) {
            const title = document.querySelector('.mindmap-title').value.trim();
            html2canvas(document.getElementById('canvas')).then(canvas => {
                if (format === 'png') {
                    const link = document.createElement('a');
                    link.download = `${title}.png`;
                    link.href = canvas.toDataURL();
                    link.click();
                } else if (format === 'pdf') {
                    const { jsPDF } = window.jspdf;
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({ orientation: 'landscape' });
                    const imgProps = pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save(`${title}.pdf`);
                }
            });

            if (format === 'json') {
                const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify({ title, nodes }));
                const downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", `${title}.json`);
                document.body.appendChild(downloadAnchorNode);
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            }
        }
        
        function showStatus(message) {
            const indicator = document.getElementById('statusIndicator');
            indicator.textContent = message;
            indicator.style.display = 'block';
            setTimeout(() => { indicator.style.display = 'none'; }, 2000);
        }
    </script>
</body>
</html>