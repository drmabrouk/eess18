<?php
if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$roles = (array) $user->roles;
$can_manage = current_user_can('manage_grades') || current_user_can('manage_options');

if (!$can_manage) {
    echo '<p style="padding:40px; text-align:center; font-weight:700; color:var(--sm-text-gray);">غير مسموح لك بالوصول لهذه الصفحة.</p>';
    return;
}

$students = SM_DB::get_students();
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 22px;">إدارة ورصد الدرجات والنتائج الأكاديمية</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">رصد درجات الاختبارات، تقييم الفصول الدراسية الثلاثة، ومتابعة التحصيل الدراسي للطلاب</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid var(--sm-border-color); padding-bottom: 5px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('individual-grading', this)">الرصد الفردي للدرجات</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('class-grading', this)">الرصد الجماعي (حسب الصف)</button>
        <?php if (current_user_can('إدارة_النظام')): ?>
            <button class="sm-tab-btn" onclick="smOpenInternalTab('subject-mgmt', this)">إدارة المواد الدراسية</button>
        <?php endif; ?>
    </div>

    <!-- Individual Grading Tab -->
    <div id="individual-grading" class="sm-internal-tab">
        <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; box-shadow: var(--sm-shadow);">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">اختر الطالب:</label>
                    <select id="grade-student-id" class="sm-select" style="height: 40px; font-size: 12px;">
                        <option value="">-- اختر طالب من القائمة --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->name); ?> (<?php echo $s->class_name; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">المادة الدراسية:</label>
                    <select id="grade-subject" class="sm-select" style="height: 40px; font-size: 12px;">
                        <option value="">-- اختر المادة --</option>
                        <?php
                        $subjects_all = SM_DB::get_subjects();
                        $unique_subjects = array_unique(array_column($subjects_all, 'name'));
                        foreach ($unique_subjects as $subname) echo '<option value="'.$subname.'">'.$subname.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الفصل الدراسي:</label>
                    <select id="grade-term" class="sm-select" style="height: 40px; font-size: 12px;">
                        <option value="الفصل الأول">الفصل الدراسي الأول</option>
                        <option value="الفصل الثاني">الفصل الدراسي الثاني</option>
                        <option value="الفصل الثالث">الفصل الدراسي الثالث</option>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الدرجة المرصودة:</label>
                    <input type="text" id="grade-val" class="sm-input" placeholder="مثال: 95" style="height: 40px; font-size: 12px;">
                </div>
                <button onclick="saveStudentGrade()" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700; width: 120px;">رصد الدرجة</button>
            </div>
        </div>

        <div id="grades-table-container">
            <div style="padding: 50px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray); box-shadow: var(--sm-shadow);">
                <span class="dashicons dashicons-search" style="font-size: 40px; width:40px; height:40px; margin-bottom:10px; color: var(--sm-text-gray);"></span>
                <p style="font-weight: 700; font-size: 13px; margin: 0;">يرجى اختيار طالب من القائمة بالأعلى لعرض وتحرير درجاته.</p>
            </div>
        </div>
    </div>

    <!-- Batch Grading Tab -->
    <div id="class-grading" class="sm-internal-tab" style="display:none;">
        <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; box-shadow: var(--sm-shadow);">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الصف الدراسي:</label>
                    <select id="batch-class" class="sm-select" onchange="loadBatchStudents()" style="height: 40px; font-size: 12px;">
                        <option value="">-- اختر الصف --</option>
                        <?php
                        global $wpdb;
                        $classes = $wpdb->get_results("SELECT DISTINCT class_name FROM {$wpdb->prefix}sm_students ORDER BY class_name ASC");
                        foreach ($classes as $c) echo '<option value="'.$c->class_name.'">'.$c->class_name.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">المادة الدراسية:</label>
                    <select id="batch-subject" class="sm-select" style="height: 40px; font-size: 12px;">
                        <option value="">-- اختر المادة --</option>
                        <?php
                        foreach ($unique_subjects as $subname) echo '<option value="'.$subname.'">'.$subname.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الفصل الدراسي:</label>
                    <select id="batch-term" class="sm-select" style="height: 40px; font-size: 12px;">
                        <option value="الفصل الأول">الفصل الدراسي الأول</option>
                        <option value="الفصل الثاني">الفصل الدراسي الثاني</option>
                        <option value="الفصل الثالث">الفصل الدراسي الثالث</option>
                    </select>
                </div>
                <button onclick="saveBatchGrades()" class="sm-btn" style="height: 40px; width: 140px; font-weight: 700; font-size: 12px;">حفظ درجات الصف</button>
            </div>
        </div>
        <div id="batch-students-container"></div>
    </div>

    <!-- Subjects Management Tab -->
    <?php if (current_user_can('إدارة_النظام')): ?>
    <div id="subject-mgmt" class="sm-internal-tab" style="display:none;">
        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px;">
            <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
                <h4 style="margin-top:0; font-size: 14px; font-weight: 800; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 10px; margin-bottom: 15px; color: var(--sm-dark-color);">إضافة مادة جديدة</h4>
                <div class="sm-form-group">
                    <label class="sm-label">اسم المادة بالكامل:</label>
                    <input type="text" id="new-subject-name" class="sm-input" placeholder="مثال: الرياضيات المتقدمة" style="height: 38px; font-size: 12px;">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تطبيق على الصفوف (متعدد):</label>
                    <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; max-height: 200px; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <label style="font-size: 11px; display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; color: var(--sm-dark-color);">
                                <input type="checkbox" class="new-subject-grade-check" value="<?php echo $i; ?>"> صف <?php echo $i; ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <button onclick="addSubject()" class="sm-btn" style="width:100%; height: 38px; font-size: 12px; font-weight: 700;">إضافة المادة المقترحة</button>
            </div>
            <div id="subjects-list-container">
                <!-- Loaded dynamically via JS -->
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('grade-student-id').addEventListener('change', function() {
    loadStudentGrades(this.value);
});

function loadStudentGrades(studentId) {
    if (!studentId) {
        document.getElementById('grades-table-container').innerHTML = '<div style="padding: 50px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray); box-shadow: var(--sm-shadow);"><span class="dashicons dashicons-search" style="font-size: 40px; width:40px; height:40px; margin-bottom:10px;"></span><p style="font-weight:700; font-size:13px; margin:0;">يرجى اختيار طالب من القائمة بالأعلى لعرض وتحرير درجاته.</p></div>';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_get_student_grades_ajax');
    formData.append('student_id', studentId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            renderGradesTable(res.data);
        }
    });
}

