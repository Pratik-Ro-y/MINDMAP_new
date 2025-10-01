<?php
// login.php
require_once 'config.php';

// If user is already logged in, redirect to the dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // Find user by email
            $stmt = $pdo->prepare("SELECT userId, username, passwordHash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify password
            if ($user && password_verify($password, $user['passwordHash'])) {
                // Set session variables and redirect
                $_SESSION['user_id'] = $user['userId'];
                $_SESSION['username'] = $user['username'];
                redirect('dashboard.php');
            } else {
                $error = 'Invalid email or password.';
            }
        } catch(PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MindMap Generator</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .auth-container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 3rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 450px; animation: slideIn 0.5s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .auth-header { text-align: center; margin-bottom: 2rem; color: white; }
        .auth-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .auth-header p { opacity: 0.8; }
        .form-group { margin-bottom: 1.5rem; }
        .form-control { width: 100%; padding: 1rem; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 10px; background: rgba(255, 255, 255, 0.1); color: white; font-size: 1rem; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: white; background: rgba(255, 255, 255, 0.2); }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.7); }
        .btn { width: 100%; padding: 1rem; border: none; border-radius: 10px; background: white; color: #667eea; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .alert-error { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.5); color: #ff6b7d; }
        .auth-links { text-align: center; margin-top: 2rem; }
        .auth-links a { color: white; text-decoration: none; opacity: 0.8; transition: opacity 0.3s ease; }
        .auth-links a:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-sign-in-alt"></i> Welcome Back</h1>
            <p>Log in to access your mindmaps</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <div class="auth-links">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
        </div>
    </div>
</body>
</html>