<?php
// signup.php
require_once 'config.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_POST) {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT userId FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Create new user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, passwordHash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $passwordHash]);

                // Auto-login the user
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;

                redirect('dashboard.php');
            }
        } catch(PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MindMap Generator</title>
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
        .form-group label { display: block; color: white; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 1rem; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 10px; background: rgba(255, 255, 255, 0.1); color: white; font-size: 1rem; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: white; background: rgba(255, 255, 255, 0.2); }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.7); }
        .btn { width: 100%; padding: 1rem; border: none; border-radius: 10px; background: white; color: #667eea; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
        .alert-error { background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.5); color: #ff6b7d; }
        .auth-links { text-align: center; margin-top: 2rem; }
        .auth-links a { color: white; text-decoration: none; opacity: 0.8; transition: opacity 0.3s ease; }
        .auth-links a:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-user-plus"></i> Join Us</h1>
            <p>Create your MindMap Generator account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Create a password (min 6 characters)" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-rocket"></i> Create Account
            </button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>
</body>
</html>