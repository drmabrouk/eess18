<?php if (!defined('ABSPATH')) exit;
$school = SM_Settings::get_school_info();
$academic = SM_Settings::get_academic_structure();
?>
<div class="sm-class-attendance-shortcode" dir="rtl" style="max-width: 850px; margin: 30px auto; padding: 35px; background: #ffffff; border-radius: 16px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow); font-family: 'Cairo', sans-serif;">

    <!-- Header: Logo, Title, Date -->
    <div style="text-align: center; margin-bottom: 35px;">
        <?php if (!empty($school['school_logo'])): ?>
            <img src="<?php echo esc_url($school['school_logo']); ?>" style="height: 70px; width: auto; margin-bottom: 15px; object-fit: contain;">
        <?php endif; ?>

        <h1 style="font-weight: 800; color: var(--sm-dark-color); margin: 0 0 10px 0; font-size: 22px; border: none; padding: 0;">تسجيل حضور ومواظبة الفصول اليومي</h1>
        <p style="margin: 0 0 20px 0; font-size: 13px; color: var(--sm-text-gray); font-weight: 600;">المنصة الوطنية للتقييد الفوري ورصد الغياب اليومي وحالات التأخر للطلاب</p>

        <div style="display: inline-block; padding: 6px 20px; background: var(--sm-bg-light); color: var(--sm-primary-color); border-radius: 9999px; font-weight: 800; font-size: 12px; border: 1px solid var(--sm-border-color);">
            📅 اليوم: <?php echo date_i18n('l، j F Y'); ?>
        </div>
    </div>

    <!-- Selection: Grade & Section -->
    <?php
    $is_staff = is_user_logged_in() && (current_user_can('إدارة_الطلاب') || current_user_can('تسجيل_مخالفة'));
    ?>
    <div id="at-selection-area" style="display: grid; grid-template-columns: <?php echo $is_staff ? '1fr 1fr 1fr' : '1fr'; ?>; gap: 15px; margin-bottom: 30px; background: var(--sm-bg-light); padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color); align-items: flex-end;">
        <?php if ($is_staff): ?>
        <div class="sm-form-group" style="margin-bottom: 0;">
            <label class="sm-label" style="margin-bottom: 6px; font-weight: 800;">الصف الدراسي:</label>
            <select id="at-grade-select" class="sm-select" style="height: 38px; font-size: 12px; background: #fff;" onchange="atUpdateSections()">
                <option value="">-- اختر الصف --</option>
                <?php
                $active_grades = $academic['active_grades'] ?? array();
                sort($active_grades, SORT_NUMERIC);
                foreach ($active_grades as $grade_num): ?>
                    <option value="الصف <?php echo $grade_num; ?>" data-grade-num="<?php echo $grade_num; ?>">الصف <?php echo $grade_num; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm-form-group" style="margin-bottom: 0;">
            <label class="sm-label" style="margin-bottom: 6px; font-weight: 800;">الشعبة / الفصل:</label>
            <select id="at-section-select" class="sm-select" style="height: 38px; font-size: 12px; background: #fff;" disabled onchange="atLoadStudents()">
                <option value="">-- اختر الشعبة --</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="sm-form-group" style="margin-bottom: 0; text-align: center;">
            <label class="sm-label" style="margin-bottom: 6px; font-weight: 800; display: block; text-align: center;">كود التحقق والتحضير للفصل:</label>
            <input type="text" id="at-security-code" class="sm-input" maxlength="4" style="height: 38px; font-size: 18px; text-align: center; letter-spacing: 4px; font-family: monospace; max-width: 150px; margin: 0 auto; font-weight: 800;" placeholder="0000" oninput="checkSecurityCode()">
            <?php if (!$is_staff): ?>
                <div style="font-size: 10px; color: var(--sm-text-gray); margin-top: 6px; font-weight: 600;">أدخل رمز التحقق (4 أرقام) لرصد الحضور</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Students List Area -->
    <div id="at-students-container" style="display: none;">
        <div id="at-list-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid var(--sm-border-color); padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div style="font-weight: 800; color: var(--sm-dark-color); font-size: 13px;">👥 طلاب الفصل المستهدف:</div>
            <div id="at-bulk-actions" style="display: flex; gap: 10px;">
                <button onclick="atSetAll('present')" class="sm-btn" style="height: 30px; line-height: 28px; width: auto; padding: 0 15px; font-size: 11px; background: #16a34a; border-color: #16a34a; font-weight: 700;">✓ رصد حضور جميع الطلاب</button>
            </div>
        </div>

        <div id="at-students-list" style="margin-bottom: 30px; display: flex; flex-direction: column; gap: 12px;">
            <!-- Loaded via AJAX -->
        </div>

        <div id="at-footer-actions" style="text-align: center; padding-top: 25px; border-top: 1px solid var(--sm-border-color);">
            <button id="at-submit-btn" onclick="atSubmitAttendance()" class="sm-btn" style="width: 100%; height: 44px; font-size: 13px; font-weight: 800; border-radius: 8px;">تأكيد وحفظ الكشف النهائي وإرساله للنظام</button>
            <p id="at-post-submit-note" style="display: none; margin-top: 15px; color: var(--sm-text-gray); font-weight: 700; font-size: 11px;">
                <span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; color: var(--sm-primary-color);"></span>
                تم إرسال كشف الغياب والحضور للنظام بنجاح. يمكنك الآن تعديل حالات الغياب أو التأخير المنفردة فقط في أي وقت.
            </p>
        </div>
    </div>

    <div id="at-no-selection" style="text-align: center; padding: 60px 20px; color: var(--sm-text-gray); background: var(--sm-bg-light); border-radius: 12px; border: 2px dashed var(--sm-border-color);">
        <span class="dashicons dashicons-id-alt" style="font-size: 36px; width: 36px; height: 36px; margin-bottom: 12px; color: var(--sm-text-gray);"></span>
        <h3 style="margin: 0; color: var(--sm-dark-color); font-size: 13px; font-weight: 800; border: none; padding: 0;">يرجى تحديد الصف والشعبة للمتابعة</h3>
        <p style="margin: 5px 0 0 0; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">سيتم جلب قائمة طلاب الصف والتحقق من كود الأمان المعتمد تلقائياً.</p>
    </div>
