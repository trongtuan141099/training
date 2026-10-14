<?php
// includes/skillmap.php - Core Manufacturing Logic, Prerequisite Checks & Auto-Grading

require_once __DIR__ . '/../config/db.php';

/**
 * Kiểm tra xem Worker có đủ điều kiện mở khóa và học/làm bài thi của một khóa học hay không
 * Quy tắc bắt buộc: Nếu có khóa học tiền đề (prerequisite), điểm thi cấp trước phải >= min_pass_score (mặc định 80%)
 */
function canAccessCourse(int $userId, int $courseId): array {
    $db = getDB();

    $stmt = $db->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        return ['allowed' => false, 'reason' => 'Khóa học không tồn tại.'];
    }

    // Cấp độ 1 hoặc không có khóa tiền đề -> Luôn mở khóa
    if ((int)$course['level'] === 1 || empty($course['prerequisite_course_id'])) {
        return [
            'allowed' => true,
            'reason'  => 'Cấp độ cơ bản - Mở khóa tự do.',
            'course'  => $course
        ];
    }

    // Lấy thông tin khóa học tiền đề
    $stmtPrev = $db->prepare('SELECT c.*, e.id AS exam_id, e.title AS exam_title FROM courses c LEFT JOIN exams e ON e.course_id = c.id WHERE c.id = :id LIMIT 1');
    $stmtPrev->execute(['id' => $course['prerequisite_course_id']]);
    $prevCourse = $stmtPrev->fetch();

    if (!$prevCourse) {
        return ['allowed' => false, 'reason' => 'Khóa học tiền đề không hợp lệ.'];
    }

    $minScore = (float)$prevCourse['min_pass_score'];

    // Kiểm tra lịch sử làm bài thi của khóa học tiền đề
    $stmtHistory = $db->prepare('
        SELECT MAX(ea.score_obtained) AS highest_score, COUNT(ea.id) AS total_attempts,
               SUM(CASE WHEN ea.passed = 1 AND ea.score_obtained >= :min_score THEN 1 ELSE 0 END) AS passed_count
        FROM exam_attempts ea
        JOIN exams e ON e.id = ea.exam_id
        WHERE ea.user_id = :user_id 
          AND e.course_id = :prev_course_id
    ');
    $stmtHistory->execute([
        'min_score'      => $minScore,
        'user_id'        => $userId,
        'prev_course_id' => $prevCourse['id']
    ]);
    $history = $stmtHistory->fetch();

    $highestScore = (float)($history['highest_score'] ?? 0);
    $passedCount = (int)($history['passed_count'] ?? 0);

    if ($passedCount > 0) {
        return [
            'allowed'       => true,
            'reason'        => sprintf('Đã hoàn thành xuất sắc cấp trước (%s) với điểm số %.1f/100.', $prevCourse['title'], $highestScore),
            'course'        => $course,
            'prev_course'   => $prevCourse,
            'highest_score' => $highestScore
        ];
    }

    return [
        'allowed'       => false,
        'reason'        => sprintf(
            'BỊ KHÓA: Bạn bắt buộc phải đạt điểm tối thiểu %.0f%% ở khóa học cấp trước [%s] để mở khóa cấp độ này. Điểm cao nhất hiện tại của bạn: %.1f%%.',
            $minScore,
            $prevCourse['title'],
            $highestScore
        ),
        'course'        => $course,
        'prev_course'   => $prevCourse,
        'highest_score' => $highestScore
    ];
}

/**
 * Chấm điểm tự động cho các loại câu hỏi (Tương tác Hotspot / Polygon / Thứ tự / Trắc nghiệm)
 */
