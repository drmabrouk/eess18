<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

// Decide target user
$target_user_id = $current_user->ID;
if (($is_admin || $is_sys_admin || $is_hr) && isset($_GET['employee_id'])) {
    $target_user_id = intval($_GET['employee_id']);
}

$u = get_userdata($target_user_id);
if (!$u) {
    echo '<div class="error" style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700;">خطأ: لم يتم العثور على الموظف المطلوب.</div>';
    return;
}

// Security: employee can only see own profile
if ($target_user_id !== $current_user->ID && !($is_admin || $is_sys_admin || $is_hr)) {
    $target_user_id = $current_user->ID;
    $u = $current_user;
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

// Fetch HR fields from metadata
$emp_num = get_user_meta($target_user_id, 'eess_employee_number', true) ?: 'غير محدد';
$school_name = get_user_meta($target_user_id, 'eess_school_name', true) ?: 'خدمات الأنظمة الإلكترونية التعليمية (EESS)';
$dept = get_user_meta($target_user_id, 'eess_department', true) ?: 'غير محدد';
$specialization = get_user_meta($target_user_id, 'sm_specialization', true) ?: 'غير محدد';
$employment_date = get_user_meta($target_user_id, 'eess_hr_employment_date', true) ?: 'غير محدد';
$employment_status = get_user_meta($target_user_id, 'eess_hr_employment_status', true) ?: 'active';
$phone = get_user_meta($target_user_id, 'sm_phone', true) ?: 'غير محدد';

// Fetch lists from metadata
$salary_records = get_user_meta($target_user_id, 'eess_hr_salary_records', true) ?: array();
if (!is_array($salary_records)) $salary_records = json_decode($salary_records, true) ?: array();

$disciplinary_records = get_user_meta($target_user_id, 'eess_hr_disciplinary_records', true) ?: array();
if (!is_array($disciplinary_records)) $disciplinary_records = json_decode($disciplinary_records, true) ?: array();

$warning_notices = get_user_meta($target_user_id, 'eess_hr_warning_notices', true) ?: array();
if (!is_array($warning_notices)) $warning_notices = json_decode($warning_notices, true) ?: array();

$admin_actions = get_user_meta($target_user_id, 'eess_hr_admin_actions', true) ?: array();
if (!is_array($admin_actions)) $admin_actions = json_decode($admin_actions, true) ?: array();

$hr_documents = get_user_meta($target_user_id, 'eess_hr_documents', true) ?: array();
if (!is_array($hr_documents)) $hr_documents = json_decode($hr_documents, true) ?: array();

$employment_history = get_user_meta($target_user_id, 'eess_hr_employment_history', true) ?: array();
if (!is_array($employment_history)) $employment_history = json_decode($employment_history, true) ?: array();
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Top Card Header -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: var(--sm-shadow);">
        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <!-- Profile Photo -->
            <div style="position: relative;">
                <?php echo get_avatar($target_user_id, 100, '', '', array('style' => 'width: 100px; height: 100px; border-radius: 50% !important; border: 4px solid var(--sm-primary-color); object-fit: cover; display: block;')); ?>
            </div>

            <div style="flex: 1; min-width: 250px;">
                <h2 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 1.5em;"><?php echo esc_html($u->display_name); ?></h2>
                <p style="margin: 5px 0; font-size: 13px; color: #64748b; font-family: monospace;">@<?php echo esc_html($u->user_login); ?> | <?php echo esc_html($u->user_email); ?></p>
                <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                    <span style="background: #e2e8f0; color: #334155; padding: 3px 12px; border-radius: 50px; font-size: 11px; font-weight: 700;">
                        <?php echo $role_map[$u->roles[0]] ?? $u->roles[0]; ?>
                    </span>
                    <span style="background: #fee2e2; color: #991b1b; padding: 3px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; border: 1px solid #fca5a5;">
                        رقم الموظف: <?php echo esc_html($emp_num); ?>
                    </span>
                    <?php if ($employment_status === 'active'): ?>
                        <span style="background: #dcfce7; color: #15803d; padding: 3px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0;">نشط بالخدمة</span>
                    <?php else: ?>
                        <span style="background: #f1f5f9; color: #475569; padding: 3px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1;">غير نشط / إجازة</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal & Employment Information Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
        <!-- Card 1: Personal Info -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow);">
            <h4 style="margin: 0 0 15px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📋 البيانات الشخصية والمهنية</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: right;">
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;">الاسم بالكامل:</td><td style="padding: 8px 0; font-weight: 700;"><?php echo esc_html($u->display_name); ?></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;">البريد الإلكتروني:</td><td style="padding: 8px 0; font-family: monospace; font-weight: 700;"><?php echo esc_html($u->user_email); ?></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;">رقم الهاتف:</td><td style="padding: 8px 0; font-family: monospace; font-weight: 700;"><?php echo esc_html($phone); ?></td></tr>
                <tr><td style="padding: 8px 0; color: #64748b;">المؤسسة التعليمية:</td><td style="padding: 8px 0; font-weight: 700;"><?php echo esc_html($school_name); ?></td></tr>
            </table>
        </div>

        <!-- Card 2: Employment Info -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow);">
            <h4 style="margin: 0 0 15px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">💼 السجل الوظيفي والتعيين</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: right;">
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;">المسمى الوظيفي:</td><td style="padding: 8px 0; font-weight: 700;"><?php echo $role_map[$u->roles[0]] ?? $u->roles[0]; ?></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;">القسم / الإدارة:</td><td style="padding: 8px 0; font-weight: 700;"><?php echo esc_html($dept); ?></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;">التخصص / المادة:</td><td style="padding: 8px 0; font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($specialization); ?></td></tr>
                <tr><td style="padding: 8px 0; color: #64748b;">تاريخ المباشرة بالعمل:</td><td style="padding: 8px 0; font-weight: 700;"><?php echo esc_html($employment_date); ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Supplementary Tabs for Financials, Violations, Docs -->
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow); padding: 25px; margin-bottom: 25px;">
        <!-- Tab Controls -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; overflow-x: auto; white-space: nowrap;">
            <button onclick="switchHrSection('hr-salaries', this)" class="sm-tab-btn sm-active">📊 سجلات الرواتب والمالية</button>
            <button onclick="switchHrSection('hr-disciplinary', this)" class="sm-tab-btn">⚠️ الإنذارات والمخالفات السلوكية</button>
            <button onclick="switchHrSection('hr-actions', this)" class="sm-tab-btn">📁 الإجراءات والقرارات الإدارية</button>
            <button onclick="switchHrSection('hr-history', this)" class="sm-tab-btn">⏳ السجل التاريخي والخبرات</button>
            <button onclick="switchHrSection('hr-docs', this)" class="sm-tab-btn">📄 الوثائق والملفات الرسمية</button>
        </div>

        <!-- Section 1: Salary Records -->
        <div id="hr-salaries" class="hr-section-panel" style="display: block;">
            <h5 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; color: #1e293b;">سجل الرواتب والتعويضات المالية</h5>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>الشهر / التاريخ</th>
                            <th>الراتب الأساسي</th>
                            <th>بدل السكن</th>
                            <th>بدل الانتقال</th>
                            <th>الاستقطاعات والخصومات</th>
                            <th>صافي الراتب المستلم</th>
                            <th>ملاحظات الصرف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salary_records)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">لا يوجد سجل رواتب متوفر حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach ($salary_records as $rec): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($rec['date']); ?></td>
                                    <td><?php echo number_format($rec['basic'] ?? 0, 2); ?> د.إ</td>
                                    <td><?php echo number_format($rec['housing'] ?? 0, 2); ?> د.إ</td>
                                    <td><?php echo number_format($rec['transport'] ?? 0, 2); ?> د.إ</td>
                                    <td style="color: #b91c1c; font-weight: bold;">-<?php echo number_format($rec['deductions'] ?? 0, 2); ?> د.إ</td>
                                    <td style="color: #15803d; font-weight: bold;"><?php echo number_format($rec['net'] ?? 0, 2); ?> د.إ</td>
                                    <td style="font-style: italic; font-size: 12px;"><?php echo esc_html($rec['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Disciplinary & Warnings -->
        <div id="hr-disciplinary" class="hr-section-panel" style="display: none;">
            <h5 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; color: #1e293b;">الإنذارات والملاحظات السلوكية المسجلة</h5>
            <div class="sm-table-container" style="margin-bottom: 25px;">
                <table class="sm-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الإنذار / الإشعار</th>
                            <th>التفاصيل والمحضر</th>
                            <th>حالة الإنذار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($warning_notices)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">لا يوجد أي إنذارات مسجلة بحق الموظف.</td></tr>
                        <?php else: ?>
                            <?php foreach ($warning_notices as $warn): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($warn['date']); ?></td>
                                    <td style="font-weight: 700; color: #b91c1c;"><?php echo esc_html($warn['subject']); ?></td>
                                    <td style="font-size: 12px; color: #4b5563;"><?php echo esc_html($warn['details']); ?></td>
                                    <td>
                                        <span style="display:inline-block; padding: 2px 8px; font-size: 10px; border-radius: 4px; font-weight: bold; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;">
                                            <?php echo esc_html($warn['status'] ?? 'نشط'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h5 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; color: #1e293b;">سجلات مجالس الانضباط والقرارات السلوكية</h5>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المخالفة / الواقعة</th>
                            <th>الإجراء والقرار المتخذ</th>
                            <th>المسؤول المعتمد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disciplinary_records)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">لا يوجد سجل مخالفات انضباطية.</td></tr>
                        <?php else: ?>
                            <?php foreach ($disciplinary_records as $disc): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($disc['date']); ?></td>
                                    <td><?php echo esc_html($disc['incident']); ?></td>
                                    <td style="color: #b91c1c; font-weight: bold;"><?php echo esc_html($disc['action']); ?></td>
                                    <td><?php echo esc_html($disc['supervisor']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Administrative Actions -->
        <div id="hr-actions" class="hr-section-panel" style="display: none;">
            <h5 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; color: #1e293b;">القرارات الإدارية والتوجيهات الرسمية</h5>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>القرار الإداري</th>
                            <th>تفاصيل وملاحظات القرار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admin_actions)): ?>
                            <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">لا يوجد سجل قرارات إدارية مرصودة.</td></tr>
                        <?php else: ?>
                            <?php foreach ($admin_actions as $act): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($act['date']); ?></td>
                                    <td style="font-weight: 700;"><?php echo esc_html($act['action']); ?></td>
                                    <td style="font-size: 12px; color: #475569;"><?php echo esc_html($act['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 4: History -->
        <div id="hr-history" class="hr-section-panel" style="display: none;">
            <h5 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; color: #1e293b;">السجل التاريخي للترقيات والتطور الوظيفي</h5>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المنصب / الإجراء</th>
                            <th>المؤسسة / المدرسة والجهة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employment_history)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">لا يوجد سجل تاريخي وظيفي مسجل حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach ($employment_history as $hist): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($hist['date']); ?></td>
                                    <td style="font-weight: 700;"><?php echo esc_html($hist['role']); ?></td>
                                    <td><?php echo esc_html($hist['organization']); ?></td>
                                    <td style="font-size: 12px;"><?php echo esc_html($hist['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 5: Official Documents -->
        <div id="hr-docs" class="hr-section-panel" style="display: none;">
            <h5 style="margin: 0 0 15px 0; font-weight: 800; font-size: 14px; color: #1e293b;">الوثائق الثبوتية والشهادات الرسمية المؤرشفة</h5>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>تاريخ الأرشفة</th>
                            <th>اسم المستند / الوثيقة</th>
                            <th>الملف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hr_documents)): ?>
                            <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">لا يوجد وثائق رسمية مرفوعة.</td></tr>
                        <?php else: ?>
                            <?php foreach ($hr_documents as $doc): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($doc['date']); ?></td>
                                    <td style="font-weight: 700;"><?php echo esc_html($doc['name']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($doc['file_url']); ?>" target="_blank" class="sm-btn" style="padding: 4px 10px; font-size: 11px; height: 26px; width: auto; background: var(--sm-secondary-color); text-decoration: none; color: white !important;">
                                            📥 تحميل واستعراض
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
// Section switching inside HR Affairs
function switchHrSection(secId, btn) {
    document.querySelectorAll('.hr-section-panel').forEach(p => p.style.display = 'none');
    const panel = document.getElementById(secId);
    if (panel) panel.style.display = 'block';

    btn.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
    btn.classList.add('sm-active');
}
</script>
