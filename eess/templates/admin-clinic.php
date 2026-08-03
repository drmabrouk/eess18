<?php
if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_staff_who_can_send = in_array('administrator', $roles) || current_user_can('manage_options') || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles);
$is_clinic_staff = in_array('sm_clinic', $roles) || in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles);

global $wpdb;

// Fetch pending referrals (arrival not confirmed)
$pending_referrals = $wpdb->get_results("
    SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
    FROM {$wpdb->prefix}sm_clinic c
    JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
    JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
    WHERE c.arrival_confirmed = 0
    ORDER BY c.created_at DESC
");

// Fetch history (arrival confirmed)
$history = $wpdb->get_results("
    SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
    FROM {$wpdb->prefix}sm_clinic c
    JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
    JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
    WHERE c.arrival_confirmed = 1
    ORDER BY c.created_at DESC
    LIMIT 100
");
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <!-- Title Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 22px;">العيادة المدرسية والخدمات الطبية</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">متابعة الحالات الصحية للطلاب، تحويل الحالات الطارئة، وإدارة تقارير العيادة الدورية</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($is_staff_who_can_send): ?>
                <button onclick="document.getElementById('referral-modal').style.display='flex'" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700;">
                    <span class="dashicons dashicons-plus-alt" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> تحويل جديد للعيادة
                </button>
            <?php endif; ?>

            <?php if ($is_clinic_staff): ?>
                <div class="sm-dropdown" style="position: relative;">
                    <button class="sm-btn sm-btn-secondary" style="height: 40px; font-size: 12px; font-weight: 700;" onclick="toggleClinicReportDropdown()">
                        <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> تحميل التقارير الطبية <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin-right: 5px;"></span>
                    </button>
                    <div id="clinic-report-menu" style="display: none; position: absolute; top: 105%; left: 0; background: white; border: 1px solid var(--sm-border-color); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 100; min-width: 180px; padding: 5px 0;">
                        <?php $c_nonce = wp_create_nonce('sm_clinic_action'); ?>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=day&nonce='.$c_nonce); ?>" class="sm-dropdown-item">تقرير اليوم المالي</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=week&nonce='.$c_nonce); ?>" class="sm-dropdown-item">تقرير الأسبوع</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=month&nonce='.$c_nonce); ?>" class="sm-dropdown-item">تقرير الشهر الكامل</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=term&nonce='.$c_nonce); ?>" class="sm-dropdown-item">تقرير الفصل الدراسي</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=year&nonce='.$c_nonce); ?>" class="sm-dropdown-item">تقرير السنة</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PENDING REFERRALS -->
    <div style="margin-bottom: 40px; background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 24px; box-shadow: var(--sm-shadow);">
        <div style="display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 20px;">
            <span class="dashicons dashicons-warning" style="color: #ef4444; font-size: 18px; width: 18px; height: 18px;"></span>
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--sm-dark-color);">الطلاب المحولون قيد الانتظار (بانتظار وصولهم للعيادة)</h3>
        </div>

        <?php if (empty($pending_referrals)): ?>
            <div style="padding: 40px; text-align: center; color: var(--sm-text-gray); font-weight: 700; font-size: 13px;">لا يوجد أي طلاب محولين بانتظار الوصول حالياً.</div>
        <?php else: ?>
            <div class="sm-table-container" style="box-shadow: none; border-radius: 8px; margin-bottom: 0;">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th style="font-weight: 700; width: 100px;">وقت التحويل</th>
                            <th style="font-weight: 700;">الطالب المحول</th>
                            <th style="font-weight: 700;">الموظف المحيل</th>
                            <th style="text-align: left; padding-left: 20px; font-weight: 700; width: 180px;">الإجراءات الطبية</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_referrals as $r): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--sm-dark-color); font-size: 13px;"><?php echo date('H:i', strtotime($r->created_at)); ?></td>
                                <td>
                                    <div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($r->student_name); ?></div>
                                    <div style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700; margin-top: 3px;"><?php echo $r->class_name . ' - ' . $r->section; ?></div>
                                </td>
                                <td style="font-weight: 700; color: var(--sm-secondary-color);"><?php echo esc_html($r->referrer_name); ?></td>
                                <td style="text-align: left; padding-left: 20px;">
                                    <?php if ($is_clinic_staff): ?>
                                        <button onclick="confirmClinicArrival(<?php echo $r->id; ?>)" class="sm-btn" style="background: #10b981; font-size: 11px; padding: 0 12px; height: 28px; font-weight: 700;">✓ تأكيد الوصول للعيادة</button>
                                    <?php else: ?>
                                        <span class="sm-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">بانتظار الوصول</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- HISTORY -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 24px; box-shadow: var(--sm-shadow);">
        <div style="display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 20px;">
            <span class="dashicons dashicons-welcome-write-blog" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--sm-dark-color);">سجل الزيارات اليومية المكتملة للعيادة</h3>
        </div>

        <?php if (empty($history)): ?>
            <div style="padding: 40px; text-align: center; color: var(--sm-text-gray); font-weight: 700; font-size: 13px;">لا يوجد سجلات زيارات قديمة ومكتملة.</div>
        <?php else: ?>
            <div class="sm-table-container" style="box-shadow: none; border-radius: 8px; margin-bottom: 0;">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">الطالب</th>
                            <th style="font-weight: 700; width: 150px;">وقت الوصول المعتمد</th>
                            <th style="font-weight: 700;">الحالة الصحية والشكوى</th>
                            <th style="font-weight: 700;">الإجراء الطبي والعلاج</th>
                            <th style="text-align: left; padding-left: 20px; font-weight: 700; width: 80px;">تعديل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($h->student_name); ?></div>
                                    <div style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700; margin-top: 3px;"><?php echo $h->class_name . ' - ' . $h->section; ?></div>
                                </td>
                                <td style="font-weight: 600; color: var(--sm-secondary-color); font-size: 12px;"><?php echo date('Y-m-d H:i', strtotime($h->arrival_at)); ?></td>
                                <td style="max-width: 250px; font-size: 12px; color: var(--sm-dark-color); font-weight: 600; line-height: 1.4;"><?php echo esc_html($h->health_condition ?: '---'); ?></td>
                                <td style="max-width: 250px; font-size: 12px; color: var(--sm-dark-color); font-weight: 600; line-height: 1.4;"><?php echo esc_html($h->action_taken ?: '---'); ?></td>
                                <td style="text-align: left; padding-left: 20px;">
                                    <?php if ($is_clinic_staff): ?>
                                        <button onclick="openClinicEditModal(<?php echo htmlspecialchars(json_encode($h)); ?>)" class="sm-btn sm-btn-outline" style="padding: 0; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; background: #fff;" title="تحديث السجل الصحي"><span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Referral Modal Dialog -->
