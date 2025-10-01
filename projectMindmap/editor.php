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
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f8f9fa; 
            height: 100vh; 
            overflow: hidden; 
        }
        .editor-header { 
            background: white; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            z-index: 1000; 
        }
        .header-left { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
        }
        .mindmap-title { 
            font-size: 1.2rem; 
            border: 1px solid transparent; 
            padding: 0.5rem; 
            border-radius: 5px; 
        }
        .mindmap-title:focus { 
            border-color: #ddd; 
            outline: none;
        }
        .toolbar {
            display: flex;
            gap: 0.5rem;
        }
        .btn { 
            padding: 0.7rem 1.2rem; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-primary { 
            background: #667eea; 
            color: white; 
        }
        .btn-success { 
            background: #28a745; 
            color: white; 
        }
        .btn-secondary { 
            background: #6c757d; 
            color: white; 
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .canvas-container { 
            height: calc(100vh - 70px); 
            position: relative; 
            overflow: auto; 
            background: #f0f2f5;
            background-image: 
                linear-gradient(rgba(0,0,0,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,.05) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .mindmap-canvas { 
            width: 2000px; 
            height: 2000px; 
            position: relative; 
        }
        .node { 
            position: absolute; 
            background: white; 
            border: 3px solid #667eea; 
            border-radius: 25px; 
            padding: 12px 20px; 
            cursor: move; 
            user-select: none; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            text-align: center; 
            min-width: 120px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            z-index: 10;
        }
        .node:hover { 
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15); 
            z-index: 100;
        }
        .node.selected {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            transform: scale(1.05);
        }
        .node.dragging {
            opacity: 0.8;
            z-index: 1000;
            cursor: grabbing;
        }
        .node.root { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            font-weight: bold;
            min-width: 150px;
        }
        .node-content { 
            background: transparent; 
            border: none; 
            outline: none; 
            width: 100%; 
            text-align: center; 
            font-size: 0.95rem;
            color: inherit;
            font-family: inherit;
        }
        .node-delete {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: #dc3545;
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            z-index: 100;
        }
        .node:hover .node-delete {
            display: flex;
        }
        .connection-line { 
            stroke: #999; 
            stroke-width: 2; 
            fill: none; 
            opacity: 0.6;
        }
        .status-indicator { 
            position: fixed; 
            bottom: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            background: rgba(0,0,0,0.8); 
            color: white; 
            padding: 0.8rem 1.5rem; 
            border-radius: 25px; 
            display: none; 
            z-index: 3000; 
        }
        .export-dropdown { 
            position: relative; 
            display: inline-block; 
        }
        .export-options { 
            display: none; 
            position: absolute; 
            right: 0;
            top: 100%;
            margin-top: 5px;
            background-color: white; 
            min-width: 160px; 
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); 
            z-index: 1; 
            border-radius: 8px;
            overflow: hidden;
        }
        .export-options a { 
            color: #333; 
            padding: 12px 16px; 
            text-decoration: none; 
            display: block; 
            transition: background 0.3s ease;
        }
        .export-options a:hover { 
            background-color: #f1f1f1; 
        }
        .export-dropdown:hover .export-options { 
            display: block; 
        }
        .controls-hint {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            font-size: 0.85rem;
            color: #666;
        }
        .controls-hint strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="editor-header">
        <div class="header-left">
            <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i></a>
            <input type="text" class="mindmap-title" id="mapTitle" placeholder="Enter mindmap title..." 
                   value="<?php echo htmlspecialchars($mindmapData['title'] ?? 'New MindMap'); ?>">
        </div>
        <div class="toolbar">
            <button class="btn btn-primary" onclick="addNode()">
                <i class="fas fa-plus"></i> Add Node
            </button>
            <button class="btn btn-danger" onclick="deleteSelectedNode()" id="deleteBtn" style="display:none;">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button class="btn btn-success" onclick="saveMindMap()">
                <i class="fas fa-save"></i> Save
            </button>
            <div class="export-dropdown">
                <button class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export
                </button>
                <div class="export-options">
                    <a href="#" onclick="exportMindMap('png'); return false;">
                        <i class="fas fa-image"></i> Export as PNG
                    </a>
                    <a href="#" onclick="exportMindMap('json'); return false;">
                        <i class="fas fa-code"></i> Export as JSON
                    </a>
                    <a href="#" onclick="exportMindMap('pdf'); return false;">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="canvas-container" id="canvasContainer">
        <div class="mindmap-canvas" id="canvas">
            <svg id="connections" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index: 1;"></svg>
        </div>
    </div>
    
    <div id="statusIndicator" class="status-indicator"></div>
    
    <div class="controls-hint">
        <strong>Controls:</strong><br>
        • Click node to select<br>
        • Drag nodes to move<br>
        • Press Tab to add child<br>
        • Press Delete to remove<br>
        • Ctrl+S to save
    </div>

    <script>
        let nodes = <?php echo json_encode($nodes, JSON_NUMERIC_CHECK) ?: '[]'; ?>;
        let mapId = <?php echo $mapId ? $mapId : 'null'; ?>;
        let isAIMap = <?php echo $isAIMap ? 'true' : 'false'; ?>;
        let isTemplate = <?php echo $isTemplate ? 'true' : 'false'; ?>;
        
        let selectedNode = null;
        let draggedNode = null;
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };
        let nodeIdCounter = nodes.length > 0 ? Math.max(...nodes.map(n => n.nodeId)) + 1 : 1;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (nodes.length === 0) {
                createRootNode();
            } else {
                renderExistingNodes();
            }
            setupEventListeners();
            
            // Auto-save every 30 seconds
            setInterval(() => {
                if (nodes.length > 0) {
                    saveMindMap(true); // silent save
                }
            }, 30000);
        });

        function createRootNode() {
            const node = {
                nodeId: nodeIdCounter++,
                parentId: null,
                content: 'Central Idea',
                x: 400,
                y: 300,
                color: '#667eea'
            };
            nodes.push(node);
            renderNode(node);
        }

        function renderExistingNodes() {
            // Clear canvas first
            const canvas = document.getElementById('canvas');
            const existingNodes = canvas.querySelectorAll('.node');
            existingNodes.forEach(node => node.remove());
            
            // Render all nodes
            nodes.forEach(node => renderNode(node));
            updateConnections();
        }

        function renderNode(node) {
            const el = document.createElement('div');
            el.className = 'node' + (node.parentId === null ? ' root' : '');
            el.id = 'node-' + node.nodeId;
            el.style.left = node.x + 'px';
            el.style.top = node.y + 'px';
            el.style.borderColor = node.color || '#667eea';
            
            if (node.parentId === null) {
                el.style.background = `linear-gradient(135deg, ${node.color || '#667eea'}, #764ba2)`;
            }
            
            // Create input for content
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'node-content';
            input.value = node.content;
            input.addEventListener('input', (e) => {
                node.content = e.target.value;
            });
            input.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            
            // Create delete button (not for root)
            if (node.parentId !== null) {
                const deleteBtn = document.createElement('div');
                deleteBtn.className = 'node-delete';
                deleteBtn.innerHTML = '×';
                deleteBtn.onclick = (e) => {
                    e.stopPropagation();
                    deleteNode(node.nodeId);
                };
                el.appendChild(deleteBtn);
            }
            
            el.appendChild(input);
            
            // Add event listeners for dragging
            el.addEventListener('mousedown', (e) => onNodeMouseDown(e, node));
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                selectNode(node);
            });
            
            document.getElementById('canvas').appendChild(el);
        }

        function selectNode(node) {
            // Remove previous selection
            document.querySelectorAll('.node.selected').forEach(n => {
                n.classList.remove('selected');
            });
            
            // Add selection to current node
            const nodeEl = document.getElementById('node-' + node.nodeId);
            if (nodeEl) {
                nodeEl.classList.add('selected');
            }
            
            selectedNode = node;
            
            // Show/hide delete button in toolbar
            const deleteBtn = document.getElementById('deleteBtn');
            if (node.parentId !== null) {
                deleteBtn.style.display = 'inline-flex';
            } else {
                deleteBtn.style.display = 'none';
            }
        }

        function addNode() {
            const parent = selectedNode || nodes.find(n => n.parentId === null);
            if (!parent) return;
            
            // Calculate position for new node
            const childNodes = nodes.filter(n => n.parentId === parent.nodeId);
            const angle = (childNodes.length * 60) % 360;
            const radius = 150;
            
            const x = parent.x + radius * Math.cos(angle * Math.PI / 180);
            const y = parent.y + radius * Math.sin(angle * Math.PI / 180);
            
            const node = {
                nodeId: nodeIdCounter++,
                parentId: parent.nodeId,
                content: 'New Idea',
                x: Math.max(50, Math.min(1900, x)),
                y: Math.max(50, Math.min(1900, y)),
                color: '#4285f4'
            };
            
            nodes.push(node);
            renderNode(node);
            updateConnections();
            selectNode(node);
            
            // Focus on the new node's input
            setTimeout(() => {
                const input = document.querySelector(`#node-${node.nodeId} input`);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 100);
        }

        function deleteNode(nodeId) {
            // Find all children of this node
            const childrenIds = [];
            const findChildren = (parentId) => {
                nodes.forEach(node => {
                    if (node.parentId === parentId) {
                        childrenIds.push(node.nodeId);
                        findChildren(node.nodeId);
                    }
                });
            };
            findChildren(nodeId);
            
            // Remove the node and all its children from DOM
            [nodeId, ...childrenIds].forEach(id => {
                const el = document.getElementById('node-' + id);
                if (el) el.remove();
            });
            
            // Remove from nodes array
            nodes = nodes.filter(n => n.nodeId !== nodeId && !childrenIds.includes(n.nodeId));
            
            // Clear selection
            selectedNode = null;
            document.getElementById('deleteBtn').style.display = 'none';
            
            updateConnections();
        }

        function deleteSelectedNode() {
            if (selectedNode && selectedNode.parentId !== null) {
                deleteNode(selectedNode.nodeId);
            }
        }

        function updateConnections() {
            const svg = document.getElementById('connections');
            svg.innerHTML = '';
            
            nodes.forEach(node => {
                if (node.parentId !== null) {
                    const parent = nodes.find(n => n.nodeId === node.parentId);
                    if (parent) {
                        drawConnection(parent, node);
                    }
                }
            });
        }

        function drawConnection(parent, child) {
            const svg = document.getElementById('connections');
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            
            const parentEl = document.getElementById('node-' + parent.nodeId);
            const childEl = document.getElementById('node-' + child.nodeId);
            
            if (!parentEl || !childEl) return;
            
            const parentX = parent.x + parentEl.offsetWidth / 2;
            const parentY = parent.y + parentEl.offsetHeight / 2;
            const childX = child.x + childEl.offsetWidth / 2;
            const childY = child.y + childEl.offsetHeight / 2;
            
            // Create curved path
            const midX = (parentX + childX) / 2;
            const midY = (parentY + childY) / 2;
            const controlX1 = midX;
            const controlY1 = parentY;
            const controlX2 = midX;
            const controlY2 = childY;
            
            const d = `M ${parentX} ${parentY} C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${childX} ${childY}`;
            
            line.setAttribute('d', d);
            line.classList.add('connection-line');
            svg.appendChild(line);
        }

        function onNodeMouseDown(e, node) {
            if (e.target.classList.contains('node-content') || e.target.classList.contains('node-delete')) {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            selectNode(node);
            draggedNode = node;
            isDragging = true;
            
            const nodeEl = document.getElementById('node-' + node.nodeId);
            nodeEl.classList.add('dragging');
            
            const rect = nodeEl.getBoundingClientRect();
            const canvasRect = document.getElementById('canvas').getBoundingClientRect();
            
            dragOffset = {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
            
            // Add document-level listeners for dragging
            document.addEventListener('mousemove', onDocumentMouseMove);
            document.addEventListener('mouseup', onDocumentMouseUp);
        }

        function onDocumentMouseMove(e) {
            if (!isDragging || !draggedNode) return;
            
            e.preventDefault();
            
            const canvas = document.getElementById('canvas');
            const canvasRect = canvas.getBoundingClientRect();
            const container = document.getElementById('canvasContainer');
            
            // Calculate new position relative to canvas
            let newX = e.clientX - canvasRect.left - dragOffset.x + container.scrollLeft;
            let newY = e.clientY - canvasRect.top - dragOffset.y + container.scrollTop;
            
            // Constrain to canvas bounds
            newX = Math.max(0, Math.min(1900, newX));
            newY = Math.max(0, Math.min(1900, newY));
            
            draggedNode.x = newX;
            draggedNode.y = newY;
            
            const nodeEl = document.getElementById('node-' + draggedNode.nodeId);
            if (nodeEl) {
                nodeEl.style.left = newX + 'px';
                nodeEl.style.top = newY + 'px';
            }
            
            updateConnections();
        }

        function onDocumentMouseUp(e) {
            if (draggedNode) {
                const nodeEl = document.getElementById('node-' + draggedNode.nodeId);
                if (nodeEl) {
                    nodeEl.classList.remove('dragging');
                }
            }
            
            isDragging = false;
            draggedNode = null;
            
            // Remove document-level listeners
            document.removeEventListener('mousemove', onDocumentMouseMove);
            document.removeEventListener('mouseup', onDocumentMouseUp);
        }

        function setupEventListeners() {
            // Canvas click to deselect
            document.getElementById('canvas').addEventListener('click', (e) => {
                if (e.target.id === 'canvas') {
                    document.querySelectorAll('.node.selected').forEach(n => {
                        n.classList.remove('selected');
                    });
                    selectedNode = null;
                    document.getElementById('deleteBtn').style.display = 'none';
                }
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    addNode();
                } else if (e.key === 'Delete' && selectedNode && selectedNode.parentId !== null) {
                    e.preventDefault();
                    deleteNode(selectedNode.nodeId);
                } else if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    saveMindMap();
                }
            });
        }

        function saveMindMap(silent = false) {
            const title = document.getElementById('mapTitle').value.trim();
            if (!title) {
                if (!silent) showStatus('Title is required!', 'error');
                return;
            }
            
            if (!silent) showStatus('Saving...');
            
            fetch('api/save_mindmap.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, nodes, mapId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (!mapId && data.mapId) {
                        mapId = data.mapId;
                        window.history.replaceState({}, '', `editor.php?id=${mapId}`);
                    }
                    if (!silent) showStatus('Saved successfully!', 'success');
                } else {
                    if (!silent) showStatus('Error: ' + data.message, 'error');
                }
            })
            .catch(err => {
                if (!silent) showStatus('Save failed!', 'error');
                console.error('Save error:', err);
            });
        }

        function exportMindMap(format) {
            const title = document.getElementById('mapTitle').value.trim() || 'mindmap';
            
            if (format === 'png') {
                showStatus('Generating PNG...');
                
                // Temporarily hide controls hint for export
                const hint = document.querySelector('.controls-hint');
                hint.style.display = 'none';
                
                html2canvas(document.getElementById('canvas'), {
                    backgroundColor: '#f0f2f5',
                    scale: 2
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = `${title}.png`;
                    link.href = canvas.toDataURL();
                    link.click();
                    hint.style.display = 'block';
                    showStatus('PNG exported!', 'success');
                });
            } else if (format === 'json') {
                const dataStr = JSON.stringify({ title, nodes }, null, 2);
                const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                const link = document.createElement('a');
                link.setAttribute('href', dataUri);
                link.setAttribute('download', `${title}.json`);
                link.click();
                showStatus('JSON exported!', 'success');
            } else if (format === 'pdf') {
                showStatus('Generating PDF...');
                const { jsPDF } = window.jspdf;
                
                html2canvas(document.getElementById('canvas'), {
                    backgroundColor: '#f0f2f5',
                    scale: 2
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('l', 'mm', 'a4');
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = pdf.internal.pageSize.getHeight();
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save(`${title}.pdf`);
                    showStatus('PDF exported!', 'success');
                });
            }
        }

        function showStatus(message, type = 'info') {
            const indicator = document.getElementById('statusIndicator');
            indicator.textContent = message;
            indicator.style.display = 'block';
            
            if (type === 'error') {
                indicator.style.background = '#dc3545';
            } else if (type === 'success') {
                indicator.style.background = '#28a745';
            } else {
                indicator.style.background = 'rgba(0,0,0,0.8)';
            }
            
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>