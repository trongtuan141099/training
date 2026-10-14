<?php
// includes/lang.php - Localization Engine (Tiếng Việt & English)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xử lý chuyển đổi ngôn ngữ qua URL param ?lang=vi hoặc ?lang=en
if (isset($_GET['lang'])) {
    $requestedLang = strtolower(trim($_GET['lang']));
    if (in_array($requestedLang, ['vi', 'en'], true)) {
        $_SESSION['app_lang'] = $requestedLang;
    }
}

function getCurrentLang(): string {
    return $_SESSION['app_lang'] ?? 'vi';
}

function getTranslationDictionary(): array {
    static $dict = null;
    if ($dict !== null) {
        return $dict;
    }

    $dict = [
        'vi' => [
            // App Branding
            'app_name'               => 'SKILLMAP 4.0',
            'app_subname'            => 'DOJO FACTORY LMS',
            'app_slogan'             => 'Hệ thống Quản lý Đào tạo & Đánh giá Năng lực Công nhân',

            // Navigation Groups
            'nav_group_overview'     => 'TỔNG QUAN HỆ THỐNG',
            'nav_group_training'     => 'QUẢN LÝ SẢN XUẤT & ĐÀO TẠO',
            'nav_group_quality'      => 'QUẢN LÝ CHẤT LƯỢNG & ĐÁNH GIÁ',
            'nav_group_admin'        => 'HỆ THỐNG & BÁO CÁO',

            // Navigation Links
            'nav_roadmap'            => 'Lộ trình Đào tạo',
            'nav_skillmap'           => 'Ma trận Kỹ năng (Skillmap)',
            'nav_exam_builder'       => 'Thiết kế & Tạo bài thi',
            'nav_sop_manager'        => 'Quản lý Tài liệu SOP',
            'nav_trainer_grading'    => 'Bàn chấm bài Tự luận',
            'nav_history'            => 'Lịch sử & Đánh giá',
            'nav_admin_console'      => 'Quản trị Xưởng & KPI',

            // Header & User
            'role_admin'             => 'Quản trị viên',
            'role_trainer'           => 'Giảng viên Dojo',
            'role_worker'            => 'Công nhân vận hành',
            'quick_switch_role'      => 'Chuyển đổi vai trò Demo',
            'logout'                 => 'Đăng xuất',
            'language'               => 'Ngôn ngữ',
            'line_station'           => 'Vị trí công đoạn',
            'department'             => 'Phân xưởng',
            'notifications'          => 'Thông báo',
            'no_notifications'       => 'Không có thông báo mới',
            'pending_essays_notify'  => 'Có :count bài thi tự luận đang chờ chấm điểm!',

            // Levels & Badges
            'level_1'                => 'Cơ bản',
            'level_2'                => 'Nâng cao',
            'level_3'                => 'Chuyên gia / Cao cấp',
            'level_short'            => 'Cấp độ :lvl',
            'status_passed'          => 'ĐÃ ĐẠT',
            'status_locked'          => 'BỊ KHÓA',
            'status_unlocked'        => 'MỞ KHÓA',
            'status_pending'         => 'CHỜ CHẤM ĐIỂM',
            'status_failed'          => 'CHƯA ĐẠT',

            // Actions & Buttons
            'btn_view_sop'           => 'Xem tài liệu SOP',
            'btn_start_exam'         => 'VÀO THI',
            'btn_retake_exam'        => 'Thi lại',
            'btn_submit_exam'        => 'HOÀN TẤT & NỘP BÀI',
            'btn_save'               => 'Lưu lại',
            'btn_cancel'             => 'Hủy bỏ',
            'btn_delete'             => 'Xóa',
            'btn_edit'               => 'Chỉnh sửa',
            'btn_create_exam'        => 'Tạo bài thi mới',
            'btn_add_question'       => 'Thêm câu hỏi',
            'btn_upload_sop'         => 'Tải lên tài liệu SOP',
            'btn_qr_scan'            => 'Quét mã QR Trạm máy',
            'btn_back_roadmap'       => 'Quay lại Lộ trình',

            // Exam & Prerequisite
            'min_pass_score'         => 'Điểm chuẩn đạt',
            'duration_minutes'       => 'Thời gian làm bài',
            'max_attempts'           => 'Số lần thi tối đa',
            'prerequisite_course'    => 'Khóa học cấp trước (Điều kiện bắt buộc)',
            'prerequisite_notice'    => 'Bắt buộc đạt điểm tối thiểu :min% ở cấp trước để mở khóa.',
            'prerequisite_locked_msg'=> 'BỊ KHÓA: Bạn cần vượt qua bài thi cấp độ [:course] với điểm tối thiểu :min% trước.',
            'unlocked_ready'         => 'Đủ điều kiện làm bài.',

            // Question Types
            'q_type_single'          => 'Trắc nghiệm (1 đáp án đúng)',
            'q_type_multi'           => 'Trắc nghiệm (Nhiều đáp án đúng)',
            'q_type_tf'              => 'Đúng / Sai',
            'q_type_essay'           => 'Tự luận (Trainer chấm tay)',
            'q_type_hotspot'         => 'Tương tác: Chỉ vị trí linh kiện (Hotspot Pinpoint)',
            'q_type_polygon'         => 'Tương tác: Khoanh vùng lỗi sản phẩm (Polygon Defect)',
            'q_type_sequence'        => 'Kéo thả sắp xếp thứ tự quy trình SOP',

            // Dojo Matrix
            'dojo_l0'                => 'L0: Chưa đào tạo',
            'dojo_l1'                => 'L1 (I): Có thể làm dưới sự kèm cặp',
            'dojo_l2'                => 'L2 (L): Độc lập vận hành đúng chuẩn SOP',
            'dojo_l3'                => 'L3 (U/O): Đào tạo người khác & Bảo trì máy',
        ],

        'en' => [
            // App Branding
            'app_name'               => 'SKILLMAP 4.0',
            'app_subname'            => 'DOJO FACTORY LMS',
            'app_slogan'             => 'Shopfloor Training & Worker Competency Matrix System',

            // Navigation Groups
            'nav_group_overview'     => 'SYSTEM OVERVIEW',
            'nav_group_training'     => 'PRODUCTION & TRAINING',
            'nav_group_quality'      => 'QUALITY & EVALUATION',
            'nav_group_admin'        => 'SYSTEM & REPORTS',

            // Navigation Links
            'nav_roadmap'            => 'Training Roadmap',
            'nav_skillmap'           => 'Competency Matrix (Skillmap)',
            'nav_exam_builder'       => 'Exam Designer & Builder',
            'nav_sop_manager'        => 'SOP Documents Hub',
            'nav_trainer_grading'    => 'Trainer Grading Desk',
            'nav_history'            => 'Audit Log & History',
            'nav_admin_console'      => 'Plant Admin & KPI',

            // Header & User
            'role_admin'             => 'System Admin',
            'role_trainer'           => 'Dojo Instructor',
            'role_worker'            => 'Line Operator',
            'quick_switch_role'      => 'Quick Switch Demo Role',
            'logout'                 => 'Sign Out',
            'language'               => 'Language',
            'line_station'           => 'Workstation',
            'department'             => 'Shop / Department',
            'notifications'          => 'Notifications',
            'no_notifications'       => 'No new notifications',
            'pending_essays_notify'  => ':count essay submission(s) pending trainer evaluation!',

            // Levels & Badges
            'level_1'                => 'Basic',
            'level_2'                => 'Advanced',
            'level_3'                => 'Expert / Master',
            'level_short'            => 'Level :lvl',
            'status_passed'          => 'PASSED',
            'status_locked'          => 'LOCKED',
            'status_unlocked'        => 'UNLOCKED',
            'status_pending'         => 'EVALUATION PENDING',
            'status_failed'          => 'NOT PASSED',

            // Actions & Buttons
            'btn_view_sop'           => 'View SOP Guide',
            'btn_start_exam'         => 'START EXAM',
            'btn_retake_exam'        => 'Retake Exam',
            'btn_submit_exam'        => 'FINISH & SUBMIT',
            'btn_save'               => 'Save Changes',
            'btn_cancel'             => 'Cancel',
            'btn_delete'             => 'Delete',
            'btn_edit'               => 'Edit',
            'btn_create_exam'        => 'Create New Exam',
            'btn_add_question'       => 'Add Question',
            'btn_upload_sop'         => 'Upload SOP Document',
            'btn_qr_scan'            => 'Scan Station QR Code',
            'btn_back_roadmap'       => 'Back to Roadmap',

            // Exam & Prerequisite
            'min_pass_score'         => 'Min Passing Score',
            'duration_minutes'       => 'Duration (Mins)',
            'max_attempts'           => 'Max Attempts Allowed',
            'prerequisite_course'    => 'Prerequisite Course (Mandatory)',
            'prerequisite_notice'    => 'Must achieve at least :min% in prerequisite course to unlock.',
            'prerequisite_locked_msg'=> 'LOCKED: You must pass prerequisite course [:course] with at least :min% first.',
            'unlocked_ready'         => 'Eligible to take exam.',

            // Question Types
            'q_type_single'          => 'Single Choice (1 correct option)',
            'q_type_multi'           => 'Multiple Choice (Multiple correct options)',
            'q_type_tf'              => 'True / False',
            'q_type_essay'           => 'Essay (Manual Trainer Grading)',
            'q_type_hotspot'         => 'Visual: Point-and-Click Component (Hotspot Pinpoint)',
            'q_type_polygon'         => 'Visual: Defect Boundary Selection (Polygon Defect)',
            'q_type_sequence'        => 'Drag & Drop Sequence Ordering',

            // Dojo Matrix
            'dojo_l0'                => 'L0: Untrained',
            'dojo_l1'                => 'L1 (I): Work under supervision',
            'dojo_l2'                => 'L2 (L): Independent standard operation',
            'dojo_l3'                => 'L3 (U/O): Train others & Maintenance master',
        ]
    ];

    return $dict;
}

function __(string $key, array $params = []): string {
    $lang = getCurrentLang();
    $dict = getTranslationDictionary();

    $text = $dict[$lang][$key] ?? ($dict['vi'][$key] ?? $key);

    foreach ($params as $paramKey => $paramValue) {
        $text = str_replace(':' . $paramKey, (string)$paramValue, $text);
    }

    return $text;
}

