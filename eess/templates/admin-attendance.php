<?php
if (!defined('ABSPATH')) exit;

// Ensure variables are initialized to avoid critical error if not passed from controller
if (!isset($attendance_date)) {
    $attendance_date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : current_time('Y-m-d');
}
if (!isset($attendance_summary)) {
    $attendance_summary = SM_DB::get_attendance_summary($attendance_date);
}
?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <!-- Main Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 22px;">سجل الحضور والغياب اليومي</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">متابعة الحضور اليومي للشعب الدراسية، طباعة الغيابات، وإدارة أكواد التحضير الآمنة</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="<?php echo home_url('/attendance/'); ?>" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700; text-decoration: none;">
                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> تسجيل الحضور
            </a>
            <button onclick="printAbsenceReport('daily')" class="sm-btn sm-btn-secondary" style="height: 40px; font-size: 12px; font-weight: 700;">
                <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> غيابات اليوم
            </button>
            <button onclick="printAbsenceReport('term')" class="sm-btn sm-btn-accent" style="height: 40px; font-size: 12px; font-weight: 700;">
                <span class="dashicons dashicons-chart-bar" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> الأكثر غياباً (الفصل)
            </button>
            <div style="display: flex; align-items: center; gap: 6px;">
                <input type="date" id="attendance-filter-date" class="sm-input" value="<?php echo esc_attr($attendance_date); ?>" onchange="window.location.href='<?php echo add_query_arg('attendance_date', '', $_SERVER['REQUEST_URI']); ?>' + this.value" style="height: 40px; width: 140px; padding: 0 10px; font-size: 12px; font-weight: 700;">
                <button onclick="location.reload()" class="sm-btn sm-btn-outline" style="height: 40px; width: 40px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #fff;" title="تحديث الصفحة"><span class="dashicons dashicons-update"></span></button>
            </div>
        </div>
    </div>

    <!-- Stats Summary Block -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 30px;">
        <?php
        $total_students = 0;
        $total_present = 0;
        $total_absent = 0;
        $total_late = 0;
        if (is_array($attendance_summary)) {
            foreach ($attendance_summary as $card) {
                $total_students += $card['student_count'];
                $total_present += $card['stats']['present'];
                $total_absent += $card['stats']['absent'];
                $total_late += $card['stats']['late'];
            }
        }
        ?>
        <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 90px; box-sizing: border-box;">
            <div style="font-size: 12px; color: var(--sm-text-gray); font-weight: 700;">إجمالي الطلاب المقيدين</div>
            <div style="font-size: 26px; font-weight: 800; color: var(--sm-dark-color); line-height: 1;"><?php echo $total_students; ?></div>
        </div>
        <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 90px; box-sizing: border-box; border-right: 4px solid #10b981;">
            <div style="font-size: 12px; color: #15803d; font-weight: 700;">إجمالي الحاضرين</div>
            <div style="font-size: 26px; font-weight: 800; color: #16a34a; line-height: 1;"><?php echo $total_present; ?></div>
        </div>
        <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 90px; box-sizing: border-box; border-right: 4px solid #ef4444;">
            <div style="font-size: 12px; color: #b91c1c; font-weight: 700;">إجمالي الغائبين</div>
            <div style="font-size: 26px; font-weight: 800; color: #dc2626; line-height: 1;"><?php echo $total_absent; ?></div>
        </div>
        <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 90px; box-sizing: border-box; border-right: 4px solid #f59e0b;">
            <div style="font-size: 12px; color: #b45309; font-weight: 700;">إجمالي المتأخرين</div>
            <div style="font-size: 26px; font-weight: 800; color: #d97706; line-height: 1;"><?php echo $total_late; ?></div>
        </div>
    </div>

    <!-- Quick Search and Filter Tool -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 24px; display: flex; gap: 15px; box-shadow: var(--sm-shadow); align-items: center;">
        <div style="flex: 1;">
            <input type="text" id="card-search" class="sm-input" placeholder="بحث سريع عن صف دراسي أو شعبة..." onkeyup="filterAttendanceCards()" style="height: 40px; font-size: 12px;">
        </div>
        <div style="width: 200px;">
            <select id="card-status-filter" class="sm-select" onchange="filterAttendanceCards()" style="height: 40px; font-size: 12px;">
                <option value="all">كل الحالات الانضباطية</option>
                <option value="complete">مكتمل (رُصِد)</option>
                <option value="incomplete">غير مكتمل (قيد الرصد)</option>
                <option value="absences">يوجد غيابات</option>
            </select>
        </div>
    </div>

    <!-- Cards Layout Grid grouped by Grade -->
    <div id="attendance-cards-grid">
        <?php
        $grouped_cards = array();
        if (is_array($attendance_summary)) {
            foreach ($attendance_summary as $card) {
                $grouped_cards[$card['class_name']][] = $card;
            }
        }

        foreach ($grouped_cards as $grade_name => $cards): ?>
            <div class="attendance-grade-section" style="margin-bottom: 30px; background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 24px; box-shadow: var(--sm-shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px;">
                    <h3 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 15px;"><?php echo esc_html($grade_name); ?></h3>
                    <button onclick="printAttendance('grade', '<?php echo esc_js($grade_name); ?>')" class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 0 12px; height: 32px; background: #fff;">
                        <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px; margin-left: 3px;"></span> طباعة الصف بالكامل
                    </button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($cards as $card):
                        $status_color = '#ef4444'; // Red (Default Incomplete)
                        $status_bg = '#fef2f2';
                        $status_text = 'رصد غير مكتمل';

                        if ($card['is_complete']) {
                            if ($card['has_absences']) {
                                $status_color = '#f59e0b'; // Yellow (Complete but with absences)
                                $status_bg = '#fffbeb';
                                $status_text = 'يوجد غيابات أو تأخير';
                            } else {
                                $status_color = '#10b981'; // Green (Full attendance)
                                $status_bg = '#f0fdf4';
                                $status_text = 'حضور كامل ومكتمل';
                            }
                        }
                    ?>
                    <div class="sm-attendance-card"
                         data-grade="<?php echo esc_attr($card['class_name']); ?>"
                         data-section="<?php echo esc_attr($card['section']); ?>"
                         data-complete="<?php echo $card['is_complete'] ? 'yes' : 'no'; ?>"
                         data-absences="<?php echo $card['has_absences'] ? 'yes' : 'no'; ?>"
                         style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 10px; padding: 15px 20px; transition: all 0.25s ease; position: relative; border-right: 5px solid <?php echo $status_color; ?>; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">

                        <div style="display: flex; align-items: center; gap: 15px; min-width: 250px;">
                            <div style="width: 42px; height: 42px; background: #f8fafc; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sm-accent-color); border: 1px solid var(--sm-border-color);">
                                <span class="dashicons dashicons-groups" style="font-size: 20px; width: 20px; height: 20px;"></span>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 13px;"><?php echo esc_html($card['class_name']); ?> - شعبة <?php echo esc_html($card['section']); ?></h4>
                                <div style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700; margin-top: 3px;">
                                    عدد الطلاب المقيدين بالشعبة: <?php echo $card['student_count']; ?> طالب
                                </div>
                            </div>
                        </div>

                        <!-- Status badges and stats -->
                        <div style="display: flex; flex-direction: column; gap: 4px; min-width: 150px;">
                            <div style="font-size: 11px; color: <?php echo $status_color; ?>; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-marker" style="font-size: 12px; width: 12px; height: 12px;"></span>
                                <?php echo $status_text; ?>
                            </div>
                            <div style="display: flex; gap: 10px; font-size: 11px; font-weight: 700; color: var(--sm-text-gray);">
                                <span>حاضر: <strong style="color: #16a34a;"><?php echo $card['stats']['present']; ?></strong></span>
                                <span>غائب: <strong style="color: #dc2626;"><?php echo $card['stats']['absent']; ?></strong></span>
                                <span>متأخر: <strong style="color: #d97706;"><?php echo $card['stats']['late']; ?></strong></span>
                            </div>
                        </div>

                        <!-- Class security code -->
                        <div style="display: flex; align-items: center; gap: 10px; border-right: 1px dashed var(--sm-border-color); padding-right: 15px; min-width: 160px;">
                            <div>
                                <div style="font-size: 9px; color: var(--sm-text-gray); font-weight: 700; margin-bottom: 2px;">رمز المزامنة للشعبة:</div>
                                <div id="code-<?php echo sanitize_title($card['class_name'] . '-' . $card['section']); ?>" style="font-family: monospace; font-size: 16px; font-weight: 800; color: var(--sm-dark-color); letter-spacing: 2px;">
                                    <?php echo SM_Settings::get_class_security_code($card['class_name'], $card['section']); ?>
                                </div>
                            </div>
                            <button onclick="resetClassCode('<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>', this)" class="sm-btn sm-btn-outline" style="padding: 0; width: 28px; height: 28px; background: #fff; display: inline-flex; align-items: center; justify-content: center;" title="إعادة تعيين الكود">
                                <span class="dashicons dashicons-randomize" style="font-size: 14px; width: 14px; height: 14px;"></span>
                            </button>
                        </div>

                        <!-- Actions -->
                        <div style="display: flex; gap: 8px;">
                            <button onclick="printAttendance('section', '<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>')" class="sm-btn sm-btn-outline" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #fff;" title="طباعة كشف الشعبة">
                                <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px;"></span>
                            </button>
                            <button onclick="openAttendanceModal('<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>')" class="sm-btn" style="height: 32px; font-size: 11px; font-weight: 700;">رصد وتحديث</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Attendance Marking Dialog Modal -->
