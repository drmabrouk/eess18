<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-printing-center" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h3 style="margin:0; border:none; padding:0;">مركز الطباعة والتقارير</h3>
        <div style="background: #f0f7ff; padding: 10px 20px; border-radius: 8px; border: 1px solid #c3dafe; font-size: 0.9em; color: var(--sm-primary-color); font-weight: 600;">
            إعدادات الطباعة: A4 عمودي
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 40px;">

        <!-- Section: Identity Cards -->
        <div>
            <h4 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--sm-primary-color); display: flex; align-items: center; gap: 10px; color: var(--sm-dark-color);">
                <span class="dashicons dashicons-id"></span> بطاقات الهوية التعريفية
            </h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <!-- Student ID Cards (All) -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #F8FAFC; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #3182CE;">
                            <span class="dashicons dashicons-groups" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">بطاقات الطلاب (الكل)</h4>
                <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">طباعة بطاقات التعريف لكافة الطلاب في النظام أو حسب صف محدد.</p>
                <div class="sm-form-group">
                    <select id="card_class_filter" class="sm-select" style="font-size: 12px; padding: 8px;">
                        <option value="">كافة الصفوف</option>
                        <?php
                        global $wpdb;
                        $classes = $wpdb->get_col("SELECT DISTINCT class_name FROM {$wpdb->prefix}sm_students ORDER BY CAST(REPLACE(class_name, 'الصف ', '') AS UNSIGNED) ASC");
                        foreach($classes as $c) echo '<option value="'.$c.'">'.$c.'</option>';
                        ?>
                    </select>
                </div>
            </div>
            <button onclick="printCards()" class="sm-btn" style="background: #3182CE; font-size: 12px;">طباعة البطاقات</button>
        </div>

                <!-- Specific Student ID Card -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #FFF5F5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #E53E3E;">
                            <span class="dashicons dashicons-id-alt" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">بطاقة طالب محدد</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">استخراج بطاقة تعريفية رسمية لطالب واحد فقط بالاسم والكود.</p>
                        <div class="sm-form-group">
                            <select id="specific_card_student_id" class="sm-select" style="font-size: 12px; padding: 8px;">
                                <?php
                                $students = SM_DB::get_students();
                                foreach($students as $s) echo '<option value="'.$s->id.'">'.$s->name.'</option>';
                                ?>
                            </select>
                        </div>
                    </div>
                    <button onclick="printSpecificCard()" class="sm-btn" style="background: #E53E3E; font-size: 12px;">توليد البطاقة</button>
                </div>
            </div>
        </div>

        <!-- Section: Attendance Reports -->
        <div>
            <h4 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #3182CE; display: flex; align-items: center; gap: 10px; color: var(--sm-dark-color);">
                <span class="dashicons dashicons-calendar-alt"></span> تقارير الحضور والغياب
            </h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <!-- Daily Absence Report -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #fff5f5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #e53e3e;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">تقرير الغياب اليومي</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">كشف بجميع الطلاب الغائبين في تاريخ محدد مع بيان عدد غياباتهم السابقة.</p>
                        <div class="sm-form-group">
                            <input type="date" id="abs_daily_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" style="font-size: 12px;">
                        </div>
                    </div>
                    <button onclick="printAbsenceFromCenter('daily')" class="sm-btn" style="background: #e53e3e; font-size: 12px;">طباعة غيابات اليوم</button>
                </div>

                <!-- Class Attendance Sheets -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #EBF4FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #3182CE;">
                            <span class="dashicons dashicons-clipboard" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">كشوف الحضور والغياب</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">طباعة كشوف الحضور لليوم الحالي لكافة الصفوف أو صف محدد.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px;">
                            <select id="att_sheet_class" class="sm-select" style="font-size: 11px; padding: 5px;">
                                <option value="">كافة الصفوف</option>
                                <?php foreach($classes as $c) echo '<option value="'.$c.'">'.$c.'</option>'; ?>
                            </select>
                            <input type="date" id="att_sheet_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" style="font-size: 11px; padding: 5px;">
                        </div>
                    </div>
                    <button onclick="printAttendanceSheets()" class="sm-btn" style="background: #3182CE; font-size: 12px;">طباعة الكشوف</button>
                </div>

                <!-- الأكثر غياباً (الفصل) -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #111F35; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #fff;">
                            <span class="dashicons dashicons-chart-bar" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">الطلاب الأكثر غياباً</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">إحصائية بالطلاب الذين تجاوزوا نسب الغياب المسموح بها خلال الفصل الدراسي الحالي.</p>
                    </div>
                    <button onclick="printAbsenceFromCenter('term')" class="sm-btn" style="background: #111F35; font-size: 12px;">تحليل غياب الفصل</button>
                </div>
            </div>
        </div>

        <!-- Section: Disciplinary & Behavior -->
        <div>
            <h4 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #38A169; display: flex; align-items: center; gap: 10px; color: var(--sm-dark-color);">
                <span class="dashicons dashicons-warning"></span> تقارير السلوك والانضباط
            </h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <!-- Disciplinary Reports -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #F0FFF4; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #38A169;">
                            <span class="dashicons dashicons-media-document" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">التقارير الانضباطية</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">تقرير رسمي مفصل وشامل لسلوك الطالب، جاهز للطباعة والختم.</p>
                        <div class="sm-form-group">
                            <select id="report_student_id" class="sm-select" style="font-size: 12px; padding: 8px;">
                                <?php foreach($students as $s) echo '<option value="'.$s->id.'">'.$s->name.'</option>'; ?>
                            </select>
                        </div>
                    </div>
                    <button onclick="printReport()" class="sm-btn" style="background: #38A169; font-size: 12px;">عرض التقرير</button>
                </div>

                <!-- General Disciplinary Log -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #FFF9DB; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #D69E2E;">
                            <span class="dashicons dashicons-list-view" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">سجل المخالفات العام</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">طباعة كشف كامل بكافة المخالفات المسجلة بالمدرسة خلال فترة زمنية.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px;">
                            <input type="date" id="log_start_date" class="sm-input" style="font-size: 10px; padding: 5px;">
                            <input type="date" id="log_end_date" class="sm-input" style="font-size: 10px; padding: 5px;">
                        </div>
                    </div>
                    <button onclick="printGeneralLog()" class="sm-btn" style="background: #111F35; font-size: 12px;">تحميل السجل</button>
                </div>

                <!-- Reports by Grade/Section -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #FAF5FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #805AD5;">
                            <span class="dashicons dashicons-category" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">التقارير حسب الصف</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">استخراج إحصائيات وتقارير مجمعة لمستوى انضباط صف أو شعبة محددة.</p>
                        <div class="sm-form-group">
                            <select id="grade_report_class" class="sm-select" style="font-size: 12px; padding: 8px;">
                                <?php foreach($classes as $c) echo '<option value="'.$c.'">'.$c.'</option>'; ?>
                            </select>
                        </div>
                    </div>
                    <button onclick="alert('قريباً: تقارير الصفوف')" class="sm-btn" style="background: #805AD5; font-size: 12px;">توليد التقرير</button>
                </div>
            </div>
        </div>

        <!-- Section: Administrative & Lists -->
        <div>
            <h4 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #4A5568; display: flex; align-items: center; gap: 10px; color: var(--sm-dark-color);">
                <span class="dashicons dashicons-admin-generic"></span> القوائم والبيانات الإدارية
            </h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <!-- Full Student List -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #EBF8FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #2B6CB0;">
                            <span class="dashicons dashicons-editor-ul" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">قائمة الطلاب الكاملة</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">طباعة كشف بجميع طلاب المدرسة مصنفين حسب الصف والشعبة.</p>
                    </div>
                    <button onclick="alert('قريباً: طباعة القائمة الكاملة')" class="sm-btn" style="background: #2B6CB0; font-size: 12px;">طباعة القائمة</button>
                </div>

                <!-- Student Login Credentials -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #F7FAFC; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #4A5568;">
                            <span class="dashicons dashicons-lock" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">بيانات دخول الطلاب</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">توليد كشف بأسماء الطلاب مع اسم المستخدم (الكود) وكلمة المرور المؤقتة.</p>
                        <div class="sm-form-group">
                            <select id="creds_class_filter" class="sm-select" style="font-size: 12px; padding: 8px;">
                                <option value="">كافة الصفوف</option>
                                <?php foreach($classes as $c) echo '<option value="'.$c.'">'.$c.'</option>'; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="printCredentials()" class="sm-btn" style="background: #4A5568; font-size: 11px; flex: 1;">كشف البيانات</button>
                        <button onclick="printCredentialsCard()" class="sm-btn" style="background: #8A244B; font-size: 11px; flex: 1;">بطاقات الدخول</button>
                    </div>
                </div>

                <!-- Single Student Login Data -->
                <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--sm-shadow);">
                    <div>
                        <div style="width: 50px; height: 50px; background: #FFF5F7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #D53F8C;">
                            <span class="dashicons dashicons-admin-users" style="font-size: 28px; width: 28px; height: 28px;"></span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; border: none; font-weight: 800; font-size: 15px;">بيانات دخول طالب واحد</h4>
                        <p style="font-size: 11px; color: #718096; line-height: 1.6; margin-bottom: 20px;">استخراج بيانات الدخول (الاسم، المستخدم، كلمة المرور) لطالب واحد فقط.</p>
                        <div class="sm-form-group">
                            <select id="single_creds_student_id" class="sm-select" style="font-size: 12px; padding: 8px;">
                                <?php foreach($students as $s) echo '<option value="'.$s->id.'">'.$s->name.'</option>'; ?>
                            </select>
                        </div>
                    </div>
                    <button onclick="printSingleStudentCreds()" class="sm-btn" style="background: #D53F8C; font-size: 12px;">توليد بطاقة الدخول</button>
                </div>
            </div>
        </div>

        <!-- Excel Templates Section -->
        <div style="grid-column: 1 / -1; background: #f8fafc; padding: 30px; border-radius: 12px; border: 2px dashed #cbd5e1; margin-top: 20px;">
            <h4 style="margin-top:0; color:var(--sm-secondary-color); display:flex; align-items:center; gap:10px;">
                <span class="dashicons dashicons-media-spreadsheet"></span> نماذج إكسل جاهزة للاستخدام
            </h4>
            <p style="font-size: 0.9em; color: #64748b; margin-bottom: 20px;">قم بتحميل النماذج التالية، املأ البيانات، ثم ارفعها في الأقسام المخصصة لتسريع عملية إدخال البيانات.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="data:text/csv;charset=utf-8,<?php echo rawurlencode("الاسم,الصف,البريد,الكود\nاسم الطالب,الصف الأول,parent@example.com,STU001"); ?>" download="students_template.csv" class="sm-btn" style="background:#fff; color:#2d3748; border:1px solid #cbd5e1; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <span class="dashicons dashicons-download"></span> نموذج الطلاب
                </a>
                <a href="data:text/csv;charset=utf-8,<?php echo rawurlencode("الاسم,المستخدم,البريد,كلمة السر\nاسم ولي الأمر,parent_user,parent@example.com,pass123"); ?>" download="parents_template.csv" class="sm-btn" style="background:#fff; color:#2d3748; border:1px solid #cbd5e1; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <span class="dashicons dashicons-download"></span> نموذج أولياء الأمور
                </a>
                <a href="data:text/csv;charset=utf-8,<?php echo rawurlencode("الاسم,المستخدم,البريد,المعرف الوظيفي,المسمى,الجوال,كلمة السر\nاسم المعلم,teacher_user,teacher@example.com,T100,معلم فصل,0500000000,pass123"); ?>" download="teachers_template.csv" class="sm-btn" style="background:#fff; color:#2d3748; border:1px solid #cbd5e1; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <span class="dashicons dashicons-download"></span> نموذج المعلمين
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function printCards() {
    const classFilter = document.getElementById('card_class_filter').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card'); ?>&class_name=' + encodeURIComponent(classFilter), '_blank');
}

