<?php if (!defined('ABSPATH')) exit; ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h3 style="margin:0; border:none; padding:0;">إدارة مستخدمي النظام</h3>
    <div style="display:flex; gap:10px;">
        <div class="sm-dropdown" style="position: relative;">
            <button class="sm-btn sm-btn-outline" style="width:auto; font-size:12px;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">تصفية حسب الرتبة <span class="dashicons dashicons-filter"></span></button>
            <div style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; min-width: 180px; margin-top: 5px;">
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
        <button onclick="document.getElementById('add-user-modal').style.display='flex'" class="sm-btn" style="width:auto;">+ إضافة مستخدم جديد</button>
    </div>
</div>

<div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center; background: #f8fafc; padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
    <span style="font-size: 13px; font-weight: 700; color: #4a5568;">الإجراءات الجماعية:</span>
    <button onclick="bulkDeleteUsers()" class="sm-btn" style="background: #e53e3e; font-size: 11px; padding: 5px 15px; width: auto;">حذف المستخدمين المحددين</button>
</div>

<div class="sm-table-container">
    <table class="sm-table">
        <thead>
            <tr>
                <th style="width: 40px;"><input type="checkbox" id="select-all-users" onclick="toggleAllUsers(this)"></th>
                <th>المستخدم</th>
                <th>البريد الإلكتروني</th>
                <th>الرتبة</th>
                <th>كلمة المرور</th>
                <th>الإجراءات</th>
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
                    <td><input type="checkbox" class="user-checkbox" value="<?php echo $u->ID; ?>" <?php if($u->ID == get_current_user_id()) echo 'disabled'; ?>></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?php echo get_avatar($u->ID, 32, '', '', array('style' => 'border-radius:50%;')); ?>
                            <div>
                                <div style="font-weight: 700;"><?php echo esc_html($u->display_name); ?></div>
                                <div style="font-size:10px; color:#a0aec0;">@<?php echo esc_html($u->user_login); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo esc_html($u->user_email); ?></td>
                    <td>
                        <div style="font-weight:700;">
                            <?php
                            $role_map = array(
                                'administrator' => 'الإدارة المركزية (المطور)',
                                'sm_system_admin' => 'مدير النظام التقني',
                                'sm_principal' => 'مدير المدرسة',
                                'sm_supervisor' => 'مشرف تربوي',
                                'sm_coordinator' => 'منسق مادة',
                                'sm_teacher' => 'معلم',
                                'sm_clinic' => 'العيادة المدرسية',
                                'sm_student' => 'طالب',
                                'sm_parent' => 'ولي أمر'
                            );
                            $u_role_key = $u->roles[0];
                            echo $role_map[$u_role_key] ?? $u_role_key;
                            ?>
                        </div>
                        <?php if ($u_role_key === 'sm_teacher'): ?>
                            <div style="font-size:11px; color:var(--sm-primary-color); font-weight:700;">التخصص: <?php echo esc_html(get_user_meta($u->ID, 'sm_specialization', true) ?: 'غير محدد'); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code style="background:#f1f5f9; padding:2px 5px; border-radius:4px; font-family:monospace;"><?php echo get_user_meta($u->ID, 'sm_temp_pass', true) ?: '********'; ?></code>
                    </td>
                    <td>
                        <div style="display:flex; gap:8px;">
                            <?php
                            $u_data = array(
                                "id" => $u->ID,
                                "name" => $u->display_name,
                                "email" => $u->user_email,
                                "login" => $u->user_login,
                                "role" => $u_role_key,
                                "specialization" => get_user_meta($u->ID, 'sm_specialization', true)
                            );
                            ?>
                            <button onclick='editSmGenericUser(<?php echo json_encode($u_data); ?>)' class="sm-btn" style="background:#edf2f7; color:#2d3748; padding:5px 10px; width:auto; font-size:11px;">تعديل</button>
                            <?php if ($u->ID != get_current_user_id()): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('حذف هذا المستخدم نهائياً؟')">
                                    <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u->ID; ?>">
                                    <button type="submit" name="sm_delete_user" class="sm-btn" style="background:#e53e3e; padding:5px 10px; width:auto; font-size:11px;">حذف</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<div id="add-user-modal" class="sm-modal-overlay">
    <div class="sm-modal-content">
        <div class="sm-modal-header">
            <h3>إضافة مستخدم جديد</h3>
            <button class="sm-modal-close" onclick="document.getElementById('add-user-modal').style.display='none'">&times;</button>
        </div>
        <form id="add-user-form">
            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="sm-form-group">
                    <label class="sm-label">الاسم الكامل:</label>
                    <input type="text" name="display_name" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">اسم المستخدم (Login):</label>
                    <input type="text" name="user_login" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">البريد الإلكتروني:</label>
                    <input type="text" class="sm-input" value="سيتم إنشاؤه تلقائياً" disabled style="background:#f1f5f9;">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">الرتبة:</label>
                    <select name="user_role" class="sm-select" onchange="toggleSpecialization(this)">
                        <?php if ($current_level >= 4): ?><option value="sm_system_admin">مدير النظام التقني</option><?php endif; ?>
                        <?php if ($current_level >= 3): ?><option value="sm_principal">مدير المدرسة</option><?php endif; ?>
                        <?php if ($current_level >= 2): ?><option value="sm_supervisor">مشرف تربوي</option><?php endif; ?>
                        <?php if ($current_level >= 1): ?><option value="sm_coordinator">منسق مادة</option><?php endif; ?>
                        <?php if ($current_level >= 0): ?>
                            <option value="sm_teacher">معلم</option>
                            <option value="sm_clinic">موظف عيادة</option>
                        <?php endif; ?>
                        <option value="sm_student">طالب</option>
                    </select>
                </div>
                <div class="sm-form-group spec-group" style="display:none;">
                    <label class="sm-label">المادة التخصصية (للمعلمين):</label>
                    <select name="specialization" class="sm-select">
                        <option value="">-- اختر المادة --</option>
                        <?php
                        $subjects = SM_DB::get_subjects();
                        $unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $subjects));
                        foreach($unique_subjects as $sub_name) echo '<option value="'.$sub_name.'">'.$sub_name.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="grid-column: span 2;">
                    <label class="sm-label">كلمة المرور:</label>
                    <input type="password" name="user_pass" class="sm-input" required>
                </div>
            </div>
            <button type="submit" class="sm-btn" style="margin-top:20px; width: 100%;">إنشاء الحساب الآن</button>
        </form>
    </div>
