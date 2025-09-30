<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to MindMap Generator</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 2rem; }
        .container { max-width: 800px; animation: fadeIn 1s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .logo { font-size: 4rem; margin-bottom: 1rem; }
        h1 { font-size: 3rem; font-weight: 700; margin-bottom: 1rem; }
        p { font-size: 1.2rem; opacity: 0.9; margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto; }
        .btn-group a { text-decoration: none; }
        .btn { display: inline-block; padding: 1rem 2.5rem; border-radius: 50px; font-size: 1.1rem; font-weight: 600; transition: all 0.3s ease; border: 2px solid white; }
        .btn-primary { background: white; color: #667eea; }
        .btn-primary:hover { background: transparent; color: white; transform: translateY(-3px); }
        .btn-secondary { background: transparent; color: white; margin-left: 1rem; }
        .btn-secondary:hover { background: white; color: #667eea; transform: translateY(-3px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><i class="fas fa-brain"></i></div>
        <h1>MindMap Generator</h1>
        <p>The intuitive and powerful tool to capture, develop, and share your ideas visually. Unleash your creativity and organize your thoughts like never before.</p>
        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">Get Started</a>
            <a href="signup.php" class="btn btn-secondary">Sign Up</a>
        </div>
    </div>
</body>
</html>