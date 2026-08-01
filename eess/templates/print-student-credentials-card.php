<?php if (!defined('ABSPATH')) exit;
$school = SM_Settings::get_school_info();

if (!empty($_GET['student_id'])) {
    $students_to_print = array(SM_DB::get_student_by_id(intval($_GET['student_id'])));
} else {
    $filters = array();
    if (!empty($_GET['class_name'])) {
        $filters['class_name'] = sanitize_text_field($_GET['class_name']);
    }
    $students_to_print = SM_DB::get_students($filters);
}

if (empty($students_to_print) || !$students_to_print[0]) wp_die('Student(s) not found');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بطاقات بيانات الدخول</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700;900&display=swap');
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 20px; background: #f0f2f5; }
        .cards-wrapper { display: flex; flex-direction: column; align-items: center; gap: 40px; }
        .card { width: 450px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; page-break-inside: avoid; }
        .card-header { background: #111F35; padding: 25px; text-align: center; color: #fff; }
        .card-body { padding: 35px; text-align: center; }
        .school-logo { height: 50px; margin-bottom: 10px; }
        .student-name { font-size: 20px; font-weight: 900; color: #111F35; margin-bottom: 5px; }
        .student-class { font-size: 14px; color: #718096; margin-bottom: 30px; }
        .cred-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; text-align: right; }
        .cred-label { font-size: 11px; color: #718096; font-weight: 700; margin-bottom: 5px; }
        .cred-value { font-family: monospace; font-size: 22px; font-weight: 900; color: #F63049; letter-spacing: 2px; }
        .note { font-size: 11px; color: #718096; line-height: 1.5; margin-top: 20px; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .card { box-shadow: none; border: 1px solid #eee; margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <div class="cards-wrapper">
        <?php foreach ($students_to_print as $student):
            if (!$student) continue;
            $pass = get_user_meta($student->parent_user_id, 'sm_temp_pass', true);
            if (empty($pass)) $pass = '********';
        ?>
        <div class="card">
            <div class="card-header">
                <?php if ($school['school_logo']): ?>
                    <img src="<?php echo esc_url($school['school_logo']); ?>" class="school-logo">
                <?php endif; ?>
                <div style="font-size: 14px; font-weight: 700;"><?php echo esc_html($school['school_name']); ?></div>
            </div>
            <div class="card-body">
                <div class="student-name"><?php echo esc_html($student->name); ?></div>
                <div class="student-class"><?php echo SM_Settings::format_grade_name($student->class_name, $student->section); ?></div>

                <div class="cred-box">
                    <div class="cred-label">اسم المستخدم (User ID):</div>
                    <div class="cred-value"><?php echo esc_html($student->student_code); ?></div>
                </div>

                <div class="cred-box" style="margin-bottom: 0;">
                    <div class="cred-label">كلمة المرور (Password):</div>
                    <div class="cred-value" style="color: #111F35;"><?php echo esc_html($pass); ?></div>
                </div>

                <div class="note">
                    * يستخدم هذا الحساب للدخول لنظام إدارة المدرسة (الطالب وولي الأمر).<br>
                    * يرجى الحفاظ على سرية هذه البيانات وتغيير كلمة المرور بعد أول دخول.
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="no-print" style="position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);">
        <button onclick="window.print()" style="padding: 12px 40px; background: #F63049; color: #fff; border: none; border-radius: 12px; cursor: pointer; font-family: 'Rubik'; font-weight: 900; box-shadow: 0 4px 14px 0 rgba(246, 48, 73, 0.39);">طباعة البطاقات الآن</button>
    </div>
</body>
</html>