<div id="referral-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 450px; padding: 25px;">
        <div class="sm-modal-header">
            <h3 style="font-size: 16px; font-weight: 800;">تحويل طالب للعيادة المدرسية</h3>
            <button class="sm-modal-close" onclick="document.getElementById('referral-modal').style.display='none'">&times;</button>
        </div>
        <div class="sm-form-group">
            <label class="sm-label">ابحث عن الطالب المراد تحويله:</label>
            <input type="text" id="clinic-student-search" class="sm-input" placeholder="اكتب اسم الطالب أو كود التحضير..." onkeyup="clinicSearchStudents(this.value)" style="height: 38px; font-size: 12px;">
            <div id="clinic-search-results" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); max-height: 200px; overflow-y: auto; display: none; margin-top: 5px;"></div>
        </div>
        <div id="selected-student-box" style="display: none; background: #f0fdf4; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px;">
            <div style="font-weight: 800; color: #166534;" id="selected-student-name"></div>
            <div style="font-size: 11px; color: #15803d; font-weight: 700; margin-top: 3px;" id="selected-student-info"></div>
            <input type="hidden" id="selected-student-id">
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <button onclick="submitClinicReferral()" class="sm-btn" style="background: var(--sm-primary-color); height: 36px; font-size: 12px;">إرسال التحويل للعيادة</button>
            <button onclick="document.getElementById('referral-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 36px; font-size: 12px;">إلغاء</button>
        </div>
    </div>
</div>

