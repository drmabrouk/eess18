<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-admin-panel" dir="rtl">
    <?php
    $user_roles = (array) wp_get_current_user()->roles;
    $is_parent = in_array('sm_parent', $user_roles) || in_array('sm_student', $user_roles);
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0; font-weight: 800;">سجل سجلات الطلاب</h3>

        <div style="display: flex; gap: 8px; align-items: center;">
            <?php if (!$is_parent): ?>
                <button type="button" onclick="document.getElementById('violation-import-form').style.display='block'" class="sm-btn sm-btn-secondary" style="padding: 0 15px; height: 38px; min-width: 90px; font-size: 13px;" title="استيراد">استيراد</button>

                <div class="sm-dropdown" style="position: relative;">
                    <button type="button" class="sm-btn" style="background:#2d3748; padding: 0 15px; height: 38px; min-width: 120px; font-size: 13px;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">تصدير تقارير <span class="dashicons dashicons-arrow-down-alt2"></span></button>
                    <div style="display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; min-width: 200px; margin-top: 5px;">
                        <div style="padding: 10px 15px; font-size: 11px; color: #111F35; border-bottom: 2px solid #eee; font-weight: 800; background: #f8fafc; border-radius: 8px 8px 0 0;">تحميل ملفات PDF</div>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=violation_report&range=today'); ?>" target="_blank" class="sm-dropdown-item">مخالفات اليوم (PDF)</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=violation_report&range=week'); ?>" target="_blank" class="sm-dropdown-item">مخالفات الأسبوع (PDF)</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=violation_report&range=month'); ?>" target="_blank" class="sm-dropdown-item">مخالفات الشهر (PDF)</a>

                        <div style="padding: 10px 15px; font-size: 11px; color: #111F35; border-bottom: 2px solid #eee; border-top: 1px solid #eee; font-weight: 800; background: #f8fafc;">تصدير بيانات CSV</div>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_violations_csv&range=today&nonce='.wp_create_nonce('sm_export_action')); ?>" class="sm-dropdown-item">مخالفات اليوم (CSV)</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_violations_csv&range=week&nonce='.wp_create_nonce('sm_export_action')); ?>" class="sm-dropdown-item">مخالفات الأسبوع (CSV)</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_violations_csv&range=month&nonce='.wp_create_nonce('sm_export_action')); ?>" class="sm-dropdown-item">مخالفات الشهر (CSV)</a>

                        <hr style="margin:0; border:none; border-top:1px solid #eee;">
                        <button onclick="exportViolationPDF()" class="sm-dropdown-item" style="width:100%; text-align:right; background:none; border:none; cursor:pointer; font-weight:700;">تقرير المخالفات الشامل (المفلتر)</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background: white; padding: 30px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); margin-bottom: 30px; box-shadow: var(--sm-shadow);">
        <form id="violation-filter-form" method="get" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <input type="hidden" name="page" value="sm-dashboard">
            <input type="hidden" name="sm_tab" value="stats">

            <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <?php if (!$is_parent): ?>
            <div class="sm-form-group" style="margin-bottom:0; flex: 2; min-width: 300px;">
                <label class="sm-label">البحث عن طالب:</label>
                <input type="text" name="student_search" class="sm-input" value="<?php echo esc_attr($_GET['student_search'] ?? ''); ?>" placeholder="ادخل اسم الطالب أو الكود الخاص به للبحث المباشر..." style="width: 100%;">
            </div>
            <div class="sm-form-group" style="margin-bottom:0; flex: 1; min-width: 150px;">
                <label class="sm-label">الصف:</label>
                <select name="class_filter" class="sm-select">
                    <option value="">كل الصفوف</option>
                    <?php
                    global $wpdb;
                    $classes = $wpdb->get_col("SELECT DISTINCT class_name FROM {$wpdb->prefix}sm_students ORDER BY CAST(REPLACE(class_name, 'الصف ', '') AS UNSIGNED) ASC");
                    foreach ($classes as $c): ?>
                        <option value="<?php echo esc_attr($c); ?>" <?php selected(isset($_GET['class_filter']) && $_GET['class_filter'] == $c); ?>><?php echo esc_html($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm-form-group" style="margin-bottom:0; flex: 1; min-width: 100px;">
                <label class="sm-label">الشعبة:</label>
                <select name="section_filter" class="sm-select">
                    <option value="">كل الشعب</option>
                    <?php 
                    $sections = $wpdb->get_col("SELECT DISTINCT section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY section ASC");
                    foreach ($sections as $s): ?>
                        <option value="<?php echo esc_attr($s); ?>" <?php selected(isset($_GET['section_filter']) && $_GET['section_filter'] == $s); ?>><?php echo esc_html($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            
            <div class="sm-form-group" style="margin-bottom:0; flex: 1; min-width: 150px;">
                <label class="sm-label">النوع:</label>
                <select name="type_filter" class="sm-select">
                    <option value="">كل الأنواع</option>
                    <?php foreach (SM_Settings::get_violation_types() as $k => $v): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected(isset($_GET['type_filter']) && $_GET['type_filter'] == $k); ?>><?php echo esc_html($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px; align-items: end; margin-bottom: 2px;">
                <button type="submit" class="sm-btn" style="padding: 0 25px; height: 45px; min-width: 130px;">تطبيق الفلترة</button>
                <div id="filter-loader" style="display:none; align-self:center; margin-right: 10px;"><span class="dashicons dashicons-update spin"></span></div>
            </div>
            </div>
        </form>
    </div>

    <div id="violation-import-form" style="display:none; background: #f8fafc; padding: 30px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 30px;">
        <h3 style="margin-top:0; color:var(--sm-secondary-color);">دليل استيراد السجلات (CSV)</h3>
        
        <div style="background:#fff; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:700; margin-bottom:10px;">هيكل ملف السجلات الصحيح:</p>
            <table style="width:100%; font-size:11px; border-collapse:collapse; text-align:center;">
                <thead>
                    <tr style="background:#edf2f7;">
                        <th style="border:1px solid #cbd5e0; padding:5px;">كود الطالب</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">النوع (سلوك/غياب/تأخر)</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">الحدة (منخفضة/متوسطة/خطيرة)</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">التفاصيل</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">الإجراء المتخذ</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">المكافأة/العقوبة</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border:1px solid #cbd5e0; padding:5px;">STU001</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">سلوكية</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">خطيرة</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">تعدي على الزملاء</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">فصل 3 أيام</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">حرمان من الرحلة</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form method="post" enctype="multipart/form-data" onsubmit="return handleImportSubmit(this, 'sm_import_violations_csv')">
            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
            <div class="sm-form-group">
                <label class="sm-label">اختر ملف CSV للمخالفات:</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <div id="import-loading" style="display:none; margin-bottom: 15px; padding: 10px; background: #ebf8ff; border-left: 4px solid #3182ce; color: #2c5282; font-weight: 700;">
                <span class="dashicons dashicons-update spin" style="margin-left: 10px;"></span>
                جاري استيراد البيانات... يرجى عدم إغلاق الصفحة.
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="sm_import_violations_csv" class="sm-btn" style="width:auto; background:#27ae60;">استيراد السجلات الآن</button>
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

    <?php if (current_user_can('إدارة_الطلاب')): ?>
    <div id="edit-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 800px;">
            <div class="sm-modal-header">
                <h3>تعديل الملف المعلوماتي للطالب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-student-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-student-form">
                <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
                <input type="hidden" name="student_id" id="edit_stu_id">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px; background:#f8fafc; padding:25px; border-radius:12px; border:1px solid #edf2f7;">
                    <div style="grid-column: span 2; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 5px; color: var(--sm-primary-color); font-weight: 700;">البيانات الأساسية</div>

                    <div class="sm-form-group">
                        <label class="sm-label">الاسم الكامل للطالب:</label>
                        <input type="text" name="name" id="edit_stu_name" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">الصف الدراسي:</label>
                        <select name="class_name" id="edit_stu_class" class="sm-select" required>
                            <?php
                            $academic = SM_Settings::get_academic_structure();
                            foreach ($academic['active_grades'] as $grade_num) {
                                echo "<option value='الصف $grade_num'>الصف $grade_num</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">الشعبة:</label>
                        <input type="text" name="section" id="edit_stu_section" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">الرقم الأكاديمي (الكود):</label>
                        <input type="text" name="student_code" id="edit_stu_code" class="sm-input" readonly>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">بريد ولي الأمر:</label>
                        <input type="email" name="parent_email" id="edit_stu_email" class="sm-input">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">رقم هاتف ولي الأمر:</label>
                        <input name="guardian_phone" id="edit_stu_phone" type="text" class="sm-input">
                    </div>
                </div>

                <div style="display:flex; gap:15px; margin-top:30px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="width:200px; height:50px; font-weight:800;">تحديث البيانات الآن</button>
                    <button type="button" onclick="document.getElementById('edit-student-modal').style.display='none'" class="sm-btn" style="background:#cbd5e0; color:#2d3748; width:120px;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function() {
        const editForm = document.getElementById('edit-student-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_update_student_ajax');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم تحديث بيانات الطالب');
                        document.getElementById('edit-student-modal').style.display = 'none';
                        document.getElementById('violation-filter-form').dispatchEvent(new Event('submit'));
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                });
            });
        }
    })();
    </script>
    <?php endif; ?>

    <div id="edit-record-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 800px;">
            <div class="sm-modal-header">
                <h3>تعديل بيانات المخالفة</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-record-modal').style.display='none'">&times;</button>
            </div>
            <form method="post" id="edit-record-form" class="sm-form-container">
                <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
                <input type="hidden" name="record_id" id="edit_record_id">
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">درجة المخالفة (المستوى):</label>
                        <select name="degree" id="edit_violation_degree" class="sm-select" onchange="updateEditHierarchicalViolations()" required>
                            <option value="1">المستوى الأول (بسيطة)</option>
                            <option value="2">المستوى الثاني (متوسطة)</option>
                            <option value="3">المستوى الثالث (جسيمة)</option>
                            <option value="4">المستوى الرابع (شديدة الخطورة)</option>
                        </select>
                    </div>

                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">البند القانوني / نوع المخالفة:</label>
                        <select name="violation_code" id="edit_violation_code_select" class="sm-select" onchange="onEditViolationSelected()" required>
                            <option value="">-- اختر البند --</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="sm-form-group">
                        <label class="sm-label">تصنيف الموقف:</label>
                        <select name="classification" id="edit_classification" class="sm-select">
                            <option value="general">عام</option>
                            <option value="inside_class">داخل الفصل</option>
                            <option value="yard">في الساحة</option>
                            <option value="labs">في المختبرات</option>
                            <option value="bus">الحافلة المدرسية</option>
                        </select>
                    </div>

                    <div class="sm-form-group">
                        <label class="sm-label">النقاط المستحقة:</label>
                        <input type="number" name="points" id="edit_violation_points" class="sm-input" value="0">
                    </div>
                    <input type="hidden" name="type" id="edit_hidden_violation_type">
                    <input type="hidden" name="severity" id="edit_violation_severity">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">الإجراء المتخذ:</label>
                    <input type="text" name="action_taken" id="edit_action_taken" class="sm-input">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">التفاصيل:</label>
                    <textarea name="details" id="edit_details" class="sm-textarea" rows="3"></textarea>
                </div>

                <div style="display:flex; gap:12px; margin-top: 20px; justify-content: flex-end;">
                    <button type="submit" name="sm_update_record" class="sm-btn" style="height: 45px; min-width: 150px;">حفظ التغييرات</button>
                    <button type="button" onclick="document.getElementById('edit-record-modal').style.display='none'" class="sm-btn" style="background:var(--sm-text-gray); height: 45px; min-width: 100px;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const hViolations = <?php echo json_encode(SM_Settings::get_hierarchical_violations()); ?>;

    function updateEditHierarchicalViolations(selectedCode = '') {
        const degree = document.getElementById('edit_violation_degree').value;
        const select = document.getElementById('edit_violation_code_select');

        select.innerHTML = '<option value="">-- اختر البند --</option>';
        if (!degree || !hViolations[degree]) return;

        Object.keys(hViolations[degree]).forEach(code => {
            const v = hViolations[degree][code];
            const opt = document.createElement('option');
            opt.value = code;
            opt.innerText = code + ' - ' + v.name;
            if (code === selectedCode) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function onEditViolationSelected() {
        const degree = document.getElementById('edit_violation_degree').value;
        const code = document.getElementById('edit_violation_code_select').value;
        if (!degree || !code || !hViolations[degree][code]) return;

        const v = hViolations[degree][code];
        document.getElementById('edit_violation_points').value = v.points;
        document.getElementById('edit_action_taken').value = v.action;
        document.getElementById('edit_hidden_violation_type').value = v.name;

        const sev = document.getElementById('edit_violation_severity');
        if (degree == 1) sev.value = 'low';
        else if (degree == 2) sev.value = 'medium';
        else sev.value = 'high';
    }

    function editSmRecord(record) {
        document.getElementById('edit_record_id').value = record.id;
        document.getElementById('edit_violation_degree').value = record.degree || 1;
        document.getElementById('edit_classification').value = record.classification || 'general';
        document.getElementById('edit_violation_points').value = record.points || 0;
        document.getElementById('edit_action_taken').value = record.action_taken || '';
        document.getElementById('edit_details').value = record.details || '';
        document.getElementById('edit_hidden_violation_type').value = record.type || '';
        document.getElementById('edit_violation_severity').value = record.severity || 'low';

        updateEditHierarchicalViolations(record.violation_code);
        document.getElementById('edit-record-modal').style.display = 'flex';
    }
    </script>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>الطالب</th>
                    <th>التاريخ</th>
                    <th>الدرجة</th>
                    <th>البند</th>
                    <th>تكرار</th>
                    <th>الحدة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="violations-table-body">
                <?php include SM_PLUGIN_DIR . 'templates/partials/violations-table-rows.php'; ?>
            </tbody>
        </table>
    </div>

    <!-- Delete Record Confirmation Modal -->
    <div id="delete-record-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center;">
            <div style="color: #e53e3e; font-size: 40px; margin-bottom: 15px;"><span class="dashicons dashicons-warning" style="font-size:40px;"></span></div>
            <h3 style="margin:0 0 10px 0; border:none;">تأكيد حذف المخالفة</h3>
            <p>هل أنت متأكد من حذف هذا السجل نهائياً؟</p>
            <input type="hidden" id="confirm_delete_record_id">
            <div style="display: flex; gap: 15px; margin-top: 25px;">
                <button onclick="executeDeleteRecord()" class="sm-btn" style="background: #e53e3e;">حذف نهائي</button>
                <button onclick="document.getElementById('delete-record-modal').style.display='none'" class="sm-btn" style="background: #cbd5e0; color: #2d3748;">تراجع</button>
            </div>
        </div>
    </div>

    <script>
    function exportViolationPDF() {
        const student = document.querySelector('input[name="student_search"]').value;
        const grade = document.querySelector('select[name="class_filter"]').value;
        const section = document.querySelector('select[name="section_filter"]').value;
        const type = document.querySelector('select[name="type_filter"]').value;

        let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=violation_report'); ?>';
        if (student) url += '&search=' + encodeURIComponent(student);
        if (grade) url += '&class_filter=' + encodeURIComponent(grade);
        if (section) url += '&section_filter=' + encodeURIComponent(section);
        if (type) url += '&type_filter=' + encodeURIComponent(type);

        window.open(url, '_blank');
    }

    (function() {
        // AJAX Filtering Logic
        const filterForm = document.getElementById('violation-filter-form');
        if (filterForm) {
            filterForm.onsubmit = function(e) {
                e.preventDefault();
                const loader = document.getElementById('filter-loader');
                const tbody = document.getElementById('violations-table-body');

                if (loader) loader.style.display = 'inline-block';
                tbody.style.opacity = '0.5';

                const formData = new FormData(this);
                formData.append('action', 'sm_filter_violations');
                formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        tbody.innerHTML = res.data.html;
                    }
                })
                .finally(() => {
                    if (loader) loader.style.display = 'none';
                    tbody.style.opacity = '1';
                });
            };
        }

        window.markAsContacted = function(recordId) {
            const formData = new FormData();
            formData.append('action', 'sm_mark_contacted');
            formData.append('record_id', recordId);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    // Update UI immediately if record is visible
                    const row = document.getElementById('record-row-' + recordId);
                    if (row) {
                        // Refresh just this row or re-filter
                        filterForm.dispatchEvent(new Event('submit'));
                    }
                }
            });
        };

        window.editSmStudentFromStats = function(s) {
            // Map keys if needed to match global editSmStudent expectations
            // expected keys: id, name, class_name, section, parent_email, guardian_phone, student_id (code)
            if (typeof window.editSmStudent === 'function') {
                window.editSmStudent(s);
            } else {
                console.error('editSmStudent function not found');
            }
        };

        window.confirmDeleteRecord = function(id) {
            document.getElementById('confirm_delete_record_id').value = id;
            document.getElementById('delete-record-modal').style.display = 'flex';
        };

        window.executeDeleteRecord = function() {
            const id = document.getElementById('confirm_delete_record_id').value;
            const formData = new FormData();
            formData.append('action', 'sm_delete_record_ajax');
            formData.append('record_id', id);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تم حذف السجل بنجاح');
                    const row = document.getElementById('record-row-' + id);
                    if (row) row.remove();
                    document.getElementById('delete-record-modal').style.display = 'none';
                }
            });
        };
    })();
    </script>

    <style>
    .sm-record-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.2s; }
    .sm-action-icon-btn { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; text-decoration: none; font-size: 16px; }
    </style>
</div>
<style>
@media print {
    body * { visibility: hidden; }
    .sm-admin-panel, .sm-admin-panel * { visibility: visible; }
    .sm-admin-panel { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
