<?php
require_once '../includes/config.php';

$categories = getCategories();
$stock = [];

foreach ($categories as $cat) {
    $stock[$cat] = getStock($cat);
}

header('Content-Type: application/json');
echo json_encode($stock);
?>
