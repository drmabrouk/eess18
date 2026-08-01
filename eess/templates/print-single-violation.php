<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>قرار انضباطي - <?php echo esc_html($record->student_name); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: portrait; margin: 15mm; }
        body { font-family: 'Rubik', sans-serif; padding: 0; background: #fff; line-height: 1.6; color: #2d3748; }
        .receipt { border: 2px solid #111F35; padding: 40px; max-width: 800px; margin: 0 auto; border-radius: 15px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); position: relative; }
        .receipt::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 10px; background: #F63049; border-radius: 15px 15px 0 0; }
        .header { text-align: center; border-bottom: 2px solid #edf2f7; margin-bottom: 30px; padding-bottom: 20px; }
        .moe-title { font-weight: 900; font-size: 20px; color: #111F35; margin-bottom: 5px; }
        .school-title { font-weight: 700; font-size: 16px; color: #4A5568; margin-bottom: 15px; }
        .report-type { margin: 15px 0; font-weight: 900; color: #F63049; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }

        .meta-info { display: flex; justify-content: space-between; font-size: 13px; color: #718096; margin-bottom: 20px; background: #f8fafc; padding: 10px 20px; border-radius: 8px; }

        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .row { border-bottom: 1px solid #edf2f7; padding-bottom: 10px; }
        .row.full { grid-column: span 2; }
        .label { font-weight: 800; color: #4a5568; font-size: 14px; margin-bottom: 4px; display: block; }
        .value { color: #2d3748; font-size: 16px; font-weight: 500; }

        .details-box { background: #fffaf0; border-right: 4px solid #ed8936; padding: 15px; margin-top: 10px; border-radius: 4px; }
        .action-box { background: #f0fff4; border-right: 4px solid #48bb78; padding: 15px; margin-top: 10px; border-radius: 4px; }

        .signatures { margin-top: 60px; display: flex; justify-content: space-between; text-align: center; }
        .sig-box { width: 250px; }
        .sig-label { font-weight: 800; color: #111F35; margin-bottom: 50px; }
        .sig-line { border-bottom: 2px dashed #cbd5e0; margin-bottom: 10px; }
        .sig-name { font-weight: 700; color: #4A5568; font-size: 14px; }

        @media print { 
            .no-print { display: none !important; }
            .receipt { box-shadow: none; border-width: 1px; margin: 0; max-width: 100%; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 20px; padding: 20px;">
        <button onclick="window.print()" style="padding: 12px 30px; background: #F63049; color: white; border: none; cursor: pointer; border-radius: 8px; font-family: 'Rubik', sans-serif; font-weight: 700; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">طباعة القرار الإداري</button>
    </div>

    <?php $school = SM_Settings::get_school_info(); ?>
    <div class="receipt">
        <div class="header">
            <div class="moe-title">وزارة التربية والتعليم – الإمارات العربية المتحدة</div>
            <div class="school-title"><?php echo esc_html($school['school_name']); ?></div>
            <?php if (!empty($school['school_logo'])): ?>
                <img src="<?php echo esc_url($school['school_logo']); ?>" style="max-height: 100px; width: auto; object-fit: contain; display: block; margin: 10px auto;">
            <?php endif; ?>
            <div class="report-type">قرار إداري انضباطي</div>
        </div>

        <?php
        $report_date = date_i18n('Y-m-d');
        $archive_no = date('Ymd') . rand(1000, 9999);
        ?>
        <div class="meta-info">
            <div style="font-size: 10px;">الرقم الأرشيفي: <strong style="color: #4A5568;"><?php echo $archive_no; ?></strong></div>
            <div style="font-size: 10px;">تاريخ التقرير: <strong style="color: #4A5568;"><?php echo $report_date; ?></strong></div>
            <div style="font-size: 10px;">الرقم المرجعي: <strong><?php echo date('Ym') . $record->id; ?></strong></div>
        </div>

        <?php
        // Dynamic Linking
        $reg = SM_Settings::get_regulation_by_code($record->violation_code);
        $display_action = $reg ? $reg['action'] : $record->action_taken;
        ?>
        <div class="content-grid">
            <div class="row">
                <span class="label">اسم الطالب:</span>
                <span class="value"><?php echo esc_html($record->student_name); ?></span>
            </div>
            <div class="row">
                <span class="label">الصف الدراسي:</span>
                <span class="value"><?php echo SM_Settings::format_grade_name($record->class_name, $record->section); ?></span>
            </div>
            <div class="row">
                <span class="label">الرقم الأكاديمي:</span>
                <span class="value"><?php echo esc_html($record->student_code); ?></span>
            </div>
            <div class="row">
                <span class="label">تاريخ المخالفة:</span>
                <span class="value"><?php echo date('Y/m/d', strtotime($record->created_at)); ?></span>
            </div>
            <div class="row">
                <span class="label">البند القانوني / المرجع:</span>
                <span class="value"><?php echo esc_html($record->violation_code); ?> (<?php echo esc_html($reg ? $reg['name'] : $record->type); ?>)</span>
            </div>
            <div class="row">
                <span class="label">القرار (الإجراء المتخذ):</span>
                <span class="value" style="font-weight: 800; color: #F63049;"><?php echo esc_html($display_action); ?></span>
            </div>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-label">مشرف الانضباط</div>
                <div class="sig-line"></div>
                <div class="sig-name">التوقيع والختم</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">مدير المدرسة</div>
                <div class="sig-line"></div>
                <div class="sig-name">التوقيع والختم</div>
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 11px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
            هذه وثيقة رسمية صادرة عن نظام إدارة السلوك المدرسي. أي كشط أو تعديل يلغيها.
        </div>
    </div>
</body>
</html>