</div>

<script>
const dbStructure = <?php echo json_encode(SM_Settings::get_sections_from_db()); ?>;
let isSubmitted = false;
let currentStudents = [];
let isAuthorized = false;

window.smShowNotification = function(msg, isError = false) {
    if (typeof window.smShowNotification === 'function') {
        window.smShowNotification(msg, isError);
        return;
    }
    const n = document.createElement('div');
    n.style.cssText = `position:fixed; bottom:20px; left:20px; background:${isError?'#dc2626':'#3b82f6'}; color:#fff; padding:12px 20px; border-radius:8px; z-index:10000; font-weight:800; font-size:12px; font-family:'Cairo',sans-serif; box-shadow: 0 10px 15px rgba(0,0,0,0.1);`;
    n.innerText = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 4000);
};

window.checkSecurityCode = function() {
    const isStaff = <?php echo $is_staff ? 'true' : 'false'; ?>;
    const inputCode = document.getElementById('at-security-code').value;

    if (inputCode.length !== 4) {
        isAuthorized = false;
        document.getElementById('at-security-code').style.borderColor = '';
        document.getElementById('at-security-code').style.background = '';
        if (!isStaff) {
            document.getElementById('at-students-container').style.display = 'none';
            document.getElementById('at-no-selection').style.display = 'block';
        }
        return;
    }

    if (isStaff) {
        const gradeSelect = document.getElementById('at-grade-select');
        const sectionSelect = document.getElementById('at-section-select');
        if (!gradeSelect || !sectionSelect) return;

        const className = gradeSelect.value;
        const section = sectionSelect.value;
        if (!className || !section) return;

        verifyCodeAndLoad(className, section, inputCode);
    } else {
        verifyCodeAndLoad('', '', inputCode);
    }
};

window.verifyCodeAndLoad = function(className, section, code) {
    const container = document.getElementById('at-students-container');
    const noSel = document.getElementById('at-no-selection');

    const date = new Date().toISOString().split('T')[0];
    const formData = new FormData();
    formData.append('action', 'sm_get_students_attendance_ajax');
    if (className) formData.append('class_name', className);
    if (section) formData.append('section', section);
    formData.append('date', date);
    formData.append('security_code', code);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            isAuthorized = true;
            document.getElementById('at-security-code').style.borderColor = '#16a34a';
            document.getElementById('at-security-code').style.background = '#f0fdf4';

            currentStudents = res.data;
            noSel.style.display = 'none';
            container.style.display = 'block';
            atRenderList();
        } else {
            isAuthorized = false;
            document.getElementById('at-security-code').style.borderColor = '#dc2626';

            if (className === '') {
                container.style.display = 'none';
                noSel.style.display = 'block';
            }
        }
    });
};

