<?php
session_start();

// Path to accounts folder
define('ACCOUNTS_DIR', __DIR__ . '/../accounts/');

// Cooldown time in seconds (10 minutes)
define('COOLDOWN_TIME', 600);

// Ensure the accounts directory exists
if (!is_dir(ACCOUNTS_DIR)) {
    mkdir(ACCOUNTS_DIR, 0755, true);
}

// Helper function to get all available categories (txt files)
function getCategories() {
    $files = glob(ACCOUNTS_DIR . '*.txt');
    $categories = [];
    foreach ($files as $file) {
        $categories[] = pathinfo($file, PATHINFO_FILENAME);
    }
    return $categories;
}

// Helper function to get stock count for a category
function getStock($category) {
    $file = ACCOUNTS_DIR . $category . '.txt';
    if (!file_exists($file)) return 0;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return count($lines);
}
?>
