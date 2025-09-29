-- MindMap Generator Database Setup
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS mindmap_generator;
USE mindmap_generator;

-- Users table
CREATE TABLE users (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MindMaps table
CREATE TABLE mindmaps (
    mapId INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    userId INT NOT NULL,
    FOREIGN KEY (userId) REFERENCES users(userId) ON DELETE CASCADE
);

-- Nodes table
CREATE TABLE nodes (
    nodeId INT AUTO_INCREMENT PRIMARY KEY,
    mapId INT NOT NULL,
    parentId INT NULL,
    content TEXT NOT NULL,
    x INT DEFAULT 0,
    y INT DEFAULT 0,
    color VARCHAR(7) DEFAULT '#4285f4',
    FOREIGN KEY (mapId) REFERENCES mindmaps(mapId) ON DELETE CASCADE,
    FOREIGN KEY (parentId) REFERENCES nodes(nodeId) ON DELETE CASCADE
);

-- Insert sample data for testing
INSERT INTO users (username, email, passwordHash) VALUES
('demo_user', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Sample mindmap
INSERT INTO mindmaps (title, userId) VALUES ('My First MindMap', 1);

-- Sample nodes
INSERT INTO nodes (mapId, parentId, content, x, y) VALUES
(1, NULL, 'Central Idea', 400, 300),
(1, 1, 'Branch 1', 300, 200),
(1, 1, 'Branch 2', 500, 200),
(1, 1, 'Branch 3', 400, 450);



-- Drop the table if it exists to ensure a clean setup
DROP TABLE IF EXISTS `templates`;

-- Create the new templates table
CREATE TABLE `templates` (
  `templateId` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `nodeStructure` JSON NOT NULL
);

-- Insert a "SWOT Analysis" template
INSERT INTO `templates` (`title`, `description`, `nodeStructure`) VALUES
('SWOT Analysis', 'Analyze your project''s strengths, weaknesses, opportunities, and threats.', '[
    {"nodeId": 1, "parentId": null, "content": "SWOT Analysis", "x": 400, "y": 200, "color": "#667eea"},
    {"nodeId": 2, "parentId": 1, "content": "Strengths", "x": 200, "y": 350, "color": "#28a745"},
    {"nodeId": 3, "parentId": 1, "content": "Weaknesses", "x": 400, "y": 350, "color": "#dc3545"},
    {"nodeId": 4, "parentId": 1, "content": "Opportunities", "x": 600, "y": 350, "color": "#ffc107"},
    {"nodeId": 5, "parentId": 1, "content": "Threats", "x": 800, "y": 350, "color": "#6c757d"}
]');

-- Insert a "Project Plan" template
INSERT INTO `templates` (`title`, `description`, `nodeStructure`) VALUES
('Project Plan', 'A basic structure for planning a new project.', '[
    {"nodeId": 1, "parentId": null, "content": "New Project Plan", "x": 400, "y": 150, "color": "#667eea"},
    {"nodeId": 2, "parentId": 1, "content": "Goals", "x": 200, "y": 300, "color": "#4285f4"},
    {"nodeId": 3, "parentId": 1, "content": "Timeline", "x": 400, "y": 300, "color": "#4285f4"},
    {"nodeId": 4, "parentId": 1, "content": "Budget", "x": 600, "y": 300, "color": "#4285f4"},
    {"nodeId": 5, "parentId": 2, "content": "Primary Objective", "x": 150, "y": 450, "color": "#34a853"},
    {"nodeId": 6, "parentId": 2, "content": "Key Deliverables", "x": 250, "y": 450, "color": "#34a853"}
]');