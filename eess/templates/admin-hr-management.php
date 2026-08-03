<?php
if (!defined('ABSPATH')) exit;

$roles = (array) wp_get_current_user()->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

$all_subjects = SM_DB::get_subjects();
$unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $all_subjects));

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
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 1.5rem;">إدارة شؤون الموظفين والموارد البشرية</h2>
                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #64748b;">إدارة شاملة لملفات العاملين، الرواتب، الترقيات، المستندات الرسمية والانضباط.</p>
                </div>
            </div>

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

            <!-- Employees Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;" id="hr-employees-grid">
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
                         style="background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s;"
                    >
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <?php echo get_avatar($emp->ID, 45, '', '', array('style' => 'border-radius: 50% !important; border: 2px solid var(--sm-primary-color); width: 45px; height: 45px; object-fit: cover;')); ?>
                            <div>
                                <h4 style="margin: 0; font-weight: 800; font-size: 13px; color: #1e293b;"><?php echo esc_html($emp->display_name); ?></h4>
                                <span style="font-size: 10px; color: #64748b; font-weight: 700;"><?php echo $role_map[$emp_role] ?? $emp_role; ?></span>
                            </div>
                        </div>

                        <div style="font-size: 12px; color: #475569; border-top: 1px solid #e2e8f0; padding-top: 8px;">
                            <div><strong>رقم الموظف:</strong> <span style="font-family: monospace; font-weight: bold;"><?php echo esc_html($emp_num); ?></span></div>
                            <div><strong>القسم:</strong> <?php echo esc_html($emp_dept); ?></div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 5px;">
                            <?php if ($emp_status === 'active'): ?>
                                <span style="display:inline-block; padding: 1px 7px; font-size: 9px; font-weight: bold; background: #dcfce7; color: #15803d; border-radius: 4px;">نشط</span>
                            <?php else: ?>
                                <span style="display:inline-block; padding: 1px 7px; font-size: 9px; font-weight: bold; background: #f1f5f9; color: #475569; border-radius: 4px;">غير نشط</span>
                            <?php endif; ?>
                            <a href="<?php echo add_query_arg('manage_employee_id', $emp->ID); ?>" class="sm-btn" style="padding: 4px 10px; font-size: 10px; height: 26px; width: auto; background: var(--sm-primary-color); text-decoration: none; color: white !important;">⚙️ إدارة الملف</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
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