function loadBatchStudents() {
    const className = document.getElementById('batch-class').value;
    if (!className) return;

    const container = document.getElementById('batch-students-container');
    container.innerHTML = '<div style="padding:30px; text-align:center; font-weight:700; color:var(--sm-text-gray);">جاري تحميل قائمة الطلاب...</div>';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_search_students&query=' + encodeURIComponent(className))
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (res.data.length === 0) {
                container.innerHTML = '<div style="padding: 40px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray); box-shadow: var(--sm-shadow);"><p style="font-weight:700; font-size:13px; margin:0;">لا يوجد طلاب مقيدين في هذا الصف حالياً.</p></div>';
                return;
            }

            let html = '<div class="sm-table-container"><table class="sm-table"><thead><tr><th style="font-weight:700;">اسم الطالب الكامل</th><th style="font-weight:700; width:180px;">الدرجة المرصودة</th></tr></thead><tbody>';
            res.data.forEach(s => {
                html += `<tr><td style="font-weight:700; color: var(--sm-dark-color);">${s.name}</td><td><input type="text" class="sm-input batch-grade-input" data-student-id="${s.id}" style="width:120px; height: 32px; font-size:12px; font-weight:800;" placeholder="رصد..."></td></tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
    });
}

function saveBatchGrades() {
    const subject = document.getElementById('batch-subject').value;
    const term = document.getElementById('batch-term').value;
    if (!subject) { alert('يرجى تحديد المادة الدراسية أولاً'); return; }

    const grades = {};
    document.querySelectorAll('.batch-grade-input').forEach(input => {
        grades[input.dataset.studentId] = input.value;
    });

    const formData = new FormData();
    formData.append('action', 'sm_save_class_grades');
    formData.append('subject', subject);
    formData.append('term', term);
    formData.append('grades', JSON.stringify(grades));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification(`تم حفظ ورصد ${res.data} درجات للمجموعة بنجاح`);
        }
    });
}

function addSubject() {
    const name = document.getElementById('new-subject-name').value;
    const gradeIds = [];
    document.querySelectorAll('.new-subject-grade-check:checked').forEach(chk => gradeIds.push(chk.value));

    if (!name || gradeIds.length === 0) {
        alert('يرجى إدخال اسم المادة واختيار صف واحد على الأقل');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_add_subject');
    formData.append('name', name);
    gradeIds.forEach(id => formData.append('grade_ids[]', id));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تمت إضافة المادة وتعميمها على الصفوف المحددة');
            loadSubjects();
            document.getElementById('new-subject-name').value = '';
            document.querySelectorAll('.new-subject-grade-check').forEach(chk => chk.checked = false);
        } else {
            smShowNotification('خطأ في حفظ المادة', true);
        }
    });
}

function loadSubjects() {
    const container = document.getElementById('subjects-list-container');
    if (!container) return;
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_subjects')
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (res.data.length === 0) {
                container.innerHTML = '<div style="padding: 40px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray); box-shadow: var(--sm-shadow);"><p style="font-weight:700; font-size:13px; margin:0;">لا يوجد مواد دراسية مسجلة في هذا الصف.</p></div>';
                return;
            }

            let html = '<div class="sm-table-container"><table class="sm-table"><thead><tr><th style="font-weight:700;">المادة الدراسية</th><th style="font-weight:700;">الصف</th><th style="text-align: left; padding-left: 20px; font-weight:700; width:80px;">إلغاء المادة</th></tr></thead><tbody>';
            res.data.forEach(s => {
                html += `<tr><td style="font-weight:800; color: var(--sm-dark-color);">${s.name}</td><td style="font-weight:700;">الصف ${s.grade_id}</td><td style="text-align: left; padding-left: 20px;"><button onclick="deleteSubject(${s.id})" class="sm-btn sm-btn-outline" style="color:#ef4444; border-color:#fca5a5; padding: 4px 8px; font-size:11px; height: 26px;">حذف</button></td></tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
    });
}
document.addEventListener('DOMContentLoaded', loadSubjects);