<!-- Clinic Record Edit Modal Dialog -->
<div id="clinic-edit-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 500px; padding: 25px;">
        <div class="sm-modal-header">
            <h3 style="font-size: 16px; font-weight: 800;">تحديث وتشخيص السجل الصحي للطالب</h3>
            <button class="sm-modal-close" onclick="document.getElementById('clinic-edit-modal').style.display='none'">&times;</button>
        </div>
        <input type="hidden" id="edit-referral-id">
        <div class="sm-form-group">
            <label class="sm-label">الحالة الصحية المرصودة / الشكوى بالتفصيل:</label>
            <textarea id="edit-health-condition" class="sm-textarea" rows="3" placeholder="مثال: صداع خفيف مع ارتفاع درجة الحرارة..." style="font-size: 12px;"></textarea>
        </div>
        <div class="sm-form-group">
            <label class="sm-label">الإجراء الطبي المتخذ / العلاج المقدم:</label>
            <textarea id="edit-action-taken" class="sm-textarea" rows="3" placeholder="مثال: إعطاء خافض حرارة وإرسال الطالب للراحة..." style="font-size: 12px;"></textarea>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <button onclick="submitClinicUpdate()" class="sm-btn" style="background: #10b981; height: 36px; font-size: 12px;">حفظ وتحديث السجل الآن</button>
            <button onclick="document.getElementById('clinic-edit-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 36px; font-size: 12px;">إلغاء النافذة</button>
        </div>
    </div>
</div>

<script>
function toggleClinicReportDropdown() {
    const menu = document.getElementById('clinic-report-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function clinicSearchStudents(query) {
    if (query.length < 2) {
        document.getElementById('clinic-search-results').style.display = 'none';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_search_students');
    formData.append('query', query);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            let html = '';
            res.data.forEach(s => {
                html += `<div style="padding: 10px 15px; border-bottom: 1px solid var(--sm-border-color); cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='var(--sm-bg-light)'" onmouseout="this.style.background='#fff'" onclick="selectClinicStudent(${s.id}, '${s.name}', '${s.class_name} ${s.section}')">
                            <strong style="color: var(--sm-dark-color); font-size: 13px;">${s.name}</strong> (${s.student_code})<br><small style="color: var(--sm-text-gray); font-weight:700;">${s.class_name} - ${s.section}</small>
                         </div>`;
            });
            document.getElementById('clinic-search-results').innerHTML = html;
            document.getElementById('clinic-search-results').style.display = 'block';
        }
    });
}

function selectClinicStudent(id, name, info) {
    document.getElementById('selected-student-id').value = id;
    document.getElementById('selected-student-name').innerText = name;
    document.getElementById('selected-student-info').innerText = info;
    document.getElementById('selected-student-box').style.display = 'block';
    document.getElementById('clinic-search-results').style.display = 'none';
    document.getElementById('clinic-student-search').value = '';
}

function submitClinicReferral() {
    const id = document.getElementById('selected-student-id').value;
    if (!id) { alert('يرجى اختيار طالب أولاً'); return; }

    const formData = new FormData();
    formData.append('action', 'sm_add_clinic_referral');
    formData.append('student_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_clinic_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تمت إحالة الطالب للعيادة بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            smShowNotification('خطأ في الإحالة', true);
        }
    });
}

function confirmClinicArrival(referralId) {
    const formData = new FormData();
    formData.append('action', 'sm_confirm_clinic_arrival');
    formData.append('referral_id', referralId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_clinic_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم تأكيد وصول الطالب للعيادة الطبية');
            setTimeout(() => location.reload(), 500);
        } else {
            smShowNotification('خطأ في التأكيد', true);
        }
    });
}

function openClinicEditModal(data) {
    document.getElementById('edit-referral-id').value = data.id;
    document.getElementById('edit-health-condition').value = data.health_condition || '';
    document.getElementById('edit-action-taken').value = data.action_taken || '';
    document.getElementById('clinic-edit-modal').style.display = 'flex';
}

function submitClinicUpdate() {
    const id = document.getElementById('edit-referral-id').value;
    const cond = document.getElementById('edit-health-condition').value;
    const act = document.getElementById('edit-action-taken').value;

    const formData = new FormData();
    formData.append('action', 'sm_update_clinic_record');
    formData.append('referral_id', id);
    formData.append('health_condition', cond);
    formData.append('action_taken', act);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_clinic_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ وتحديث الملف الطبي للطالب بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            smShowNotification('خطأ في الحفظ والتحديث', true);
        }
    });
}

document.addEventListener('click', function(e) {
    const results = document.getElementById('clinic-search-results');
    if (results && !results.contains(e.target) && e.target.id !== 'clinic-student-search') {
        results.style.display = 'none';
    }
});
</script>
