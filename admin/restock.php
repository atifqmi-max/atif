<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $accounts = $_POST['accounts'] ?? '';
    $category = preg_replace('/[^a-zA-Z0-9_-]/', '', $category);

    if ($category && !empty(trim($accounts))) {
        $file = ACCOUNTS_DIR . $category . '.txt';
        $lines = array_filter(array_map('trim', explode("\n", $accounts)));
        // simple email:pass validation (optional)
        $validLines = array_filter($lines, function($line) {
            return preg_match('/^.+@.+\..+:.+$/', $line);
        });

        if (!empty($validLines)) {
            file_put_contents($file, implode("\n", $validLines) . "\n", FILE_APPEND | LOCK_EX);
            $message = '<p style="color:#4ade80;">✅ Accounts added successfully!</p>';
        } else {
            $message = '<p style="color:#f87171;">❌ No valid email:pass lines found.</p>';
        }
    } else {
        $message = '<p style="color:#f87171;">Please fill all fields.</p>';
    }
}

$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restock · Pro Free Gen</title>
    <style>
        body { background: #0b0e14; color: #eee; font-family: system-ui; padding: 2rem; }
        .container { max-width: 700px; margin: 0 auto; background: #161c28; border-radius: 36px; padding: 2.5rem; border: 1px solid #2e3650; }
        h1 { color: #c7b9ff; }
        label { display: block; margin: 1.5rem 0 0.5rem; font-weight: 600; color: #b9c2e0; }
        select, textarea { width: 100%; padding: 1rem; background: #0f1524; border: 1px solid #354052; color: white; border-radius: 28px; }
        textarea { border-radius: 28px; resize: vertical; }
        button { background: #8b5cf6; color: white; border: none; padding: 1rem 2rem; border-radius: 60px; font-weight: bold; font-size: 1.2rem; margin-top: 1.5rem; cursor: pointer; }
        .logout { display: inline-block; margin-top: 1.5rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Pro Free Gen · Restock Panel</h1>
        <?php echo $message; ?>
        <form method="post">
            <label>📁 Category</label>
            <select name="category" required>
                <option value="">-- select --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>

            <label>📝 Accounts (one email:pass per line)</label>
            <textarea name="accounts" rows="10" placeholder="example@gmail.com:password123&#10;user2@outlook.com:pass456" required></textarea>

            <button type="submit">➕ Add to Stock</button>
        </form>
        <a href="logout.php" class="logout">🚪 Logout</a>
    </div>
</body>
</html>
