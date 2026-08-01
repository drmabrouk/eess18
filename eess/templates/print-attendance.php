<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سجل الحضور والغياب - <?php echo esc_html($date); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: 'Rubik', sans-serif; background: #fff; margin: 0; padding: 0; color: #111F35; }
        .print-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111F35; padding-bottom: 15px; margin-bottom: 30px; }
        .school-info h1 { margin: 0; font-size: 1.5em; font-weight: 900; }
        .report-title { text-align: center; margin-bottom: 30px; }
        .report-title h2 { margin: 0; font-size: 1.8em; font-weight: 800; color: #F63049; }
        .report-meta { display: flex; justify-content: center; gap: 40px; font-weight: 700; margin-bottom: 20px; font-size: 0.9em; }

        .attendance-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .attendance-table th { background: #f8fafc; color: #111F35; font-weight: 800; border: 1px solid #cbd5e1; padding: 10px; font-size: 0.85em; text-align: center; }
        .attendance-table td { border: 1px solid #cbd5e1; padding: 10px; font-size: 0.85em; text-align: center; }
        .attendance-table tr:nth-child(even) { background: #fcfcfc; }

        .status-badge { padding: 3px 8px; border-radius: 4px; font-weight: 800; font-size: 0.75em; }
        .status-present { background: #f0fff4; color: #38a169; }
        .status-absent { background: #fff5f5; color: #e53e3e; }
        .status-late { background: #fffff0; color: #ecc94b; }
        .status-excused { background: #f0f7ff; color: #3182ce; }

        .grade-summary { margin-top: 50px; page-break-after: always; }
        .grade-summary:last-child { page-break-after: auto; }

        @media print {
            .no-print { display: none !important; }
            .attendance-table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f8fafc; padding: 20px; text-align: center; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px;">
        <button onclick="window.print()" style="padding: 12px 30px; background: #38a169; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-family: 'Rubik', sans-serif;">بدء الطباعة الآن</button>
    </div>

    <?php
    $school = SM_Settings::get_school_info();

    foreach ($grouped_data as $scope_key => $students):
        $parts = explode('|', $scope_key);
        $curr_grade = $parts[0];
        $curr_section = $parts[1] ?? '';
    ?>
    <div class="grade-summary">
        <div class="print-header">
            <div class="school-info">
                <?php if ($school['school_logo']): ?>
                    <img src="<?php echo esc_url($school['school_logo']); ?>" style="height: 50px; margin-bottom: 10px;">
                <?php endif; ?>
                <h1><?php echo esc_html($school['school_name']); ?></h1>
            </div>
            <div style="text-align: left;">
                <div style="font-weight: 800;">سجل الحضور والغياب</div>
                <div style="font-size: 0.9em;"><?php echo date_i18n('l، j F Y', strtotime($date)); ?></div>
            </div>
        </div>

        <div class="report-title">
            <h2><?php echo esc_html($curr_grade); ?><?php echo $curr_section ? ' - شعبة ' . esc_html($curr_section) : ''; ?></h2>
        </div>

        <div class="report-meta">
            <span>عدد الطلاب: <?php echo count($students); ?></span>
            <span>الحاضرين: <?php echo count(array_filter($students, function($s) { return $s->status == 'present'; })); ?></span>
            <span>الغائبين: <?php echo count(array_filter($students, function($s) { return $s->status == 'absent'; })); ?></span>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>كود الطالب</th>
                    <th style="text-align: right;">اسم الطالب</th>
                    <th>الحالة</th>
                    <th style="text-align: right;">ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $s): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo esc_html($s->student_code); ?></td>
                    <td style="text-align: right; font-weight: 700;"><?php echo esc_html($s->name); ?></td>
                    <td>
                        <?php
                        $status_label = 'غير مرصود';
                        $class = '';
                        switch($s->status) {
                            case 'present': $status_label = 'حاضر'; $class = 'status-present'; break;
                            case 'absent': $status_label = 'غائب'; $class = 'status-absent'; break;
                            case 'late': $status_label = 'متأخر'; $class = 'status-late'; break;
                            case 'excused': $status_label = 'بعذر'; $class = 'status-excused'; break;
                        }
                        ?>
                        <span class="status-badge <?php echo $class; ?>"><?php echo $status_label; ?></span>
                    </td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="text-align: center;">
                <p style="font-weight: 800; margin-bottom: 30px;">توقيع رائد الفصل</p>
                <p>............................</p>
            </div>
            <div style="text-align: center;">
                <p style="font-weight: 800; margin-bottom: 10px;">يعتمد مدير المدرسة</p>
                <p style="font-weight: 900; color: #111F35; margin-bottom: 20px;"><?php echo esc_html($school['school_principal_name'] ?? ''); ?></p>
                <p>التوقيع: ............................</p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>
