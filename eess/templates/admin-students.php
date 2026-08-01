<?php if (!defined('ABSPATH')) exit; ?>
<?php
$is_admin = current_user_can('إدارة_الطلاب');
$import_results = get_transient('sm_import_results_' . get_current_user_id());
if ($import_results) {
    delete_transient('sm_import_results_' . get_current_user_id());
}
?>
<div class="sm-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin:0; font-weight: 900; color: #111F35;">إدارة الطلاب</h2>

        <?php if ($is_admin): ?>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="document.getElementById('add-single-student-modal').style.display='flex'" class="sm-btn" style="min-width: 160px; height: 42px; font-size: 13px;">+ إضافة طالب جديد</button>
            <button onclick="document.getElementById('csv-import-form').style.display='block'" class="sm-btn sm-btn-secondary" style="min-width: 160px; height: 42px; font-size: 13px;">استيراد طلاب (Excel)</button>
            <a href="data:text/csv;charset=utf-8,<?php echo rawurlencode("الاسم الكامل,الصف,الشعبة,الجنسية,البريد,الهاتف,رقم الهوية\nأحمد محمد,الصف 12,أ,إماراتي,parent@example.com,0501234567,784-1234-1234567-1"); ?>" download="student_template.csv" class="sm-btn sm-btn-outline" style="text-decoration:none; min-width: 160px; height: 42px; display: flex; align-items: center; justify-content: center; font-size: 13px;">تحميل نموذج CSV</a>
            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card'); ?>" target="_blank" class="sm-btn sm-btn-accent" style="background: #27ae60; text-decoration:none; min-width: 160px; height: 42px; display: flex; align-items: center; justify-content: center; font-size: 13px;">طباعة كافة البطاقات</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($import_results): ?>
        <div style="background: #fff; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; overflow: hidden; box-shadow: var(--sm-shadow);">
            <div style="background: var(--sm-bg-light); padding: 15px 25px; border-bottom: 1px solid var(--sm-border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin:0; color: var(--sm-dark-color); font-weight: 800;">تقرير استيراد الطلاب الأخير</h4>
                <span style="font-size: 12px; color: #718096;">إجمالي السجلات المعالجة: <?php echo $import_results['total']; ?></span>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 1px solid #c6f6d5; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #2f855a;"><?php echo $import_results['success'] - ($import_results['duplicate'] ?? 0); ?></div>
                        <div style="font-size: 11px; color: #38a169;">سجلات جديدة</div>
                    </div>
                    <div style="background: #e6fffa; padding: 15px; border-radius: 8px; border: 1px solid #b2f5ea; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #2c7a7b;"><?php echo $import_results['generated'] ?? 0; ?></div>
                        <div style="font-size: 11px; color: #319795;">أكواد تم توليدها</div>
                    </div>
                    <div style="background: #ebf8ff; padding: 15px; border-radius: 8px; border: 1px solid #bee3f8; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #2b6cb0;"><?php echo $import_results['duplicate'] ?? 0; ?></div>
                        <div style="font-size: 11px; color: #3182ce;">سجلات مكررة</div>
                    </div>
                    <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #c05621;"><?php echo $import_results['warning']; ?></div>
                        <div style="font-size: 11px; color: #dd6b20;">تنبيهات</div>
                    </div>
                    <div style="background: #fff5f5; padding: 15px; border-radius: 8px; border: 1px solid #fed7d7; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #c53030;"><?php echo $import_results['error']; ?></div>
                        <div style="font-size: 11px; color: #e53e3e;">أخطاء</div>
                    </div>
                </div>

                <?php if (!empty($import_results['details'])): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; max-height: 250px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: right;">
                            <thead>
                                <tr style="background: #edf2f7; position: sticky; top: 0;">
                                    <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e0; width: 80px;">النوع</th>
                                    <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e0;">التفاصيل والسبب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_results['details'] as $detail): ?>
                                    <tr>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">
                                            <?php if ($detail['type'] == 'error'): ?>
                                                <span style="color: #e53e3e; font-weight: 700;">خطأ</span>
                                            <?php elseif ($detail['type'] == 'info'): ?>
                                                <span style="color: #3182ce; font-weight: 700;">تكرار</span>
                                            <?php else: ?>
                                                <span style="color: #dd6b20; font-weight: 700;">تنبيه</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0; color: #4a5568;"><?php echo esc_html($detail['msg']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); margin-bottom: 30px; box-shadow: var(--sm-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
            <input type="hidden" name="sm_tab" value="students">

            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">اسم الطالب:</label>
                <input type="text" name="student_search" class="sm-input" value="<?php echo esc_attr(isset($_GET['student_search']) ? $_GET['student_search'] : ''); ?>" placeholder="بحث بالاسم أو الكود...">
            </div>
            
            <div class="sm-form-group" style="margin-bottom:0;">
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

            <div class="sm-form-group" style="margin-bottom:0;">
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

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="sm-btn">بحث</button>
                <a href="<?php echo add_query_arg('sm_tab', 'students', remove_query_arg(['student_search', 'class_filter', 'section_filter', 'teacher_filter'])); ?>" class="sm-btn sm-btn-outline" style="text-decoration:none;">إعادة ضبط</a>
            </div>
        </form>
    </div>


    <div id="csv-import-form" style="display:none; background: #f8fafc; padding: 30px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 30px;">
        <h3 style="margin-top:0; color:var(--sm-secondary-color);">دليل استيراد الطلاب (Excel Mapping)</h3>
        
        <div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:20px;">
            <p style="font-size:14px; font-weight:700; margin-bottom:15px; color: var(--sm-dark-color);">يتم استيراد البيانات وفقاً لخريطة الأعمدة التالية:</p>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود A</div>
                    <div style="font-weight: 800;">الاسم الكامل للطالب</div>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود B</div>
                    <div style="font-weight: 800;">الصف الدراسي</div>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود C</div>
                    <div style="font-weight: 800;">الشعبة</div>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود D</div>
                    <div style="font-weight: 800;">الجنسية</div>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود E</div>
                    <div style="font-weight: 800;">بريد ولي الأمر</div>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود F</div>
                    <div style="font-weight: 800;">هاتف ولي الأمر</div>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 11px; color: #64748b;">العمود G</div>
                    <div style="font-weight: 800;">رقم الهوية (ID)</div>
                </div>
            </div>
            <p style="font-size:12px; color:#718096; line-height: 1.6;">يرجى التأكد من أن ملف الإكسل يحتوي على كافة سجلات الطلاب وأن البيانات مرتبة بدقة في الأعمدة المذكورة أعلاه (A, B, C) لضمان نجاح عملية الاستيراد.</p>
        </div>

        <form id="sm-import-form-ajax" enctype="multipart/form-data">
            <div class="sm-form-group">
                <label class="sm-label">اختر ملف CSV للملفات:</label>
                <input type="file" id="import_csv_file" accept=".csv" required>
            </div>

            <div id="import-progress-container" style="display:none; margin-top:20px; background:#fff; padding:20px; border-radius:10px; border:1px solid #e2e8f0;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:13px; font-weight:700;">
                    <span id="import-status-text" style="color:var(--sm-primary-color);">جاري تحضير البيانات...</span>
                    <span id="import-percentage">0%</span>
                </div>
                <div style="width:100%; height:12px; background:#edf2f7; border-radius:10px; overflow:hidden;">
                    <div id="import-progress-bar" style="width:0%; height:100%; background:var(--sm-primary-color); transition: width 0.3s ease;"></div>
                </div>
                <div id="import-row-stats" style="margin-top:10px; font-size:11px; color:#718096;">
                    معالجة السطر <span id="current-row-num">0</span> من <span id="total-rows-num">0</span>
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="button" id="start-import-btn" class="sm-btn" style="width:auto; background:#27ae60;">بدء عملية الاستيراد</button>
                <button type="button" onclick="this.parentElement.parentElement.parentElement.style.display='none'" class="sm-btn" style="width:auto; background:var(--sm-text-gray);">إلغاء</button>
            </div>
        </form>

        <script>
        (function() {
            const startBtn = document.getElementById('start-import-btn');
            const fileInput = document.getElementById('import_csv_file');
            const progressContainer = document.getElementById('import-progress-container');
            const progressBar = document.getElementById('import-progress-bar');
            const statusText = document.getElementById('import-status-text');
            const percentageText = document.getElementById('import-percentage');
            const currentRowNum = document.getElementById('current-row-num');
            const totalRowsNum = document.getElementById('total-rows-num');

            let isImporting = false;
            window.addEventListener('beforeunload', function(e) {
                if (isImporting) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            startBtn.onclick = function() {
                if (!fileInput.files[0]) {
                    alert('يرجى اختيار ملف أولاً');
                    return;
                }

                startBtn.disabled = true;
                startBtn.style.opacity = '0.5';
                fileInput.disabled = true;
                progressContainer.style.display = 'block';
                isImporting = true;

                // Stage 1: Upload
                const formData = new FormData();
                formData.append('action', 'sm_upload_import_csv');
                formData.append('csv_file', fileInput.files[0]);
                formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        totalRowsNum.innerText = res.data.total;
                        processChunk(res.data.file_path, 0, res.data.total);
                    } else {
                        alert('خطأ أثناء الرفع: ' + res.data);
                        resetUI();
                    }
                });
            };

            function processChunk(filePath, offset, total) {
                statusText.innerText = 'جاري استيراد البيانات...';

                const formData = new FormData();
                formData.append('action', 'sm_process_import_chunk');
                formData.append('file_path', filePath);
                formData.append('offset', offset);
                formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const newOffset = res.data.total_so_far;
                        const percentage = Math.min(100, Math.round((newOffset / total) * 100));

                        progressBar.style.width = percentage + '%';
                        percentageText.innerText = percentage + '%';
                        currentRowNum.innerText = newOffset;

                        if (res.data.finished || newOffset >= total) {
                            statusText.innerText = 'اكتمل الاستيراد بنجاح!';
                            isImporting = false;
                            setTimeout(() => {
                                window.location.href = window.location.pathname + '?page=sm-dashboard&sm_tab=students&sm_admin_msg=import_completed';
                            }, 1000);
                        } else {
                            processChunk(filePath, newOffset, total);
                        }
                    } else {
                        isImporting = false;
                        statusText.innerText = 'توقف الاستيراد بسبب خطأ.';
                        statusText.style.color = 'red';

                        const retryBtn = document.createElement('button');
                        retryBtn.innerText = 'إعادة المحاولة من السطر ' + offset;
                        retryBtn.className = 'sm-btn';
                        retryBtn.style.marginTop = '10px';
                        retryBtn.onclick = function() {
                            this.remove();
                            statusText.style.color = 'var(--sm-primary-color)';
                            isImporting = true;
                            processChunk(filePath, offset, total);
                        };
                        progressContainer.appendChild(retryBtn);

                        alert('خطأ أثناء المعالجة: ' + res.data);
                    }
                });
            }

            function resetUI() {
                isImporting = false;
                startBtn.disabled = false;
                startBtn.style.opacity = '1';
                fileInput.disabled = false;
                progressContainer.style.display = 'none';
            }
        })();
        </script>
    </div>
    
    <div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center; background: #f8fafc; padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
        <span style="font-size: 13px; font-weight: 700; color: #4a5568;">الإجراءات الجماعية:</span>
        <button onclick="bulkDeleteSelected()" class="sm-btn" style="background: #e53e3e; font-size: 11px; padding: 5px 15px; width: auto;">حذف المحدد</button>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="select-all-students" onclick="toggleAllStudents(this)"></th>
                    <th>كود الطالب</th>
                    <th>الصورة</th>
                    <th>اسم الطالب</th>
                    <th>الصف</th>
                    <th>الشعبة</th>
                    <th>النقاط</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" style="padding: 60px; text-align: center; color: var(--sm-text-gray);">
                            <span class="dashicons dashicons-search" style="font-size: 40px; width:40px; height:40px; margin-bottom:10px;"></span>
                            <p>لا يوجد طلاب يطابقون معايير البحث الحالية.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr id="stu-row-<?php echo $student->id; ?>">
                            <td><input type="checkbox" class="student-checkbox" value="<?php echo $student->id; ?>"></td>
                            <td style="font-family: 'Rubik', sans-serif; font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($student->student_code); ?></td>
                            <td>
                                <?php if ($student->photo_url): ?>
                                    <img src="<?php echo esc_url($student->photo_url); ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--sm-border-color);">
                                <?php else: ?>
                                    <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--sm-bg-light); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--sm-text-gray);">👤</div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($student->name); ?></td>
                            <td><?php echo esc_html($student->class_name); ?></td>
                            <td><span class="sm-badge sm-badge-low"><?php echo esc_html($student->section); ?></span></td>
                            <td style="text-align:center;">
                                <div style="font-weight:900; color:<?php echo $student->behavior_points > 15 ? '#e53e3e' : '#111F35'; ?>;">
                                    <?php echo (int)$student->behavior_points; ?>
                                    <?php if ($student->case_file_active): ?>
                                        <div style="font-size:9px; color:#e53e3e; font-weight:800;">[ملف مفتوح]</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button onclick='viewSmStudent(<?php echo json_encode(array(
                                        "id" => $student->id,
                                        "name" => $student->name,
                                        "student_id" => $student->student_code,
                                        "class" => SM_Settings::format_grade_name($student->class_name, $student->section),
                                        "photo" => $student->photo_url
                                    )); ?>)' class="sm-btn sm-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px; min-width: 80px;">
                                        <span class="dashicons dashicons-visibility"></span> سجل
                                    </button>

                                    <?php if ($is_admin):
                                        $temp_pass = get_user_meta($student->parent_user_id, 'sm_temp_pass', true);
                                    ?>
                                        <button onclick='showStudentCreds("<?php echo esc_js($student->student_code); ?>", "<?php echo esc_js($temp_pass ?: '********'); ?>", "<?php echo esc_js($student->name); ?>", "<?php echo $student->id; ?>")' class="sm-btn sm-btn-outline" style="padding: 5px; min-width: 32px;" title="بيانات الدخول"><span class="dashicons dashicons-lock"></span></button>

                                        <button onclick='editSmStudent(<?php echo json_encode(array(
                                            "id" => $student->id,
                                            "name" => $student->name,
                                            "student_id" => $student->student_code,
                                            "class_name" => $student->class_name,
                                            "section" => $student->section,
                                            "parent_id" => $student->parent_user_id,
                                            "parent_email" => $student->parent_email,
                                            "guardian_phone" => $student->guardian_phone,
                                            "nationality" => $student->nationality,
                                            "registration_date" => $student->registration_date,
                                            "photo" => $student->photo_url
                                        )); ?>)' class="sm-btn sm-btn-outline" style="padding: 5px; min-width: 32px;" title="تعديل"><span class="dashicons dashicons-edit"></span></button>

                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card&student_id=' . $student->id); ?>" target="_blank" class="sm-btn sm-btn-outline" style="padding: 5px; min-width: 32px;" title="بطاقة الهوية"><span class="dashicons dashicons-id"></span></a>

                                        <button onclick="confirmDeleteStudent(<?php echo $student->id; ?>, '<?php echo esc_js($student->name); ?>')" class="sm-btn sm-btn-outline" style="padding: 5px; min-width: 32px; color: #e53e3e;" title="حذف الطالب"><span class="dashicons dashicons-trash"></span></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <style>
    .sm-student-row:hover {
        border-color: var(--sm-primary-color);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        transform: translateX(-5px);
    }
    .sm-action-btn-row {
        padding: 8px 15px;
        border-radius: 8px;
        border: none;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .sm-action-btn-row:hover {
        opacity: 0.8;
        transform: translateY(-1px);
    }
    .sm-icon-btn-row {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    .sm-icon-btn-row:hover {
        background: #edf2f7;
        color: var(--sm-primary-color);
    }
    </style>

    <?php if ($is_admin): ?>
    <div id="add-single-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 750px;">
            <div class="sm-modal-header">
                <h3>تسجيل طالب جديد في النظام</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-single-student-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-student-form">
                <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background:#f8fafc; padding:25px; border-radius:12px; border:1px solid #edf2f7;">
                    <div class="sm-form-group">
                        <label class="sm-label">الاسم الثلاثي للطالب:</label>
                        <input name="name" type="text" class="sm-input" placeholder="أدخل الاسم كاملاً..." required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">الصف الدراسي:</label>
                        <select name="class" class="sm-select" required>
                            <option value="">-- اختر الصف --</option>
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
                        <input name="section" type="text" class="sm-input" placeholder="مثال: أ، ب، 1، 2..." required list="existing-sections">
                        <datalist id="existing-sections">
                            <?php
                            $all_sections = $wpdb->get_col("SELECT DISTINCT section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY section ASC");
                            foreach ($all_sections as $s) echo '<option value="'.$s.'">';
                            ?>
                        </datalist>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">بريد ولي الأمر (اختياري):</label>
                        <input name="email" type="email" class="sm-input" placeholder="example@mail.com">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">رقم هاتف ولي الأمر:</label>
                        <input name="guardian_phone" type="text" class="sm-input" placeholder="05xxxxxxxx">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">جنسية الطالب:</label>
                        <input name="nationality" type="text" class="sm-input" placeholder="مثال: إماراتي">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">تاريخ التسجيل:</label>
                        <input name="registration_date" type="date" class="sm-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">ربط بحساب الطالب (اختياري):</label>
                        <select name="parent_user_id" class="sm-select">
                            <option value="">-- بلا ربط --</option>
                            <?php foreach (get_users(array('role' => 'sm_student')) as $p): ?>
                                <option value="<?php echo $p->ID; ?>"><?php echo esc_html($p->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="text-align:left; margin-top:25px;">
                    <button type="submit" class="sm-btn" style="width:220px; height:50px; font-weight:800; font-size:1.05em;">تأكيد إضافة الطالب</button>
                </div>
            </form>
        </div>
    </div>

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
                            foreach ($academic['active_grades'] as $grade_num) {
                                echo "<option value='الصف $grade_num'>الصف $grade_num</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">الشعبة:</label>
                        <input type="text" name="section" id="edit_stu_section" class="sm-input" required list="existing-sections">
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
                    <div class="sm-form-group">
                        <label class="sm-label">جنسية الطالب:</label>
                        <input name="nationality" id="edit_stu_nationality" type="text" class="sm-input">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">تاريخ التسجيل:</label>
                        <input name="registration_date" id="edit_stu_reg_date" type="date" class="sm-input">
                    </div>

                    <div style="grid-column: span 2; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-top: 15px; margin-bottom: 5px; color: var(--sm-primary-color); font-weight: 700;">الربط والمتابعة</div>

                    <div class="sm-form-group">
                        <label class="sm-label">ربط بحساب الطالب المسجل:</label>
                        <select name="parent_user_id" id="edit_stu_parent_user" class="sm-select">
                            <option value="">-- اختر من مستخدمي النظام --</option>
                            <?php foreach (get_users(array('role' => 'sm_student')) as $p): ?>
                                <option value="<?php echo $p->ID; ?>"><?php echo esc_html($p->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:15px; margin-top:30px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="width:200px; height:50px; font-weight:800;">تحديث البيانات الآن</button>
                    <button type="button" onclick="document.getElementById('edit-student-modal').style.display='none'" class="sm-btn" style="background:#cbd5e0; color:#2d3748; width:120px;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div id="delete-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center;">
            <div class="sm-modal-header">
                <h3>تأكيد الحذف</h3>
                <button class="sm-modal-close" onclick="document.getElementById('delete-student-modal').style.display='none'">&times;</button>
            </div>
            <div style="color: #e53e3e; font-size: 50px; margin-bottom: 15px;"><span class="dashicons dashicons-warning" style="font-size: 50px; width:50px; height:50px;"></span></div>
            <p id="delete-confirm-msg">هل أنت متأكد من حذف الطالب وسجلاته بالكامل؟</p>
            <form method="post" id="delete-student-form">
                <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
                <input type="hidden" name="delete_student_id" id="confirm_delete_stu_id">
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="submit" name="delete_student" class="sm-btn" style="background: #e53e3e;">تأكيد الحذف النهائي</button>
                    <button type="button" onclick="document.getElementById('delete-student-modal').style.display='none'" class="sm-btn" style="background: #cbd5e0; color: #2d3748;">تراجع</button>
                </div>
            </form>
        </div>
    </div>

    <div id="view-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 950px; background: #fdfdfd;">
            <div class="sm-modal-header" style="border-bottom: 3px solid var(--sm-primary-color); padding-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 style="margin:0; font-size: 1.5em; font-weight: 900; color: #111F35;">السجل الانضباطي الشامل</h3>
                    <div style="display: flex; gap: 10px;">
                        <button id="print-full-record-btn" class="sm-btn" style="background: #27ae60; width: auto; font-size: 13px; font-weight: 700; height: 40px; display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons dashicons-printer"></span> طباعة السجل الكامل PDF
                        </button>
                        <button class="sm-modal-close" style="position:static; margin:0;" onclick="document.getElementById('view-student-modal').style.display='none'">&times;</button>
                    </div>
                </div>
            </div>
            <div id="stu_details_content" style="padding: 20px 0; max-height: 70vh; overflow-y: auto;"></div>
            <div style="margin-top: 20px; text-align: left; border-top: 1px solid #eee; padding-top: 20px;">
                <button type="button" onclick="document.getElementById('view-student-modal').style.display='none'" class="sm-btn" style="width:auto; background:var(--sm-text-gray); height: 40px;">إغلاق النافذة</button>
            </div>
        </div>
    </div>

    <!-- Student Credentials Modal -->
    <div id="student-creds-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center;">
            <div class="sm-modal-header">
                <h3>بيانات دخول الطالب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('student-creds-modal').style.display='none'">&times;</button>
            </div>
            <div style="padding: 20px; background: #f8fafc; border-radius: 12px; margin-top: 15px; border: 1px solid #edf2f7;">
                <div style="font-weight: 800; color: var(--sm-dark-color); margin-bottom: 15px; font-size: 1.1em;" id="cred-stu-name"></div>

                <div style="margin-bottom: 15px;">
                    <div style="font-size: 11px; color: #718096; margin-bottom: 5px;">اسم المستخدم (كود الطالب):</div>
                    <div style="font-family: monospace; font-size: 1.3em; font-weight: 900; color: var(--sm-primary-color); background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;" id="cred-username"></div>
                </div>

                <div style="margin-bottom: 10px;">
                    <div style="font-size: 11px; color: #718096; margin-bottom: 5px;">كلمة المرور المؤقتة:</div>
                    <div style="font-family: monospace; font-size: 1.3em; font-weight: 900; color: var(--sm-dark-color); background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;" id="cred-password"></div>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; gap: 10px;">
                <a href="#" id="cred-download-link" target="_blank" class="sm-btn" style="background: #3182ce; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; flex: 1;">
                    <span class="dashicons dashicons-download"></span> تحميل البطاقة
                </a>
                <button onclick="document.getElementById('student-creds-modal').style.display='none'" class="sm-btn sm-btn-outline" style="flex: 1;">إغلاق</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        // Show Credentials
        window.showStudentCreds = function(user, pass, name, id) {
            document.getElementById('cred-username').innerText = user;
            document.getElementById('cred-password').innerText = pass;
            document.getElementById('cred-stu-name').innerText = name;
            document.getElementById('cred-download-link').href = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_credentials_card&student_id='); ?>' + id;
            document.getElementById('student-creds-modal').style.display = 'flex';
        };

        // Handle View Record
        window.viewSmStudent = function(student) {
            const modal = document.getElementById('view-student-modal');
            const content = document.getElementById('stu_details_content');
            const printBtn = document.getElementById('print-full-record-btn');
            if (!modal || !content) return;
            
            content.innerHTML = '<div style="text-align:center; padding:50px;"><p style="font-weight:700; color:#718096;">جاري جلب الملف الانضباطي وتنسيقه...</p></div>';
            modal.style.display = 'flex';

            printBtn.onclick = function() {
                window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id, '_blank');
            };

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id)
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    // Remove print buttons from the content
                    doc.querySelectorAll('.no-print').forEach(el => el.remove());
                    // Enhance style for modal display
                    const styles = doc.querySelectorAll('style');
                    content.innerHTML = doc.body.innerHTML;

                    // Re-apply styles scoped to content if needed or just rely on existing report styling
                    // The report is already RTL and has fonts.
                });
        };

        // Handle Add Student AJAX
        const addForm = document.getElementById('add-student-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_add_student_ajax');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تمت إضافة الطالب بنجاح');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

        // Handle Edit Student AJAX
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
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

        // Handle Delete
        window.confirmDeleteStudent = function(id, name) {
            document.getElementById('confirm_delete_stu_id').value = id;
            document.getElementById('delete-confirm-msg').innerText = `هل أنت متأكد من حذف الطالب "${name}" وكافة سجلاته؟`;
            document.getElementById('delete-student-modal').style.display = 'flex';
        };

        const deleteForm = document.getElementById('delete-student-form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_delete_student_ajax');
                formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_student"); ?>');
                formData.append('student_id', document.getElementById('confirm_delete_stu_id').value);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم حذف الطالب');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

        window.toggleAllStudents = function(master) {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = master.checked);
        };

        window.bulkDeleteSelected = function() {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert('يرجى اختيار طلاب أولاً'); return; }
            if (!confirm(`هل أنت متأكد من حذف ${selected.length} طالب نهائياً؟`)) return;

            const formData = new FormData();
            formData.append('action', 'sm_bulk_delete_students_ajax');
            formData.append('student_ids', selected.join(','));
            formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_student"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification(`تم حذف ${selected.length} طالب بنجاح`);
                    setTimeout(() => location.reload(), 500);
                }
            });
        };
    })();
    </script>
</div>
