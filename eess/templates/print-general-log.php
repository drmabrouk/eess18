<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سجل المخالفات العام</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: portrait; margin: 15mm; }
        body { font-family: 'Rubik', sans-serif; padding: 0; color: #1a202c; line-height: 1.6; }
        .report-header { text-align: center; border-bottom: 4px double #2d3748; padding-bottom: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85em; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: right; }
        th { background: #f1f5f9; font-weight: 700; }
        .severity-high { background: #fee2e2; color: #991b1b; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 20px; padding: 20px;">
        <button onclick="window.print()" style="padding: 10px 25px; background: #27ae60; color: #fff; border: none; border-radius: 5px; cursor: pointer;">طباعة السجل</button>
    </div>

    <?php $school = SM_Settings::get_school_info(); ?>
    <div class="report-header">
        <h2 style="margin: 0;"><?php echo esc_html($school['school_name']); ?></h2>
        <h3 style="margin: 10px 0 0 0; color: #4a5568;">سجل المخالفات السلوكية والانضباطية</h3>
        <p style="margin: 5px 0; font-size: 0.9em;"><?php 
            if(!empty($_GET['start_date']) || !empty($_GET['end_date'])) {
                echo 'الفترة من: ' . ($_GET['start_date'] ?: '---') . ' إلى: ' . ($_GET['end_date'] ?: '---');
            } else {
                echo 'كافة السجلات المسجلة';
            }
        ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>اسم الطالب</th>
                <th>الصف</th>
                <th>نوع المخالفة</th>
                <th>الحدة</th>
                <th>الإجراء المتخذ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $type_labels = SM_Settings::get_violation_types();
            $sev_labels = SM_Settings::get_severities();
            foreach ($records as $r): ?>
                <tr class="<?php echo $r->severity === 'high' ? 'severity-high' : ''; ?>">
                    <td style="white-space: nowrap;"><?php echo date('Y-m-d', strtotime($r->created_at)); ?></td>
                    <td><strong><?php echo esc_html($r->student_name); ?></strong></td>
                    <td><?php echo esc_html($r->class_name); ?></td>
                    <td><?php echo $type_labels[$r->type] ?? $r->type; ?></td>
                    <td><?php echo $sev_labels[$r->severity] ?? $r->severity; ?></td>
                    <td><?php echo esc_html($r->action_taken); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: left; font-size: 0.9em;">
        <p>يعتمد مدير المدرسة: <?php echo esc_html($school['school_principal_name'] ?? ''); ?></p>
        <br>
        <p>التوقيع: ................................................</p>
    </div>
</body>
</html>
