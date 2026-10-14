<?php
// reinit_db.php - Recreate schema and seed data cleanly with UTF-8
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

echo "Executing schema.sql...\n";
$schemaSql = file_get_contents(__DIR__ . '/../schema.sql');
$pdo->exec($schemaSql);
echo "Schema created successfully.\n";

echo "Executing seed.sql...\n";
$seedSql = file_get_contents(__DIR__ . '/../seed.sql');
// seed.sql has multiple queries
$pdo->exec($seedSql);
echo "Seed executed successfully.\n";

echo "Verifying Course 1 title...\n";
$row = $pdo->query("SELECT title FROM courses WHERE id=1")->fetch();
echo "Course 1: " . $row['title'] . "\n";

echo "Verifying Question 1 prompt...\n";
$row = $pdo->query("SELECT prompt FROM questions WHERE id=1")->fetch();
echo "Question 1: " . $row['prompt'] . "\n";

echo "Verifying User 1 name...\n";
$row = $pdo->query("SELECT full_name FROM users WHERE id=1")->fetch();
echo "User 1: " . $row['full_name'] . "\n";

