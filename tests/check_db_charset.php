<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "Database charset variables:\n";
$stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set%'");
while ($r = $stmt->fetch()) {
    echo $r['Variable_name'] . " = " . $r['Value'] . "\n";
}

echo "\nDatabase collation variables:\n";
$stmt = $pdo->query("SHOW VARIABLES LIKE 'collation%'");
while ($r = $stmt->fetch()) {
    echo $r['Variable_name'] . " = " . $r['Value'] . "\n";
}

echo "\nCheck questions table charset:\n";
$stmt = $pdo->query("SHOW CREATE TABLE questions");
$row = $stmt->fetch();
echo $row['Create Table'] . "\n";

echo "\nSample question prompt:\n";
$stmt = $pdo->query("SELECT id, prompt FROM questions LIMIT 5");
while ($r = $stmt->fetch()) {
    echo "#" . $r['id'] . ": " . $r['prompt'] . "\n";
}

echo "\nSample courses title:\n";
$stmt = $pdo->query("SELECT id, title FROM courses LIMIT 5");
while ($r = $stmt->fetch()) {
    echo "#" . $r['id'] . ": " . $r['title'] . "\n";
}

