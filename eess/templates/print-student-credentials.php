<?php if (!defined('ABSPATH')) exit;
$school = SM_Settings::get_school_info();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بيانات دخول الطلاب</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;900&display=swap');
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 20px; background: #fff; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #111F35; padding-bottom: 20px; }
        .school-name { font-size: 24px; font-weight: 900; color: #111F35; }
        .report-title { font-size: 18px; color: #F63049; margin-top: 10px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #111F35; color: #fff; padding: 12px; text-align: right; font-size: 14px; }
        td { border: 1px solid #e2e8f0; padding: 10px; font-size: 13px; }
        tr:nth-child(even) { background: #f8fafc; }
        .code { font-family: monospace; font-weight: 700; color: #D02752; font-size: 1.1em; }
        .footer { margin-top: 40px; text-align: left; font-size: 12px; color: #718096; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name"><?php echo esc_html($school['school_name']); ?></div>
        <div class="report-title">كشف بيانات دخول الطلاب (ولي الأمر / الطالب)</div>
        <div style="font-size: 12px; margin-top: 5px;">تاريخ التوليد: <?php echo date_i18n('j F Y'); ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>اسم الطالب</th>
                <th>الصف والشعبة</th>
                <th>اسم المستخدم (الكود)</th>
                <th>كلمة المرور المؤقتة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 1;
            foreach ($students as $s):
                $pass = get_user_meta($s->parent_user_id, 'sm_temp_pass', true);
                if (empty($pass)) $pass = '********';
            ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td style="font-weight: 700;"><?php echo esc_html($s->name); ?></td>
                    <td><?php echo SM_Settings::format_grade_name($s->class_name, $s->section); ?></td>
                    <td class="code"><?php echo esc_html($s->student_code); ?></td>
                    <td class="code"><?php echo esc_html($pass); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        * يتم استخدام الكود كاسم مستخدم للدخول للنظام لكل من الطالب وولي الأمر.<br>
        * ينصح بتغيير كلمة المرور فور الدخول الأول للنظام.
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #111F35; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-family: 'Rubik'; font-weight: 700;">طباعة الكشف الآن</button>
    </div>
</body>
</html>