<div id="sm-attendance-marking-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 650px; padding: 25px;">
        <div class="sm-modal-header" style="margin-bottom: 20px;">
            <div>
                <h3 id="modal-attendance-title" style="margin: 0; font-size: 15px; font-weight: 800;">تسجيل حضور وانضباط الشعبة</h3>
                <div id="modal-attendance-subtitle" style="font-size: 12px; color: var(--sm-text-gray); margin-top: 5px; font-weight: 700;"></div>
            </div>
            <button class="sm-modal-close" onclick="closeAttendanceModal()">&times;</button>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: var(--sm-bg-light); padding: 12px 15px; border-radius: 8px; border: 1px solid var(--sm-border-color);">
            <div style="font-weight: 700; font-size: 12px; color: var(--sm-dark-color);">خيارات سريعة للمجموعة:</div>
            <div style="display: flex; gap: 8px;">
                <button onclick="setAllAttendance('present')" class="sm-btn" style="background: #10b981; font-size: 10px; padding: 4px 10px; height: 28px; font-weight: 700;">✓ حاضر للكل</button>
                <button onclick="setAllAttendance('absent')" class="sm-btn" style="background: #ef4444; font-size: 10px; padding: 4px 10px; height: 28px; font-weight: 700;">✗ غائب للكل</button>
            </div>
        </div>

        <div id="attendance-students-list" style="max-height: 380px; overflow-y: auto; padding-left: 5px;">
            <!-- Loaded dynamically via AJAX -->
            <div style="text-align: center; padding: 40px; color: var(--sm-text-gray); font-weight: 700;">جاري تحميل قائمة طلاب الشعبة...</div>
        </div>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--sm-border-color); display: flex; justify-content: flex-end;">
            <button onclick="closeAttendanceModal()" class="sm-btn" style="background: var(--sm-dark-color); width: 120px;">إغلاق وحفظ</button>
        </div>
    </div>
