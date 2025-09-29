<?php
require_once 'config.php';
requireLogin();

try {
    $stmt = $pdo->prepare("SELECT templateId, title, description FROM templates");
    $stmt->execute();
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error - for now, just show an empty array
    $templates = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Templates - MindMap Platform</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="editor.php"><i class="fas fa-plus"></i> New MindMap</a></li>
                    <li><a href="templates.php" class="active"><i class="fas fa-th-large"></i> Templates</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <header class="top-navbar">
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </header>

            <main class="mindmap-list-section templates-page">
                <h2>Choose a Template</h2>
                <p>Start your mindmap with a pre-defined structure to save time.</p>
                
                <div class="mindmaps-grid">
                    <?php if (empty($templates)): ?>
                        <p>No templates are available at the moment. Please check back later.</p>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <div class="mindmap-card">
                                <h3 class="mindmap-card-title"><?php echo htmlspecialchars($template['title']); ?></h3>
                                <p class="template-description"><?php echo htmlspecialchars($template['description']); ?></p>
                                <div class="mindmap-card-actions">
                                    <a href="editor.php?template_id=<?php echo $template['templateId']; ?>">Use Template</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>