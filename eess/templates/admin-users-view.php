<?php if (!defined('ABSPATH')) exit;

// Fetch pending approval registration requests
$pending_users = get_users(array(
    'meta_key'   => 'eess_approval_status',
    'meta_value' => 'pending'
));
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <!-- Title Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 22px;">إدارة مستخدمي النظام</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">إدارة صلاحيات الموظفين والمعلمين، مراجعة طلبات التسجيل، والاستيراد الجماعي</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="document.getElementById('user-csv-import-box').style.display = document.getElementById('user-csv-import-box').style.display === 'none' ? 'block' : 'none'" class="sm-btn sm-btn-outline" style="height: 40px; font-size: 12px; font-weight: 700; background: #fff;">
                <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> استيراد مستخدمين (CSV)
            </button>
            <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_users_csv&nonce=' . wp_create_nonce('eess_admin_action')); ?>" class="sm-btn sm-btn-outline" style="height: 40px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; background: #fff;">
                <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> تصدير مستخدمين (CSV)
            </a>
            <div class="sm-dropdown" style="position: relative;">
                <button class="sm-btn sm-btn-outline" style="height: 40px; font-size: 12px; font-weight: 700; background: #fff;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                    <span class="dashicons dashicons-filter" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> تصفية حسب الرتبة
                </button>
                <div style="display: none; position: absolute; top: 105%; right: 0; background: white; border: 1px solid var(--sm-border-color); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 100; min-width: 180px; padding: 5px 0;">
                    <a href="<?php echo remove_query_arg('role_filter'); ?>" class="sm-dropdown-item">الكل</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_student'); ?>" class="sm-dropdown-item">الطلاب</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_teacher'); ?>" class="sm-dropdown-item">المعلمون</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_coordinator'); ?>" class="sm-dropdown-item">منسقو المواد</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_supervisor'); ?>" class="sm-dropdown-item">المشرفون</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_principal'); ?>" class="sm-dropdown-item">مديرو المدارس</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_clinic'); ?>" class="sm-dropdown-item">موظفو العيادة</a>
                    <a href="<?php echo add_query_arg('role_filter', 'sm_system_admin'); ?>" class="sm-dropdown-item">مديرو النظام</a>
                </div>
            </div>
            <button onclick="document.getElementById('add-user-modal').style.display='flex'" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700;">
                <span class="dashicons dashicons-plus-alt" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> إضافة مستخدم جديد
            </button>
        </div>
    </div>

    <!-- Pending Approval Requests Block -->
    <?php if (!empty($pending_users)): ?>
    <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 24px; border-radius: 12px; margin-bottom: 30px; box-shadow: var(--sm-shadow);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <span class="dashicons dashicons-warning" style="color: #b45309; font-size: 22px; width: 22px; height: 22px;"></span>
            <h4 style="margin: 0; color: #b45309; font-weight: 800; font-size: 15px;">طلبات التسجيل الجديدة بانتظار الاعتماد والمراجعة (<?php echo count($pending_users); ?>)</h4>
        </div>
        <p style="margin: 0 0 20px 0; font-size: 12px; color: #92400e; font-weight: 600; line-height: 1.6;">تم تقديم طلبات التسجيل التالية ذاتياً من قبل المعلمين والموظفين بالمنصة. يرجى مراجعة البيانات واعتماد تفعيل الحساب أو رفضه وإلغائه.</p>

        <div class="sm-table-container" style="overflow-x: auto; background: white; margin-bottom: 0;">
            <table class="sm-table" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="font-weight: 700;">البريد الإلكتروني</th>
                        <th style="font-weight: 700;">رقم الموظف</th>
                        <th style="font-weight: 700;">المسمى المطلوب</th>
                        <th style="font-weight: 700;">المدرسة التابع لها</th>
                        <th style="font-weight: 700;">تاريخ الطلب</th>
                        <th style="text-align: left; padding-left: 20px; font-weight: 700;">الإجراءات الإدارية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_users as $pu):
                        $emp_num = get_user_meta($pu->ID, 'eess_employee_number', true);
                        $school = get_user_meta($pu->ID, 'eess_school_name', true);
                        $role_label = 'مستخدم';
                        if (in_array('sm_teacher', (array)$pu->roles)) $role_label = 'معلم';
                        elseif (in_array('sm_coordinator', (array)$pu->roles)) $role_label = 'منسق مادة';
                        elseif (in_array('sm_supervisor', (array)$pu->roles)) $role_label = 'مشرف تربوي';
                        elseif (in_array('sm_clinic', (array)$pu->roles)) $role_label = 'ممرض عيادة';
                    ?>
                    <tr id="pending-user-row-<?php echo $pu->ID; ?>">
                        <td style="font-weight: 700; color: var(--sm-dark-color);"><?php echo esc_html($pu->user_email); ?></td>
                        <td><span style="font-weight: 800; font-family: monospace; color: var(--sm-secondary-color);"><?php echo esc_html($emp_num); ?></span></td>
                        <td><span class="sm-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"><?php echo $role_label; ?></span></td>
                        <td style="font-weight: 600;"><?php echo esc_html($school); ?></td>
                        <td style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700;"><?php echo date_i18n('Y-m-d H:i', strtotime($pu->user_registered)); ?></td>
                        <td style="text-align: left; padding-left: 20px;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <button type="button" onclick="eessApproveUser(<?php echo $pu->ID; ?>)" class="sm-btn" style="height: 30px; padding: 0 12px; font-size: 11px; font-weight: 700; background: #10b981;">✓ اعتماد وتفعيل الحساب</button>
                                <button type="button" onclick="eessRejectUser(<?php echo $pu->ID; ?>)" class="sm-btn" style="height: 30px; padding: 0 12px; font-size: 11px; font-weight: 700; background-color: #ef4444 !important;">✗ رفض وإلغاء</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function eessApproveUser(userId) {
        if (!confirm('هل أنت متأكد من رغبتك في اعتماد وتنشيط حساب هذا الموظف؟')) return;

        const data = new FormData();
        data.append('action', 'eess_approve_user');
        data.append('user_id', userId);
        data.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم اعتماد وتفعيل الحساب بنجاح وإرسال إشعار للمستخدم.');
                const row = document.getElementById('pending-user-row-' + userId);
                if (row) row.remove();
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                smShowNotification('فشل الاعتماد: ' + res.data, true);
            }
        });
    }

    function eessRejectUser(userId) {
        if (!confirm('هل أنت متأكد من رفض طلب هذا المستخدم وحذف حسابه المعلق نهائياً؟')) return;

        const data = new FormData();
        data.append('action', 'eess_reject_user');
        data.append('user_id', userId);
        data.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم رفض طلب التسجيل وحذف الحساب بنجاح.');
                const row = document.getElementById('pending-user-row-' + userId);
                if (row) row.remove();
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                smShowNotification('فشل الرفض: ' + res.data, true);
            }
        });
    }
    </script>
    <?php endif; ?>

    <!-- User CSV Import Drawer -->
    <div id="user-csv-import-box" style="display:none; background: #ffffff; padding: 25px; border: 1px solid var(--sm-border-color); border-radius: 12px; margin-bottom: 24px; box-shadow: var(--sm-shadow);">
        <h4 style="margin-top:0; color: var(--sm-dark-color); font-weight: 800; font-size: 14px;">استيراد المستخدمين الشامل من ملف CSV</h4>
        <p style="font-size:12px; color: var(--sm-text-gray); margin-bottom:15px; line-height:1.6;">يرجى تجهيز ملف CSV الخاص بك بحيث يضم الحقول التالية بالترتيب: <strong>اسم المستخدم، البريد، الاسم الكامل، الدور (مثال: sm_teacher)، الجوال، كلمة المرور، رابط الصورة الشخصية، التخصص</strong>.</p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
            <div style="display:flex; gap:15px; align-items:center;">
                <input type="file" name="csv_file" accept=".csv" required class="sm-input" style="width:auto;">
                <button type="submit" name="sm_import_users_csv" class="sm-btn" style="width:auto; height: 38px;">تأكيد وبدء الاستيراد</button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Toolbar -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; background: var(--sm-bg-light); padding: 12px 20px; border-radius: var(--sm-radius); border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
        <span style="font-size: 13px; font-weight: 700; color: var(--sm-text-gray);">الإجراءات الجماعية:</span>
        <button onclick="bulkDeleteUsers()" class="sm-btn" style="background: #ef4444; font-size: 11px; padding: 6px 14px; width: auto; font-weight: 700;">حذف المستخدمين المحددين</button>
    </div>

    <!-- Unified Users Listing Table -->
    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-users" onclick="toggleAllUsers(this)"></th>
                    <th>اسم المستخدم كاملاً</th>
                    <th>البريد الإلكتروني</th>
                    <th>الرتبة والدور بالمنصة</th>
                    <th>كلمة المرور المؤقتة</th>
                    <th style="text-align: left; padding-left: 20px; width: 160px;">الإجراءات والعمليات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hierarchy = array(
                    'administrator' => 5,
                    'sm_system_admin' => 4,
                    'sm_principal' => 3,
                    'sm_supervisor' => 2,
                    'sm_coordinator' => 1,
                    'sm_teacher' => 0,
                    'sm_student' => -1,
                    'sm_parent' => -2
                );
                $current_user = wp_get_current_user();
                $current_role = $current_user->roles[0];
                $current_level = $hierarchy[$current_role] ?? -3;

                $all_users = get_users();

                // Filter by role if requested
                if (!empty($_GET['role_filter'])) {
                    $filter_role = sanitize_text_field($_GET['role_filter']);
                    $all_users = array_filter($all_users, function($u) use ($filter_role) {
                        return in_array($filter_role, $u->roles);
                    });
                }

                // Ordering hierarchy: Students, Teachers, Coordinators, Supervisors, Principal, Admin
                $sort_hierarchy = array(
                    'sm_student' => 0,
                    'sm_teacher' => 1,
                    'sm_coordinator' => 2,
                    'sm_supervisor' => 3,
                    'sm_principal' => 4,
                    'sm_system_admin' => 5,
                    'administrator' => 6
                );

                usort($all_users, function($a, $b) use ($sort_hierarchy) {
                    $lvl_a = $sort_hierarchy[$a->roles[0]] ?? 99;
                    $lvl_b = $sort_hierarchy[$b->roles[0]] ?? 99;
                    return $lvl_a <=> $lvl_b;
                });

                foreach ($all_users as $u):
                    $u_role = $u->roles[0];
                    $u_level = $hierarchy[$u_role] ?? -3;

                    // Hierarchical Visibility: Can only see equal or lower
                    if ($u_level > $current_level && !current_user_can('administrator')) continue;
                ?>
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="user-checkbox" value="<?php echo $u->ID; ?>" <?php if($u->ID == get_current_user_id()) echo 'disabled'; ?>></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php echo get_avatar($u->ID, 36, '', '', array('style' => 'border-radius:50%; border: 1px solid var(--sm-border-color);')); ?>
                                <div>
                                    <div style="font-weight: 700; color: var(--sm-dark-color);"><?php echo esc_html($u->display_name); ?></div>
                                    <div style="font-size:10px; color: var(--sm-text-gray); font-weight: 700;">@<?php echo esc_html($u->user_login); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 600;"><?php echo esc_html($u->user_email); ?></td>
                        <td>
                            <div style="font-weight:700; color: var(--sm-primary-color);">
                                <?php
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
                                    'sm_student' => 'طالب',
                                    'sm_parent' => 'ولي أمر'
                                );
                                $u_role_key = $u->roles[0];
                                echo $role_map[$u_role_key] ?? $u_role_key;
                                ?>
                            </div>
                            <?php if ($u_role_key === 'sm_teacher'): ?>
                                <div style="font-size:11px; color: var(--sm-text-gray); font-weight:700;">التخصص: <?php echo esc_html(get_user_meta($u->ID, 'sm_specialization', true) ?: 'غير محدد'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code style="background: var(--sm-bg-light); padding: 3px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; border: 1px solid var(--sm-border-color); font-weight: 700;"><?php echo get_user_meta($u->ID, 'sm_temp_pass', true) ?: '********'; ?></code>
                        </td>
                        <td style="text-align: left; padding-left: 20px;">
                            <div style="display:flex; gap:6px; justify-content: flex-end;">
                                <?php
                                $u_data = array(
                                    "id" => $u->ID,
                                    "name" => $u->display_name,
                                    "email" => $u->user_email,
                                    "login" => $u->user_login,
                                    "role" => $u_role_key,
                                    "photo" => get_user_meta($u->ID, 'eess_profile_photo', true),
                                    "specialization" => get_user_meta($u->ID, 'sm_specialization', true)
                                );
                                ?>
                                <button onclick='editSmGenericUser(<?php echo json_encode($u_data); ?>)' class="sm-btn sm-btn-outline" style="background:#fff; padding:4px 10px; width:auto; font-size:11px; height: 30px;">تعديل</button>
                                <?php if ($u->ID != get_current_user_id()): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا المستخدم نهائياً؟')">
                                        <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                                        <input type="hidden" name="delete_user_id" value="<?php echo $u->ID; ?>">
                                        <button type="submit" name="sm_delete_user" class="sm-btn" style="background:#ef4444; padding:4px 10px; width:auto; font-size:11px; height: 30px;">حذف</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add User Modal -->
    <div id="add-user-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 650px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 16px; font-weight: 800;">إضافة حساب مستخدم جديد</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-user-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-user-form">
                <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px; background: var(--sm-bg-light); padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" class="sm-input" placeholder="مثال: د. خالد السفياني" required>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">اسم المستخدم للولوج:</label>
                        <input type="text" name="user_login" class="sm-input" placeholder="مثال: khaled_teacher" required>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" class="sm-input" placeholder="khaled@eess.online">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الرتبة والوظيفة:</label>
                        <select name="user_role" class="sm-select" onchange="toggleSpecialization(this)">
                            <option value="sm_teacher">معلم</option>
                            <option value="sm_coordinator">منسق مادة</option>
                            <option value="sm_supervisor">مشرف تربوي</option>
                            <option value="sm_principal">مدير المدرسة</option>
                            <option value="sm_system_admin">مدير النظام</option>
                            <option value="sm_discipline_supervisor">مشرف سلوك / انضباط</option>
                            <option value="sm_activities_supervisor">مشرف أنشطة</option>
                            <option value="sm_transportation_supervisor">مشرف نقل ومواصلات</option>
                            <option value="sm_bus_supervisor">مشرف حافلة</option>
                            <option value="sm_clinic">العيادة المدرسية</option>
                            <option value="sm_parent">ولي أمر</option>
                            <option value="sm_student">طالب</option>
                        </select>
                    </div>
                    <div class="sm-form-group spec-group" style="display:none; margin-bottom: 10px;">
                        <label class="sm-label">المادة التخصصية المعتمدة:</label>
                        <select name="specialization" class="sm-select">
                            <option value="">-- اختر مادة التخصص --</option>
                            <?php
                            $subjects = SM_DB::get_subjects();
                            $unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $subjects));
                            foreach($unique_subjects as $sub_name) echo '<option value="'.$sub_name.'">'.$sub_name.'</option>';
                            ?>
                        </select>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">كلمة المرور للولوج:</label>
                        <input type="password" name="user_pass" class="sm-input" placeholder="********" required>
                    </div>
                    <div class="sm-form-group" style="grid-column: span 2; margin-bottom: 5px;">
                        <label class="sm-label">الصورة الشخصية (الملف الشخصي):</label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <input type="file" name="profile_photo" class="sm-input" accept="image/*" onchange="previewProfilePhoto(this, 'add')">
                            <button type="button" class="sm-btn sm-btn-outline" style="width: auto; background:#ef4444; color:white !important; display:none; height: 36px;" id="add_remove_photo_btn" onclick="removeSelectedPhoto('add')">حذف</button>
                        </div>
                        <img id="add_photo_preview" style="display:none; width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-top: 10px; border: 1.5px solid var(--sm-primary-color);">
                    </div>
                </div>
                <button type="submit" class="sm-btn" style="margin-top:20px; width: 100%;">إنشاء حساب مستخدم جديد</button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 650px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 16px; font-weight: 800;">تعديل بيانات المستخدم</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-user-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-user-form">
                <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                <input type="hidden" name="edit_user_id" id="edit_u_id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px; background: var(--sm-bg-light); padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" id="edit_u_name" class="sm-input" required>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" id="edit_u_email" class="sm-input">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الرتبة والدور:</label>
                        <select name="user_role" id="edit_u_role" class="sm-select" onchange="toggleSpecialization(this, 'edit')">
                            <option value="sm_teacher">معلم</option>
                            <option value="sm_coordinator">منسق مادة</option>
                            <option value="sm_supervisor">مشرف تربوي</option>
                            <option value="sm_principal">مدير المدرسة</option>
                            <option value="sm_system_admin">مدير النظام</option>
                            <option value="sm_discipline_supervisor">مشرف سلوك / انضباط</option>
                            <option value="sm_activities_supervisor">مشرف أنشطة</option>
                            <option value="sm_transportation_supervisor">مشرف نقل ومواصلات</option>
                            <option value="sm_bus_supervisor">مشرف حافلة</option>
                            <option value="sm_clinic">العيادة المدرسية</option>
                            <option value="sm_parent">ولي أمر</option>
                            <option value="sm_student">طالب</option>
                        </select>
                    </div>
                    <div class="sm-form-group spec-group" id="edit_spec_group" style="display:none; margin-bottom: 10px;">
                        <label class="sm-label">المادة التخصصية المعتمدة:</label>
                        <select name="specialization" id="edit_u_spec" class="sm-select">
                            <option value="">-- اختر المادة --</option>
                            <?php
                            foreach($unique_subjects as $sub_name) echo '<option value="'.$sub_name.'">'.$sub_name.'</option>';
                            ?>
                        </select>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">كلمة مرور جديدة (اختياري):</label>
                        <input type="password" name="user_pass" class="sm-input" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                    <div class="sm-form-group" style="grid-column: span 2; margin-bottom: 5px;">
                        <label class="sm-label">الصورة الشخصية (الملف الشخصي):</label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <input type="file" name="profile_photo" class="sm-input" accept="image/*" onchange="previewProfilePhoto(this, 'edit')">
                            <button type="button" class="sm-btn sm-btn-outline" style="width: auto; background:#ef4444; color:white !important; height: 36px;" id="edit_remove_photo_btn" onclick="removeSelectedPhoto('edit')">حذف الصورة</button>
                            <input type="hidden" name="delete_photo_flag" id="edit_delete_photo_flag" value="0">
                        </div>
                        <img id="edit_photo_preview" style="display:none; width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-top: 10px; border: 1.5px solid var(--sm-primary-color);">
                    </div>
                </div>
                <button type="submit" class="sm-btn" style="margin-top:20px; width: 100%;">حفظ تعديلات الحساب</button>
            </form>
        </div>
    </div>