function printSpecificCard() {
    const studentId = document.getElementById('specific_card_student_id').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card'); ?>&student_id=' + studentId, '_blank');
}

function printReport() {
    const studentId = document.getElementById('report_student_id').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=disciplinary_report'); ?>&student_id=' + studentId, '_blank');
}

function printGeneralLog() {
    const start = document.getElementById('log_start_date').value;
    const end = document.getElementById('log_end_date').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=general_log'); ?>&start_date=' + start + '&end_date=' + end, '_blank');
}

function printAbsenceFromCenter(type) {
    let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=absence_report'); ?>';
    url += '&type=' + type;
    if (type === 'daily') {
        url += '&date=' + document.getElementById('abs_daily_date').value;
    }
    window.open(url, '_blank');
}

function printCredentials() {
    const classFilter = document.getElementById('creds_class_filter').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_credentials'); ?>&class_name=' + encodeURIComponent(classFilter), '_blank');
}

function printCredentialsCard() {
    const classFilter = document.getElementById('creds_class_filter').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_credentials_card'); ?>&class_name=' + encodeURIComponent(classFilter), '_blank');
}

function printSingleStudentCreds() {
    const studentId = document.getElementById('single_creds_student_id').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_credentials_card'); ?>&student_id=' + studentId, '_blank');
}

function printAttendanceSheets() {
    const grade = document.getElementById('att_sheet_class').value;
    const date = document.getElementById('att_sheet_date').value;
    let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=attendance_sheet'); ?>';
    url += '&date=' + date;
    url += '&scope=' + (grade ? 'grade' : 'all');
    if (grade) url += '&grade=' + encodeURIComponent(grade);
    window.open(url, '_blank');
}
</script>
