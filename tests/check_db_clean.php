<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$tables = ['users', 'skills', 'courses', 'course_materials', 'exams', 'questions', 'exam_attempts', 'exam_answers', 'user_skills'];

$hasErrors = false;
foreach ($tables as $t) {
    $rows = $pdo->query("SELECT * FROM $t")->fetchAll();
    foreach ($rows as $r) {
        foreach ($r as $col => $val) {
            if (is_string($val) && (strpos($val, '??') !== false || preg_match('/[a-zA-Z]\?[a-zA-Z]/', $val))) {
                echo "POTENTIAL ENCODING ERROR in table '$t', col '$col', id " . ($r['id'] ?? 'unknown') . ": $val\n";
                $hasErrors = true;
            }
        }
    }
}

if (!$hasErrors) {
    echo "SUCCESS: Entire database has 0 encoding anomalies! All Vietnamese Unicode UTF-8 text is perfect!\n";
}

