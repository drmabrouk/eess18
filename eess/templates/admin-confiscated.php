<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0; border:none; padding:0;">المواد المصادرة والممتلكات المحظورة</h3>
        <button onclick="document.getElementById('add-confiscated-modal').style.display='flex'" class="sm-btn" style="width:auto;">+ تسجيل مادة جديدة</button>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>الطالب</th>
                    <th>المادة</th>
                    <th>تاريخ المصادرة</th>
                    <th>فترة الحجز</th>
                    <th>نوع الحجز</th>
                    <th>الوقت المتبقي</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="7" style="padding: 40px; text-align: center;">لا توجد مواد مصادرة مسجلة حالياً.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $row): 
                        $created = strtotime($row->created_at);
                        $expires = $created + ($row->holding_period * 24 * 60 * 60);
                        $remaining = $expires - time();
                        $days_left = ceil($remaining / (24 * 60 * 60));
                        $is_expired = $remaining <= 0;
                    ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo esc_html($row->student_name); ?><br><small style="color:#718096;"><?php echo SM_Settings::format_grade_name($row->class_name, $row->section, 'short'); ?></small></td>
                            <td style="color:var(--sm-primary-color); font-weight:600;"><?php echo esc_html($row->item_name); ?></td>
                            <td><?php echo date('Y-m-d', $created); ?></td>
                            <td><?php echo (int)$row->holding_period; ?> يوم</td>
                            <td><?php echo $row->is_returnable ? 'قابل للإعادة' : 'مصادرة نهائية'; ?></td>
                            <td>
                                <?php if ($row->status == 'returned'): ?>
                                    <span style="color:#38a169;">تم التسليم</span>
                                <?php elseif ($is_expired): ?>
                                    <span style="color:#e53e3e; font-weight:800;">انتهت المدة! (يجب الإعادة)</span>
                                <?php else: ?>
                                    <span style="color:#dd6b20; font-weight:600;"><?php echo $days_left; ?> يوم متبقي</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="sm-badge sm-badge-<?php echo $row->status == 'held' ? 'high' : 'low'; ?>">
                                    <?php echo $row->status == 'held' ? 'محجوزة' : 'تمت الإعادة'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <?php if ($row->status == 'held'): ?>
                                        <button onclick="markReturned(<?php echo $row->id; ?>)" class="sm-btn" style="background:#38a169; padding:5px 10px; font-size:11px; width:auto;">إعادة للطالب</button>
                                    <?php endif; ?>
                                    <button onclick="deleteConfiscated(<?php echo $row->id; ?>)" class="sm-btn sm-btn-outline" style="padding: 5px; min-width: 32px; color: #e53e3e;" title="حذف السجل">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Item Modal -->
    <div id="add-confiscated-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 600px;">
            <div class="sm-modal-header">
                <h3>تسجيل مادة مصادرة جديدة</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-confiscated-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-confiscated-form">
                <?php wp_nonce_field('sm_confiscated_action', 'nonce'); ?>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="sm-form-group" style="grid-column: span 2;">
                        <label class="sm-label">البحث عن الطالب:</label>
                        <input type="text" id="confiscated-student-search" class="sm-input" placeholder="اكتب اسم الطالب أو كوده..." onkeyup="confiscatedSearchStudents(this.value)" autocomplete="off">
                        <div id="confiscated-search-results" style="background: #fff; border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; display: none; position: absolute; z-index: 100; width: calc(100% - 40px);"></div>

                        <div id="confiscated-selected-student-box" style="display: none; background: #f0fdf4; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; margin-top: 10px; position: relative;">
                            <div style="font-weight: 800;" id="confiscated-selected-student-name"></div>
                            <div style="font-size: 12px; color: #166534;" id="confiscated-selected-student-info"></div>
                            <input type="hidden" name="student_id" id="confiscated-selected-student-id" required>
                            <button type="button" onclick="resetConfiscatedStudent()" style="position: absolute; left: 10px; top: 10px; background: none; border: none; color: #e53e3e; cursor: pointer;">&times; تغيير الطالب</button>
                        </div>
                    </div>

                    <div class="sm-form-group">
                        <label class="sm-label">نوع المادة المصادرة:</label>
                        <select name="item_name" class="sm-select" required onchange="if(this.value=='other') document.getElementById('other_item').style.display='block'; else document.getElementById('other_item').style.display='none';">
                            <option value="هاتف محمول">هاتف محمول</option>
                            <option value="سماعات">سماعات</option>
                            <option value="سيجارة إلكترونية">سيجارة إلكترونية</option>
                            <option value="ألعاب إلكترونية">ألعاب إلكترونية</option>
                            <option value="أدوات حادة">أدوات حادة</option>
                            <option value="other">أخرى (اذكرها...)</option>
                        </select>
                        <input type="text" id="other_item" name="item_name_other" class="sm-input" style="display:none; margin-top:10px;" placeholder="اكتب اسم المادة...">
                    </div>

                    <div class="sm-form-group">
                        <label class="sm-label">مدة الحجز (يوم):</label>
                        <select name="holding_period" class="sm-select">
                            <option value="7">أسبوع واحد</option>
                            <option value="15">15 يوم</option>
                            <option value="30" selected>شهر كامل (30 يوم)</option>
                            <option value="90">فصل دراسي (90 يوم)</option>
                            <option value="0">مصادرة نهائية (حتى نهاية العام)</option>
                        </select>
                    </div>

                    <div class="sm-form-group" style="grid-column: span 2; background: #fdf2f2; padding: 15px; border-radius: 8px;">
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight:700; color:#8b0000;">
                            <input type="checkbox" name="is_returnable" value="1" checked>
                            <span>تعهد بإعادة المادة بعد انتهاء الفترة المحددة</span>
                        </label>
                    </div>

                    <div class="sm-form-group" style="grid-column: span 2; background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8;">
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight:700; color:#c05621; margin-bottom:10px;">
                            <input type="checkbox" name="link_violation" value="1" onchange="document.getElementById('violation-linking-fields').style.display = this.checked ? 'block' : 'none'">
                            <span>ربط العملية بمخالفة سلوكية (حسم تلقائي من الدرجات)</span>
                        </label>

                        <div id="violation-linking-fields" style="display: none; padding-top: 10px; border-top: 1px dashed #feebc8;">
                            <label class="sm-label">نوع المخالفة المرتبطة:</label>
                            <select name="violation_code" class="sm-select">
                                <option value="1.9">1.9 - سوء استخدام الأجهزة الإلكترونية الشخصية (3 درجات)</option>
                                <option value="3.9">3.9 - حيازة مواد ممنوعة (10 درجات)</option>
                                <option value="3.10">3.10 - التصوير داخل المدرسة بدون إذن (10 درجات)</option>
                                <option value="other">أخرى (سيتم تسجيلها كمخالفة عامة)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="sm-btn" style="margin-top:20px; width: 100%; height:50px; font-size:1.1em;">تأكيد وحفظ عملية المصادرة</button>
            </form>
        </div>
    </div>

    <script>
    (function() {
        window.confiscatedSearchStudents = function(query) {
            if (query.length < 2) {
                document.getElementById('confiscated-search-results').style.display = 'none';
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
                        html += `<div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;" onclick="selectConfiscatedStudent(${s.id}, '${s.name}', '${s.class_name} ${s.section}')">
                                    <strong>${s.name}</strong> (${s.student_code})<br><small>${s.class_name} - ${s.section}</small>
                                 </div>`;
                    });
                    document.getElementById('confiscated-search-results').innerHTML = html;
                    document.getElementById('confiscated-search-results').style.display = 'block';
                }
            });
        };

        window.selectConfiscatedStudent = function(id, name, info) {
            document.getElementById('confiscated-selected-student-id').value = id;
            document.getElementById('confiscated-selected-student-name').innerText = name;
            document.getElementById('confiscated-selected-student-info').innerText = info;
            document.getElementById('confiscated-selected-student-box').style.display = 'block';
            document.getElementById('confiscated-search-results').style.display = 'none';
            document.getElementById('confiscated-student-search').style.display = 'none';
        };

        window.resetConfiscatedStudent = function() {
            document.getElementById('confiscated-selected-student-id').value = '';
            document.getElementById('confiscated-selected-student-box').style.display = 'none';
            document.getElementById('confiscated-student-search').style.display = 'block';
            document.getElementById('confiscated-student-search').value = '';
            document.getElementById('confiscated-student-search').focus();
        };

        const addForm = document.getElementById('add-confiscated-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!document.getElementById('confiscated-selected-student-id').value) {
                    alert('يرجى اختيار طالب أولاً');
                    return;
                }
                const formData = new FormData(this);
                formData.append('action', 'sm_add_confiscated_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم تسجيل المادة بنجاح');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification(res.data || 'حدث خطأ ما', true);
                    }
                });
            });
        }

        window.markReturned = function(id) {
            document.getElementById('return-confirm-item-id').value = id;
            document.getElementById('return-confirmation-modal').style.display = 'flex';
        };

        window.confirmReturnAction = function() {
            const id = document.getElementById('return-confirm-item-id').value;
            const formData = new FormData();
            formData.append('action', 'sm_update_confiscated_ajax');
            formData.append('item_id', id);
            formData.append('status', 'returned');
            formData.append('nonce', '<?php echo wp_create_nonce("sm_confiscated_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تم تحديث الحالة: تمت الإعادة بنجاح');
                    setTimeout(() => location.reload(), 500);
                }
            });
        };

        window.deleteConfiscated = function(id) {
            if (!confirm('هل أنت متأكد من حذف سجل هذه المادة المصادرة؟ لا يمكن التراجع عن هذا الإجراء.')) return;

            const formData = new FormData();
            formData.append('action', 'sm_delete_confiscated_ajax');
            formData.append('item_id', id);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_confiscated_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تم حذف السجل بنجاح');
                    setTimeout(() => location.reload(), 500);
                } else {
                    smShowNotification('خطأ في عملية الحذف', true);
                }
            });
        };

        document.addEventListener('click', function(e) {
            const results = document.getElementById('confiscated-search-results');
            if (results && !results.contains(e.target) && e.target.id !== 'confiscated-student-search') {
                results.style.display = 'none';
            }
        });
    })();
    </script>

    <!-- Return Confirmation Modal -->
    <div id="return-confirmation-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center;">
            <div style="color: #38a169; font-size: 48px; margin-bottom: 20px;">
                <span class="dashicons dashicons-external" style="font-size: 48px; width: 48px; height: 48px;"></span>
            </div>
            <h3 style="margin: 0 0 15px 0; border: none; font-weight: 800;">تأكيد تسليم العهدة</h3>
            <p style="color: #4A5568; font-size: 14px; margin-bottom: 25px;">هل تم التأكد من تسليم المادة المصادرة للطالب أو ولي أمره بشكل رسمي؟</p>

            <input type="hidden" id="return-confirm-item-id">

            <div style="display: flex; gap: 12px;">
                <button onclick="confirmReturnAction()" class="sm-btn" style="flex: 1; background: #38a169;">نعم، تم التسليم</button>
                <button onclick="document.getElementById('return-confirmation-modal').style.display='none'" class="sm-btn" style="flex: 1; background: #EDF2F7; color: #4A5568 !important;">إلغاء</button>
            </div>
        </div>
    </div>
</div>