window.atUpdateSections = function() {
    const gradeSelect = document.getElementById('at-grade-select');
    const sectionSelect = document.getElementById('at-section-select');
    const gradeNum = gradeSelect.options[gradeSelect.selectedIndex].getAttribute('data-grade-num');

    sectionSelect.innerHTML = '<option value="">-- اختر الشعبة --</option>';
    isSubmitted = false;

    if (!gradeNum) {
        sectionSelect.disabled = true;
        document.getElementById('at-students-container').style.display = 'none';
        document.getElementById('at-no-selection').style.display = 'block';
        return;
    }

    const sections = dbStructure[gradeNum] || [];
    sections.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.innerText = 'شعبة ' + s;
        sectionSelect.appendChild(opt);
    });

    sectionSelect.disabled = false;
    document.getElementById('at-students-container').style.display = 'none';
    document.getElementById('at-no-selection').style.display = 'block';
};

window.atLoadStudents = function() {
    const gradeSelect = document.getElementById('at-grade-select');
    const sectionSelect = document.getElementById('at-section-select');
    if (!gradeSelect || !sectionSelect) return;

    const className = gradeSelect.value;
    const section = sectionSelect.value;

    const codeInput = document.getElementById('at-security-code');
    if (codeInput) {
        codeInput.value = '';
        checkSecurityCode();
    }

    const container = document.getElementById('at-students-container');
    const noSel = document.getElementById('at-no-selection');

    if (!className || !section) {
        container.style.display = 'none';
        noSel.style.display = 'block';
        return;
    }

    container.style.display = 'none';
    noSel.style.display = 'block';
};

