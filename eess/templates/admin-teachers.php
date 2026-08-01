<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0; border:none; padding:0;">إدارة مستخدمي النظام</h3>
        <?php if (current_user_can('إدارة_المستخدمين')): ?>
            <div style="display:flex; gap:10px;">
                <button onclick="executeBulkDeleteUsers()" class="sm-btn" style="width:auto; background:#e53e3e;">حذف المستخدمين المحددين</button>
                <button onclick="document.getElementById('teacher-csv-import-form').style.display='block'" class="sm-btn" style="width:auto; background:var(--sm-secondary-color);">استيراد جماعي (CSV)</button>
                <button onclick="document.getElementById('add-teacher-modal').style.display='flex'" class="sm-btn" style="width:auto;">+ إضافة مستخدم جديد</button>
            </div>
        <?php endif; ?>
    </div>

    <div id="teacher-csv-import-form" style="display:none; background: #f8fafc; padding: 30px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 30px;">
        <h3 style="margin-top:0; color:var(--sm-secondary-color);">دليل استيراد المعلمين (CSV)</h3>
        
        <div style="background:#fff; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:700; margin-bottom:10px;">هيكل ملف المعلمين الصحيح:</p>
            <table style="width:100%; font-size:11px; border-collapse:collapse; text-align:center;">
                <thead>
                    <tr style="background:#edf2f7;">
                        <th style="border:1px solid #cbd5e0; padding:5px;">اسم المستخدم</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">البريد</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">الاسم الكامل</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">الكود الوظيفي</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">المسمى</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">رقم الجوال</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">كلمة المرور</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border:1px solid #cbd5e0; padding:5px;">teacher_ali</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">ali@school.com</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">علي محمود</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">T101</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">معلم رياضة</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">050000000</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">123456</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form method="post" enctype="multipart/form-data" onsubmit="return handleImportSubmit(this, 'sm_import_teachers_csv')">
            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
            <div class="sm-form-group">
                <label class="sm-label">اختر ملف CSV للمعلمين:</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <div id="import-loading" style="display:none; margin-bottom: 15px; padding: 10px; background: #ebf8ff; border-left: 4px solid #3182ce; color: #2c5282; font-weight: 700;">
                <span class="dashicons dashicons-update spin" style="margin-left: 10px;"></span>
                جاري استيراد البيانات... يرجى عدم إغلاق الصفحة.
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="sm_import_teachers_csv" class="sm-btn" style="width:auto; background:#27ae60;">استيراد القائمة الآن</button>
                <button type="button" onclick="this.parentElement.parentElement.parentElement.style.display='none'" class="sm-btn" style="width:auto; background:var(--sm-text-gray);">إلغاء</button>
            </div>
        </form>

        <script>
        function handleImportSubmit(form, btnName) {
            const btn = form.querySelector('button[name="' + btnName + '"]');
            const loader = form.querySelector('#import-loading');

            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.innerText = 'جاري المعالجة...';
            if(loader) loader.style.display = 'block';

            return true;
        }
        </script>

        <style>
        @keyframes spin { 100% { transform:rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; }
        </style>
    </div>

    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee;">
        <a href="<?php echo remove_query_arg('role_filter'); ?>" class="sm-tab-btn <?php echo empty($_GET['role_filter']) ? 'sm-active' : ''; ?>" style="text-decoration:none;">الكل</a>
        <a href="<?php echo add_query_arg('role_filter', 'sm_teacher'); ?>" class="sm-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'sm_teacher' ? 'sm-active' : ''; ?>" style="text-decoration:none;">المعلمون</a>
        <a href="<?php echo add_query_arg('role_filter', 'sm_coordinator'); ?>" class="sm-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'sm_coordinator' ? 'sm-active' : ''; ?>" style="text-decoration:none;">المنسقون</a>
        <a href="<?php echo add_query_arg('role_filter', 'sm_supervisor'); ?>" class="sm-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'sm_supervisor' ? 'sm-active' : ''; ?>" style="text-decoration:none;">المشرفون</a>
        <a href="<?php echo add_query_arg('role_filter', 'sm_principal'); ?>" class="sm-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'sm_principal' ? 'sm-active' : ''; ?>" style="text-decoration:none;">مديرو المدرسة</a>
        <a href="<?php echo add_query_arg('role_filter', 'sm_system_admin'); ?>" class="sm-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'sm_system_admin' ? 'sm-active' : ''; ?>" style="text-decoration:none;">مديرو النظام التقني</a>
    </div>

    <div style="background: white; padding: 30px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); margin-bottom: 30px; box-shadow: var(--sm-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: end;">
            <input type="hidden" name="page" value="sm-dashboard">
            <input type="hidden" name="sm_tab" value="teachers">

            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">بحث عن مستخدم (اسم/بريد/كود):</label>
                <input type="text" name="teacher_search" class="sm-input" value="<?php echo esc_attr(isset($_GET['teacher_search']) ? $_GET['teacher_search'] : ''); ?>" placeholder="أدخل بيانات البحث...">
            </div>

            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">تصفية حسب الدور:</label>
                <select name="role_filter" class="sm-select">
                    <option value="">كل الأدوار</option>
                    <option value="sm_system_admin" <?php selected($_GET['role_filter'] ?? '', 'sm_system_admin'); ?>>مدير النظام</option>
                    <option value="sm_principal" <?php selected($_GET['role_filter'] ?? '', 'sm_principal'); ?>>مدير المدرسة</option>
                    <option value="sm_supervisor" <?php selected($_GET['role_filter'] ?? '', 'sm_supervisor'); ?>>مشرف</option>
                    <option value="sm_coordinator" <?php selected($_GET['role_filter'] ?? '', 'sm_coordinator'); ?>>منسق مادة</option>
                    <option value="sm_teacher" <?php selected($_GET['role_filter'] ?? '', 'sm_teacher'); ?>>معلم</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="sm-btn">تطبيق البحث</button>
                <a href="<?php echo add_query_arg(array('sm_tab'=>'teachers'), remove_query_arg(array('teacher_search', 'role_filter'))); ?>" class="sm-btn sm-btn-outline" style="text-decoration:none;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" onclick="toggleAllUsers(this)"></th>
                    <th>كود المستخدم</th>
                    <th>الاسم الكامل</th>
                    <th>الدور / الرتبة</th>
                    <th>رقم التواصل</th>
                    <th>البريد الإلكتروني</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $role_labels = array(
                    'sm_system_admin' => 'مدير النظام',
                    'sm_principal' => 'مدير المدرسة',
                    'sm_supervisor' => 'مشرف',
                    'sm_coordinator' => 'منسق مادة',
                    'sm_teacher' => 'معلم'
                );

                $args = array('role__in' => array_keys($role_labels));
                if (!empty($_GET['role_filter'])) {
                    $args['role'] = sanitize_text_field($_GET['role_filter']);
                }
                if (!empty($_GET['teacher_search'])) {
                    $args['search'] = '*' . esc_attr($_GET['teacher_search']) . '*';
                    $args['search_columns'] = array('user_login', 'display_name', 'user_email');
                }

                $users = get_users($args);
                if (empty($users)): ?>
                    <tr><td colspan="6" style="padding: 40px; text-align: center;">لا يوجد مستخدمون يطابقون البحث.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $role = (array)$u->roles;
                        $role_slug = reset($role);
                        if ($u->ID === get_current_user_id()) continue; // Skip current user
                    ?>
                        <tr class="user-row" data-user-id="<?php echo $u->ID; ?>">
                            <td><input type="checkbox" class="user-cb" value="<?php echo $u->ID; ?>"></td>
                            <td style="font-family: 'Rubik', sans-serif; font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html(get_user_meta($u->ID, 'sm_teacher_id', true) ?: $u->user_login); ?></td>
                            <td style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($u->display_name); ?></td>
                            <td><span class="sm-badge sm-badge-low"><?php echo $role_labels[$role_slug] ?? $role_slug; ?></span></td>
                            <td dir="ltr" style="text-align: right;"><?php echo esc_html(get_user_meta($u->ID, 'sm_phone', true)); ?></td>
                            <td><?php echo esc_html($u->user_email); ?></td>
                            <td>
                                <div style="display:flex; gap:8px; justify-content: flex-end;">
                                    <?php
                                    $assigned = get_user_meta($u->ID, 'sm_assigned_sections', true) ?: (get_user_meta($u->ID, 'sm_supervised_classes', true) ?: array());
                                    ?>
                                    <button onclick='editSmUser(<?php echo json_encode(array(
                                        "id" => $u->ID,
                                        "name" => $u->display_name,
                                        "email" => $u->user_email,
                                        "login" => $u->user_login,
                                        "role" => $role_slug,
                                        "assigned" => $assigned,
                                        "teacher_id" => get_user_meta($u->ID, "sm_teacher_id", true),
                                        "phone" => get_user_meta($u->ID, "sm_phone", true)
                                    )); ?>)' class="sm-btn sm-btn-outline" style="padding: 5px 12px; font-size: 12px;">تعديل</button>
                                    
                                    <form method="post" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟')">
                                        <?php wp_nonce_field('sm_teacher_action', 'sm_nonce'); ?>
                                        <input type="hidden" name="delete_teacher_id" value="<?php echo $u->ID; ?>">
                                        <button type="submit" name="sm_delete_teacher" class="sm-btn sm-btn-outline" style="padding: 5px 12px; font-size: 12px; color:#e53e3e;">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>


    <div id="edit-teacher-modal" class="sm-modal-overlay">
        <div class="sm-modal-content">
            <div class="sm-modal-header">
                <h3>تعديل بيانات الحساب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-teacher-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-teacher-form">
                <?php wp_nonce_field('sm_teacher_action', 'sm_nonce'); ?>
                <input type="hidden" name="edit_teacher_id" id="edit_t_id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="sm-form-group">
                        <label class="sm-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" id="edit_t_name" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">كود المستخدم (ID):</label>
                        <input type="text" name="teacher_id" id="edit_t_code" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">رقم الهاتف:</label>
                        <input type="text" name="phone" id="edit_t_phone" class="sm-input">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" id="edit_t_email" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">تغيير الدور:</label>
                        <select name="role" id="edit_t_role" class="sm-select" onchange="toggleAssignFields(this, 'edit')">
                            <option value="sm_system_admin">مدير النظام</option>
                            <option value="sm_principal">مدير المدرسة</option>
                            <option value="sm_supervisor">مشرف</option>
                            <option value="sm_coordinator">منسق مادة</option>
                            <option value="sm_teacher">معلم</option>
                        </select>
                    </div>
                    <div class="sm-form-group" id="edit_assign_fields" style="grid-column: span 2; display: none;">
                        <label class="sm-label">الصفحات/الشعب المسندة للمستخدم:</label>
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; max-height: 150px; overflow-y: auto; background:#fff; padding:10px; border:1px solid #ddd; border-radius:8px;">
                            <?php
                            $struct = SM_Settings::get_sections_from_db();
                            foreach($struct as $g => $secs) {
                                foreach($secs as $s) {
                                    echo "<label style='font-size:11px; display:flex; align-items:center; gap:5px;'><input type='checkbox' name='assigned[]' value='$g|$s'> $g - $s</label>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">حالة الحساب:</label>
                        <select name="account_status" id="edit_t_status" class="sm-select">
                            <option value="active">نشط</option>
                            <option value="restricted">مقيد (لا يمكنه الدخول)</option>
                        </select>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">كلمة مرور جديدة (اختياري):</label>
                        <input type="password" name="user_pass" class="sm-input" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                </div>
                <button type="submit" class="sm-btn" style="margin-top:20px;">حفظ التغييرات</button>
            </form>
        </div>
    </div>

    <div id="add-teacher-modal" class="sm-modal-overlay">
        <div class="sm-modal-content">
            <div class="sm-modal-header">
                <h3>إضافة حساب مستخدم جديد</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-teacher-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-teacher-form">
                <?php wp_nonce_field('sm_teacher_action', 'sm_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="sm-form-group">
                        <label class="sm-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">كود المستخدم (ID):</label>
                        <input type="text" name="teacher_id" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">اختيار الدور:</label>
                        <select name="role" class="sm-select" onchange="toggleAssignFields(this, 'add')">
                            <option value="sm_system_admin">مدير النظام</option>
                            <option value="sm_principal">مدير المدرسة</option>
                            <option value="sm_supervisor">مشرف</option>
                            <option value="sm_coordinator">منسق مادة</option>
                            <option value="sm_teacher">معلم</option>
                        </select>
                    </div>
                    <div class="sm-form-group" id="add_assign_fields" style="grid-column: span 2; display: none;">
                        <label class="sm-label">الصفحات/الشعب المسندة (للمعلمين والمشرفين):</label>
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; max-height: 150px; overflow-y: auto; background:#fff; padding:10px; border:1px solid #ddd; border-radius:8px;">
                            <?php
                            foreach($struct as $g => $secs) {
                                foreach($secs as $s) {
                                    echo "<label style='font-size:11px; display:flex; align-items:center; gap:5px;'><input type='checkbox' name='assigned[]' value='$g|$s'> $g - $s</label>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">رقم الهاتف:</label>
                        <input type="text" name="phone" class="sm-input">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">اسم المستخدم (Login):</label>
                        <input type="text" name="user_login" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" class="sm-input" required>
                    </div>
                    <div class="sm-form-group" style="grid-column: span 2;">
                        <label class="sm-label">كلمة المرور (اترك فارغاً للتوليد التلقائي 10 أرقام):</label>
                        <input type="password" name="user_pass" class="sm-input" placeholder="********">
                    </div>
                </div>
                <button type="submit" class="sm-btn" style="margin-top:20px;">إنشاء الحساب الآن</button>
            </form>
        </div>
    </div>

    <script>
    function toggleAllUsers(master) {
        document.querySelectorAll('.user-cb').forEach(cb => cb.checked = master.checked);
    }

    function executeBulkDeleteUsers() {
        const ids = Array.from(document.querySelectorAll('.user-cb:checked')).map(cb => cb.value);
        if (ids.length === 0) {
            alert('يرجى تحديد مستخدمين أولاً');
            return;
        }
        if (!confirm('هل أنت متأكد من حذف ' + ids.length + ' مستخدم؟')) return;

        const formData = new FormData();
        formData.append('action', 'sm_bulk_delete_users_ajax');
        formData.append('user_ids', ids.join(','));
        formData.append('nonce', '<?php echo wp_create_nonce("sm_teacher_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف المستخدمين بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function toggleAssignFields(select, mode) {
        const fields = document.getElementById(mode + '_assign_fields');
        if (select.value === 'sm_teacher' || select.value === 'sm_supervisor') {
            fields.style.display = 'block';
        } else {
            fields.style.display = 'none';
        }
    }

    (function() {
        window.editSmUser = function(u) {
            document.getElementById('edit_t_id').value = u.id;
            document.getElementById('edit_t_name').value = u.name;
            document.getElementById('edit_t_code').value = u.teacher_id;
            document.getElementById('edit_t_phone').value = u.phone;
            document.getElementById('edit_t_email').value = u.email;
            document.getElementById('edit_t_status').value = u.status || 'active';
            const roleSel = document.getElementById('edit_t_role');
            roleSel.value = u.role;
            toggleAssignFields(roleSel, 'edit');

            document.querySelectorAll('#edit_assign_fields input').forEach(cb => cb.checked = false);
            if (u.assigned) {
                u.assigned.forEach(val => {
                    const cb = document.querySelector(`#edit_assign_fields input[value="${val}"]`);
                    if (cb) cb.checked = true;
                });
            }
            document.getElementById('edit-teacher-modal').style.display = 'flex';
        };

        const addForm = document.getElementById('add-teacher-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_add_teacher_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تمت إضافة المعلم');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                });
            });
        }

        const editForm = document.getElementById('edit-teacher-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_update_teacher_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم تحديث بيانات المعلم');
                        setTimeout(() => location.reload(), 500);
                    }
                });
            });
        }
    })();
    </script>
</div>
