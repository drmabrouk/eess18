<?php
if (!defined('ABSPATH')) exit;

$roles = (array) wp_get_current_user()->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

$all_subjects = SM_DB::get_subjects();
$unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $all_subjects));

// PRINT EXPORTER INTERCEPTOR
$role_map = array(
    'administrator' => 'الإدارة المركزية (المطور)',
    'sm_system_admin' => 'مدير النظام التقني',
    'sm_principal' => 'مدير المدرسة',
    'sm_supervisor' => 'مشرف تربوي',
    'sm_coordinator' => 'منسق مادة',
    'sm_teacher' => 'معلم',
    'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
    'sm_activities_supervisor' => 'مشرف أنشطة',
    'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
    'sm_bus_supervisor' => 'مشرف حافلة',
    'sm_clinic' => 'العيادة المدرسية',
    'sm_hr' => 'الموارد البشرية (HR)'
);

if (isset($_GET['eess_print_report'])) {
    $print_emp_id = intval($_GET['employee_id']);
    $pe = get_userdata($print_emp_id);
    if ($pe && ($is_admin || $is_sys_admin || $is_hr)) {
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>التقرير المهني الموحد - <?php echo esc_html($pe->display_name); ?></title>
            <style>
                body { font-family: 'Cairo', sans-serif; padding: 40px; color: #1e293b; background: white; line-height: 1.6; }
                .header { border-bottom: 3px solid #1e293b; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
                .title { font-size: 24px; font-weight: 900; margin: 0; }
                .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                .meta-table th, .meta-table td { border: 1px solid #cbd5e1; padding: 12px; text-align: right; }
                .meta-table th { background: #f8fafc; font-weight: bold; width: 30%; }
                .section-title { font-size: 18px; font-weight: 800; border-bottom: 2px solid #cbd5e1; padding-bottom: 5px; margin: 30px 0 15px 0; color: #1e293b; }
                .records-table { width: 100%; border-collapse: collapse; }
                .records-table th, .records-table td { border: 1px solid #cbd5e1; padding: 10px; text-align: right; font-size: 13px; }
                .records-table th { background: #f1f5f9; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body onload="window.print()">
            <div class="no-print" style="background:#f1f5f9; padding:15px; border-radius:8px; margin-bottom:30px; text-align:center;">
                <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer;">🖨️ اضغط هنا لبدء الطباعة</button>
            </div>

            <div class="header">
                <div>
                    <h1 class="title">الملف المهني والوظيفي المتكامل (EESS)</h1>
                    <p style="margin:5px 0 0 0; color:#64748b;">تاريخ التصدير: <?php echo current_time('Y-m-d H:i'); ?></p>
                </div>
                <div style="font-weight: 900; font-size: 20px; color: #8b1e1e;">EESS ONLINE</div>
            </div>

            <div style="display:flex; gap:30px; align-items:center; margin-bottom:30px;">
                <?php echo get_avatar($pe->ID, 100, '', '', array('style' => 'border-radius: 50%; border: 3px solid #cbd5e1; width: 100px; height: 100px;')); ?>
                <div>
                    <h2 style="margin:0; font-weight:800;"><?php echo esc_html($pe->display_name); ?></h2>
                    <p style="margin:5px 0 0 0; color:#475569;">المسمى الوظيفي: <?php echo esc_html($role_map[$pe->roles[0]] ?? $pe->roles[0]); ?></p>
                </div>
            </div>

            <h3 class="section-title">📋 البيانات العامة والوظيفية</h3>
            <table class="meta-table">
                <tr><th>الرقم الوظيفي للموظف</th><td><?php echo esc_html(get_user_meta($pe->ID, 'eess_employee_number', true) ?: 'غير محدد'); ?></td></tr>
                <tr><th>البريد الإلكتروني المعتمد</th><td><?php echo esc_html($pe->user_email); ?></td></tr>
                <tr><th>رقم الهاتف المتحرك</th><td><?php echo esc_html(get_user_meta($pe->ID, 'sm_phone', true) ?: 'غير محدد'); ?></td></tr>
                <tr><th>القسم / الإدارة</th><td><?php echo esc_html(get_user_meta($pe->ID, 'eess_department', true) ?: 'غير محدد'); ?></td></tr>
                <tr><th>المؤسسة / المدرسة</th><td><?php echo esc_html(get_user_meta($pe->ID, 'eess_school_name', true) ?: 'غير محدد'); ?></td></tr>
                <tr><th>تاريخ مباشرة العمل</th><td><?php echo esc_html(get_user_meta($pe->ID, 'eess_hr_employment_date', true) ?: 'غير محدد'); ?></td></tr>
                <tr><th>حالة التوظيف الحالية</th><td><?php echo esc_html(get_user_meta($pe->ID, 'eess_hr_employment_status', true) === 'active' ? 'نشط بالخدمة' : 'غير نشط / مقيد'); ?></td></tr>
            </table>

            <h3 class="section-title">📊 سجل الرواتب والمالية</h3>
            <table class="records-table">
                <thead>
                    <tr><th>الشهر</th><th>الأساسي</th><th>بدل سكن</th><th>بدل انتقال</th><th>خصومات</th><th>الصافي</th></tr>
                </thead>
                <tbody>
                    <?php
                    $salaries = get_user_meta($pe->ID, 'eess_hr_salary_records', true) ?: array();
                    if (!is_array($salaries)) $salaries = json_decode($salaries, true) ?: array();
                    if (empty($salaries)): ?>
                        <tr><td colspan="6" style="text-align:center; color:#94a3b8;">لا توجد سجلات رواتب.</td></tr>
                    <?php else: ?>
                        <?php foreach ($salaries as $s): ?>
                            <tr>
                                <td><?php echo esc_html($s['date']); ?></td>
                                <td><?php echo number_format($s['basic'] ?? 0, 2); ?> د.إ</td>
                                <td><?php echo number_format($s['housing'] ?? 0, 2); ?> د.إ</td>
                                <td><?php echo number_format($s['transport'] ?? 0, 2); ?> د.إ</td>
                                <td style="color:#b91c1c;">-<?php echo number_format($s['deductions'] ?? 0, 2); ?> د.إ</td>
                                <td style="font-weight:bold; color:#15803d;"><?php echo number_format($s['net'] ?? 0, 2); ?> د.إ</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 class="section-title">⚠️ التنبيهات والإنذارات الرسمية</h3>
            <table class="records-table">
                <thead>
                    <tr><th>التاريخ</th><th>الموضوع / نوع الإنذار</th><th>تفاصيل ومسببات المخالفة</th><th>الحالة</th></tr>
                </thead>
                <tbody>
                    <?php
                    $warnings = get_user_meta($pe->ID, 'eess_hr_warning_notices', true) ?: array();
                    if (!is_array($warnings)) $warnings = json_decode($warnings, true) ?: array();
                    if (empty($warnings)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#94a3b8;">السجل خالي من أي إنذارات مسجلة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($warnings as $w): ?>
                            <tr>
                                <td><?php echo esc_html($w['date']); ?></td>
                                <td style="font-weight:bold; color:#dc2626;"><?php echo esc_html($w['subject']); ?></td>
                                <td><?php echo esc_html($w['details']); ?></td>
                                <td><?php echo esc_html($w['status'] ?? 'نشط'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 class="section-title">📈 تقييم الأداء والتقارير</h3>
            <table class="records-table">
                <thead>
                    <tr><th>التاريخ / الفترة</th><th>الدرجة (%)</th><th>التقدير العام</th><th>التوصيات والملاحظات</th><th>المقيم المعتمد</th></tr>
                </thead>
                <tbody>
                    <?php
                    $evals = get_user_meta($pe->ID, 'eess_hr_evaluations', true) ?: array();
                    if (!is_array($evals)) $evals = json_decode($evals, true) ?: array();
                    if (empty($evals)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8;">لا توجد تقييمات مسجلة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($evals as $ev): ?>
                            <tr>
                                <td><?php echo esc_html($ev['period'] ?? $ev['date'] ?? 'غير محدد'); ?></td>
                                <td><?php echo esc_html($ev['score'] ?? '0'); ?>%</td>
                                <td><?php echo esc_html($ev['grade'] ?? 'غير محدد'); ?></td>
                                <td><?php echo esc_html($ev['notes'] ?? $ev['comments'] ?? ''); ?></td>
                                <td><?php echo esc_html($ev['evaluator'] ?? 'الإدارة'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top:50px; text-align:left; font-size:12px; color:#64748b;">
                <p>إعتماد إدارة الموارد البشرية والأنظمة الإلكترونية التعليمية (EESS)</p>
                <p style="margin-top:40px; font-weight:bold;">التوقيع والختم الرسمي: _______________________</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

if (!$is_admin && !$is_sys_admin && !$is_hr) {
    echo '<div class="error" style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700;">غير مصرح لك بالوصول لهذه الصفحة.</div>';
    return;
}

// Arabic role maps
$role_map = array(
    'administrator' => 'الإدارة المركزية (المطور)',
    'sm_system_admin' => 'مدير النظام التقني',
    'sm_principal' => 'مدير المدرسة',
    'sm_supervisor' => 'مشرف تربوي',
    'sm_coordinator' => 'منسق مادة',
    'sm_teacher' => 'معلم',
    'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
    'sm_activities_supervisor' => 'مشرف أنشطة',
    'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
    'sm_bus_supervisor' => 'مشرف حافلة',
    'sm_clinic' => 'العيادة المدرسية',
    'sm_hr' => 'الموارد البشرية (HR)',
    'sm_student' => 'طالب',
    'sm_parent' => 'ولي أمر'
);

// HANDLE SUBMISSIONS AND HR MUTATIONS
$status_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eess_hr_action']) && wp_verify_nonce($_POST['eess_hr_nonce'], 'eess_hr_action_nonce')) {
    $emp_id = intval($_POST['target_employee_id']);
    $action_type = sanitize_text_field($_POST['eess_hr_action']);

    if ($action_type === 'save_employment') {
        update_user_meta($emp_id, 'eess_employee_number', sanitize_text_field($_POST['employee_number']));
        update_user_meta($emp_id, 'eess_department', sanitize_text_field($_POST['department']));
        update_user_meta($emp_id, 'eess_school_name', sanitize_text_field($_POST['school_name']));
        update_user_meta($emp_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        update_user_meta($emp_id, 'eess_hr_employment_date', sanitize_text_field($_POST['employment_date']));
        update_user_meta($emp_id, 'eess_hr_employment_status', sanitize_text_field($_POST['employment_status']));
        update_user_meta($emp_id, 'sm_phone', sanitize_text_field($_POST['phone']));

        wp_update_user(array('ID' => $emp_id, 'display_name' => sanitize_text_field($_POST['display_name'])));
        $status_message = 'تم تحديث بيانات التعيين والسجل الوظيفي للموظف بنجاح.';
        SM_Logger::log('تحديث السجل الوظيفي', "تم تحديث السجل الوظيفي للموظف المعرف: $emp_id");
    }

    elseif ($action_type === 'restrict_platform_access') {
        $reason = sanitize_text_field($_POST['restriction_reason'] ?? '');
        update_user_meta($emp_id, 'eess_approval_status', 'restricted');
        update_user_meta($emp_id, 'eess_access_restricted', 'yes');
        update_user_meta($emp_id, 'eess_restriction_reason', $reason);
        update_user_meta($emp_id, 'eess_hr_employment_status', 'restricted');

        $timeline = get_user_meta($emp_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) $timeline = array();
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => 'تقييد الوصول للمنصة',
            'actor' => $current_user->display_name,
            'details' => "تم تقييد وصول الموظف إلى المنصة لسبب: $reason"
        ));
        update_user_meta($emp_id, 'eess_hr_activity_timeline', $timeline);

        clean_user_cache($emp_id);
        wp_cache_flush();

        $status_message = "✅ تم تقييد وصول الموظف إلى المنصة بنجاح لسبب: $reason";
        SM_Logger::log('تقييد دخول موظف', "تم تقييد حساب الموظف $emp_id لسبب: $reason");
    }

    elseif ($action_type === 'remove_platform_restriction') {
        update_user_meta($emp_id, 'eess_approval_status', 'approved');
        update_user_meta($emp_id, 'eess_access_restricted', 'no');
        update_user_meta($emp_id, 'eess_restriction_reason', '');
        update_user_meta($emp_id, 'eess_hr_employment_status', 'active');

        $timeline = get_user_meta($emp_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) $timeline = array();
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => 'إلغاء تقييد الوصول للمنصة',
            'actor' => $current_user->display_name,
            'details' => "تم إلغاء تقييد الوصول وتفعيل الحساب مجدداً."
        ));
        update_user_meta($emp_id, 'eess_hr_activity_timeline', $timeline);

        clean_user_cache($emp_id);
        wp_cache_flush();

        $status_message = "✅ تم إلغاء تقييد وصول الموظف وتفعيل حسابه مجدداً بنجاح.";
        SM_Logger::log('تنشيط حساب موظف', "تم إلغاء تقييد حساب الموظف $emp_id");
    }

    elseif ($action_type === 'add_salary') {
        $records = get_user_meta($emp_id, 'eess_hr_salary_records', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();

        $records[] = array(
            'date' => sanitize_text_field($_POST['salary_date']),
            'basic' => floatval($_POST['salary_basic']),
            'housing' => floatval($_POST['salary_housing']),
            'transport' => floatval($_POST['salary_transport']),
            'deductions' => floatval($_POST['salary_deductions']),
            'net' => floatval($_POST['salary_basic']) + floatval($_POST['salary_housing']) + floatval($_POST['salary_transport']) - floatval($_POST['salary_deductions']),
            'notes' => sanitize_textarea_field($_POST['salary_notes'])
        );
        update_user_meta($emp_id, 'eess_hr_salary_records', $records);
        $status_message = 'تمت إضافة قيد الرواتب والمالية بنجاح.';
    }

    elseif ($action_type === 'delete_salary') {
        $records = get_user_meta($emp_id, 'eess_hr_salary_records', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();
        $index = intval($_POST['delete_index']);
        if (isset($records[$index])) {
            unset($records[$index]);
            $records = array_values($records);
            update_user_meta($emp_id, 'eess_hr_salary_records', $records);
            $status_message = 'تم حذف قيد الراتب المحدد بنجاح.';
        }
    }

    elseif ($action_type === 'add_warning') {
        $records = get_user_meta($emp_id, 'eess_hr_warning_notices', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();

        $records[] = array(
            'date' => sanitize_text_field($_POST['warning_date']),
            'subject' => sanitize_text_field($_POST['warning_subject']),
            'details' => sanitize_textarea_field($_POST['warning_details']),
            'status' => sanitize_text_field($_POST['warning_status'])
        );
        update_user_meta($emp_id, 'eess_hr_warning_notices', $records);
        $status_message = 'تم تسجيل الإنذار الرسمي وحفظ المحضر بنجاح.';
    }

    elseif ($action_type === 'delete_warning') {
        $records = get_user_meta($emp_id, 'eess_hr_warning_notices', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();
        $index = intval($_POST['delete_index']);
        if (isset($records[$index])) {
            unset($records[$index]);
            $records = array_values($records);
            update_user_meta($emp_id, 'eess_hr_warning_notices', $records);
            $status_message = 'تم حذف الإنذار بنجاح.';
        }
    }

    elseif ($action_type === 'add_disciplinary') {
        $records = get_user_meta($emp_id, 'eess_hr_disciplinary_records', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();

        $records[] = array(
            'date' => sanitize_text_field($_POST['disc_date']),
            'incident' => sanitize_text_field($_POST['disc_incident']),
            'action' => sanitize_text_field($_POST['disc_action']),
            'supervisor' => sanitize_text_field($_POST['disc_supervisor'])
        );
        update_user_meta($emp_id, 'eess_hr_disciplinary_records', $records);
        $status_message = 'تم تسجيل قرار مجلس الانضباط بنجاح.';
    }

    elseif ($action_type === 'delete_disciplinary') {
        $records = get_user_meta($emp_id, 'eess_hr_disciplinary_records', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();
        $index = intval($_POST['delete_index']);
        if (isset($records[$index])) {
            unset($records[$index]);
            $records = array_values($records);
            update_user_meta($emp_id, 'eess_hr_disciplinary_records', $records);
            $status_message = 'تم حذف سجل الانضباط بنجاح.';
        }
    }

    elseif ($action_type === 'add_admin_action') {
        $records = get_user_meta($emp_id, 'eess_hr_admin_actions', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();

        $records[] = array(
            'date' => sanitize_text_field($_POST['admin_date']),
            'action' => sanitize_text_field($_POST['admin_title']),
            'notes' => sanitize_textarea_field($_POST['admin_notes'])
        );
        update_user_meta($emp_id, 'eess_hr_admin_actions', $records);
        $status_message = 'تم تسجيل التوجيه / القرار الإداري بنجاح.';
    }

    elseif ($action_type === 'delete_admin_action') {
        $records = get_user_meta($emp_id, 'eess_hr_admin_actions', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();
        $index = intval($_POST['delete_index']);
        if (isset($records[$index])) {
            unset($records[$index]);
            $records = array_values($records);
            update_user_meta($emp_id, 'eess_hr_admin_actions', $records);
            $status_message = 'تم حذف القرار الإداري بنجاح.';
        }
    }

    elseif ($action_type === 'add_document') {
        $records = get_user_meta($emp_id, 'eess_hr_documents', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();

        $records[] = array(
            'date' => sanitize_text_field($_POST['doc_date']),
            'name' => sanitize_text_field($_POST['doc_name']),
            'file_url' => esc_url_raw($_POST['doc_file_url'])
        );
        update_user_meta($emp_id, 'eess_hr_documents', $records);
        $status_message = 'تم رفع وأرشفة المستند الثبوتي بنجاح.';
    }

    elseif ($action_type === 'delete_document') {
        $records = get_user_meta($emp_id, 'eess_hr_documents', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();
        $index = intval($_POST['delete_index']);
        if (isset($records[$index])) {
            unset($records[$index]);
            $records = array_values($records);
            update_user_meta($emp_id, 'eess_hr_documents', $records);
            $status_message = 'تم حذف المستند بنجاح.';
        }
    }

    elseif ($action_type === 'add_history') {
        $records = get_user_meta($emp_id, 'eess_hr_employment_history', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();

        $records[] = array(
            'date' => sanitize_text_field($_POST['hist_date']),
            'role' => sanitize_text_field($_POST['hist_role']),
            'organization' => sanitize_text_field($_POST['hist_organization']),
            'notes' => sanitize_textarea_field($_POST['hist_notes'])
        );
        update_user_meta($emp_id, 'eess_hr_employment_history', $records);
        $status_message = 'تم تسجيل الخبرة السابقة للتاريخ الوظيفي بنجاح.';
    }

    elseif ($action_type === 'delete_history') {
        $records = get_user_meta($emp_id, 'eess_hr_employment_history', true) ?: array();
        if (!is_array($records)) $records = json_decode($records, true) ?: array();
        $index = intval($_POST['delete_index']);
        if (isset($records[$index])) {
            unset($records[$index]);
            $records = array_values($records);
            update_user_meta($emp_id, 'eess_hr_employment_history', $records);
            $status_message = 'تم حذف السجل التاريخي المحدد بنجاح.';
        }
    }
}

// Fetch list of employees (all users except students/parents)
$employees = get_users();
$employees = array_filter($employees, function($u) {
    $role = !empty($u->roles) ? $u->roles[0] : '';
    return $role !== 'sm_student' && $role !== 'sm_parent';
});

// Deciding active edited employee details if requested
$edit_emp = null;
if (isset($_GET['manage_employee_id'])) {
    $edit_emp = get_userdata(intval($_GET['manage_employee_id']));
}
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Action feedback notices -->
    <?php if (!empty($status_message)): ?>
        <div class="updated" style="background:#def7ec; color:#03543f; padding:15px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:20px; font-weight:700; font-size: 13px;">
            <?php echo esc_html($status_message); ?>
        </div>
    <?php endif; ?>

    <!-- MAIN DASHBOARD VIEW -->
    <?php if (!$edit_emp): ?>
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: var(--sm-shadow);">

            <!-- Advanced Filters -->
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="font-size: 12px; font-weight: bold; color: #475569;">البحث بالاسم / الرقم الوظيفي</label>
                    <input type="text" id="hr-search" onkeyup="filterHREmployees()" placeholder="ابحث بالاسم، الرقم الوظيفي..." class="sm-input" style="height: 36px; font-size: 12px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: bold; color: #475569;">تصفية حسب القسم</label>
                    <input type="text" id="hr-dept-filter" onkeyup="filterHREmployees()" placeholder="مثال: العلوم، الإدارة..." class="sm-input" style="height: 36px; font-size: 12px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: bold; color: #475569;">حالة الموظف</label>
                    <select id="hr-status-filter" onchange="filterHREmployees()" class="sm-select" style="height: 36px; font-size: 12px;">
                        <option value="">الكل</option>
                        <option value="active">نشط بالخدمة</option>
                        <option value="suspended">موقوف مؤقتاً</option>
                        <option value="leave">إجازة سنوية</option>
                    </select>
                </div>
            </div>

            <!-- Employees List (Full-Width Redesigned Cards) -->
            <div style="display: flex; flex-direction: column; gap: 15px;" id="hr-employees-grid">
                <?php foreach ($employees as $emp):
                    $emp_role = !empty($emp->roles) ? $emp->roles[0] : '';
                    $emp_num = get_user_meta($emp->ID, 'eess_employee_number', true) ?: 'غير محدد';
                    $emp_dept = get_user_meta($emp->ID, 'eess_department', true) ?: 'غير محدد';
                    $emp_status = get_user_meta($emp->ID, 'eess_hr_employment_status', true) ?: 'active';
                ?>
                    <div class="hr-employee-card"
                         data-name="<?php echo esc_attr(strtolower($emp->display_name)); ?>"
                         data-number="<?php echo esc_attr($emp_num); ?>"
                         data-dept="<?php echo esc_attr(strtolower($emp_dept)); ?>"
                         data-status="<?php echo esc_attr($emp_status); ?>"
                         style="background: #fff; border: 1px solid #cbd5e0; border-radius: 12px; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; transition: 0.2s;"
                    >
                        <!-- Left block: Avatar, Name & Role -->
                        <div style="display: flex; gap: 15px; align-items: center; min-width: 250px; flex: 1;">
                            <?php echo get_avatar($emp->ID, 50, '', '', array('style' => 'border-radius: 50% !important; border: 2.5px solid var(--sm-primary-color); width: 50px; height: 50px; object-fit: cover; display: block;')); ?>
                            <div>
                                <h4 style="margin: 0 0 4px 0; font-weight: 800; font-size: 14px; color: #1e293b;"><?php echo esc_html($emp->display_name); ?></h4>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 10px; color: #475569; font-weight: bold; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
                                        <?php echo $role_map[$emp_role] ?? $emp_role; ?>
                                    </span>
                                    <span style="font-size: 10px; color: #64748b; font-family: monospace;">@<?php echo esc_html($emp->user_login); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Middle block: Employee Metadata -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px 15px; min-width: 280px; flex: 1.5; font-size: 12px; color: #475569;">
                            <div><strong>الرقم الوظيفي:</strong> <code style="background: #f8fafc; padding: 2px 6px; border-radius: 4px; font-weight:bold;"><?php echo esc_html($emp_num); ?></code></div>
                            <div><strong>القسم / الإدارة:</strong> <span style="font-weight: 600;"><?php echo esc_html($emp_dept); ?></span></div>
                            <div><strong>المادة / التخصص:</strong> <span style="color: var(--sm-primary-color); font-weight: 700;"><?php echo esc_html(get_user_meta($emp->ID, 'sm_specialization', true) ?: 'غير محدد'); ?></span></div>
                            <div><strong>البريد الإلكتروني:</strong> <span style="font-family: monospace;"><?php echo esc_html($emp->user_email); ?></span></div>
                        </div>

                        <!-- Status Badge -->
                        <div style="min-width: 120px; display: flex; flex-direction: column; gap: 4px; align-items: center; justify-content: center;">
                            <?php if ($emp_status === 'active'): ?>
                                <span style="display:inline-block; padding: 3px 12px; font-size: 11px; font-weight: bold; background: #dcfce7; color: #15803d; border-radius: 50px; border: 1px solid #bbf7d0; text-align:center; width:100%;">نشط بالخدمة</span>
                            <?php elseif ($emp_status === 'restricted'): ?>
                                <span style="display:inline-block; padding: 3px 12px; font-size: 11px; font-weight: bold; background: #fee2e2; color: #991b1b; border-radius: 50px; border: 1px solid #fca5a5; text-align:center; width:100%;">مقيد الدخول</span>
                            <?php else: ?>
                                <span style="display:inline-block; padding: 3px 12px; font-size: 11px; font-weight: bold; background: #f1f5f9; color: #475569; border-radius: 50px; border: 1px solid #cbd5e1; text-align:center; width:100%;">غير نشط</span>
                            <?php endif; ?>

                            <?php if (get_user_meta($emp->ID, 'eess_access_restricted', true) === 'yes'): ?>
                                <div style="font-size: 10px; color: #991b1b; font-weight: bold; text-align: center;">السبب: <?php echo esc_html(get_user_meta($emp->ID, 'eess_restriction_reason', true) ?: 'غير محدد'); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Right block: Quick Action Buttons -->
                        <div style="display: flex; gap: 6px; align-items: center; justify-content: flex-end; flex-wrap: nowrap; min-width: 180px;">
                            <a href="<?php echo add_query_arg('manage_employee_id', $emp->ID); ?>" class="sm-btn" style="padding: 0 10px !important; font-size: 11px !important; height: 32px !important; line-height: 32px !important; background: #334155 !important; border: 1px solid #334155 !important; color: white !important; border-radius: 6px !important; display: inline-flex !important; align-items: center !important; gap: 4px !important; text-decoration: none !important;" title="إدارة الملف المهني">
                                <span class="dashicons dashicons-admin-generic" style="font-size:14px; margin:0;"></span>
                                <span>إدارة الملف</span>
                            </a>

                            <!-- Print Employee Report (Blue Printer Icon only, without text) -->
                            <button type="button" onclick="eessPrintEmployeeReport(<?php echo $emp->ID; ?>)" class="sm-btn" style="padding: 0 !important; width: 32px !important; min-width: 32px !important; height: 32px !important; background: #3182ce !important; border: 1px solid #3182ce !important; color: white !important; border-radius: 6px !important; cursor: pointer; display: inline-flex !important; align-items: center !important; justify-content: center !important;" title="طباعة التقرير المهني">
                                <span class="dashicons dashicons-printer" style="font-size:16px; margin:0;"></span>
                            </button>

                            <!-- Restrict Platform Access (Red Lock Icon only, without text) -->
                            <?php if (get_user_meta($emp->ID, 'eess_access_restricted', true) === 'yes'): ?>
                                <button type="button" onclick="eessOpenUnrestrictModal(<?php echo $emp->ID; ?>, '<?php echo esc_attr($emp->display_name); ?>')" class="sm-btn" style="padding: 0 !important; width: 32px !important; min-width: 32px !important; height: 32px !important; background: #16a34a !important; border: 1px solid #16a34a !important; color: white !important; border-radius: 6px !important; cursor: pointer; display: inline-flex !important; align-items: center !important; justify-content: center !important;" title="إلغاء تقييد الدخول">
                                    <span class="dashicons dashicons-unlock" style="font-size:16px; margin:0;"></span>
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="eessOpenRestrictModal(<?php echo $emp->ID; ?>, '<?php echo esc_attr($emp->display_name); ?>')" class="sm-btn" style="padding: 0 !important; width: 32px !important; min-width: 32px !important; height: 32px !important; background: #dc2626 !important; border: 1px solid #dc2626 !important; color: white !important; border-radius: 6px !important; cursor: pointer; display: inline-flex !important; align-items: center !important; justify-content: center !important;" title="تقييد الدخول">
                                    <span class="dashicons dashicons-lock" style="font-size:16px; margin:0;"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ADD EMPLOYEE MODAL -->
        <div id="eessAddEmployeeModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px); direction: rtl;">
            <div style="background: #fff; width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; font-family: 'Cairo', sans-serif;">
                <div style="background: #334155; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: white !important;">➕ إضافة موظف جديد (حساب معلق)</h3>
                    <button type="button" onclick="eessCloseAddEmployeeModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
                </div>
                <form id="eess-add-employee-form" style="padding: 20px; margin: 0;" onsubmit="eessSubmitAddEmployee(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">الاسم الكامل:</label>
                            <input type="text" name="display_name" class="sm-input" required placeholder="الاسم ثلاثي" style="height: 38px;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">اسم المستخدم (Login):</label>
                            <input type="text" name="user_login" class="sm-input" required placeholder="login_name" style="height: 38px;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" name="user_email" class="sm-input" required placeholder="name@company.com" style="height: 38px;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">المسمى الوظيفي / الرتبة:</label>
                            <select name="user_role" class="sm-select" required style="height: 38px; padding: 0 10px;">
                                <option value="sm_teacher">معلم</option>
                                <option value="sm_coordinator">منسق مادة</option>
                                <option value="sm_supervisor">مشرف تربوي</option>
                                <option value="sm_principal">مدير المدرسة</option>
                                <option value="sm_hr">الموارد البشرية (HR)</option>
                                <option value="sm_clinic">العيادة المدرسية</option>
                                <option value="sm_discipline_supervisor">مشرف سلوك / انضباط</option>
                                <option value="sm_activities_supervisor">مشرف أنشطة</option>
                                <option value="sm_transportation_supervisor">مشرف نقل ومواصلات</option>
                                <option value="sm_bus_supervisor">مشرف حافلة</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">الرقم الوظيفي:</label>
                            <input type="text" name="employee_number" class="sm-input" placeholder="EESS-00000" style="height: 38px;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">المؤسسة / المدرسة المنتسب لها:</label>
                            <input type="text" name="institution" class="sm-input" placeholder="اسم المدرسة أو الإدارة" style="height: 38px;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">القسم التابع له:</label>
                            <input type="text" name="department" class="sm-input" placeholder="قسم العلوم، الإدارة..." style="height: 38px;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 11px;">المادة التخصصية:</label>
                            <select name="specialization" class="sm-select" style="height: 38px; padding: 0 10px;">
                                <option value="">-- اختر المادة --</option>
                                <?php foreach($unique_subjects as $sub_name): ?>
                                    <option value="<?php echo esc_attr($sub_name); ?>"><?php echo esc_html($sub_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2;">
                            <label class="sm-label" style="font-size: 11px;">كلمة المرور:</label>
                            <input type="password" name="user_pass" class="sm-input" required placeholder="أدخل كلمة مرور قوية" style="height: 38px;">
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2; margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 11px;">الصورة الشخصية:</label>
                            <input type="file" name="profile_photo" class="sm-input" accept="image/*" style="height: auto; padding: 5px;">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                        <button type="button" onclick="eessCloseAddEmployeeModal()" class="sm-btn sm-btn-outline" style="height: 38px;">إلغاء</button>
                        <button type="submit" class="sm-btn" style="background: #334155; color: white; height: 38px; padding: 0 25px;">إضافة كمعلق والمزامنة</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function eessOpenAddEmployeeModal() {
            document.getElementById('eessAddEmployeeModal').style.display = 'flex';
        }
        function eessCloseAddEmployeeModal() {
            document.getElementById('eessAddEmployeeModal').style.display = 'none';
        }
        function eessSubmitAddEmployee(e) {
            e.preventDefault();
            const form = document.getElementById('eess-add-employee-form');
            const formData = new FormData(form);
            formData.append('action', 'eess_hr_add_employee');
            formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_hr_add_employee_nonce"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تمت إضافة الموظف بنجاح كحساب معلق لمراجعته في إدارة الحسابات.');
                    eessCloseAddEmployeeModal();
                    form.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    smShowNotification('خطأ: ' + res.data, true);
                }
            })
            .catch(err => {
                smShowNotification('حدث خطأ أثناء معالجة الطلب.', true);
            });
        }

        function filterHREmployees() {
            const search = document.getElementById('hr-search').value.toLowerCase().trim();
            const dept = document.getElementById('hr-dept-filter').value.toLowerCase().trim();
            const status = document.getElementById('hr-status-filter').value;

            const cards = document.querySelectorAll('.hr-employee-card');
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const num = card.getAttribute('data-number');
                const cdept = card.getAttribute('data-dept');
                const cstatus = card.getAttribute('data-status');

                const matchesSearch = !search || name.includes(search) || num.includes(search);
                const matchesDept = !dept || cdept.includes(dept);
                const matchesStatus = !status || cstatus === status;

                if (matchesSearch && matchesDept && matchesStatus) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        </script>

    <!-- EMPLOYEE MANAGEMENT PROFILE EDITOR -->
    <?php else:
        $emp_id = $edit_emp->ID;
        // Fetch supplemental details
        $emp_num = get_user_meta($emp_id, 'eess_employee_number', true) ?: '';
        $emp_dept = get_user_meta($emp_id, 'eess_department', true) ?: '';
        $emp_school = get_user_meta($emp_id, 'eess_school_name', true) ?: '';
        $emp_spec = get_user_meta($emp_id, 'sm_specialization', true) ?: '';
        $emp_date = get_user_meta($emp_id, 'eess_hr_employment_date', true) ?: '';
        $emp_status = get_user_meta($emp_id, 'eess_hr_employment_status', true) ?: 'active';
        $emp_phone = get_user_meta($emp_id, 'sm_phone', true) ?: '';

        // Lists
        $salary_records = get_user_meta($emp_id, 'eess_hr_salary_records', true) ?: array();
        if (!is_array($salary_records)) $salary_records = json_decode($salary_records, true) ?: array();

        $disciplinary_records = get_user_meta($emp_id, 'eess_hr_disciplinary_records', true) ?: array();
        if (!is_array($disciplinary_records)) $disciplinary_records = json_decode($disciplinary_records, true) ?: array();

        $warning_notices = get_user_meta($emp_id, 'eess_hr_warning_notices', true) ?: array();
        if (!is_array($warning_notices)) $warning_notices = json_decode($warning_notices, true) ?: array();

        $admin_actions = get_user_meta($emp_id, 'eess_hr_admin_actions', true) ?: array();
        if (!is_array($admin_actions)) $admin_actions = json_decode($admin_actions, true) ?: array();

        $hr_documents = get_user_meta($emp_id, 'eess_hr_documents', true) ?: array();
        if (!is_array($hr_documents)) $hr_documents = json_decode($hr_documents, true) ?: array();

        $employment_history = get_user_meta($emp_id, 'eess_hr_employment_history', true) ?: array();
        if (!is_array($employment_history)) $employment_history = json_decode($employment_history, true) ?: array();
    ?>
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow); margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px;">
                <h3 style="margin: 0; font-weight: 800; font-size: 1.3rem;">الملف المهني للموظف: <?php echo esc_html($edit_emp->display_name); ?></h3>
                <a href="<?php echo remove_query_arg('manage_employee_id'); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 32px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; color: inherit;">← العودة للقائمة</a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">

                <!-- Box 1: Employment Details Form -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e0; padding: 20px; border-radius: 10px;">
                    <h4 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">⚙️ تعديل بيانات التعيين</h4>
                    <form method="post">
                        <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                        <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                        <input type="hidden" name="eess_hr_action" value="save_employment">

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">الاسم الكامل:</label>
                            <input type="text" name="display_name" value="<?php echo esc_attr($edit_emp->display_name); ?>" class="sm-input" required style="height: 34px; font-size: 12px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">رقم الهاتف الجوال:</label>
                            <input type="text" name="phone" value="<?php echo esc_attr($emp_phone); ?>" class="sm-input" style="height: 34px; font-size: 12px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">رقم الموظف الوظيفي:</label>
                            <input type="text" name="employee_number" value="<?php echo esc_attr($emp_num); ?>" class="sm-input" style="height: 34px; font-size: 12px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">القسم التابع له:</label>
                            <input type="text" name="department" value="<?php echo esc_attr($emp_dept); ?>" class="sm-input" style="height: 34px; font-size: 12px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">المؤسسة / المدرسة:</label>
                            <input type="text" name="school_name" value="<?php echo esc_attr($emp_school); ?>" class="sm-input" style="height: 34px; font-size: 12px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">تخصيص المادة (العربية):</label>
                            <select name="specialization" class="sm-select" style="height: 34px; font-size: 12px; padding: 0 10px;">
                                <option value="">غير محدد</option>
                                <?php foreach($unique_subjects as $sub_name): ?>
                                    <option value="<?php echo esc_attr($sub_name); ?>" <?php selected($emp_spec === $sub_name); ?>><?php echo esc_html($sub_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">تاريخ مباشرة العمل:</label>
                            <input type="date" name="employment_date" value="<?php echo esc_attr($emp_date); ?>" class="sm-input" style="height: 34px; font-size: 12px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 15px;">
                            <label class="sm-label" style="font-size: 11px;">الحالة الوظيفية:</label>
                            <select name="employment_status" class="sm-select" style="height: 34px; font-size: 12px; padding: 0 10px;">
                                <option value="active" <?php selected($emp_status === 'active'); ?>>نشط بالخدمة</option>
                                <option value="suspended" <?php selected($emp_status === 'suspended'); ?>>موقوف مؤقتاً</option>
                                <option value="leave" <?php selected($emp_status === 'leave'); ?>>إجازة سنوية</option>
                            </select>
                        </div>

                        <button type="submit" class="sm-btn" style="width: 100%; height: 36px; font-size: 12px; font-weight: bold;">حفظ تحديث السجل الوظيفي</button>
                    </form>
                </div>

                <!-- Box 2: Mutate Salary, Warning, Docs Records -->
                <div style="display: flex; flex-direction: column; gap: 20px;">

                    <!-- Salary Management Box -->
                    <div style="background: #fff; border: 1px solid #cbd5e0; padding: 15px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0; font-weight: 800; font-size: 13px; color: #1e293b;">📊 إدارة الرواتب والمالية</h4>
                        <form method="post" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                            <input type="hidden" name="eess_hr_action" value="add_salary">

                            <input type="text" name="salary_date" placeholder="الشهر (مثال: مارس 2026)" class="sm-input" required style="height: 30px; font-size: 11px;">
                            <input type="number" name="salary_basic" placeholder="الأساسي" class="sm-input" step="0.01" required style="height: 30px; font-size: 11px;">
                            <input type="number" name="salary_housing" placeholder="السكن" class="sm-input" step="0.01" style="height: 30px; font-size: 11px;">
                            <input type="number" name="salary_transport" placeholder="الانتقال" class="sm-input" step="0.01" style="height: 30px; font-size: 11px;">
                            <input type="number" name="salary_deductions" placeholder="الاستقطاعات" class="sm-input" step="0.01" style="height: 30px; font-size: 11px;">
                            <input type="text" name="salary_notes" placeholder="ملاحظات الصرف" class="sm-input" style="height: 30px; font-size: 11px;">

                            <button type="submit" class="sm-btn" style="grid-column: span 2; height: 30px; font-size: 11px; background: #16a34a;">إضافة قيد الراتب</button>
                        </form>

                        <!-- Existing salary values listing -->
                        <div style="max-height: 120px; overflow-y: auto; font-size: 11px; background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #cbd5e0;">
                            <?php if (empty($salary_records)): ?>
                                <div style="color:#64748b; text-align: center;">لا يوجد قيود رواتب.</div>
                            <?php else: ?>
                                <?php foreach($salary_records as $idx => $sr): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding: 4px 0;">
                                        <span><?php echo esc_html($sr['date']); ?>: <?php echo number_format($sr['net'], 2); ?> د.إ</span>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('حذف هذا القيد المالي؟')">
                                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                                            <input type="hidden" name="eess_hr_action" value="delete_salary">
                                            <input type="hidden" name="delete_index" value="<?php echo $idx; ?>">
                                            <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 10px;">[حذف]</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Warning Notices Management Box -->
                    <div style="background: #fff; border: 1px solid #cbd5e0; padding: 15px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0; font-weight: 800; font-size: 13px; color: #1e293b;">⚠️ إدارة الإنذارات الرسمية</h4>
                        <form method="post" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">
                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                            <input type="hidden" name="eess_hr_action" value="add_warning">

                            <div style="display: flex; gap: 8px;">
                                <input type="date" name="warning_date" class="sm-input" required style="height: 30px; font-size: 11px; flex: 1;">
                                <input type="text" name="warning_subject" placeholder="عنوان الإنذار (مثال: غياب متكرر)" class="sm-input" required style="height: 30px; font-size: 11px; flex: 2;">
                            </div>
                            <textarea name="warning_details" placeholder="تفاصيل ومحضر الواقعة..." class="sm-input" required style="height: 45px; font-size: 11px; padding: 5px;"></textarea>

                            <select name="warning_status" class="sm-select" style="height: 30px; font-size: 11px; padding: 0 5px;">
                                <option value="نشط (تحت الملاحظة)">نشط (تحت الملاحظة)</option>
                                <option value="ملغي / منتهي">ملغي / منتهي</option>
                            </select>

                            <button type="submit" class="sm-btn" style="height: 30px; font-size: 11px; background: #dc2626;">إرسال وتسجيل إنذار موظف</button>
                        </form>

                        <div style="max-height: 120px; overflow-y: auto; font-size: 11px; background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #cbd5e0;">
                            <?php if (empty($warning_notices)): ?>
                                <div style="color:#64748b; text-align: center;">لا يوجد إنذارات مسجلة.</div>
                            <?php else: ?>
                                <?php foreach($warning_notices as $idx => $wn): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding: 4px 0;">
                                        <span><?php echo esc_html($wn['date']); ?>: <?php echo esc_html($wn['subject']); ?></span>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('حذف هذا الإنذار نهائياً؟')">
                                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                                            <input type="hidden" name="eess_hr_action" value="delete_warning">
                                            <input type="hidden" name="delete_index" value="<?php echo $idx; ?>">
                                            <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 10px;">[حذف]</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Box 3: Disciplinary & Admin Actions -->
                <div style="display: flex; flex-direction: column; gap: 20px;">

                    <!-- Disciplinary Records Box -->
                    <div style="background: #fff; border: 1px solid #cbd5e0; padding: 15px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0; font-weight: 800; font-size: 13px; color: #1e293b;">🔨 مجالس الانضباط والقرارات السلوكية</h4>
                        <form method="post" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">
                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                            <input type="hidden" name="eess_hr_action" value="add_disciplinary">

                            <div style="display: flex; gap: 8px;">
                                <input type="date" name="disc_date" class="sm-input" required style="height: 30px; font-size: 11px; flex: 1;">
                                <input type="text" name="disc_incident" placeholder="المخالفة / الواقعة" class="sm-input" required style="height: 30px; font-size: 11px; flex: 2;">
                            </div>
                            <input type="text" name="disc_action" placeholder="القرار السلوكي والجزاء المتخذ" class="sm-input" required style="height: 30px; font-size: 11px;">
                            <input type="text" name="disc_supervisor" placeholder="المشرف المعتمد للقرار" class="sm-input" required style="height: 30px; font-size: 11px;">

                            <button type="submit" class="sm-btn" style="height: 30px; font-size: 11px; background: #e53e3e;">تسجيل قرار مجلس الانضباط</button>
                        </form>

                        <div style="max-height: 120px; overflow-y: auto; font-size: 11px; background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #cbd5e0;">
                            <?php if (empty($disciplinary_records)): ?>
                                <div style="color:#64748b; text-align: center;">لا يوجد سجل مخالفات.</div>
                            <?php else: ?>
                                <?php foreach($disciplinary_records as $idx => $dr): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding: 4px 0;">
                                        <span><?php echo esc_html($dr['date']); ?>: <?php echo esc_html($dr['incident']); ?></span>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('حذف هذا القيد الجزائي؟')">
                                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                                            <input type="hidden" name="eess_hr_action" value="delete_disciplinary">
                                            <input type="hidden" name="delete_index" value="<?php echo $idx; ?>">
                                            <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 10px;">[حذف]</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Official Documents Archiving -->
                    <div style="background: #fff; border: 1px solid #cbd5e0; padding: 15px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0; font-weight: 800; font-size: 13px; color: #1e293b;">📄 أرشفة مستند ثبوتي / وثيقة رسمية</h4>
                        <form method="post" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">
                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                            <input type="hidden" name="eess_hr_action" value="add_document">

                            <div style="display: flex; gap: 8px;">
                                <input type="date" name="doc_date" class="sm-input" required style="height: 30px; font-size: 11px; flex: 1;">
                                <input type="text" name="doc_name" placeholder="اسم الوثيقة (الهوية، جواز السفر، المؤهل...)" class="sm-input" required style="height: 30px; font-size: 11px; flex: 2;">
                            </div>

                            <!-- Media URL Upload integration -->
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="doc_file_url" name="doc_file_url" placeholder="رابط الملف / الوثيقة" class="sm-input" required style="height: 30px; font-size: 11px; flex: 1;">
                                <button type="button" onclick="smOpenMediaUploader('doc_file_url')" class="sm-btn sm-btn-outline" style="height: 30px; font-size: 11px; padding: 0 10px; width: auto;">رفع</button>
                            </div>

                            <button type="submit" class="sm-btn" style="height: 30px; font-size: 11px; background: #000000;">حفظ وأرشفة الوثيقة بالملف</button>
                        </form>

                        <div style="max-height: 120px; overflow-y: auto; font-size: 11px; background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #cbd5e0;">
                            <?php if (empty($hr_documents)): ?>
                                <div style="color:#64748b; text-align: center;">لا يوجد وثائق مرفوعة.</div>
                            <?php else: ?>
                                <?php foreach($hr_documents as $idx => $doc): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding: 4px 0;">
                                        <a href="<?php echo esc_url($doc['file_url']); ?>" target="_blank" style="color: var(--sm-primary-color); font-weight: bold; text-decoration: underline;"><?php echo esc_html($doc['name']); ?></a>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('حذف هذا المستند نهائياً؟')">
                                            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
                                            <input type="hidden" name="target_employee_id" value="<?php echo $emp_id; ?>">
                                            <input type="hidden" name="eess_hr_action" value="delete_document">
                                            <input type="hidden" name="delete_index" value="<?php echo $idx; ?>">
                                            <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 10px;">[حذف]</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Print & Platform Access Modals -->
<script>
function eessPrintEmployeeReport(empId) {
    const url = window.location.href + '&eess_print_report=1&employee_id=' + empId;
    const printWindow = window.open(url, '_blank', 'width=900,height=800,scrollbars=yes');
    if (printWindow) {
        printWindow.focus();
    }
}

function eessOpenRestrictModal(empId, empName) {
    document.getElementById('restrict_target_id').value = empId;
    document.getElementById('restrict_emp_name_lbl').innerText = empName;
    document.getElementById('eessRestrictModal').style.display = 'flex';
}

function eessCloseRestrictModal() {
    document.getElementById('eessRestrictModal').style.display = 'none';
}

function eessOpenUnrestrictModal(empId, empName) {
    document.getElementById('unrestrict_target_id').value = empId;
    document.getElementById('unrestrict_emp_name_lbl').innerText = empName;
    document.getElementById('eessUnrestrictModal').style.display = 'flex';
}

function eessCloseUnrestrictModal() {
    document.getElementById('eessUnrestrictModal').style.display = 'none';
}
</script>

<!-- Platform Restriction Reason Selection Modal -->
<div id="eessRestrictModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px); direction: rtl;">
    <div style="background: #fff; width: 100%; max-width: 450px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; font-family: 'Cairo', sans-serif;">
        <div style="background: #dc2626; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1rem; font-weight: 800;">🚫 تقييد وصول موظف للمنصة</h3>
            <button type="button" onclick="eessCloseRestrictModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 20px; margin:0;">
            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
            <input type="hidden" name="eess_hr_action" value="restrict_platform_access">
            <input type="hidden" name="target_employee_id" id="restrict_target_id" value="">

            <p style="font-size: 13px; color: #475569; margin: 0 0 15px 0;">أنت على وشك حظر وتقييد دخول الموظف <strong id="restrict_emp_name_lbl" style="color:#1e293b;"></strong> إلى المنصة الرقمية. يرجى اختيار سبب التقييد المعتمد:</p>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 5px;">سبب تقييد الوصول للمنصة *</label>
                <select name="restriction_reason" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Cairo'; font-size: 13px; height: 40px;">
                    <option value="">-- اختر سبب الإيقاف --</option>
                    <option value="إيقاف تأديبي مؤقت">إيقاف تأديبي مؤقت</option>
                    <option value="انتهاء التعاقد وفترة العمل">انتهاء التعاقد وفترة العمل</option>
                    <option value="إجازة غير مدفوعة الأجر">إجازة غير مدفوعة الأجر</option>
                    <option value="دواعي تقنية وأمن المعلومات">دواعي تقنية وأمن المعلومات</option>
                    <option value="تغيير المسمى الوظيفي">تغيير المسمى الوظيفي</option>
                    <option value="أسباب أخرى مسببة">أسباب أخرى مسببة</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                <button type="button" onclick="eessCloseRestrictModal()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 15px; border-radius:6px; font-weight:700; cursor:pointer; font-family:'Cairo';">إلغاء</button>
                <button type="submit" style="background:#dc2626; color:white; border:none; padding:8px 20px; border-radius:6px; font-weight:700; cursor:pointer; font-family:'Cairo';">تأكيد وتقييد الدخول</button>
            </div>
        </form>
    </div>
</div>

<!-- Platform Unrestriction Confirmation Modal -->
<div id="eessUnrestrictModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px); direction: rtl;">
    <div style="background: #fff; width: 100%; max-width: 450px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; font-family: 'Cairo', sans-serif;">
        <div style="background: #16a34a; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1rem; font-weight: 800;">🔓 تفعيل وإلغاء تقييد موظف</h3>
            <button type="button" onclick="eessCloseUnrestrictModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 20px; margin:0;">
            <?php wp_nonce_field('eess_hr_action_nonce', 'eess_hr_nonce'); ?>
            <input type="hidden" name="eess_hr_action" value="remove_platform_restriction">
            <input type="hidden" name="target_employee_id" id="unrestrict_target_id" value="">

            <p style="font-size: 13px; color: #475569; margin: 0 0 20px 0;">هل أنت متأكد من رغبتك في إلغاء التقييد وتفعيل حساب الموظف <strong id="unrestrict_emp_name_lbl" style="color:#1e293b;"></strong> وتمكينه من تسجيل الدخول واستخدام كافة ميزات المنصة مجدداً؟</p>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                <button type="button" onclick="eessCloseUnrestrictModal()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 15px; border-radius:6px; font-weight:700; cursor:pointer; font-family:'Cairo';">إلغاء</button>
                <button type="submit" style="background:#16a34a; color:white; border:none; padding:8px 20px; border-radius:6px; font-weight:700; cursor:pointer; font-family:'Cairo';">تفعيل وإلغاء التقييد</button>
            </div>
        </form>
    </div>
</div>
