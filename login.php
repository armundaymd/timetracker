<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['api_token'] = $user['api_token'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title>Login · Time Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #f1f5f9;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --accent: #0ea5e9;
            --accent-hover: #0284c7;
            --radius: 14px;
            --radius-sm: 10px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.08);
        }
        * { -webkit-tap-highlight-color: transparent; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-page);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-card {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 380px;
        }
        .login-card h1 { font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; }
        .login-card .form-control {
            font-size: 1rem;
            min-height: 48px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        .login-card .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14,165,233,0.15);
        }
        .login-card .btn-primary {
            min-height: 48px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            background: var(--accent);
            border: none;
        }
        .login-card .btn-primary:hover { background: var(--accent-hover); }
    </style>
</head>
<body>
    <div class="login-card">
        <h1 class="text-center">Time Tracker</h1>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>"; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-500">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label fw-500">Password</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>