function renderGradesTable(grades) {
    const container = document.getElementById('grades-table-container');
    if (grades.length === 0) {
        container.innerHTML = '<div style="padding: 40px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray); box-shadow: var(--sm-shadow);"><p style="font-weight:700; font-size:13px; margin:0;">لا يوجد درجات مسجلة ومدرجة لهذا الطالب بعد.</p></div>';
        return;
    }

    let html = `
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th style="font-weight:700;">المادة الدراسية</th>
                        <th style="font-weight:700;">الفصل الدراسي</th>
                        <th style="font-weight:700; width:120px;">الدرجة</th>
                        <th style="font-weight:700; width:160px;">تاريخ الرصد</th>
                        <th style="text-align: left; padding-left: 20px; font-weight:700; width:80px;">العمليات</th>
                    </tr>
                </thead>
                <tbody>
    `;

    grades.forEach(g => {
        html += `
            <tr>
                <td style="font-weight:700; color: var(--sm-dark-color);">${g.subject}</td>
                <td style="font-weight:600;">${g.term}</td>
                <td><span class="sm-badge" style="background:#eff6ff; color:#1d4ed8; font-size:13px; font-weight:800; border: 1px solid #bfdbfe; padding: 2px 10px; border-radius: 6px;">${g.grade_val}</span></td>
                <td style="font-size:11px; color: var(--sm-text-gray); font-weight:700;">${g.created_at}</td>
                <td style="text-align: left; padding-left: 20px;">
                    <button onclick="deleteGrade(${g.id})" class="sm-btn sm-btn-outline" style="color:#ef4444; border-color:#fca5a5; padding: 0; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; background:#fff;" title="حذف الدرجة"><span class="dashicons dashicons-trash" style="font-size:14px; width:14px; height:14px;"></span></button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function saveStudentGrade() {
    const studentId = document.getElementById('grade-student-id').value;
    const subject = document.getElementById('grade-subject').value;
    const term = document.getElementById('grade-term').value;
    const gradeVal = document.getElementById('grade-val').value;

    if (!studentId || !subject || !gradeVal) {
        alert('يرجى إكمال كافة البيانات المطلوبة للرصد');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_save_grade_ajax');
    formData.append('student_id', studentId);
    formData.append('subject', subject);
    formData.append('term', term);
    formData.append('grade_val', gradeVal);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ ورصد درجة الطالب بنجاح');
            loadStudentGrades(studentId);
            document.getElementById('grade-subject').value = '';
            document.getElementById('grade-val').value = '';
        } else {
            smShowNotification('خطأ في الرصد: ' + res.data, true);
        }
    });
}

function deleteGrade(gradeId) {
    if (!confirm('هل أنت متأكد من رغبتك في حذف وإلغاء هذه الدرجة نهائياً؟')) return;

    const formData = new FormData();
    formData.append('action', 'sm_delete_grade_ajax');
    formData.append('grade_id', gradeId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حذف وإلغاء رصد درجة الطالب');
            loadStudentGrades(document.getElementById('grade-student-id').value);
        } else {
            smShowNotification('خطأ في الحذف: ' + res.data, true);
        }
    });
}

function deleteSubject(subjectId) {
    if (!confirm('هل أنت متأكد من رغبتك في إلغاء وحذف هذه المادة الدراسية نهائياً؟')) return;

    const formData = new FormData();
    formData.append('action', 'sm_delete_subject');
    formData.append('subject_id', subjectId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حذف المادة الدراسية بنجاح');
            loadSubjects();
        } else {
            smShowNotification('خطأ في الحذف: ' + res.data, true);
        }
    });
}
</script>
