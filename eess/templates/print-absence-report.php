<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { font-family: 'Rubik', sans-serif; background: #fff; margin: 0; padding: 0; color: #111F35; }
        .print-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111F35; padding-bottom: 15px; margin-bottom: 30px; }
        .school-info h1 { margin: 0; font-size: 1.5em; font-weight: 900; }
        .report-title { text-align: center; margin-bottom: 30px; }
        .report-title h2 { margin: 0; font-size: 1.8em; font-weight: 800; color: #F63049; }
        .report-meta { display: flex; justify-content: center; gap: 40px; font-weight: 700; margin-bottom: 20px; font-size: 0.9em; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th { background: #f8fafc; color: #111F35; font-weight: 800; border: 1px solid #cbd5e1; padding: 12px; font-size: 0.9em; text-align: center; }
        td { border: 1px solid #cbd5e1; padding: 12px; font-size: 0.9em; text-align: center; }
        tr:nth-child(even) { background: #fcfcfc; }

        .footer { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-start; }
        .signature-box { text-align: center; }
        .signature-box p { font-weight: 800; margin-bottom: 40px; }
        .principal-name { font-weight: 900; color: #111F35; margin-bottom: 5px; }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f8fafc; padding: 20px; text-align: center; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px;">
        <button onclick="window.print()" style="padding: 12px 30px; background: #38a169; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-family: 'Rubik', sans-serif;">بدء الطباعة الآن</button>
    </div>

    <?php $school = SM_Settings::get_school_info(); ?>
    <div class="print-header">
        <div class="school-info">
            <?php if ($school['school_logo']): ?>
                <img src="<?php echo esc_url($school['school_logo']); ?>" style="height: 50px; margin-bottom: 10px;">
            <?php endif; ?>
            <h1><?php echo esc_html($school['school_name']); ?></h1>
        </div>
        <div style="text-align: left;">
            <div style="font-weight: 800;">تقرير الغياب</div>
            <div style="font-size: 0.9em;">تاريخ التقرير: <?php echo date_i18n('j F Y'); ?></div>
        </div>
    </div>

    <div class="report-title">
        <h2><?php echo esc_html($title); ?></h2>
        <?php if (isset($subtitle)): ?><p><?php echo esc_html($subtitle); ?></p><?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>كود الطالب</th>
                <th style="text-align: right;">اسم الطالب</th>
                <th>الصف والشعبة</th>
                <?php if ($report_type === 'daily'): ?>
                    <th>ملاحظات</th>
                <?php else: ?>
                    <th>إجمالي الغياب (هذا الفصل)</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="5">لا يوجد بيانات لعرضها في هذا التقرير.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $index => $row): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo esc_html($row->student_code); ?></td>
                    <td style="text-align: right; font-weight: 700;"><?php echo esc_html($row->name); ?></td>
                    <td><?php echo SM_Settings::format_grade_name($row->class_name, $row->section); ?></td>
                    <td>
                        <?php if ($report_type === 'daily'): ?>
                            إجمالي الغيابات: <?php echo (int)$row->total_absences; ?>
                        <?php else: ?>
                            <strong><?php echo (int)$row->absence_count; ?> يوم</strong>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>المشرف الإداري</p>
            <p>............................</p>
        </div>
        <div class="signature-box">
            <p>يعتمد مدير المدرسة</p>
            <div class="principal-name"><?php echo esc_html($school['school_principal_name'] ?? ''); ?></div>
            <p>التوقيع: ............................</p>
        </div>
    </div>
</body>
</html>
