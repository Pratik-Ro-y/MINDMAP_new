<?php
require_once 'config.php';
requireLogin();

$mindmaps = [];
$errorMessage = '';

try {
    $stmt = $pdo->prepare("SELECT mapId, title, updatedAt FROM mindmaps WHERE userId = ? ORDER BY updatedAt DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $mindmaps = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Unable to load your mindmaps. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MindMap Platform</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-progress { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 9999; text-align: center; }
        .upload-progress.active { display: block; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid var(--primary-color); border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .alert { padding: 1rem; border-radius: 10px; margin: 1rem 0; display: none; }
        .alert.show { display: block; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .empty-state { text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .empty-state i { font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem; }
        .quick-action-btn { padding: 1.5rem; background: white; border-radius: 10px; text-align: center; text-decoration: none; color: var(--text-dark); box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .quick-action-btn:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .quick-action-btn i { font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem; display: block; }
        .mindmap-card-meta { font-size: 0.85rem; color: var(--text-light); margin-top: 0.5rem; }
        .delete-btn { color: #dc3545; cursor: pointer; float: right; padding: 0.3rem; }
        .delete-btn:hover { color: #a71d2a; }
        .search-bar { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-brain logo-icon"></i>
                <span class="logo-text">MindMap</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="editor.php"><i class="fas fa-plus"></i> New MindMap</a></li>
                    <li><a href="templates.php"><i class="fas fa-th-large"></i> Templates</a></li>
                </ul>
                
                <div class="sidebar-section-title">AI Generator</div>
                <form id="ai-upload-form" enctype="multipart/form-data">
                    <label for="ai-file-input" class="upload-card">
                        <i class="fas fa-robot"></i>
                        <div>Upload & Generate</div>
                        <small>Drop a file or click</small>
                    </label>
                    <input type="file" id="ai-file-input" name="document" style="display: none;">
                </form>
                
                <?php if (!empty($mindmaps)): ?>
                <div class="sidebar-section-title">Recent MindMaps</div>
                <ul>
                    <?php foreach (array_slice($mindmaps, 0, 5) as $mindmap): ?>
                        <li>
                            <a href="editor.php?id=<?php echo $mindmap['mapId']; ?>">
                                <i class="fas fa-project-diagram"></i> 
                                <?php echo htmlspecialchars(substr($mindmap['title'], 0, 20)); ?><?php if (strlen($mindmap['title']) > 20) echo '...'; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </nav>
        </aside>
        
        <div class="main-content">
            <header class="top-navbar">
                <span style="margin-right: auto; color: var(--text-dark); font-weight: 600;">
                    Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                </span>
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </header>
            
            <main class="mindmap-list-section">
                <div id="alertContainer"></div>
                
                <?php if ($errorMessage): ?>
                    <div class="alert alert-error show"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>
                
                <h2>Your MindMaps</h2>
                
                <?php if (empty($mindmaps)): ?>
                    <div class="empty-state">
                        <i class="fas fa-lightbulb"></i>
                        <h3>Ready to organize your thoughts?</h3>
                        <p>Create your first mindmap to get started!</p>
                        <div class="quick-actions">
                            <a href="editor.php" class="quick-action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <strong>Create Blank</strong>
                            </a>
                            <a href="templates.php" class="quick-action-btn">
                                <i class="fas fa-magic"></i>
                                <strong>Use Template</strong>
                            </a>
                            <label for="ai-file-input-2" class="quick-action-btn" style="cursor: pointer;">
                                <i class="fas fa-file-upload"></i>
                                <strong>Upload File</strong>
                            </label>
                            <input type="file" id="ai-file-input-2" name="document" style="display: none;">
                        </div>
                    </div>
                <?php else: ?>
                    <input type="text" id="searchBar" class="search-bar" placeholder="Search for a mindmap...">
                    <p>You have <?php echo count($mindmaps); ?> mindmap<?php echo count($mindmaps) != 1 ? 's' : ''; ?>.</p>
                    <div class="mindmaps-grid">
                        <?php foreach ($mindmaps as $mindmap): ?>
                            <div class="mindmap-card" data-mapid="<?php echo $mindmap['mapId']; ?>" data-title="<?php echo htmlspecialchars($mindmap['title']); ?>">
                                <span class="delete-btn" onclick="deleteMindmap(<?php echo $mindmap['mapId']; ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <h3 class="mindmap-card-title"><?php echo htmlspecialchars($mindmap['title']); ?></h3>
                                <div class="mindmap-card-meta">Last updated: <?php echo date('M d, Y', strtotime($mindmap['updatedAt'])); ?></div>
                                <div class="mindmap-card-actions">
                                    <a href="editor.php?id=<?php echo $mindmap['mapId']; ?>"><i class="fas fa-edit"></i> Open Map</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <div class="upload-progress" id="uploadProgress">
        <div class="spinner"></div>
        <p>Generating your mindmap...</p>
    </div>
    
    <script>
        document.getElementById('searchBar')?.addEventListener('keyup', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.mindmap-card').forEach(card => {
                const title = card.getAttribute('data-title').toLowerCase();
                card.style.display = title.includes(searchTerm) ? 'block' : 'none';
            });
        });

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} show`;
            alertDiv.textContent = message;
            alertContainer.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }
        
        function handleFileUpload(file) {
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showAlert('File size must be less than 5MB', 'error');
                return;
            }
            const formData = new FormData();
            formData.append('document', file);
            
            const progressIndicator = document.getElementById('uploadProgress');
            progressIndicator.classList.add('active');
            
            fetch('api/ai_generator.php', { method: 'POST', body: formData })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Server error'); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showAlert(data.message);
                        setTimeout(() => { window.location.href = 'editor.php?ai=true'; }, 1000);
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => showAlert(error.message, 'error'))
                .finally(() => progressIndicator.classList.remove('active'));
        }
        
        document.getElementById('ai-file-input')?.addEventListener('change', function(e) {
            handleFileUpload(this.files[0]);
            this.value = '';
        });
        
        document.getElementById('ai-file-input-2')?.addEventListener('change', function(e) {
            handleFileUpload(this.files[0]);
            this.value = '';
        });
        
        const uploadCards = document.querySelectorAll('.upload-card');
        uploadCards.forEach(card => {
            card.addEventListener('dragover', e => { e.preventDefault(); card.style.backgroundColor = '#f8faff'; });
            card.addEventListener('dragleave', e => { e.preventDefault(); card.style.backgroundColor = ''; });
            card.addEventListener('drop', e => {
                e.preventDefault();
                card.style.backgroundColor = '';
                if (e.dataTransfer.files.length > 0) {
                    handleFileUpload(e.dataTransfer.files[0]);
                }
            });
        });
        
        function deleteMindmap(mapId) {
            if (!confirm('Are you sure you want to delete this mindmap?')) return;
            
            fetch('api/delete_mindmap.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mapId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Mindmap deleted successfully');
                    document.querySelector(`[data-mapid="${mapId}"]`)?.remove();
                    if (document.querySelectorAll('.mindmap-card').length === 0) {
                        location.reload();
                    }
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(() => showAlert('Failed to delete mindmap', 'error'));
        }
    </script>
</body>
</html>