<?php if (!defined('ABSPATH')) exit; ?>
<script>
/**
 * SCHOOL MANAGEMENT - CORE UI ENGINE (ULTRA HARDENED V5)
 * Standard linking and routing fix.
 */
(function(window) {
    const SM_UI = {
        showNotification: function(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'sm-toast';
            toast.style.cssText = "position:fixed; top:20px; left:50%; transform:translateX(-50%); background:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:10001; display:flex; align-items:center; gap:10px; border-right:5px solid " + (isError ? '#e53e3e' : '#38a169');
            toast.innerHTML = `<strong>${isError ? '✖' : '✓'}</strong> <span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '0.5s'; setTimeout(() => toast.remove(), 500); }, 3000);
        },

        openInternalTab: function(tabId, element) {
            const target = document.getElementById(tabId);
            if (!target || !element) return;
            const container = target.parentElement;
            container.querySelectorAll('.sm-internal-tab').forEach(p => p.style.setProperty('display', 'none', 'important'));
            target.style.setProperty('display', 'block', 'important');
            element.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
            element.classList.add('sm-active');
        }
    };

    window.smShowNotification = SM_UI.showNotification;
    window.smOpenInternalTab = SM_UI.openInternalTab;

    // REAL-TIME COUNTERS
    function updateRealTimeCounters() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_counts_ajax')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const badgeReports = document.getElementById('pending-reports-badge');
                if (badgeReports) {
                    const count = parseInt(res.data.pending_reports);
                    badgeReports.innerText = count;
                    badgeReports.style.display = count > 0 ? 'block' : 'none';
                }
                const badgeItems = document.getElementById('expired-items-badge');
                if (badgeItems) {
                    const count = parseInt(res.data.expired_items);
                    badgeItems.innerText = count;
                    badgeItems.style.display = count > 0 ? 'block' : 'none';
                }
            }
        });
    }
    setInterval(updateRealTimeCounters, 10000); // Every 10 seconds
    window.addEventListener('load', updateRealTimeCounters);

    // MEDIA UPLOADER FOR LOGO
    window.smOpenMediaUploader = function(inputId) {
        const frame = wp.media({
            title: 'اختر شعار المدرسة',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    };

    // GLOBAL EDIT HANDLERS
    window.editSmStudent = function(s) {
        document.getElementById('edit_stu_id').value = s.id;
        document.getElementById('edit_stu_name').value = s.name;
        document.getElementById('edit_stu_class').value = s.class_name || s.class;
        if (document.getElementById('edit_stu_section')) document.getElementById('edit_stu_section').value = s.section || '';
        document.getElementById('edit_stu_email').value = s.parent_email || '';
        document.getElementById('edit_stu_code').value = s.student_id || '';

        if (document.getElementById('edit_stu_phone')) document.getElementById('edit_stu_phone').value = s.guardian_phone || '';
        if (document.getElementById('edit_stu_nationality')) document.getElementById('edit_stu_nationality').value = s.nationality || '';
        if (document.getElementById('edit_stu_reg_date')) document.getElementById('edit_stu_reg_date').value = s.registration_date || '';

        if (document.getElementById('edit_stu_parent_user')) document.getElementById('edit_stu_parent_user').value = s.parent_id || '';
        document.getElementById('edit-student-modal').style.display = 'flex';
    };

    window.editSmTeacher = function(t) {
        document.getElementById('edit_t_id').value = t.id;
        document.getElementById('edit_t_name').value = t.name;
        document.getElementById('edit_t_code').value = t.teacher_id;
        document.getElementById('edit_t_job').value = t.job_title;
        document.getElementById('edit_t_phone').value = t.phone;
        document.getElementById('edit_t_email').value = t.email;
        document.getElementById('edit-teacher-modal').style.display = 'flex';
    };

    window.updateRecordStatus = function(id, status) {
        const formData = new FormData();
        formData.append('action', 'sm_update_record_status');
        formData.append('record_id', id);
        formData.append('status', status);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث حالة المخالفة');
                setTimeout(() => location.reload(), 500);
            }
        });
    };

    window.smDeleteAllLogs = function() {
        if (!confirm('هل أنت متأكد من مسح كافة سجلات النشاط؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const formData = new FormData();
        formData.append('action', 'sm_delete_all_logs_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم مسح كافة النشاطات بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    };

    window.smDeleteLog = function(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل نهائياً؟')) return;

        const formData = new FormData();
        formData.append('action', 'sm_delete_log_ajax');
        formData.append('log_id', logId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف السجل بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    };

    window.smOpenViolationModal = function() {
        document.getElementById('sm-global-violation-modal').style.display = 'flex';
    };

    window.smCloseViolationModal = function() {
        document.getElementById('sm-global-violation-modal').style.display = 'none';
    };

    window.smToggleUserDropdown = function() {
        const menu = document.getElementById('sm-user-dropdown-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            document.getElementById('sm-profile-view').style.display = 'block';
            document.getElementById('sm-profile-edit').style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.smEditProfile = function() {
        document.getElementById('sm-profile-view').style.display = 'none';
        document.getElementById('sm-profile-edit').style.display = 'block';
    };

    window.smSaveProfile = function() {
        const name = document.getElementById('sm_edit_display_name').value;
        const email = document.getElementById('sm_edit_user_email').value;
        const pass = document.getElementById('sm_edit_user_pass').value;
        const nonce = '<?php echo wp_create_nonce("sm_profile_action"); ?>';

        const formData = new FormData();
        formData.append('action', 'sm_update_profile_ajax');
        formData.append('display_name', name);
        formData.append('user_email', email);
        formData.append('user_pass', pass);
        formData.append('nonce', nonce);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.sm-user-dropdown');
        const menu = document.getElementById('sm-user-dropdown-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

    window.smBulkDelete = function(type) {
        if (!confirm('هل أنت متأكد من مسح كافة البيانات المحددة؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const formData = new FormData();
        formData.append('action', 'sm_bulk_delete_ajax');
        formData.append('delete_type', type);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم مسح البيانات بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    window.smRollbackLog = function(logId) {
        if (!confirm('هل أنت متأكد من استعادة هذه البيانات المحذوفة؟')) return;

        const formData = new FormData();
        formData.append('action', 'sm_rollback_log_ajax');
        formData.append('log_id', logId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    window.smInitializeSystem = function() {
        const code = prompt('لتأكيد تهيأة النظام بالكامل، يرجى إدخال كود التأكيد (1011996):');
        if (!code) return;

        const formData = new FormData();
        formData.append('action', 'sm_initialize_system_ajax');
        formData.append('confirm_code', code);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت تهيأة النظام بالكامل بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };
})(window);
</script>

<?php 
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_teacher = in_array('sm_teacher', $roles);
$is_student = in_array('sm_student', $roles);
$is_parent = in_array('sm_parent', $roles);
$is_clinic = in_array('sm_clinic', $roles);

$active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';
$school = SM_Settings::get_school_info();
$stats = array();
$sidebar_visibility = SM_Settings::get_sidebar_visibility();
$my_role = !empty($user->roles) ? $user->roles[0] : '';
$my_visibility = $sidebar_visibility[$my_role] ?? array();

// Ensure site administrators always see everything regardless of role-based settings
$is_wp_admin = current_user_can('manage_options');

if ($active_tab === 'summary') {
    $stats = SM_DB::get_statistics();

    // For parents, filter stats to their student
    if ($is_parent) {
        $student = SM_DB::get_student_by_parent(get_current_user_id());
        if ($student) {
            $stats = SM_DB::get_student_stats($student->id);
        }
    }
}

// Dynamic Greeting logic
$hour = (int)current_time('G');
$greeting = ($hour >= 5 && $hour < 12) ? 'صباح الخير' : 'مساء الخير';
?>

<div class="sm-admin-dashboard" dir="rtl" style="font-family: 'Rubik', sans-serif; background: #fff; border: 1px solid var(--sm-border-color); border-radius: 12px; overflow: hidden;">
    <!-- OFFICIAL SYSTEM HEADER -->
    <div class="sm-main-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($school['school_logo'])): ?>
                <div style="background: white; padding: 5px; border: 1px solid var(--sm-border-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <img src="<?php echo esc_url($school['school_logo']); ?>" style="height: 45px; width: auto; object-fit: contain; display: block;">
                </div>
            <?php else: ?>
                <div style="background: #f1f5f9; padding: 5px; border: 1px solid var(--sm-border-color); border-radius: 10px; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <span class="dashicons dashicons-building" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; border: none; padding: 0; color: var(--sm-dark-color); font-weight: 800; font-size: 1.3em; text-decoration: none; line-height: 1;">
                    <?php echo esc_html($school['school_name']); ?>
                </h1>
                <div style="display: inline-block; padding: 3px 12px; background: #fff5f5; color: #F63049; border-radius: 50px; font-size: 11px; font-weight: 700; margin-top: 6px; border: 1px solid #fed7d7;">
                    <?php 
                    if ($is_admin) echo 'مدير النظام';
                    elseif ($is_sys_admin) echo 'مدير النظام التقني';
                    elseif ($is_principal) echo 'مدير المدرسة';
                    elseif ($is_supervisor) echo 'مشرف تربوي';
                    elseif ($is_coordinator) echo 'منسق مادة';
                    elseif ($is_teacher) echo 'معلم';
                    elseif ($is_student) echo 'طالب';
                    else echo 'مستخدم النظام';
                    ?>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="sm-header-info-box" style="text-align: right; border-left: 1px solid var(--sm-border-color); padding-left: 15px;">
                <div style="font-size: 0.85em; font-weight: 700; color: var(--sm-dark-color);"><?php echo date_i18n('l j F Y'); ?></div>
            </div>

            <?php if (($is_wp_admin || !empty($my_visibility['attendance'])) && ($is_admin || current_user_can('إدارة_الطلاب'))): ?>
                <a href="<?php echo add_query_arg('sm_tab', 'attendance'); ?>" class="sm-btn sm-btn-secondary" style="height: 38px; font-size: 12px; color: white !important; text-decoration: none;">سجل الحضور والغياب</a>
            <?php endif; ?>

            <?php if ($active_tab !== 'attendance' && ($is_admin || current_user_can('تسجيل_مخالفة'))): ?>
                <button onclick="smOpenViolationModal()" class="sm-btn" style="background: var(--sm-primary-color); height: 38px; font-size: 12px; color: white !important;">+ تسجيل مخالفة</button>
            <?php endif; ?>

            <div class="sm-user-dropdown" style="position: relative;">
                <div class="sm-user-profile-nav" onclick="smToggleUserDropdown()" style="display: flex; align-items: center; gap: 12px; background: white; padding: 6px 12px; border-radius: 50px; border: 1px solid var(--sm-border-color); cursor: pointer;">
                    <div style="text-align: right;">
                        <div style="font-size: 0.85em; font-weight: 700; color: var(--sm-dark-color);"><?php echo $greeting . '، ' . $user->display_name; ?></div>
                        <div style="font-size: 0.7em; color: #38a169;">متصل الآن <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px;"></span></div>
                    </div>
                    <?php echo get_avatar($user->ID, 32, '', '', array('style' => 'border-radius: 50%; border: 2px solid var(--sm-primary-color);')); ?>
                </div>
                <div id="sm-user-dropdown-menu" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid var(--sm-border-color); border-radius: 8px; width: 260px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; animation: smFadeIn 0.2s ease-out; padding: 10px 0;">
                    <div id="sm-profile-view">
                        <div style="padding: 10px 20px; border-bottom: 1px solid #f0f0f0; margin-bottom: 5px;">
                            <div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo $user->display_name; ?></div>
                            <div style="font-size: 11px; color: var(--sm-text-gray);"><?php echo $user->user_email; ?></div>
                        </div>
                        <?php if (!$is_student && !$is_parent): ?>
                            <a href="javascript:smEditProfile()" class="sm-dropdown-item"><span class="dashicons dashicons-edit"></span> تعديل البيانات الشخصية</a>
                        <?php endif; ?>
                        <?php if ($is_student || $is_parent): ?>
                            <a href="javascript:smEditProfile()" class="sm-dropdown-item"><span class="dashicons dashicons-lock"></span> تغيير كلمة المرور</a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>" class="sm-dropdown-item"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <?php endif; ?>
                        <a href="javascript:location.reload()" class="sm-dropdown-item"><span class="dashicons dashicons-update"></span> تحديث الصفحة</a>
                    </div>

                    <div id="sm-profile-edit" style="display: none; padding: 15px;">
                        <div style="font-weight: 800; margin-bottom: 15px; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px;">تعديل الملف الشخصي</div>
                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">الاسم المفضل:</label>
                            <input type="text" id="sm_edit_display_name" class="sm-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->display_name); ?>" <?php if ($is_student || $is_parent) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" id="sm_edit_user_email" class="sm-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->user_email); ?>" <?php if ($is_student || $is_parent) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="sm-form-group" style="margin-bottom: 15px;">
                            <label class="sm-label" style="font-size: 11px;">كلمة مرور جديدة (اختياري):</label>
                            <input type="password" id="sm_edit_user_pass" class="sm-input" style="padding: 8px; font-size: 12px;" placeholder="********">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="smSaveProfile()" class="sm-btn" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">حفظ</button>
                            <button onclick="document.getElementById('sm-profile-edit').style.display='none'; document.getElementById('sm-profile-view').style.display='block';" class="sm-btn sm-btn-outline" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">إلغاء</button>
                        </div>
                    </div>

                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                    <a href="<?php echo wp_logout_url(home_url('/sm-login')); ?>" class="sm-dropdown-item" style="color: #e53e3e;"><span class="dashicons dashicons-logout"></span> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sm-admin-layout" style="display: flex; min-height: 800px;">
        <!-- SIDEBAR -->
        <div class="sm-sidebar" style="width: 280px; flex-shrink: 0; background: var(--sm-bg-light); border-left: 1px solid var(--sm-border-color); padding: 20px 0;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li class="sm-sidebar-item <?php echo $active_tab == 'summary' ? 'sm-active' : ''; ?>">
                    <a href="<?php echo add_query_arg('sm_tab', 'summary'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-dashboard"></span> لوحة المعلومات</a>
                </li>

                <?php if (($is_wp_admin || !empty($my_visibility['stats'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_teacher || $is_student || $is_parent)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'stats' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo remove_query_arg(['student_search', 'class_filter', 'section_filter', 'type_filter', 'start_date', 'end_date'], add_query_arg('sm_tab', 'stats')); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-list-view"></span> سجل المخالفات</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['students'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_teacher)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'students' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo remove_query_arg(['student_search', 'class_filter', 'section_filter', 'teacher_filter'], add_query_arg('sm_tab', 'students')); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-groups"></span> إدارة الطلاب</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['teachers'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'teachers' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'teachers'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-admin-users"></span> إدارة مستخدمي النظام</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['parents'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'parents' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'parents'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-admin-users"></span> إدارة أولياء الأمور</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['grades'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_teacher || $is_student || $is_parent)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'grades' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'grades'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-welcome-learn-more"></span> إدارة الدرجات والنتائج</a>
                    </li>
                <?php endif; ?>


                <?php if (($is_wp_admin || !empty($my_visibility['teacher-reports'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'teacher-reports' ? 'sm-active' : ''; ?>" style="position:relative;">
                        <a href="<?php echo add_query_arg('sm_tab', 'teacher-reports'); ?>" class="sm-sidebar-link">
                            <span class="dashicons dashicons-warning"></span> بلاغات المعلمين
                            <span id="pending-reports-badge" class="sm-sidebar-badge" style="display:none;">0</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['confiscated'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'confiscated' ? 'sm-active' : ''; ?>" style="position:relative;">
                        <a href="<?php echo add_query_arg('sm_tab', 'confiscated'); ?>" class="sm-sidebar-link">
                            <span class="dashicons dashicons-lock"></span> المواد المصادرة
                            <span id="expired-items-badge" class="sm-sidebar-badge" style="display:none;">0</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['lesson-plans'])) && ($is_coordinator || $is_teacher)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'lesson-plans' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'lesson-plans'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-welcome-write-blog"></span> تحضير الدروس</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['assignments'])) && ($is_teacher || $is_student)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'assignments' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'assignments'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-portfolio"></span> الواجبات المدرسية</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['messaging']))): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'messaging' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'messaging'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-email"></span> مركز المراسلة</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['documents']))): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'documents' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'documents'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-media-document"></span> مكتبة الوثائق والتقارير</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['printing'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'printing' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'printing'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-printer"></span> مركز الطباعة</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['clinic'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_clinic)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'clinic' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'clinic'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-heart"></span> العيادة المدرسية</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['surveys'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'surveys' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'surveys'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-clipboard"></span> استطلاعات الرأي</a>
                    </li>
                <?php endif; ?>

                <?php if (($is_wp_admin || !empty($my_visibility['timetables'])) && ($is_admin || $is_sys_admin || $is_principal || $is_supervisor)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'timetables' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'timetables'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-calendar-alt"></span> الجداول المدرسية</a>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'global-settings' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- CONTENT AREA -->
        <div class="sm-main-panel" style="flex: 1; min-width: 0; padding: 40px; background: #fff;">
            
            <?php 
            switch ($active_tab) {
                case 'summary':
                    if ($is_parent) {
                        if (isset($student) && $student) include SM_PLUGIN_DIR . 'templates/parent-student-summary.php';
                        else echo '<p>لا يوجد بيانات لعرضها.</p>';
                    } else {
                        include SM_PLUGIN_DIR . 'templates/public-dashboard-summary.php'; 
                    }
                    break;

                case 'students':
                    if ($is_admin || current_user_can('إدارة_الطلاب')) {
                        include SM_PLUGIN_DIR . 'templates/admin-students.php';
                    }
                    break;

                case 'record':
                    // This tab is now handled by a global modal
                    echo '<script>window.location.href="' . remove_query_arg('sm_tab') . '";</script>';
                    break;

                case 'stats':
                    if ($is_admin || current_user_can('إدارة_المخالفات') || $is_parent) {
                        include SM_PLUGIN_DIR . 'templates/public-dashboard-stats.php'; 
                    }
                    break;

                case 'messaging':
                    include SM_PLUGIN_DIR . 'templates/messaging-center.php';
                    break;

                case 'documents':
                    include SM_PLUGIN_DIR . 'templates/admin-documents.php';
                    break;

                case 'teachers':
                    if ($is_admin || current_user_can('إدارة_المعلمين')) {
                        include SM_PLUGIN_DIR . 'templates/admin-teachers.php';
                    }
                    break;

                case 'parents':
                    if ($is_admin || current_user_can('إدارة_أولياء_الأمور')) {
                        include SM_PLUGIN_DIR . 'templates/admin-parents.php';
                    }
                    break;

                case 'printing':
                    if ($is_admin || current_user_can('طباعة_التقارير')) {
                        include SM_PLUGIN_DIR . 'templates/printing-cards.php';
                    }
                    break;


                case 'teacher-reports':
                    include SM_PLUGIN_DIR . 'templates/admin-teacher-reports.php';
                    break;

                case 'confiscated':
                    include SM_PLUGIN_DIR . 'templates/admin-confiscated.php';
                    break;

                case 'attendance':
                    include SM_PLUGIN_DIR . 'templates/admin-attendance.php';
                    break;

                case 'lesson-plans':
                    include SM_PLUGIN_DIR . 'templates/admin-lesson-plans.php';
                    break;

                case 'assignments':
                    include SM_PLUGIN_DIR . 'templates/admin-assignments.php';
                    break;

                case 'clinic':
                    include SM_PLUGIN_DIR . 'templates/admin-clinic.php';
                    break;

                case 'surveys':
                    if ($is_admin || $is_sys_admin || $is_principal || $is_supervisor) {
                        include SM_PLUGIN_DIR . 'templates/admin-surveys.php';
                    }
                    break;

                case 'timetables':
                    if ($is_admin || $is_sys_admin || $is_principal || $is_supervisor) {
                        include SM_PLUGIN_DIR . 'templates/admin-timetables.php';
                    }
                    break;

                case 'grades':
                    include SM_PLUGIN_DIR . 'templates/admin-grades.php';
                    break;

                case 'global-settings':
                    if ($is_admin || current_user_can('إدارة_النظام')) {
                        ?>
                        <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('school-settings', this)">السلطة</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('design-settings', this)">تصميم النظام</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('user-settings', this)">إدارة المستخدمين</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('sidebar-settings', this)">تخصيص القائمة</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('school-structure', this)">الهيكل المدرسي</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('backup-settings', this)">مركز النسخ الاحتياطي</button>
                            <?php if ($is_admin): ?>
                                <button class="sm-tab-btn" onclick="smOpenInternalTab('activity-logs', this)">سجل النشاطات</button>
                            <?php endif; ?>
                        </div>
                        <div id="school-settings" class="sm-internal-tab">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                                    <div class="sm-form-group"><label class="sm-label">اسم المدرسة:</label><input type="text" name="school_name" value="<?php echo esc_attr($school['school_name']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">اسم مدير المدرسة:</label><input type="text" name="school_principal_name" value="<?php echo esc_attr($school['school_principal_name'] ?? ''); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">رقم الهاتف:</label><input type="text" name="school_phone" value="<?php echo esc_attr($school['phone']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">البريد الإلكتروني:</label><input type="email" name="school_email" value="<?php echo esc_attr($school['email']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group">
                                        <label class="sm-label">شعار المدرسة:</label>
                                        <div style="display:flex; gap:10px;">
                                            <input type="text" name="school_logo" id="sm_school_logo_url" value="<?php echo esc_attr($school['school_logo']); ?>" class="sm-input">
                                            <button type="button" onclick="smOpenMediaUploader('sm_school_logo_url')" class="sm-btn" style="width:auto; font-size:12px; background:var(--sm-secondary-color);">رفع/اختيار</button>
                                        </div>
                                    </div>
                                    <div class="sm-form-group" style="grid-column: span 2;"><label class="sm-label">العنوان:</label><input type="text" name="school_address" value="<?php echo esc_attr($school['address']); ?>" class="sm-input"></div>

                                    <div class="sm-form-group" style="grid-column: span 2; background: #fffaf0; padding: 20px; border-radius: 8px; border: 1px solid #feebc8; margin-top: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <h4 style="margin:0; color: #744210;">قسم تصميم النظام</h4>
                                                <p style="margin: 5px 0 0 0; font-size: 12px; color: #975a16;">يمكنك التحكم في الألوان والخطوط والمظهر العام للنظام من خلال تبويب "تصميم النظام".</p>
                                            </div>
                                            <button type="button" onclick="smOpenInternalTab('design-settings', document.querySelector('[onclick*=\"design-settings\"]'))" class="sm-btn" style="width:auto; background:#d69e2e;">انتقل للتصميم</button>
                                        </div>
                                    </div>

                                    <div class="sm-form-group" style="grid-column: span 2;">
                                        <label class="sm-label">أيام العمل الأسبوعية (الجدول الرسمي):</label>
                                        <div style="display: flex; gap: 40px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                            <div>
                                                <div style="font-weight: 800; margin-bottom: 10px; color: var(--sm-primary-color);">المعلمين والطلاب:</div>
                                                <?php
                                                $days = array('sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس', 'fri' => 'الجمعة', 'sat' => 'السبت');
                                                $work_students = $school['working_schedule']['students'] ?? array();
                                                foreach ($days as $key => $label): ?>
                                                    <label style="display: block; font-size: 13px; margin-bottom: 5px;">
                                                        <input type="checkbox" name="work_students[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $work_students)); ?>> <?php echo $label; ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 800; margin-bottom: 10px; color: var(--sm-secondary-color);">الكادر الإداري:</div>
                                                <?php
                                                $work_staff = $school['working_schedule']['staff'] ?? array();
                                                foreach ($days as $key => $label): ?>
                                                    <label style="display: block; font-size: 13px; margin-bottom: 5px;">
                                                        <input type="checkbox" name="work_staff[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $work_staff)); ?>> <?php echo $label; ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="sm_save_settings_unified" class="sm-btn" style="width:auto;">حفظ الإعدادات</button>
                            </form>
                        </div>
                        <div id="design-settings" class="sm-internal-tab" style="display:none;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); $appearance = SM_Settings::get_appearance(); ?>
                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">إعدادات الألوان والمظهر</h4>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">
                                    <div class="sm-form-group"><label class="sm-label">اللون الأساسي (#F63049):</label><input type="color" name="primary_color" value="<?php echo esc_attr($appearance['primary_color'] ?? '#F63049'); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">اللون الثانوي (#D02752):</label><input type="color" name="secondary_color" value="<?php echo esc_attr($appearance['secondary_color'] ?? '#D02752'); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">لون التمييز (#8A244B):</label><input type="color" name="accent_color" value="<?php echo esc_attr($appearance['accent_color'] ?? '#8A244B'); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">لون الهيدر (#111F35):</label><input type="color" name="dark_color" value="<?php echo esc_attr($appearance['dark_color'] ?? '#111F35'); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">حجم الخط (بكسل):</label><input type="text" name="font_size" value="<?php echo esc_attr($appearance['font_size'] ?? '15px'); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">نصف قطر الزوايا (بكسل):</label><input type="text" name="border_radius" value="<?php echo esc_attr($appearance['border_radius'] ?? '12px'); ?>" class="sm-input"></div>
                                </div>
                                <h4 style="margin-top:20px; border-bottom:1px solid #eee; padding-bottom:10px;">مكونات واجهة المستخدم</h4>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">
                                    <div class="sm-form-group">
                                        <label class="sm-label">نمط الجداول:</label>
                                        <select name="table_style" class="sm-select">
                                            <option value="modern" <?php selected($appearance['table_style'] ?? '', 'modern'); ?>>عصري - بدون حدود</option>
                                            <option value="classic" <?php selected($appearance['table_style'] ?? '', 'classic'); ?>>كلاسيكي - بحدود كاملة</option>
                                        </select>
                                    </div>
                                    <div class="sm-form-group">
                                        <label class="sm-label">نمط الأزرار:</label>
                                        <select name="button_style" class="sm-select">
                                            <option value="flat" <?php selected($appearance['button_style'] ?? '', 'flat'); ?>>مسطح (Flat)</option>
                                            <option value="gradient" <?php selected($appearance['button_style'] ?? '', 'gradient'); ?>>متدرج (Gradient)</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="sm_save_appearance" class="sm-btn" style="width:auto;">حفظ تصميم النظام</button>
                            </form>
                        </div>

                        <div id="user-settings" class="sm-internal-tab" style="display:none;">
                            <?php include SM_PLUGIN_DIR . 'templates/admin-users-view.php'; ?>
                        </div>
                        <div id="sidebar-settings" class="sm-internal-tab" style="display:none;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce');
                                $visibility = SM_Settings::get_sidebar_visibility();
                                $roles_list = array(
                                    'sm_system_admin' => 'مدير النظام',
                                    'sm_principal' => 'مدير المدرسة',
                                    'sm_supervisor' => 'مشرف',
                                    'sm_coordinator' => 'منسق مادة',
                                    'sm_teacher' => 'معلم',
                                    'sm_student' => 'طالب',
                                    'sm_parent' => 'ولي أمر'
                                );
                                $sections = array(
                                    'stats' => 'سجل المخالفات',
                                    'students' => 'إدارة الطلاب',
                                    'teachers' => 'إدارة مستخدمي النظام',
                                    'parents' => 'إدارة أولياء الأمور',
                                    'grades' => 'إدارة الدرجات والنتائج',
                                    'teacher-reports' => 'بلاغات المعلمين',
                                    'confiscated' => 'المواد المصادرة',
                                    'printing' => 'مركز الطباعة',
                                    'surveys' => 'استطلاعات الرأي',
                                    'timetables' => 'الجداول المدرسية',
                                    'attendance' => 'سجل الحضور والغياب',
                                    'lesson-plans' => 'تحضير الدروس',
                                    'assignments' => 'الواجبات المدرسية',
                                    'clinic' => 'العيادة المدرسية',
                                    'messaging' => 'مركز المراسلة',
                                    'documents' => 'مكتبة الوثائق والتقارير'
                                );
                                ?>
                                <h4 style="margin-top:0;">تخصيص ظهور أقسام القائمة الجانبية حسب الرتب</h4>
                                <p style="font-size:12px; color:#666; margin-bottom:20px;">حدد الأقسام التي تظهر لكل رتبة من رتب مستخدمي النظام.</p>

                                <div class="sm-table-container" style="overflow-x: auto;">
                                    <table class="sm-table">
                                        <thead>
                                            <tr>
                                                <th>القسم \ الرتبة</th>
                                                <?php foreach($roles_list as $role_label): ?>
                                                    <th style="font-size: 11px; text-align: center;"><?php echo $role_label; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($sections as $sec_key => $sec_label): ?>
                                                <tr>
                                                    <td style="font-weight: 700; font-size: 13px;"><?php echo $sec_label; ?></td>
                                                    <?php foreach($roles_list as $role_key => $role_label): ?>
                                                        <td style="text-align: center;">
                                                            <input type="checkbox" name="sidebar_visibility[<?php echo $role_key; ?>][<?php echo $sec_key; ?>]" value="1" <?php checked(!empty($visibility[$role_key][$sec_key])); ?>>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" name="sm_save_sidebar_visibility" class="sm-btn" style="width:auto; margin-top: 20px;">حفظ إعدادات القائمة</button>
                            </form>
                        </div>
                        <div id="school-structure" class="sm-internal-tab" style="display:none;">
                            <?php
                            $academic = SM_Settings::get_academic_structure();
                            $db_structure = SM_Settings::get_sections_from_db();
                            ?>
                            <form method="post" id="sm-academic-structure-form">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>

                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">التقويم الأكاديمي (UAE Framework)</h4>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:30px;">
                                    <?php for($i=1; $i<=3; $i++): ?>
                                    <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                                        <div style="font-weight:700; margin-bottom:10px; color:var(--sm-primary-color);">الفصل الدراسي <?php echo $i; ?></div>
                                        <div class="sm-form-group">
                                            <label class="sm-label" style="font-size:11px;">تاريخ البدء:</label>
                                            <input type="date" name="term_dates[term<?php echo $i; ?>][start]" value="<?php echo esc_attr($academic['term_dates']["term$i"]['start'] ?? ''); ?>" class="sm-input">
                                        </div>
                                        <div class="sm-form-group">
                                            <label class="sm-label" style="font-size:11px;">تاريخ الانتهاء:</label>
                                            <input type="date" name="term_dates[term<?php echo $i; ?>][end]" value="<?php echo esc_attr($academic['term_dates']["term$i"]['end'] ?? ''); ?>" class="sm-input">
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>

                                <h4 style="border-bottom:1px solid #eee; padding-bottom:10px;">المراحل التعليمية</h4>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:30px;">
                                    <?php foreach($academic['academic_stages'] as $index => $stage): ?>
                                    <div style="background:#fff; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                                        <div class="sm-form-group">
                                            <label class="sm-label">اسم المرحلة:</label>
                                            <input type="text" name="academic_stages[<?php echo $index; ?>][name]" value="<?php echo esc_attr($stage['name']); ?>" class="sm-input">
                                        </div>
                                        <div style="display:flex; gap:10px;">
                                            <div class="sm-form-group" style="flex:1;">
                                                <label class="sm-label">من صف:</label>
                                                <input type="number" name="academic_stages[<?php echo $index; ?>][start]" value="<?php echo esc_attr($stage['start']); ?>" class="sm-input">
                                            </div>
                                            <div class="sm-form-group" style="flex:1;">
                                                <label class="sm-label">إلى صف:</label>
                                                <input type="number" name="academic_stages[<?php echo $index; ?>][end]" value="<?php echo esc_attr($stage['end']); ?>" class="sm-input">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <h4 style="border-bottom:1px solid #eee; padding-bottom:10px;">إدارة الصفوف والشعب (تلقائي)</h4>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-bottom:30px;">
                                    <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0;">
                                        <div class="sm-form-group">
                                            <label class="sm-label">إجمالي عدد الصفوف:</label>
                                            <input type="number" name="grades_count" value="<?php echo esc_attr($academic['grades_count']); ?>" class="sm-input" min="1" max="15">
                                        </div>
                                        <div class="sm-form-group">
                                            <label class="sm-label">الصفوف النشطة:</label>
                                            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; background:#f8fafc; padding:10px; border-radius:8px;">
                                                <?php for($i=1; $i<=$academic['grades_count']; $i++): ?>
                                                <label style="font-size:12px; display:flex; align-items:center; gap:5px;">
                                                    <input type="checkbox" name="active_grades[]" value="<?php echo $i; ?>" <?php checked(in_array($i, $academic['active_grades'] ?? [])); ?>> صف <?php echo $i; ?>
                                                </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; grid-column: span 2;">
                                        <label class="sm-label">الشعب المسجلة لكل صف (تؤخذ من بيانات الطلاب):</label>
                                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; background:#f8fafc; padding:15px; border-radius:8px; max-height: 400px; overflow-y: auto;">
                                            <?php for($i=1; $i<=$academic['grades_count']; $i++):
                                                $sections = $db_structure[$i] ?? array();
                                            ?>
                                            <div style="border:1px solid #e2e8f0; padding:10px; border-radius:6px; background:white;">
                                                <div style="font-weight:700; margin-bottom:8px; font-size:12px; border-bottom:1px solid #eee; padding-bottom:5px;">الصف <?php echo $i; ?></div>
                                                <div style="font-size:11px; color:var(--sm-text-gray);">عدد الشعب: <strong><?php echo count($sections); ?></strong></div>
                                                <div style="font-size:11px; color:var(--sm-text-gray); margin-top:5px;">الرموز: <span style="color:var(--sm-primary-color); font-weight:700;"><?php echo !empty($sections) ? implode(', ', $sections) : '---'; ?></span></div>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                        <p style="font-size:11px; color:#718096; margin-top:10px;">ملاحظة: لا يمكن تعديل الشعب يدوياً، يتم تحديثها تلقائياً عند إضافة أو استيراد الطلاب.</p>
                                    </div>
                                </div>

                                <div style="background:#f0fff4; border:1px solid #c6f6d5; border-radius:8px; padding:15px; margin-bottom:25px;">
                                    <p style="margin:0; font-size:13px; color:#2f855a; font-weight:700;">💡 نظام التسمية الموحد:</p>
                                    <ul style="margin:10px 0 0 0; font-size:12px; color:#276749;">
                                        <li>التنسيق الكامل: <strong>الصف 12 شعبة أ</strong></li>
                                        <li>التنسيق المختصر: <strong>12 أ</strong></li>
                                    </ul>
                                </div>

                                <button type="submit" name="sm_save_academic_structure" class="sm-btn" style="width:auto; padding:0 40px; height:45px;">حفظ الهيكل المدرسي</button>
                            </form>
                        </div>

                        <div id="backup-settings" class="sm-internal-tab" style="display:none;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <h4 style="margin-top:0;">مركز النسخ الاحتياطي وإدارة البيانات</h4>
                                <?php $backup_info = SM_Settings::get_last_backup_info(); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر تصدير ناجح:</div>
                                        <div style="font-weight:700; color:var(--sm-primary-color);"><?php echo $backup_info['export']; ?></div>
                                    </div>
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر استيراد ناجح:</div>
                                        <div style="font-weight:700; color:var(--sm-secondary-color);"><?php echo $backup_info['import']; ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير البيانات الشاملة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">قم بتحميل نسخة كاملة من بيانات الطلاب والمخالفات بصيغة JSON.</p>
                                        <div style="display:flex; gap:10px;">
                                            <form method="post">
                                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                                <button type="submit" name="sm_download_backup" class="sm-btn" style="background:#27ae60; width:auto;">تصدير الآن (JSON)</button>
                                            </form>
                                            <form method="get" action="<?php echo admin_url('admin-ajax.php'); ?>">
                                                <input type="hidden" name="action" value="sm_export_violations_csv">
                                                <input type="hidden" name="range" value="all">
                                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sm_export_action'); ?>">
                                                <button type="submit" class="sm-btn" style="background:#111F35; width:auto;">سجل الانضباط الشامل (CSV)</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير سجلات طالب محدد</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">تصدير كافة مخالفات طالب معين باستخدام الكود الخاص به.</p>
                                        <form method="get" action="<?php echo admin_url('admin-ajax.php'); ?>" target="_blank">
                                            <input type="hidden" name="action" value="sm_export_violations_csv">
                                            <input type="hidden" name="range" value="all">
                                            <?php $ex_nonce = wp_create_nonce('sm_export_action'); ?>
                                            <input type="hidden" name="nonce" value="<?php echo $ex_nonce; ?>">
                                            <div class="sm-form-group">
                                                <input type="text" name="student_code" class="sm-input" placeholder="أدخل كود الطالب (مثال: ST00001)" required style="font-size:11px;">
                                            </div>
                                            <button type="submit" class="sm-btn" style="background:#3182ce; width:auto; font-size:11px;">تصدير سجلات الطالب</button>
                                        </form>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">استيراد البيانات</h5>
                                        <p style="font-size:12px; color:#e53e3e; margin-bottom:15px;">تحذير: سيقوم الاستيراد بمسح البيانات الحالية واستبدالها بالنسخة المرفوعة.</p>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                            <input type="file" name="backup_file" required style="margin-bottom:10px; font-size:11px;">
                                            <button type="submit" name="sm_restore_backup" class="sm-btn" style="background:#2980b9; width:auto;">بدء الاستيراد</button>
                                        </form>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">مسح البيانات المخصص</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">اختر القسم الذي تريد مسح كافة بياناته نهائياً:</p>
                                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                            <button onclick="smBulkDelete('students')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح الطلاب</button>
                                            <button onclick="smBulkDelete('teachers')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح المعلمين</button>
                                            <button onclick="smBulkDelete('parents')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح أولياء الأمور</button>
                                            <button onclick="smBulkDelete('records')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح المخالفات</button>
                                        </div>
                                    </div>
                                    <div style="background:#fff5f5; padding:20px; border-radius:8px; border:2px dashed #feb2b2;">
                                        <h5 style="margin-top:0; color:#c53030;">تهيأة النظام (إعادة ضبط المصنع)</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">هذا الإجراء سيقوم بمسح **كافة** البيانات من جميع الأقسام بما في ذلك الإعدادات والمستخدمين والطلاب.</p>
                                        <button onclick="smInitializeSystem()" class="sm-btn" style="background:#c53030; width:auto;">تهيأة النظام بالكامل</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($is_admin): ?>
                        <div id="activity-logs" class="sm-internal-tab" style="display:none;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <div>
                                        <h4 style="margin:0;">سجل نشاطات النظام الشامل</h4>
                                        <div style="font-size:12px; color:#718096; margin-top:5px;">يتم الاحتفاظ بآخر 200 نشاط فقط تلقائياً.</div>
                                    </div>
                                    <button onclick="smDeleteAllLogs()" class="sm-btn" style="background:#e53e3e; width:auto; font-size:12px;">مسح كافة النشاطات</button>
                                </div>
                                <div class="sm-table-container">
                                    <table class="sm-table">
                                        <thead>
                                            <tr>
                                                <th>الوقت</th>
                                                <th>المستخدم</th>
                                                <th>الإجراء</th>
                                                <th>التفاصيل</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $limit = 20;
                                            $page_num = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                                            $offset = ($page_num - 1) * $limit;
                                            $all_logs = SM_Logger::get_logs($limit, $offset);
                                            $total_logs = SM_Logger::get_total_logs();
                                            $total_pages = ceil($total_logs / $limit);

                                            foreach ($all_logs as $log):
                                                $can_rollback = strpos($log->details, 'ROLLBACK_DATA:') === 0;
                                                $details_display = $can_rollback ? 'بيانات مستعادة' : esc_html($log->details);
                                            ?>
                                                <tr>
                                                    <td style="font-size: 0.85em; color: #718096;"><?php echo esc_html($log->created_at); ?></td>
                                                    <td style="font-weight: 600;">
                                                        <?php echo esc_html($log->display_name ?: 'مستخدم غير معروف'); ?>
                                                    </td>
                                                    <td style="font-weight:700; color:var(--sm-primary-color);"><?php echo esc_html($log->action); ?></td>
                                                    <td style="font-size:0.9em;"><?php echo $details_display; ?></td>
                                                    <td>
                                                        <div style="display:flex; gap:8px;">
                                                            <?php if ($can_rollback): ?>
                                                                <button onclick="smRollbackLog(<?php echo $log->id; ?>)" class="sm-btn" style="width:auto; height:28px; padding:0 12px; font-size:11px; background:#2d3748;">استعادة</button>
                                                            <?php endif; ?>
                                                            <button onclick="smDeleteLog(<?php echo $log->id; ?>)" class="sm-btn" style="width:auto; height:28px; padding:0 12px; font-size:11px; background:#e53e3e;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages > 1): ?>
                                    <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                                        <?php if ($page_num > 1): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num - 1); ?>" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">السابق</a>
                                        <?php endif; ?>
                                        <span style="align-self:center; font-size:13px;">صفحة <?php echo $page_num; ?> من <?php echo $total_pages; ?></span>
                                        <?php if ($page_num < $total_pages): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num + 1); ?>" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">التالي</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php
                    }
                    break;

            }
            ?>

        </div>
    </div>
</div>

<!-- GLOBAL VIOLATION MODAL -->
<div id="sm-global-violation-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 800px;">
        <div class="sm-modal-header">
            <h3>تسجيل مخالفة جديدة</h3>
            <button class="sm-modal-close" onclick="smCloseViolationModal()">&times;</button>
        </div>
        <div id="sm-violation-modal-body">
            <?php include SM_PLUGIN_DIR . 'templates/system-form.php'; ?>
        </div>
    </div>
</div>

<style>
.sm-sidebar-item { border-bottom: 1px solid #e2e8f0; transition: 0.2s; }
.sm-sidebar-link { 
    padding: 15px 25px; 
    cursor: pointer; font-weight: 600; color: #4a5568 !important;
    display: flex; align-items: center; gap: 12px;
    text-decoration: none !important;
    width: 100%;
}
.sm-sidebar-item:hover { background: #edf2f7; }
.sm-sidebar-item.sm-active { 
    background: #fff !important; 
    border-right: 4px solid var(--sm-primary-color) !important; 
}
.sm-sidebar-item.sm-active .sm-sidebar-link {
    color: var(--sm-primary-color) !important;
}

.sm-sidebar-badge {
    position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
    background: #e53e3e; color: white; border-radius: 20px; padding: 2px 8px; font-size: 10px; font-weight: 800;
}

.sm-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--sm-dark-color) !important;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.sm-dropdown-item:hover { background: var(--sm-bg-light); color: var(--sm-primary-color) !important; }

@keyframes smFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORCE VISIBILITY FOR PANELS */
.sm-admin-dashboard .sm-main-tab-panel {
    width: 100% !important;
}
.sm-tab-btn { padding: 10px 20px; border: 1px solid #e2e8f0; background: #f8f9fa; cursor: pointer; border-radius: 5px 5px 0 0; }
.sm-tab-btn.sm-active { background: var(--sm-primary-color) !important; color: #fff !important; border-bottom: none; }
.sm-quick-btn { background: #48bb78 !important; color: white !important; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-block; }
.sm-refresh-btn { background: #718096; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.sm-logout-btn { background: #e53e3e; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; font-weight: 700; display: inline-block; }
</style>