function gradeQuestion(array $question, $rawUserResponse): array {
    $qType = $question['question_type'];
    $config = is_string($question['answer_config']) ? json_decode($question['answer_config'], true) : $question['answer_config'];
    $userResponse = is_string($rawUserResponse) ? json_decode($rawUserResponse, true) : $rawUserResponse;
    $maxScore = (float)$question['score'];

    // Trường hợp câu hỏi Tự luận (Cần Trainer chấm tay)
    if ($qType === 'ESSAY') {
        return [
            'is_correct'    => null, // Chờ duyệt
            'awarded_score' => 0.00,
            'needs_manual'  => true,
            'feedback'      => 'Đang chờ Giảng viên/Trainer đánh giá và chấm điểm.'
        ];
    }

    // 1. HOTSPOT_PINPOINT (Chạm ngón tay vào vị trí linh kiện / nút bấm trên ảnh)
    if ($qType === 'HOTSPOT_PINPOINT') {
        $targetX = (float)($config['target_x_pct'] ?? 0);
        $targetY = (float)($config['target_y_pct'] ?? 0);
        $tolerance = (float)($config['tolerance_radius_pct'] ?? 7.0);

        $clickX = (float)($userResponse['clicked_x_pct'] ?? -999);
        $clickY = (float)($userResponse['clicked_y_pct'] ?? -999);

        // Tính khoảng cách Euclidean giữa điểm chạm và điểm mục tiêu (%)
        $distance = sqrt(pow($clickX - $targetX, 2) + pow($clickY - $targetY, 2));

        if ($distance <= $tolerance) {
            return [
                'is_correct'    => true,
                'awarded_score' => $maxScore,
                'needs_manual'  => false,
                'feedback'      => sprintf('Chính xác! Vị trí chạm lệch %.1f%% (trong phạm vi cho phép %.1f%%).', $distance, $tolerance)
            ];
        }

        return [
            'is_correct'    => false,
            'awarded_score' => 0.00,
            'needs_manual'  => false,
            'feedback'      => sprintf('Chưa chính xác. Điểm chạm lệch %.1f%% so với vị trí linh kiện chuẩn.', $distance)
        ];
    }

    // 2. POLYGON_DEFECT (Khoanh vùng lỗi sản phẩm trên màn hình cảm ứng)
    if ($qType === 'POLYGON_DEFECT') {
        $centerX = (float)($config['center_x_pct'] ?? 50.0);
        $centerY = (float)($config['center_y_pct'] ?? 44.0);
        $tolerance = (float)($config['tolerance_radius_pct'] ?? 10.0);

        // Kiểm tra tâm của vùng người dùng đã khoanh (hoặc các điểm vẽ)
        $drawnPoints = $userResponse['drawn_polygon'] ?? [];
        if (empty($drawnPoints)) {
            return [
                'is_correct'    => false,
                'awarded_score' => 0.00,
                'needs_manual'  => false,
                'feedback'      => 'Bạn chưa khoanh vùng lỗi trên hình ảnh.'
            ];
        }

        // Tính trọng tâm (Centroid) của vùng vẽ
        $sumX = 0; $sumY = 0; $count = count($drawnPoints);
        foreach ($drawnPoints as $pt) {
            $sumX += (float)$pt['x'];
            $sumY += (float)$pt['y'];
        }
        $avgX = $sumX / max(1, $count);
        $avgY = $sumY / max(1, $count);

        $distance = sqrt(pow($avgX - $centerX, 2) + pow($avgY - $centerY, 2));

        if ($distance <= $tolerance) {
            return [
                'is_correct'    => true,
                'awarded_score' => $maxScore,
                'needs_manual'  => false,
                'feedback'      => 'Chính xác! Bạn đã khoanh đúng vị trí linh kiện bị kênh chân/lỗi hàn.'
            ];
        } elseif ($distance <= $tolerance * 1.5) {
            // Chấm điểm một phần (50%) nếu khoanh gần đúng
            $partial = round($maxScore * 0.5, 2);
            return [
                'is_correct'    => false,
                'awarded_score' => $partial,
                'needs_manual'  => false,
                'feedback'      => sprintf('Gần đúng (Được cộng %.1f điểm): Vùng khoanh hơi lệch so với tâm điểm lỗi.', $partial)
            ];
        }

        return [
            'is_correct'    => false,
            'awarded_score' => 0.00,
            'needs_manual'  => false,
            'feedback'      => 'Chưa đúng vị trí lỗi kỹ thuật cần phát hiện.'
        ];
    }

    // 3. DRAG_DROP_SEQUENCE (Kéo thả sắp xếp đúng quy trình SOP)
    if ($qType === 'DRAG_DROP_SEQUENCE') {
        $correctOrder = $config['correct_order'] ?? [];
        $submittedOrder = $userResponse['submitted_order'] ?? [];

        if ($correctOrder === $submittedOrder) {
            return [
                'is_correct'    => true,
                'awarded_score' => $maxScore,
                'needs_manual'  => false,
                'feedback'      => 'Hoàn hảo! Thứ tự các bước chuẩn 100% theo quy chuẩn SOP.'
            ];
        }

        return [
            'is_correct'    => false,
            'awarded_score' => 0.00,
            'needs_manual'  => false,
            'feedback'      => 'Thứ tự các bước quy trình chưa chính xác.'
        ];
    }

    // 4. SINGLE_CHOICE & TRUE_FALSE
    if ($qType === 'SINGLE_CHOICE' || $qType === 'TRUE_FALSE') {
        $correct = $config['correct'] ?? [];
        $selected = $userResponse['selected'] ?? '';
        if (in_array($selected, $correct, true)) {
            return [
                'is_correct'    => true,
                'awarded_score' => $maxScore,
                'needs_manual'  => false,
                'feedback'      => 'Đáp án chính xác.'
            ];
        }
        return [
            'is_correct'    => false,
            'awarded_score' => 0.00,
            'needs_manual'  => false,
            'feedback'      => 'Đáp án chưa chính xác.'
        ];
    }

    // 5. MULTI_CHOICE
    if ($qType === 'MULTI_CHOICE') {
        $correct = $config['correct'] ?? [];
        $selected = $userResponse['selected'] ?? [];
        sort($correct);
        sort($selected);
        if ($correct === $selected) {
            return [
                'is_correct'    => true,
                'awarded_score' => $maxScore,
                'needs_manual'  => false,
                'feedback'      => 'Đã chọn đầy đủ các đáp án đúng.'
            ];
        }
        return [
            'is_correct'    => false,
            'awarded_score' => 0.00,
            'needs_manual'  => false,
            'feedback'      => 'Chưa chọn đúng hoặc thiếu phương án.'
        ];
    }

    return [
        'is_correct'    => false,
        'awarded_score' => 0.00,
        'needs_manual'  => false,
        'feedback'      => 'Không xác định được dạng câu hỏi.'
    ];
}