</div>

<div id="edit-user-modal" class="sm-modal-overlay">
    <div class="sm-modal-content">
        <div class="sm-modal-header">
            <h3>تعديل بيانات المستخدم</h3>
            <button class="sm-modal-close" onclick="document.getElementById('edit-user-modal').style.display='none'">&times;</button>
        </div>
        <form id="edit-user-form">
            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
            <input type="hidden" name="edit_user_id" id="edit_u_id">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="sm-form-group">
                    <label class="sm-label">الاسم الكامل:</label>
                    <input type="text" name="display_name" id="edit_u_name" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">البريد الإلكتروني:</label>
                    <input type="email" name="user_email" id="edit_u_email" class="sm-input" readonly style="background:#f1f5f9;">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">الرتبة:</label>
                    <select name="user_role" id="edit_u_role" class="sm-select" onchange="toggleSpecialization(this, 'edit')">
                        <?php if ($current_level >= 4): ?><option value="sm_system_admin">مدير النظام التقني</option><?php endif; ?>
                        <?php if ($current_level >= 3): ?><option value="sm_principal">مدير المدرسة</option><?php endif; ?>
                        <?php if ($current_level >= 2): ?><option value="sm_supervisor">مشرف تربوي</option><?php endif; ?>
                        <?php if ($current_level >= 1): ?><option value="sm_coordinator">منسق مادة</option><?php endif; ?>
                        <?php if ($current_level >= 0): ?>
                            <option value="sm_teacher">معلم</option>
                            <option value="sm_clinic">موظف عيادة</option>
                        <?php endif; ?>
                        <option value="sm_student">طالب</option>
                    </select>
                </div>
                <div class="sm-form-group spec-group" id="edit_spec_group" style="display:none;">
                    <label class="sm-label">المادة التخصصية (للمعلمين):</label>
                    <select name="specialization" id="edit_u_spec" class="sm-select">
                        <option value="">-- اختر المادة --</option>
                        <?php
                        foreach($unique_subjects as $sub_name) echo '<option value="'.$sub_name.'">'.$sub_name.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">كلمة مرور جديدة (اختياري):</label>
                    <input type="password" name="user_pass" class="sm-input" placeholder="اتركه فارغاً لعدم التغيير">
                </div>
            </div>
            <button type="submit" class="sm-btn" style="margin-top:20px; width: 100%;">حفظ التغييرات</button>
        </form>
    </div>
</div>

<script>
(function() {
    window.toggleSpecialization = function(select, mode = 'add') {
        const group = mode === 'add' ? select.closest('form').querySelector('.spec-group') : document.getElementById('edit_spec_group');
        if (select.value === 'sm_teacher') {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    };

    window.editSmGenericUser = function(u) {
        document.getElementById('edit_u_id').value = u.id;
        document.getElementById('edit_u_name').value = u.name;
        document.getElementById('edit_u_email').value = u.email;
        document.getElementById('edit_u_role').value = u.role;
        document.getElementById('edit_u_spec').value = u.specialization || '';

        toggleSpecialization(document.getElementById('edit_u_role'), 'edit');
        document.getElementById('edit-user-modal').style.display = 'flex';
    };

    const addForm = document.getElementById('add-user-form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_add_user_ajax'); // Need to implement this
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تمت إضافة المستخدم');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
    }

    const editForm = document.getElementById('edit-user-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_update_generic_user_ajax'); // Need to implement this
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تم تحديث المستخدم');
                    setTimeout(() => location.reload(), 500);
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
            }
        });
    };
})();
</script>
