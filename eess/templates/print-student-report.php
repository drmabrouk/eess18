<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير الطالب الانضباطي - <?php echo esc_html($student->name); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { font-family: 'Cairo', sans-serif; padding: 0; color: #333; line-height: 1.4; background: #fff; font-size: 13px; }

        .report-wrapper { max-width: 210mm; margin: 0 auto; }

        /* Compact Header */
        .report-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1.5px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header-column { flex: 1; }
        .header-column.center { text-align: center; }
        .header-column.left { text-align: left; }
        .header-column.right { text-align: right; }

        .school-name { font-weight: 800; font-size: 16px; margin-bottom: 2px; }
        .report-title { font-weight: 900; font-size: 18px; text-transform: uppercase; color: #000; margin: 5px 0; }

        /* Compact Info Block */
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; border: 1px solid #ddd; padding: 12px; background: #fcfcfc; }
        .info-item { display: flex; align-items: center; gap: 8px; }
        .info-label { font-weight: 800; color: #555; min-width: 100px; font-size: 12px; }
        .info-value { font-weight: 600; color: #000; }

        /* Professional Table */
        .section-title { font-size: 14px; font-weight: 800; border-right: 4px solid #333; padding-right: 10px; margin: 20px 0 10px 0; background: #f0f0f0; padding-top: 5px; padding-bottom: 5px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: right; font-size: 12px; }
        th { background: #eee; color: #000; font-weight: 800; border-bottom: 2px solid #000; }

        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; }
        .summary-item { border: 1px solid #eee; padding: 10px; text-align: center; background: #fff; }
        .summary-item .label { font-size: 10px; color: #777; display: block; margin-bottom: 2px; }
        .summary-item .value { font-size: 16px; font-weight: 900; color: #000; }

        .footer-sigs { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; text-align: center; }
        .sig-space { margin-top: 35px; border-bottom: 1px dashed #999; width: 180px; margin-left: auto; margin-right: auto; }

        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
            .report-wrapper { width: 100%; }
        }
        <?php $print_settings = get_option('sm_print_settings'); echo $print_settings['custom_css'] ?? ''; ?>
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #27ae60; color: white; border: none; cursor: pointer; border-radius: 5px;">طباعة التقرير (أو حفظ كـ PDF)</button>
    </div>

    <?php 
    $school = SM_Settings::get_school_info(); 
    $print_settings = get_option('sm_print_settings');
    ?>
    
    <div class="report-wrapper">
    <?php if (!empty($print_settings['header'])): ?>
        <div class="custom-print-header"><?php echo $print_settings['header']; ?></div>
    <?php else: ?>
        <div class="report-header">
            <div class="header-column right">
                <div class="school-name"><?php echo esc_html($school['school_name']); ?></div>
                <div style="font-size: 11px; color: #666;">وزارة التربية والتعليم - دولة الإمارات</div>
            </div>
            <div class="header-column center">
                <?php if (!empty($school['school_logo'])): ?>
                    <img src="<?php echo esc_url($school['school_logo']); ?>" style="max-height: 70px; width: auto;">
                <?php endif; ?>
                <div class="report-title">السجل الانضباطي للطالب</div>
            </div>
            <div class="header-column left" style="font-size: 11px;">
                <div>تاريخ الإصدار: <?php echo date_i18n('Y-m-d'); ?></div>
                <div>الرقم المرجعي: <?php echo 'REP-' . date('Ym') . '-' . $student->id; ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="section-title">أولاً: بيانات الطالب الشخصية</div>
    <div class="info-grid">
        <div class="info-item"><span class="info-label">اسم الطالب:</span> <span class="info-value"><?php echo esc_html($student->name); ?></span></div>
        <div class="info-item"><span class="info-label">الرقم الأكاديمي:</span> <span class="info-value"><?php echo esc_html($student->student_code); ?></span></div>
        <div class="info-item"><span class="info-label">الصف / الشعبة:</span> <span class="info-value"><?php echo SM_Settings::format_grade_name($student->class_name, $student->section); ?></span></div>
        <div class="info-item"><span class="info-label">رقم الهوية (ID):</span> <span class="info-value"><?php echo esc_html($student->national_id ?? '---'); ?></span></div>
        <div class="info-item"><span class="info-label">الجنسية:</span> <span class="info-value"><?php echo esc_html($student->nationality ?: '---'); ?></span></div>
        <div class="info-item"><span class="info-label">تاريخ التسجيل:</span> <span class="info-value"><?php echo esc_html($student->registration_date); ?></span></div>
    </div>


    <div class="section-title">ثانياً: التسلسل الزمني للمخالفات والقرارات</div>
    <table>
        <thead>
            <tr>
                <th style="width: 100px;">التاريخ</th>
                <th style="width: 70px;">المستوى</th>
                <th>بند المخالفة والوصف</th>
                <th style="width: 60px;">النقاط</th>
                <th>الإجراء المتخذ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 30px;">لا توجد سجلات انضباطية مسجلة لهذا الطالب.</td></tr>
            <?php else: ?>
                <?php
                // Sort records: Newest first
                usort($records, function($a, $b) { return strtotime($b->created_at) - strtotime($a->created_at); });
                foreach ($records as $r):
                    $reg = SM_Settings::get_regulation_by_code($r->violation_code);
                    $display_type = $reg ? $reg['name'] : $r->type;
                    $display_action = $reg ? $reg['action'] : $r->action_taken;
                ?>
                <tr>
                    <td style="text-align:center;"><?php echo date('Y-m-d', strtotime($r->created_at)); ?></td>
                    <td style="text-align:center;"><strong><?php echo (int)$r->degree; ?></strong></td>
                    <td>
                        <div style="font-weight:700;"><?php echo esc_html($display_type); ?></div>
                        <?php if($r->violation_code): ?><div style="font-size:10px; color:#666;">كود: <?php echo esc_html($r->violation_code); ?></div><?php endif; ?>
                    </td>
                    <td style="text-align:center;"><strong><?php echo (int)$r->points; ?></strong></td>
                    <td style="font-weight:600;"><?php echo esc_html($display_action); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">ثالثاً: ملخص الحالة الانضباطية</div>
    <div class="summary-grid">
        <div class="summary-item">
            <span class="label">إجمالي المخالفات</span>
            <span class="value"><?php echo count($records); ?></span>
        </div>
        <div class="summary-item">
            <span class="label">النقاط التراكمية</span>
            <span class="value"><?php echo (int)$student->behavior_points; ?></span>
        </div>
        <div class="summary-item">
            <span class="label">مستوى الانضباط</span>
            <span class="value"><?php
                if ($student->behavior_points > 20) echo 'حرج';
                elseif ($student->behavior_points > 10) echo 'متوسط';
                else echo 'جيد';
            ?></span>
        </div>
        <div class="summary-item">
            <span class="label">حالة الملف</span>
            <span class="value"><?php echo $student->case_file_active ? 'ملف مفتوح' : 'اعتيادي'; ?></span>
        </div>
    </div>

    <div class="footer-sigs">
        <div>
            <div style="font-weight:800; font-size:14px;">مسؤول وحدة الانضباط</div>
            <div class="sig-space"></div>
            <div style="font-size:11px; color:#666;">التوقيع والختم</div>
        </div>
        <div>
            <div style="font-weight:800; font-size:14px;">مدير المدرسة</div>
            <div class="sig-space"></div>
            <div style="font-size:11px; color:#666;">الختم والتوقيع</div>
        </div>
    </div>
    </div><!-- End report-wrapper -->
    <?php if (!empty($print_settings['footer'])): ?>
        <div class="custom-print-footer" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; text-align: center; font-size: 12px;">
            <?php echo $print_settings['footer']; ?>
        </div>
    <?php endif; ?>
</body>
</html>