window.atRenderList = function() {
    const listContainer = document.getElementById('at-students-list');
    const bulkArea = document.getElementById('at-bulk-actions');
    const submitBtn = document.getElementById('at-submit-btn');
    const note = document.getElementById('at-post-submit-note');

    if (currentStudents.length === 0) {
        listContainer.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--sm-text-gray); font-size:11px; font-weight:700;">لا يوجد طلاب مسجلون في هذا الصف والشعبة حالياً.</div>';
        return;
    }

    if (isSubmitted) {
        bulkArea.style.display = 'none';
        submitBtn.style.display = 'none';
        note.style.display = 'block';
    } else {
        bulkArea.style.display = 'flex';
        submitBtn.style.display = 'block';
        note.style.display = 'none';
    }

    let html = '';
    currentStudents.forEach(s => {
        const photo = s.photo_url ? `<img src="${s.photo_url}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--sm-border-color); flex-shrink: 0;">` : `<div style="width: 44px; height: 44px; border-radius: 50%; background: var(--sm-bg-light); border: 1px solid var(--sm-border-color); display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--sm-text-gray); flex-shrink: 0;">👤</div>`;

        const status = s.status || 'present';

        if (isSubmitted && status === 'present') return;

        html += `
            <div class="at-student-row animated fadeIn" data-student-id="${s.id}" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; border: 1px solid var(--sm-border-color); border-radius: 12px; background: #ffffff; transition: all 0.2s ease; gap: 15px; flex-wrap: wrap; box-shadow: var(--sm-shadow);">
                <div style="display: flex; align-items: center; gap: 15px; min-width: 180px;">
                    ${photo}
                    <div>
                        <div style="font-weight: 800; font-size: 12px; color: var(--sm-dark-color);">${s.name}</div>
                        <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700; margin-top: 3px;">كود الطالب: ${s.student_code}</div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <div style="display: flex; gap: 6px;">
                        ${!isSubmitted ? `
                            <button onclick="atSetStatus(this, 'present')" class="at-choice-btn ${status === 'present' ? 'active' : ''}" data-status="present" title="حاضر">
                                <span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; margin:0;"></span>
                                <span class="btn-lbl">حاضر</span>
                            </button>
                        ` : ''}
                        <button onclick="atSetStatus(this, 'late')" class="at-choice-btn ${status === 'late' ? 'active' : ''}" data-status="late" title="متأخر">
                            <span class="dashicons dashicons-clock" style="font-size:14px; width:14px; height:14px; margin:0;"></span>
                            <span class="btn-lbl">تأخير</span>
                        </button>
                        <button onclick="atSetStatus(this, 'absent')" class="at-choice-btn ${status === 'absent' ? 'active' : ''}" data-status="absent" title="غائب">
                            <span class="dashicons dashicons-no" style="font-size:14px; width:14px; height:14px; margin:0;"></span>
                            <span class="btn-lbl">غياب</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    listContainer.innerHTML = html;
};

window.atSetStatus = function(btn, status) {
    const row = btn.closest('.at-student-row');
    const sid = row.getAttribute('data-student-id');

    const stu = currentStudents.find(s => s.id == sid);
    if (stu) stu.status = status;

    row.querySelectorAll('.at-choice-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (isSubmitted) {
        saveAttendanceToDB(sid, status);
    }
};

window.atSetAll = function(status) {
    currentStudents.forEach(s => s.status = status);
    atRenderList();
};

window.atSubmitAttendance = function() {
    const btn = document.getElementById('at-submit-btn');
    const date = new Date().toISOString().split('T')[0];
    const nonce = '<?php echo wp_create_nonce("sm_attendance_action"); ?>';

    btn.disabled = true;
    btn.innerHTML = '<div class="at-spinner-sm"></div> جاري حفظ الكشف وإرسال الإشعارات...';

    const batch = currentStudents.map(s => ({
        student_id: s.id,
        status: s.status || 'present'
    }));

    const code = document.getElementById('at-security-code').value;
    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_batch_ajax');
    formData.append('batch', JSON.stringify(batch));
    formData.append('date', date);
    formData.append('nonce', nonce);
    formData.append('security_code', code);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ كشف حضور الفصل بنجاح عبر النظام');

            const confirmNotif = document.createElement('div');
            confirmNotif.style.cssText = "position:fixed; top:80px; left:50%; transform:translateX(-50%); background:#16a34a; color:white; padding:12px 25px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.2); z-index:10002; font-weight:800; font-family:'Cairo',sans-serif; font-size:12px; animation: smFadeIn 0.3s ease-out;";
            confirmNotif.innerHTML = '✅ تم إرسال وحفظ كشف الحضور بنجاح';
            document.body.appendChild(confirmNotif);
            setTimeout(() => {
                confirmNotif.style.opacity = '0';
                confirmNotif.style.transition = '0.5s';
                setTimeout(() => confirmNotif.remove(), 500);
            }, 3000);

            isSubmitted = true;
            atRenderList();
        } else {
            smShowNotification('حدث خطأ في الاتصال بالنظام: ' + res.data, true);
            btn.disabled = false;
            btn.innerText = 'تأكيد وإرسال الكشف للنظام';
        }
    })
    .catch(err => {
        smShowNotification('حدث خطأ تقني في الاتصال', true);
        btn.disabled = false;
        btn.innerText = 'تأكيد وإرسال الكشف للنظام';
    });
};

window.saveAttendanceToDB = function(sid, status) {
    const date = new Date().toISOString().split('T')[0];
    const code = document.getElementById('at-security-code').value;
    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_ajax');
    formData.append('student_id', sid);
    formData.append('status', status);
    formData.append('date', date);
    formData.append('security_code', code);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم تحديث حالة الطالب السلوكية بنجاح');
            atRenderList();
        }
    });
};
</script>

<style>
.at-spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; margin-left: 8px; vertical-align: middle; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.at-student-row:hover { border-color: var(--sm-primary-color); transform: translateY(-2px); }

.at-choice-btn {
    height: 32px; min-width: 75px; padding: 0 12px;
    border-radius: 6px; border: 1px solid var(--sm-border-color); background: #ffffff;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    transition: all 0.2s ease; color: var(--sm-text-gray); font-weight: 700; font-size: 11px;
}
.at-choice-btn[data-status="present"].active { background: #16a34a; color: #fff; border-color: #16a34a; }
.at-choice-btn[data-status="late"].active { background: #d97706; color: #fff; border-color: #d97706; }
.at-choice-btn[data-status="absent"].active { background: #dc2626; color: #fff; border-color: #dc2626; }

.animated { animation-duration: 0.3s; animation-fill-mode: both; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.fadeIn { animation-name: fadeIn; }

@media (max-width: 600px) {
    .sm-class-attendance-shortcode { padding: 20px; }
    .at-student-row { flex-direction: column; gap: 10px; align-items: flex-start; }
    .at-student-row > div:last-child { width: 100%; justify-content: flex-end; }
    .at-choice-btn { flex: 1; min-width: 0; }
    .btn-lbl { display: none; }
}
</style>
