<?php
$files = [
    __DIR__ . '/../exam_result.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../includes/lang.php'
];

foreach ($files as $file) {
    $lines = file($file);
    foreach ($lines as $i => $line) {
        if (preg_match('/(Ã¡|Ã|áº|á»|Ã©|Ã¨|Ã³|Ã²|Ãº|Ã¹)/', $line, $m)) {
            echo basename($file) . " L" . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}

