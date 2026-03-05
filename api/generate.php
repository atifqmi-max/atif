<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['category'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$category = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['category']); // sanitize
$file = ACCOUNTS_DIR . $category . '.txt';

// Check if category exists
if (!file_exists($file)) {
    echo json_encode(['success' => false, 'message' => 'Category not found.']);
    exit;
}

// Cooldown check (per session per category)
if (!isset($_SESSION['cooldown'])) {
    $_SESSION['cooldown'] = [];
}

if (isset($_SESSION['cooldown'][$category])) {
    $remaining = COOLDOWN_TIME - (time() - $_SESSION['cooldown'][$category]);
    if ($remaining > 0) {
        $minutes = ceil($remaining / 60);
        echo json_encode([
            'success' => false,
            'message' => "Please wait {$minutes} minute(s) before generating another {$category} account."
        ]);
        exit;
    }
}

// Open file with exclusive lock
$fp = fopen($file, 'r+');
if (flock($fp, LOCK_EX)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (empty($lines)) {
        flock($fp, LOCK_UN);
        fclose($fp);
        echo json_encode(['success' => false, 'message' => 'Out of stock for this category.']);
        exit;
    }

    // Take the first account
    $account = array_shift($lines);
    
    // Write back remaining lines
    $new_content = implode("\n", $lines) . (empty($lines) ? '' : "\n");
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $new_content);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    // Update cooldown
    $_SESSION['cooldown'][$category] = time();

    // Return success
    echo json_encode([
        'success' => true,
        'account' => $account,
        'new_stock' => count($lines)
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Could not lock file. Try again.']);
    exit;
}
?>