</div>

<script>
(function() {
    window.previewProfilePhoto = function(input, mode) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(mode + '_photo_preview');
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                const btn = document.getElementById(mode + '_remove_photo_btn');
                if (btn) btn.style.display = 'inline-flex';
            };
            reader.readAsDataURL(file);
        }
    };

    window.removeSelectedPhoto = function(mode) {
        const input = document.querySelector(`#${mode}-user-form input[type="file"]`);
        if (input) input.value = '';
        const img = document.getElementById(mode + '_photo_preview');
        if (img) {
            img.src = '';
            img.style.display = 'none';
        }
        const btn = document.getElementById(mode + '_remove_photo_btn');
        if (btn) btn.style.display = 'none';

        if (mode === 'edit') {
            const flag = document.getElementById('edit_delete_photo_flag');
            if (flag) flag.value = '1';
        }
    };

    window.toggleSpecialization = function(select, mode = 'add') {
        const group = mode === 'add' ? select.closest('form').querySelector('.spec-group') : document.getElementById('edit_spec_group');
        if (group) {
            if (select.value === 'sm_teacher' || select.value === 'sm_coordinator') {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        }
    };

    window.editSmGenericUser = function(u) {
        document.getElementById('edit_u_id').value = u.id;
        document.getElementById('edit_u_name').value = u.name;
        document.getElementById('edit_u_email').value = u.email;
        document.getElementById('edit_u_role').value = u.role;
        document.getElementById('edit_u_spec').value = u.specialization || '';
        document.getElementById('edit_delete_photo_flag').value = '0';

        const img = document.getElementById('edit_photo_preview');
        const btn = document.getElementById('edit_remove_photo_btn');
        if (u.photo) {
            img.src = u.photo;
            img.style.display = 'block';
            btn.style.display = 'inline-flex';
        } else {
            img.src = '';
            img.style.display = 'none';
            btn.style.display = 'none';
        }

        toggleSpecialization(document.getElementById('edit_u_role'), 'edit');
        document.getElementById('edit-user-modal').style.display = 'flex';
    };

    const addForm = document.getElementById('add-user-form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_add_user_ajax');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تمت إضافة المستخدم وتنشيط حسابه بنجاح');
                    setTimeout(() => location.reload(), 500);
                } else {
                    smShowNotification('خطأ: ' + res.data, true);
                }
            });
        });
    }

    const editForm = document.getElementById('edit-user-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_update_generic_user_ajax');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تم تحديث بيانات المستخدم بنجاح');
                    setTimeout(() => location.reload(), 500);
                } else {
                    smShowNotification('خطأ: ' + res.data, true);
                }
            });
        });
    }

    window.toggleAllUsers = function(master) {
        document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(cb => cb.checked = master.checked);
    };

    window.bulkDeleteUsers = function() {
        const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) { alert('يرجى اختيار مستخدمين أولاً'); return; }
        if (!confirm(`هل أنت متأكد من حذف ${selected.length} مستخدم نهائياً؟`)) return;

        const formData = new FormData();
        formData.append('action', 'sm_bulk_delete_users_ajax');
        formData.append('user_ids', selected.join(','));
        formData.append('nonce', '<?php echo wp_create_nonce("sm_teacher_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification(`تم حذف ${selected.length} مستخدم بنجاح`);
                setTimeout(() => location.reload(), 500);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };
})();
</script>
