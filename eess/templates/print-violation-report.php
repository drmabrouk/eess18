<?php
if (!defined('ABSPATH')) exit;

$school = SM_Settings::get_school_info();
$academic = SM_Settings::get_academic_structure();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير مخالفات الطلاب - <?php echo esc_html($school['school_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #F63049; padding-bottom: 20px; margin-bottom: 30px; }
        .school-info h1 { margin: 0; font-size: 24px; font-weight: 900; }
        .school-info p { margin: 5px 0 0 0; color: #666; font-size: 14px; }
        .logo img { max-height: 80px; }
        .report-title { text-align: center; margin-bottom: 30px; }
        .report-title h2 { display: inline-block; padding: 10px 40px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin: 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #111F35; color: white; padding: 12px 8px; font-size: 13px; text-align: center; border: 1px solid #111F35; }
        td { padding: 10px 8px; border: 1px solid #e2e8f0; font-size: 12px; text-align: center; }
        tr:nth-child(even) { background: #fcfcfc; }

        .footer { position: fixed; bottom: 30px; left: 40px; right: 40px; display: flex; justify-content: space-between; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <?php
    $report_date = date_i18n('Y-m-d');
    $archive_no = date('Ymd') . rand(1000, 9999);
    ?>
    <div class="header" style="border-bottom: 3px double #F63049; padding-bottom: 20px;">
        <div class="school-info">
            <h3 style="margin: 0; color: #111F35; font-weight: 900; font-size: 17px;">وزارة التربية والتعليم</h3>
            <h3 style="margin: 0; color: #111F35; font-weight: 800; font-size: 15px;">الإمارات العربية المتحدة</h3>
            <h2 style="margin: 8px 0; color: #4A5568; font-weight: 800; font-size: 18px; border-top: 1px solid #eee; padding-top: 5px;"><?php echo esc_html($school['school_name']); ?></h2>
            <div style="margin-top: 15px;">
                <span style="font-weight: 800; color: #4A5568; font-size: 11px; margin-left: 20px;">الرقم الأرشيفي: <?php echo $archive_no; ?></span>
                <span style="font-weight: 800; color: #4A5568; font-size: 11px;">تاريخ التقرير: <?php echo $report_date; ?></span>
            </div>
        </div>
        <div class="logo">
            <?php if (!empty($school['school_logo'])): ?>
                <img src="<?php echo esc_url($school['school_logo']); ?>" style="max-height: 100px; width: auto; display: block;" alt="Logo">
            <?php endif; ?>
        </div>
    </div>

    <div class="report-title">
        <h2>تقرير سجلات مخالفات الطلاب</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم الطالب</th>
                <th>كود الطالب</th>
                <th>نص بند المخالفة</th>
                <th>الصف / الفصل</th>
                <th>تاريخ المخالفة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td style="font-weight: 800; text-align: right;"><?php echo esc_html($r->student_name); ?></td>
                    <td style="font-family: monospace; font-weight: 700;"><?php echo esc_html($r->student_code); ?></td>
                    <td style="text-align: right;"><?php echo esc_html($r->violation_code) . ' - ' . esc_html($r->type); ?></td>
                    <td><?php echo SM_Settings::format_grade_name($r->class_name, $r->section); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($r->created_at)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; text-align: center;">
        <div>
            <p style="font-weight: 700; margin-bottom: 50px; color: #111F35; font-size: 16px;">مشرف الانضباط</p>
            <div style="border-bottom: 1px dashed #cbd5e0; width: 220px; margin: 0 auto;"></div>
            <div style="font-size: 11px; color: #718096; margin-top: 8px;">التوقيع والختم الرسمي</div>
        </div>
        <div>
            <p style="font-weight: 700; margin-bottom: 50px; color: #111F35; font-size: 16px;">مدير المدرسة</p>
            <div style="border-bottom: 1px dashed #cbd5e0; width: 220px; margin: 0 auto;"></div>
            <div style="font-size: 11px; color: #718096; margin-top: 8px;">الختم والتوقيع</div>
        </div>
    </div>

    <div class="footer">
        <div>نظام إدارة المدرسة - تم الاستخراج بواسطة: <?php echo wp_get_current_user()->display_name; ?></div>
        <div>صفحة 1 من 1</div>
    </div>

    <div class="no-print" style="margin-top: 40px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #F63049; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: inherit;">طباعة التقرير</button>
    </div>
</body>
</html>