</div>

<script>
function filterAttendanceCards() {
    const search = document.getElementById('card-search').value.toLowerCase();
    const status = document.getElementById('card-status-filter').value;
    const cards = document.querySelectorAll('.sm-attendance-card');

    cards.forEach(card => {
        const grade = card.getAttribute('data-grade').toLowerCase();
        const section = card.getAttribute('data-section').toLowerCase();
        const isComplete = card.getAttribute('data-complete') === 'yes';
        const hasAbsences = card.getAttribute('data-absences') === 'yes';

        let show = true;
        if (search && !grade.includes(search) && !section.includes(search)) show = false;

        if (status === 'complete' && !isComplete) show = false;
        if (status === 'incomplete' && isComplete) show = false;
        if (status === 'absences' && !hasAbsences) show = false;

        card.style.display = show ? 'flex' : 'none';
    });
}

function openAttendanceModal(className, section) {
    const date = document.getElementById('attendance-filter-date').value;
    document.getElementById('modal-attendance-title').innerText = 'تسجيل حضور: ' + className;
    document.getElementById('modal-attendance-subtitle').innerText = 'الشعبة: ' + section + ' | التاريخ المختار: ' + date;
    document.getElementById('sm-attendance-marking-modal').style.display = 'flex';

    loadAttendanceStudents(className, section, date);
}

function closeAttendanceModal() {
    document.getElementById('sm-attendance-marking-modal').style.display = 'none';
    location.reload();
}

function loadAttendanceStudents(className, section, date) {
    const listContainer = document.getElementById('attendance-students-list');
    listContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--sm-text-gray); font-weight: 700;">جاري تحميل البيانات...</div>';

    const formData = new FormData();
    formData.append('action', 'sm_get_students_attendance_ajax');
    formData.append('class_name', className);
    formData.append('section', section);
    formData.append('date', date);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            renderStudentsList(res.data);
        } else {
            listContainer.innerHTML = '<div style="color: red; padding: 20px;">' + res.data + '</div>';
        }
    });
}