/**
 * Xử lý nộp bài thi, lưu chi tiết câu trả lời và tự động thăng cấp Skillmap nếu Đạt
 */
function submitExamAttempt(int $userId, int $examId, array $submittedAnswers): array {
    $db = getDB();
    $db->beginTransaction();

    try {
        // Lấy thông tin bài thi và khóa học
        $stmtExam = $db->prepare('SELECT e.*, c.skill_id, c.level, c.min_pass_score, c.title AS course_title FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = :id LIMIT 1');
        $stmtExam->execute(['id' => $examId]);
        $exam = $stmtExam->fetch();

        if (!$exam) {
            throw new Exception('Bài thi không tồn tại.');
        }

        // Đếm số lần thi trước đó
        $stmtCount = $db->prepare('SELECT COUNT(*) FROM exam_attempts WHERE user_id = :uid AND exam_id = :eid');
        $stmtCount->execute(['uid' => $userId, 'eid' => $examId]);
        $attemptNum = (int)$stmtCount->fetchColumn() + 1;

        // Tạo bản ghi exam_attempt
        $stmtInsertAttempt = $db->prepare('
            INSERT INTO exam_attempts (user_id, exam_id, attempt_number, start_time, end_time, score_obtained, passed, status)
            VALUES (:uid, :eid, :att_num, NOW(), NOW(), 0, 0, "IN_PROGRESS")
        ');
        $stmtInsertAttempt->execute([
            'uid'     => $userId,
            'eid'     => $examId,
            'att_num' => $attemptNum
        ]);
        $attemptId = (int)$db->lastInsertId();

        // Lấy danh sách câu hỏi
        $stmtQ = $db->prepare('SELECT * FROM questions WHERE exam_id = :eid ORDER BY sort_order ASC');
        $stmtQ->execute(['eid' => $examId]);
        $questions = $stmtQ->fetchAll();

        $totalScoreObtained = 0.00;
        $hasManualEssay = false;

        $stmtInsertAns = $db->prepare('
            INSERT INTO exam_answers (attempt_id, question_id, user_response, is_correct, awarded_score, trainer_comment)
            VALUES (:att_id, :qid, :resp, :is_corr, :score, :comment)
        ');

        foreach ($questions as $q) {
            $qid = (int)$q['id'];
            $rawResponse = $submittedAnswers[$qid] ?? [];
            $gradeResult = gradeQuestion($q, $rawResponse);

            if ($gradeResult['needs_manual']) {
                $hasManualEssay = true;
            } else {
                $totalScoreObtained += $gradeResult['awarded_score'];
            }

            $stmtInsertAns->execute([
                'att_id'   => $attemptId,
                'qid'      => $qid,
                'resp'     => json_encode($rawResponse, JSON_UNESCAPED_UNICODE),
                'is_corr'  => $gradeResult['is_correct'] === null ? null : ($gradeResult['is_correct'] ? 1 : 0),
                'score'    => $gradeResult['awarded_score'],
                'comment'  => $gradeResult['feedback']
            ]);
        }

        $minPassScore = (float)$exam['min_pass_score'];
        $totalMaxScore = (float)$exam['total_score'];
        $finalStatus = $hasManualEssay ? 'PENDING_EVALUATION' : 'COMPLETED';
        $passPercentage = ($totalMaxScore > 0) ? (($totalScoreObtained / $totalMaxScore) * 100) : 0;
        $isPassed = (!$hasManualEssay && $passPercentage >= $minPassScore);

        // Cập nhật kết quả bài thi
        $stmtUpdateAtt = $db->prepare('
            UPDATE exam_attempts 
            SET score_obtained = :score, passed = :passed, status = :status, end_time = NOW()
            WHERE id = :id
        ');
        $stmtUpdateAtt->execute([
            'score'  => $totalScoreObtained,
            'passed' => $isPassed ? 1 : 0,
            'status' => $finalStatus,
            'id'     => $attemptId
        ]);

        // NÂNG CẤP SKILLMAP TỰ ĐỘNG KHI ĐẠT YÊU CẦU
        $skillUpgraded = false;
        $newLevel = 0;
        if ($isPassed) {
            $skillId = (int)$exam['skill_id'];
            $courseLevel = (int)$exam['level'];

            // Lấy level hiện tại
            $stmtUserSkill = $db->prepare('SELECT * FROM user_skills WHERE user_id = :uid AND skill_id = :sid LIMIT 1');
            $stmtUserSkill->execute(['uid' => $userId, 'sid' => $skillId]);
            $userSkill = $stmtUserSkill->fetch();

            $currentLevel = $userSkill ? (int)$userSkill['current_level'] : 0;

            if ($courseLevel > $currentLevel) {
                if ($userSkill) {
                    $stmtUpgrade = $db->prepare('UPDATE user_skills SET current_level = :lvl, certified_at = NOW() WHERE id = :id');
                    $stmtUpgrade->execute(['lvl' => $courseLevel, 'id' => $userSkill['id']]);
                } else {
                    $stmtInsertSkill = $db->prepare('INSERT INTO user_skills (user_id, skill_id, current_level, certified_at) VALUES (:uid, :sid, :lvl, NOW())');
                    $stmtInsertSkill->execute(['uid' => $userId, 'sid' => $skillId, 'lvl' => $courseLevel]);
                }
                $skillUpgraded = true;
                $newLevel = $courseLevel;
            }
        }

        $db->commit();

        return [
            'success'          => true,
            'attempt_id'       => $attemptId,
            'status'           => $finalStatus,
            'score_obtained'   => $totalScoreObtained,
            'total_max_score'  => $totalMaxScore,
            'pass_percentage'  => round($passPercentage, 1),
            'min_pass_score'   => $minPassScore,
            'passed'           => $isPassed,
            'has_manual_essay' => $hasManualEssay,
            'skill_upgraded'   => $skillUpgraded,
            'new_level'        => $newLevel
        ];
    } catch (Exception $e) {
        $db->rollBack();
        return [
            'success' => false,
            'message' => 'Lỗi trong quá trình nộp bài: ' . $e->getMessage()
        ];
    }
}

/**
 * Lấy Ma trận Skillmap cá nhân của người dùng
 */
function getUserSkillmap(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare('
        SELECT s.id AS skill_id, s.code, s.name AS skill_name, s.description,
               COALESCE(us.current_level, 0) AS current_level,
               us.certified_at,
               u_cert.full_name AS certified_by_name
        FROM skills s
        LEFT JOIN user_skills us ON us.skill_id = s.id AND us.user_id = :uid
        LEFT JOIN users u_cert ON u_cert.id = us.certified_by
        ORDER BY s.id ASC
    ');
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Lấy Ma trận Skillmap toàn bộ nhà máy (dành cho Trainer và Admin)
 */
function getFactorySkillmapOverview(): array {
    $db = getDB();
    // Lấy danh sách kỹ năng
    $skills = $db->query('SELECT * FROM skills ORDER BY id ASC')->fetchAll();

    // Lấy danh sách công nhân
    $workers = $db->query('
        SELECT id, employee_code, full_name, department, line_station 
        FROM users 
        WHERE role = "WORKER" 
        ORDER BY department ASC, employee_code ASC
    ')->fetchAll();

    // Lấy tất cả cấp độ hiện tại
    $userSkills = $db->query('SELECT user_id, skill_id, current_level, certified_at FROM user_skills')->fetchAll();

    $matrix = [];
    foreach ($userSkills as $us) {
        $matrix[$us['user_id']][$us['skill_id']] = [
            'level'        => (int)$us['current_level'],
            'certified_at' => $us['certified_at']
        ];
    }

    return [
        'skills'  => $skills,
        'workers' => $workers,
        'matrix'  => $matrix
    ];
}

