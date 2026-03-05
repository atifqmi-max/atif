<?php
session_start();
$admin_pass = 'MySecret123'; // CHANGE THIS!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === $admin_pass) {
        $_SESSION['admin'] = true;
        header('Location: restock.php');
        exit;
    } else {
        $error = 'Wrong password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login · Pro Free Gen</title>
    <style>
        body { background: #0d0f17; color: #ddd; font-family: system-ui; display: flex; height: 100vh; align-items: center; justify-content: center; }
        .login-box { background: #1a1e2d; padding: 2.5rem; border-radius: 32px; border: 1px solid #3a405b; width: 300px; }
        h2 { margin-top: 0; color: #a78bfa; }
        input { width: 100%; padding: 0.8rem; margin: 1rem 0; background: #0f1322; border: 1px solid #343b55; color: white; border-radius: 40px; }
        button { background: #6d28d9; color: white; border: none; padding: 0.8rem; width: 100%; border-radius: 40px; font-weight: bold; cursor: pointer; }
        .error { color: #f87171; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔒 Owner only</h2>
        <form method="post">
            <input type="password" name="password" placeholder="Admin password" required>
            <button type="submit">Login</button>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </form>
    </div>
</body>
</html>
