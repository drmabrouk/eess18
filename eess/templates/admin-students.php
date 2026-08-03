<?php if (!defined('ABSPATH')) exit; ?>
<?php
$is_admin = current_user_can('إدارة_الطلاب');
$import_results = get_transient('sm_import_results_' . get_current_user_id());
if ($import_results) {
    delete_transient('sm_import_results_' . get_current_user_id());
}
?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <!-- Main Dashboard Title and Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 22px;">إدارة شؤون الطلاب</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">البحث عن الطلاب، استيراد القوائم، وإدارة السجلات السلوكية والانضباطية</p>
        </div>

        <?php if ($is_admin): ?>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button onclick="document.getElementById('add-single-student-modal').style.display='flex'" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700;">
                <span class="dashicons dashicons-plus" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> إضافة طالب جديد
            </button>
            <button onclick="document.getElementById('csv-import-form').style.display = document.getElementById('csv-import-form').style.display === 'none' ? 'block' : 'none'" class="sm-btn sm-btn-secondary" style="height: 40px; font-size: 12px; font-weight: 700;">
                <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> استيراد طلاب (Excel)
            </button>
            <a href="data:text/csv;charset=utf-8,<?php echo rawurlencode("الاسم الكامل,الصف,الشعبة,الجنسية,البريد,الهاتف,رقم الهوية\nأحمد محمد,الصف 12,أ,إماراتي,parent@example.com,0501234567,784-1234-1234567-1"); ?>" download="student_template.csv" class="sm-btn sm-btn-outline" style="text-decoration:none; height: 40px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; background: #fff;">
                <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> نموذج CSV
            </a>
            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card'); ?>" target="_blank" class="sm-btn" style="background: #10b981; height: 40px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; text-decoration: none;">
                <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> طباعة كافة البطاقات
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Import Results Panel -->
    <?php if ($import_results): ?>
        <div style="background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; overflow: hidden; box-shadow: var(--sm-shadow);">
            <div style="background: var(--sm-bg-light); padding: 15px 25px; border-bottom: 1px solid var(--sm-border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin:0; color: var(--sm-dark-color); font-weight: 800; font-size: 14px;">تقرير استيراد الطلاب الأخير</h4>
                <span style="font-size: 12px; color: var(--sm-text-gray); font-weight: 700;">إجمالي السجلات المعالجة: <?php echo $import_results['total']; ?></span>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; text-align: center;">
                        <div style="font-size: 24px; font-weight: 800; color: #16a34a;"><?php echo $import_results['success'] - ($import_results['duplicate'] ?? 0); ?></div>
                        <div style="font-size: 11px; color: #15803d; font-weight: 700; margin-top: 5px;">سجلات جديدة</div>
                    </div>
                    <div style="background: #f0fdfa; padding: 15px; border-radius: 8px; border: 1px solid #99f6e4; text-align: center;">
                        <div style="font-size: 24px; font-weight: 800; color: #0d9488;"><?php echo $import_results['generated'] ?? 0; ?></div>
                        <div style="font-size: 11px; color: #0f766e; font-weight: 700; margin-top: 5px;">أكواد تم توليدها</div>
                    </div>
                    <div style="background: #eff6ff; padding: 15px; border-radius: 8px; border: 1px solid #bfdbfe; text-align: center;">
                        <div style="font-size: 24px; font-weight: 800; color: #1d4ed8;"><?php echo $import_results['duplicate'] ?? 0; ?></div>
                        <div style="font-size: 11px; color: #1e40af; font-weight: 700; margin-top: 5px;">سجلات مكررة</div>
                    </div>
                    <div style="background: #fffbeb; padding: 15px; border-radius: 8px; border: 1px solid #fde68a; text-align: center;">
                        <div style="font-size: 24px; font-weight: 800; color: #b45309;"><?php echo $import_results['warning']; ?></div>
                        <div style="font-size: 11px; color: #92400e; font-weight: 700; margin-top: 5px;">تنبيهات</div>
                    </div>
                    <div style="background: #fef2f2; padding: 15px; border-radius: 8px; border: 1px solid #fca5a5; text-align: center;">
                        <div style="font-size: 24px; font-weight: 800; color: #b91c1c;"><?php echo $import_results['error']; ?></div>
                        <div style="font-size: 11px; color: #991b1b; font-weight: 700; margin-top: 5px;">أخطاء</div>
                    </div>
                </div>

                <?php if (!empty($import_results['details'])): ?>
                    <div style="background: var(--sm-bg-light); border: 1px solid var(--sm-border-color); border-radius: 8px; max-height: 250px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: right;">
                            <thead>
                                <tr style="background: #f1f5f9; position: sticky; top: 0;">
                                    <th style="padding: 10px 15px; border-bottom: 1px solid var(--sm-border-color); width: 80px; font-weight: 700;">النوع</th>
                                    <th style="padding: 10px 15px; border-bottom: 1px solid var(--sm-border-color); font-weight: 700;">التفاصيل والسبب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_results['details'] as $detail): ?>
                                    <tr>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid var(--sm-border-color);">
                                            <?php if ($detail['type'] == 'error'): ?>
                                                <span class="sm-badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5;">خطأ</span>
                                            <?php elseif ($detail['type'] == 'info'): ?>
                                                <span class="sm-badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">تكرار</span>
                                            <?php else: ?>
                                                <span class="sm-badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">تنبيه</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid var(--sm-border-color); color: var(--sm-dark-color); font-weight: 600;"><?php echo esc_html($detail['msg']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Panel -->
    <div style="background: white; padding: 24px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); margin-bottom: 24px; box-shadow: var(--sm-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.2fr; gap: 16px; align-items: end;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
            <input type="hidden" name="sm_tab" value="students">

            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">اسم الطالب أو الكود:</label>
                <input type="text" name="student_search" class="sm-input" value="<?php echo esc_attr(isset($_GET['student_search']) ? $_GET['student_search'] : ''); ?>" placeholder="بحث بالاسم الكامل أو الرقم الكودي...">
            </div>
            
            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">الصف الدراسي:</label>
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
                <button type="submit" class="sm-btn" style="flex: 1; height: 38px;">بحث</button>
                <a href="<?php echo add_query_arg('sm_tab', 'students', remove_query_arg(['student_search', 'class_filter', 'section_filter', 'teacher_filter'])); ?>" class="sm-btn sm-btn-outline" style="text-decoration:none; flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center; background: #fff;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <!-- Excel Mapping Instructions Block (CSV import drawer) -->
    <div id="csv-import-form" style="display:none; background: #ffffff; padding: 30px; border: 1px solid var(--sm-border-color); border-radius: 12px; margin-bottom: 24px; box-shadow: var(--sm-shadow);">
        <h3 style="margin-top:0; color: var(--sm-dark-color); font-weight: 800; font-size: 15px; margin-bottom: 10px;">دليل استيراد ملف الطلاب المعتمد</h3>
        <p style="font-size: 12px; color: var(--sm-text-gray); margin-bottom: 20px;">يرجى ترتيب أعمدة ملف الـ Excel / CSV بدقة متناهية حسب الترتيب الوارد أدناه لتفادي أي أخطاء في المزامنة:</p>
        
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-bottom: 20px;">
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود A</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">الاسم الكامل</div>
            </div>
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود B</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">الصف الدراسي</div>
            </div>
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود C</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">الشعبة</div>
            </div>
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود D</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">الجنسية</div>
            </div>
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود E</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">بريد ولي الأمر</div>
            </div>
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود F</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">هاتف ولي الأمر</div>
            </div>
            <div style="background: var(--sm-bg-light); padding: 12px; border: 1px solid var(--sm-border-color); border-radius: 8px; text-align: center;">
                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700;">العمود G</div>
                <div style="font-weight: 800; font-size: 12px; margin-top: 4px; color: var(--sm-dark-color);">رقم الهوية</div>
            </div>
        </div>

        <form id="sm-import-form-ajax" enctype="multipart/form-data">
            <div class="sm-form-group" style="margin-bottom: 15px;">
                <label class="sm-label">حدد ملف استيراد CSV للرفع:</label>
                <input type="file" id="import_csv_file" accept=".csv" class="sm-input" required>
            </div>

            <!-- Import Progress Bar -->
            <div id="import-progress-container" style="display:none; margin-top:20px; background: var(--sm-bg-light); padding:20px; border-radius:10px; border:1px solid var(--sm-border-color);">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:13px; font-weight:700;">
                    <span id="import-status-text" style="color:var(--sm-primary-color);">جاري تحضير البيانات...</span>
                    <span id="import-percentage">0%</span>
                </div>
                <div style="width:100%; height:12px; background: #cbd5e1; border-radius:10px; overflow:hidden;">
                    <div id="import-progress-bar" style="width:0%; height:100%; background:var(--sm-primary-color); transition: width 0.3s ease;"></div>
                </div>
                <div id="import-row-stats" style="margin-top:10px; font-size:11px; color:var(--sm-text-gray); font-weight: 700;">
                    معالجة السطر <span id="current-row-num">0</span> من <span id="total-rows-num">0</span>
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="button" id="start-import-btn" class="sm-btn" style="width:auto; background:#10b981;">بدء عملية الاستيراد الفوري</button>
                <button type="button" onclick="document.getElementById('csv-import-form').style.display='none'" class="sm-btn sm-btn-outline" style="width:auto; background: #fff;">إلغاء النافذة</button>
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

            if (startBtn) {
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
            }

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
                if (startBtn) {
                    startBtn.disabled = false;
                    startBtn.style.opacity = '1';
                }
                fileInput.disabled = false;
                progressContainer.style.display = 'none';
            }
        })();
        </script>
    </div>

    <!-- Group Actions Toolbar -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; background: var(--sm-bg-light); padding: 12px 20px; border-radius: var(--sm-radius); border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
        <span style="font-size: 13px; font-weight: 700; color: var(--sm-text-gray);">الإجراءات الجماعية:</span>
        <button onclick="bulkDeleteSelected()" class="sm-btn" style="background: #ef4444; font-size: 11px; padding: 6px 14px; width: auto; font-weight: 700;">حذف الطلاب المحددين</button>
    </div>

    <!-- Unified Students Table -->
    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-students" onclick="toggleAllStudents(this)"></th>
                    <th style="width: 140px;">كود الطالب</th>
                    <th style="width: 80px; text-align: center;">الصورة</th>
                    <th>اسم الطالب الكامل</th>
                    <th>الصف الدراسي</th>
                    <th>الشعبة</th>
                    <th style="text-align: center; width: 100px;">النقاط</th>
                    <th style="text-align: left; padding-left: 20px; width: 220px;">الإجراءات والعمليات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" style="padding: 60px; text-align: center; color: var(--sm-text-gray);">
                            <span class="dashicons dashicons-search" style="font-size: 40px; width:40px; height:40px; margin-bottom:10px;"></span>
                            <p style="font-weight: 700; font-size: 14px; margin: 0;">لا يوجد طلاب يطابقون معايير البحث الحالية.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr id="stu-row-<?php echo $student->id; ?>">
                            <td style="text-align: center;"><input type="checkbox" class="student-checkbox" value="<?php echo $student->id; ?>"></td>
                            <td style="font-family: monospace; font-weight: 700; color: var(--sm-primary-color); font-size: 13px;"><?php echo esc_html($student->student_code); ?></td>
                            <td style="text-align: center;">
                                <?php if ($student->photo_url): ?>
                                    <img src="<?php echo esc_url($student->photo_url); ?>" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--sm-border-color);">
                                <?php else: ?>
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--sm-bg-light); display: inline-flex; align-items: center; justify-content: center; font-size: 16px; border: 1px solid var(--sm-border-color); color: var(--sm-text-gray);">👤</div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 700; color: var(--sm-dark-color);"><?php echo esc_html($student->name); ?></td>
                            <td style="font-weight: 600;"><?php echo esc_html($student->class_name); ?></td>
                            <td><span class="sm-badge sm-badge-low" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"><?php echo esc_html($student->section ?: 'أ'); ?></span></td>
                            <td style="text-align: center;">
                                <div style="font-weight: 800; color: <?php echo $student->behavior_points > 15 ? '#ef4444' : 'var(--sm-dark-color)'; ?>; font-size: 14px;">
                                    <?php echo (int)$student->behavior_points; ?>
                                    <?php if ($student->case_file_active): ?>
                                        <div style="font-size: 9px; color: #ef4444; font-weight: 800; margin-top: 2px;">[ملف مفتوح]</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align: left; padding-left: 20px;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <button onclick='viewSmStudent(<?php echo json_encode(array(
                                        "id" => $student->id,
                                        "name" => $student->name,
                                        "student_id" => $student->student_code,
                                        "class" => SM_Settings::format_grade_name($student->class_name, $student->section),
                                        "photo" => $student->photo_url
                                    )); ?>)' class="sm-btn sm-btn-outline" style="padding: 4px 10px; font-size: 11px; height: 30px; background: #fff;">
                                        <span class="dashicons dashicons-visibility" style="font-size: 14px; width:14px; height:14px; margin-left: 3px;"></span> سجل الطالب
                                    </button>

                                    <?php if ($is_admin):
                                        $temp_pass = get_user_meta($student->parent_user_id, 'sm_temp_pass', true);
                                    ?>
                                        <button onclick='showStudentCreds("<?php echo esc_js($student->student_code); ?>", "<?php echo esc_js($temp_pass ?: '********'); ?>", "<?php echo esc_js($student->name); ?>", "<?php echo $student->id; ?>")' class="sm-btn sm-btn-outline" style="padding: 0; width: 30px; height: 30px; background: #fff;" title="بيانات الدخول"><span class="dashicons dashicons-lock" style="font-size: 14px; width:14px; height:14px; margin: 0 auto; display: block;"></span></button>

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
                                        )); ?>)' class="sm-btn sm-btn-outline" style="padding: 0; width: 30px; height: 30px; background: #fff;" title="تعديل البيانات"><span class="dashicons dashicons-edit" style="font-size: 14px; width:14px; height:14px; margin: 0 auto; display: block;"></span></button>

                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card&student_id=' . $student->id); ?>" target="_blank" class="sm-btn sm-btn-outline" style="padding: 0; width: 30px; height: 30px; background: #fff; display: inline-flex; align-items: center; justify-content: center;" title="بطاقة الهوية"><span class="dashicons dashicons-id" style="font-size: 14px; width:14px; height:14px;"></span></a>

                                        <button onclick="confirmDeleteStudent(<?php echo $student->id; ?>, '<?php echo esc_js($student->name); ?>')" class="sm-btn sm-btn-outline" style="padding: 0; width: 30px; height: 30px; background: #fff; color: #ef4444;" title="حذف الطالب"><span class="dashicons dashicons-trash" style="font-size: 14px; width:14px; height:14px; margin: 0 auto; display: block; color: #ef4444;"></span></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Student Modal -->
    <?php if ($is_admin): ?>
    <div id="add-single-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 700px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 16px; font-weight: 800;">تسجيل طالب جديد في النظام</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-single-student-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-student-form">
                <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: var(--sm-bg-light); padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الاسم الكامل للطالب:</label>
                        <input name="name" type="text" class="sm-input" placeholder="مثال: أحمد سعيد محمد..." required>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الصف الدراسي:</label>
                        <select name="class" class="sm-select" required>
                            <option value="">-- اختر الصف الدراسي --</option>
                            <?php 
                            $academic = SM_Settings::get_academic_structure();
                            foreach ($academic['active_grades'] as $grade_num) {
                                echo "<option value='الصف $grade_num'>الصف $grade_num</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الشعبة:</label>
                        <input name="section" type="text" class="sm-input" placeholder="مثال: أ، ب، ج..." required list="existing-sections">
                        <datalist id="existing-sections">
                            <?php
                            $all_sections = $wpdb->get_col("SELECT DISTINCT section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY section ASC");
                            foreach ($all_sections as $s) echo '<option value="'.$s.'">';
                            ?>
                        </datalist>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">بريد ولي الأمر (اختياري):</label>
                        <input name="email" type="email" class="sm-input" placeholder="parent@eess.online">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">رقم هاتف ولي الأمر:</label>
                        <input name="guardian_phone" type="text" class="sm-input" placeholder="05xxxxxxxx">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">جنسية الطالب:</label>
                        <input name="nationality" type="text" class="sm-input" placeholder="مثال: إماراتي">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">تاريخ التسجيل:</label>
                        <input name="registration_date" type="date" class="sm-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">ربط بحساب الطالب (اختياري):</label>
                        <select name="parent_user_id" class="sm-select">
                            <option value="">-- بلا ربط --</option>
                            <?php foreach (get_users(array('role' => 'sm_student')) as $p): ?>
                                <option value="<?php echo $p->ID; ?>"><?php echo esc_html($p->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="text-align: left; margin-top: 20px;">
                    <button type="submit" class="sm-btn" style="width: 180px; height: 40px; font-weight: 700;">حفظ وإضافة الطالب</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="edit-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 700px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 16px; font-weight: 800;">تعديل الملف المعلوماتي للطالب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-student-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-student-form">
                <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
                <input type="hidden" name="student_id" id="edit_stu_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: var(--sm-bg-light); padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الاسم الكامل للطالب:</label>
                        <input type="text" name="name" id="edit_stu_name" class="sm-input" required>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الصف الدراسي:</label>
                        <select name="class_name" id="edit_stu_class" class="sm-select" required>
                            <?php
                            foreach ($academic['active_grades'] as $grade_num) {
                                echo "<option value='الصف $grade_num'>الصف $grade_num</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الشعبة:</label>
                        <input type="text" name="section" id="edit_stu_section" class="sm-input" required list="existing-sections">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">الرقم الأكاديمي (الكود):</label>
                        <input type="text" name="student_code" id="edit_stu_code" class="sm-input" readonly style="background: #f1f5f9; cursor: not-allowed;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">بريد ولي الأمر:</label>
                        <input type="email" name="parent_email" id="edit_stu_email" class="sm-input">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">رقم هاتف ولي الأمر:</label>
                        <input name="guardian_phone" id="edit_stu_phone" type="text" class="sm-input">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">جنسية الطالب:</label>
                        <input name="nationality" id="edit_stu_nationality" type="text" class="sm-input">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px;">
                        <label class="sm-label">تاريخ التسجيل:</label>
                        <input name="registration_date" id="edit_stu_reg_date" type="date" class="sm-input">
                    </div>
                    <div class="sm-form-group" style="margin-bottom: 10px; grid-column: span 2;">
                        <label class="sm-label">ربط بحساب الطالب المسجل:</label>
                        <select name="parent_user_id" id="edit_stu_parent_user" class="sm-select">
                            <option value="">-- اختر من مستخدمي النظام --</option>
                            <?php foreach (get_users(array('role' => 'sm_student')) as $p): ?>
                                <option value="<?php echo $p->ID; ?>"><?php echo esc_html($p->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="width: 150px;">حفظ التغييرات</button>
                    <button type="button" onclick="document.getElementById('edit-student-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div id="delete-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">تأكيد الحذف النهائي</h3>
                <button class="sm-modal-close" onclick="document.getElementById('delete-student-modal').style.display='none'">&times;</button>
            </div>
            <div style="color: #ef4444; font-size: 40px; margin: 15px 0;"><span class="dashicons dashicons-warning" style="font-size: 40px; width:40px; height:40px;"></span></div>
            <p id="delete-confirm-msg" style="font-size: 13px; font-weight: 700; color: var(--sm-dark-color);">هل أنت متأكد من حذف الطالب وسجلاته بالكامل؟</p>
            <form method="post" id="delete-student-form">
                <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
                <input type="hidden" name="delete_student_id" id="confirm_delete_stu_id">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="delete_student" class="sm-btn" style="background: #ef4444; flex: 1;">تأكيد الحذف</button>
                    <button type="button" onclick="document.getElementById('delete-student-modal').style.display='none'" class="sm-btn sm-btn-outline" style="flex: 1; background: #fff;">تراجع</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Comprehensive Student Record Modal -->
    <div id="view-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 900px; padding: 25px;">
            <div class="sm-modal-header" style="border-bottom: 2px solid var(--sm-primary-color); padding-bottom: 15px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 style="margin:0; font-size: 16px; font-weight: 800; color: var(--sm-dark-color);">السجل الانضباطي الشامل للطالب</h3>
                    <div style="display: flex; gap: 8px;">
                        <button id="print-full-record-btn" class="sm-btn" style="background: #10b981; font-size: 11px; height: 32px; padding: 0 12px; font-weight: 700;">
                            <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px; margin-left: 3px;"></span> طباعة السجل PDF
                        </button>
                        <button class="sm-modal-close" style="position:static; margin:0;" onclick="document.getElementById('view-student-modal').style.display='none'">&times;</button>
                    </div>
                </div>
            </div>
            <div id="stu_details_content" style="padding: 10px 0; max-height: 65vh; overflow-y: auto;"></div>
            <div style="margin-top: 15px; text-align: left; border-top: 1px solid var(--sm-border-color); padding-top: 15px;">
                <button type="button" onclick="document.getElementById('view-student-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff;">إغلاق السجل</button>
            </div>
        </div>
    </div>

    <!-- Student Credentials Modal -->
    <div id="student-creds-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">بيانات دخول الطالب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('student-creds-modal').style.display='none'">&times;</button>
            </div>
            <div style="padding: 20px; background: var(--sm-bg-light); border-radius: 12px; margin-top: 15px; border: 1px solid var(--sm-border-color);">
                <div style="font-weight: 800; color: var(--sm-dark-color); margin-bottom: 15px; font-size: 13px;" id="cred-stu-name"></div>

                <div style="margin-bottom: 15px;">
                    <div style="font-size: 11px; color: var(--sm-text-gray); margin-bottom: 5px; font-weight: 700;">اسم المستخدم (كود الطالب):</div>
                    <div style="font-family: monospace; font-size: 16px; font-weight: 800; color: var(--sm-primary-color); background: #fff; padding: 8px; border-radius: 6px; border: 1px solid var(--sm-border-color);" id="cred-username"></div>
                </div>

                <div style="margin-bottom: 5px;">
                    <div style="font-size: 11px; color: var(--sm-text-gray); margin-bottom: 5px; font-weight: 700;">كلمة المرور المؤقتة:</div>
                    <div style="font-family: monospace; font-size: 16px; font-weight: 800; color: var(--sm-dark-color); background: #fff; padding: 8px; border-radius: 6px; border: 1px solid var(--sm-border-color);" id="cred-password"></div>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <a href="#" id="cred-download-link" target="_blank" class="sm-btn" style="background: var(--sm-primary-color); text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; flex: 1; font-size: 12px; font-weight: 700; height: 36px;">
                    <span class="dashicons dashicons-download"></span> تحميل البطاقة
                </a>
                <button onclick="document.getElementById('student-creds-modal').style.display='none'" class="sm-btn sm-btn-outline" style="flex: 1; background: #fff;">إغلاق</button>
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
            
            content.innerHTML = '<div style="text-align:center; padding:50px;"><p style="font-weight:700; color:var(--sm-text-gray);">جاري جلب الملف الانضباطي وتنسيقه...</p></div>';
            modal.style.display = 'flex';

            printBtn.onclick = function() {
                window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id, '_blank');
            };

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id)
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    doc.querySelectorAll('.no-print').forEach(el => el.remove());
                    content.innerHTML = doc.body.innerHTML;
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
                        smShowNotification('تم تحديث بيانات الطالب بنجاح');
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
                        smShowNotification('تم حذف الطالب من النظام بنجاح');
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