function renderStudentsList(students) {
    const listContainer = document.getElementById('attendance-students-list');
    if (students.length === 0) {
        listContainer.innerHTML = '<div style="padding: 20px; text-align: center; font-weight: 700; color: var(--sm-text-gray);">لا يوجد طلاب مسجلين في هذا الصف الدراسي حالياً.</div>';
        return;
    }

    let html = '<table class="sm-table" style="box-shadow: none; border: none; margin: 0;"><tbody>';
    students.forEach(s => {
        const photo = s.photo_url ? `<img src="${s.photo_url}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid var(--sm-border-color);">` : `<div style="width: 32px; height: 32px; border-radius: 50%; background: var(--sm-bg-light); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray);">👤</div>`;

        html += `
            <tr data-student-id="${s.id}">
                <td style="width: 45px; border-bottom: 1px solid var(--sm-border-color); padding: 8px 10px;">${photo}</td>
                <td style="border-bottom: 1px solid var(--sm-border-color); padding: 8px 10px;">
                    <div style="font-weight: 700; font-size: 13px; color: var(--sm-dark-color);">${s.name}</div>
                    <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">${s.student_code}</div>
                </td>
                <td style="text-align: left; border-bottom: 1px solid var(--sm-border-color); padding: 8px 10px;">
                    <div class="attendance-options" style="display: flex; gap: 4px; justify-content: flex-end;">
                        <button onclick="saveAttendance(${s.id}, 'present', this)" class="attendance-btn ${s.status === 'present' ? 'active' : ''}" data-status="present" title="حضور">ح</button>
                        <button onclick="saveAttendance(${s.id}, 'absent', this)" class="attendance-btn ${s.status === 'absent' ? 'active' : ''}" data-status="absent" title="غياب">غ</button>
                        <button onclick="saveAttendance(${s.id}, 'late', this)" class="attendance-btn ${s.status === 'late' ? 'active' : ''}" data-status="late" title="تأخير">ت</button>
                        <button onclick="saveAttendance(${s.id}, 'excused', this)" class="attendance-btn ${s.status === 'excused' ? 'active' : ''}" data-status="excused" title="بعذر">ع</button>
                    </div>
                </td>
            </tr>
        `;
    });
    html += '</tbody></table>';
    listContainer.innerHTML = html;
}

function saveAttendance(studentId, status, btn) {
    const date = document.getElementById('attendance-filter-date').value;
    const row = btn.closest('tr');

    row.querySelectorAll('.attendance-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_ajax');
    formData.append('student_id', studentId);
    formData.append('status', status);
    formData.append('date', date);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            smShowNotification('خطأ في حفظ الحضور: ' + res.data, true);
            btn.classList.remove('active');
        }
    });
}

function setAllAttendance(status) {
    const buttons = document.querySelectorAll(`.attendance-btn[data-status="${status}"]`);
    buttons.forEach(btn => btn.click());
}

function resetClassCode(grade, section, btn) {
    if (!confirm('هل أنت متأكد من إعادة تعيين كود الأمان لهذا الفصل؟')) return;

    btn.disabled = true;
    const formData = new FormData();
    formData.append('action', 'sm_reset_class_code_ajax');
    formData.append('grade', grade);
    formData.append('section', section);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const codeId = 'code-' + (grade + '-' + section).toLowerCase().replace(/ /g, '-').replace(/[^\w-]/g, '');
            const el = document.getElementById(codeId);
            if (el) el.innerText = res.data;
            smShowNotification('تم تغيير كود الأمان بنجاح');
        } else {
            smShowNotification('خطأ: ' + res.data, true);
        }
        btn.disabled = false;
    });
}

function printAttendance(type, grade = '', section = '') {
    const date = document.getElementById('attendance-filter-date').value;
    let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=attendance_sheet'); ?>';
    url += '&date=' + date;
    url += '&scope=' + type;
    if (grade) url += '&grade=' + encodeURIComponent(grade);
    if (section) url += '&section=' + encodeURIComponent(section);

    window.open(url, '_blank');
}

function printAbsenceReport(type) {
    const date = document.getElementById('attendance-filter-date').value;
    let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=absence_report'); ?>';
    url += '&type=' + type + '&date=' + date;
    window.open(url, '_blank');
}
</script>

<style>
.attendance-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid var(--sm-border-color);
    background: #fff;
    cursor: pointer;
    font-weight: 800;
    font-size: 11px;
    transition: 0.2s;
    color: var(--sm-text-gray);
}
.attendance-btn[data-status="present"]:hover, .attendance-btn[data-status="present"].active { background: #10b981; color: #fff; border-color: #10b981; }
.attendance-btn[data-status="absent"]:hover, .attendance-btn[data-status="absent"].active { background: #ef4444; color: #fff; border-color: #ef4444; }
.attendance-btn[data-status="late"]:hover, .attendance-btn[data-status="late"].active { background: #f59e0b; color: #fff; border-color: #f59e0b; }
.attendance-btn[data-status="excused"]:hover, .attendance-btn[data-status="excused"].active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
</style>
