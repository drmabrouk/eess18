<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-form-container" dir="rtl" style="font-family: 'Cairo', sans-serif; background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
    <!-- Title & Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid var(--sm-border-color); flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 class="sm-form-title" style="margin: 0; font-size: 15px; font-weight: 800; color: var(--sm-dark-color);">بيانات المخالفة الجديدة واللائحة السلوكية</h3>
            <p style="margin: 3px 0 0 0; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">رصد وإدراج الحالات السلوكية فوراً، وإشعار أولياء الأمور وحسم نقاط السلوك</p>
        </div>
        <div id="barcode-scanner-section" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px; background: var(--sm-bg-light); padding: 0 12px; border-radius: 8px; border: 1px solid var(--sm-border-color); height: 38px;">
                <label class="sm-label" style="margin:0; font-size: 11px; font-weight: 800; color: var(--sm-primary-color);">التاريخ:</label>
                <input type="date" form="violation-form" name="custom_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" required style="padding: 0; font-size: 11px; font-weight: 800; width: auto; border: none; background: transparent; height: 100%; color: var(--sm-dark-color);">
            </div>
            <button id="start-scanner" type="button" class="sm-btn" style="width: auto; padding: 0 15px; background: var(--sm-dark-color); font-size: 11px; font-weight: 700; height: 38px; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-barcode" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span> استخدام الماسح الضوئي
            </button>
        </div>
    </div>

    <!-- QR Scanner Reader -->
    <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto 20px auto; display: none; border-radius: 12px; overflow: hidden; border: 2px solid var(--sm-primary-color); box-shadow: var(--sm-shadow);"></div>
    
    <!-- Student Intelligence Panel -->
    <div id="student-intelligence-panel" style="display:none; background: #fffdf5; border: 1px solid #fde68a; border-radius: 10px; padding: 20px; margin-bottom: 25px; border-right: 4px solid #d97706; box-shadow: var(--sm-shadow);">
        <h4 style="margin: 0 0 12px 0; color: #d97706; font-size: 13px; font-weight: 800;">🧠 تحليل السلوك الطلابي الذكي (مؤشرات الإنذار المبكر)</h4>
        <div id="intel-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; font-size: 11px; font-weight: 700;">
            <!-- Content loaded via AJAX -->
        </div>
        <div id="intel-history" style="margin-top: 15px; font-size: 11px; color: var(--sm-text-gray); border-top: 1px dashed #fde68a; padding-top: 12px; font-weight: 600; line-height: 1.6;">
            <!-- Latest violations -->
        </div>
    </div>

    <div id="sm-ajax-response" style="display:none; margin-bottom: 25px;"></div>

    <!-- Main Registration Form -->
    <form method="post" id="violation-form">
        <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
        
        <!-- Student Auto-complete Field -->
        <div class="sm-form-group" style="position:relative; margin-bottom: 20px;">
            <label class="sm-label">ابحث عن الطلاب بالاسم أو الكود (يمكنك اختيار طالب واحد أو أكثر):</label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="student_unified_search" class="sm-input" placeholder="اكتب اسم الطالب، الكود السلوكي، الصف..." autocomplete="off" style="height: 38px; font-size: 12px;">
            </div>
            <!-- Search Dropdown Results -->
            <div id="search_results_dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid var(--sm-border-color); border-radius:8px; z-index:1000; box-shadow: var(--sm-shadow); max-height:250px; overflow-y:auto; margin-top: 5px;">
                <!-- Results via AJAX -->
            </div>
            <!-- Selected Tags -->
            <div id="selected_students_container" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                <!-- Selected students tags -->
            </div>
            <input type="hidden" name="student_ids" id="selected_student_ids" required>
        </div>

        <!-- Grade level / violation rules selector box -->
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; background: var(--sm-bg-light); padding: 18px; border-radius: 10px; border: 1px solid var(--sm-border-color); margin-bottom: 20px; flex-wrap: wrap;">
            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">درجة المخالفة (المستوى السلوكي):</label>
                <select name="degree" id="violation_degree" class="sm-select" onchange="updateHierarchicalViolations()" required style="height: 38px; font-size: 12px; font-weight: 700; background: #fff;">
                    <option value="">-- اختر الدرجة --</option>
                    <option value="1">المستوى الأول (بسيطة)</option>
                    <option value="2">المستوى الثاني (متوسطة)</option>
                    <option value="3">المستوى الثالث (جسيمة)</option>
                    <option value="4">المستوى الرابع (شديدة الخطورة)</option>
                </select>
            </div>

            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">البند اللائحي المعتمد / نوع المخالفة:</label>
                <select name="violation_code" id="violation_code_select" class="sm-select" onchange="onViolationSelected()" required disabled style="height: 38px; font-size: 12px; font-weight: 700; background: #fff;">
                    <option value="">-- اختر البند --</option>
                </select>
            </div>
        </div>

        <!-- Additional Information Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="sm-form-group">
                <label class="sm-label">تصنيف وسياق الموقف:</label>
                <select name="classification" class="sm-select" style="height: 38px; font-size: 12px; background: #fff;">
                    <option value="general">عام / ساحة المدرسة</option>
                    <option value="inside_class">داخل الصف الدراسي</option>
                    <option value="yard">في الفناء والاستراحة</option>
                    <option value="labs">في المختبرات وقاعات المصادر</option>
                    <option value="bus">الحافلة المدرسية والنقل</option>
                </select>
            </div>

            <div class="sm-form-group">
                <label class="sm-label">النقاط المستقطعة لملف السلوك:</label>
                <input type="number" name="points" id="violation_points" class="sm-input" value="0" style="height: 38px; font-size: 12px; text-align: center; font-weight: 800;">
            </div>

            <input type="hidden" name="severity" id="violation_severity" value="low">
            <input type="hidden" name="type" id="hidden_violation_type">
        </div>

        <!-- Disciplinary Actions Selector -->
        <div class="sm-form-group" style="margin-bottom: 20px;">
            <label class="sm-label">الإجراء المتخذ (وفق مستويات التدرج):</label>
            <select name="action_taken" id="action_taken" class="sm-select" required style="height: 38px; font-size: 12px; background: #fff;">
                <option value="">-- اختر الإجراء السلوكي المعتمد --</option>
                <?php foreach (SM_Settings::get_disciplinary_actions() as $level => $act): ?>
                    <option value="<?php echo esc_attr($act); ?>" data-level="<?php echo $level; ?>"><?php echo $level . '. ' . esc_html($act); ?></option>
                <?php endforeach; ?>
            </select>
            <div id="action-progression-warning" style="display:none; margin-top:10px; padding:12px; background: #fefaf0; border:1px solid #fde68a; border-radius:8px; font-size:11px; color:#b45309; font-weight: 700; line-height: 1.5;">
                <span class="dashicons dashicons-info" style="font-size:14px; width:14px; height:14px; margin-left:5px; vertical-align: middle;"></span>
                ملاحظة ذكية: هذا الطالب لديه قرارات سلوكية سابقة في ملفه. ننصح بالانتقال للإجراء التالي بالتدرج أو العقوبة الموازية للمستوى الدراسي.
            </div>
        </div>

        <!-- Notes Field -->
        <div class="sm-form-group" style="margin-bottom: 25px;">
            <label class="sm-label">ملاحظات إضافية وتفاصيل الموقف:</label>
            <textarea name="details" class="sm-textarea" placeholder="يرجى كتابة ملخص موجز أو ملاحظات عن كيفية حدوث الواقعة للتسجيل والطباعة..." rows="3" style="font-size: 12px;"></textarea>
        </div>

        <!-- Submit Button -->
        <button type="submit" id="submit-btn" class="sm-btn" style="width: 100%; height: 46px; font-weight: 800; font-size: 13px; border-radius: 8px;">حفظ واعتماد تسجيل الحالة السلوكية فورا</button>
    </form>
</div>

<script>
const hViolations = <?php echo json_encode(SM_Settings::get_hierarchical_violations()); ?>;

window.updateHierarchicalViolations = function() {
    const degree = document.getElementById('violation_degree').value;
    const select = document.getElementById('violation_code_select');

    select.innerHTML = '<option value="">-- اختر البند اللائحي --</option>';
    if (!degree || !hViolations[degree]) {
        select.disabled = true;
        return;
    }

    Object.keys(hViolations[degree]).forEach(code => {
        const v = hViolations[degree][code];
        const opt = document.createElement('option');
        opt.value = code;
        opt.innerText = code + ' - ' + v.name;
        select.appendChild(opt);
    });
    select.disabled = false;
};

window.onViolationSelected = function() {
    const degree = document.getElementById('violation_degree').value;
    const code = document.getElementById('violation_code_select').value;

    if (!degree || !code || !hViolations[degree][code]) return;

    const v = hViolations[degree][code];
    document.getElementById('violation_points').value = v.points;

    // Auto-select based on severity but allow structured override
    const actionSelect = document.getElementById('action_taken');
    if (actionSelect && v.action) {
        let found = false;
        for (let i = 0; i < actionSelect.options.length; i++) {
            if (actionSelect.options[i].value === v.action) {
                actionSelect.selectedIndex = i;
                found = true;
                break;
            }
        }
    }

    document.getElementById('hidden_violation_type').value = v.name;

    // Auto severity
    const sev = document.getElementById('violation_severity');
    if (degree == 1) sev.value = 'low';
    else if (degree == 2) sev.value = 'medium';
    else sev.value = 'high';

    if (typeof updateSuggestions === 'function') updateSuggestions(sev.value);
};

(function() {
    let searchTimer;
    document.addEventListener('click', function(e) {
        const searchInput = document.getElementById('student_unified_search');
        if (searchInput && !searchInput.contains(e.target)) {
            const dropdown = document.getElementById('search_results_dropdown');
            if (dropdown) dropdown.style.display = 'none';
        }
    });

    const unifiedSearch = document.getElementById('student_unified_search');
    if (unifiedSearch) {
        unifiedSearch.addEventListener('input', function() {
            const query = this.value;
            clearTimeout(searchTimer);
            if (query.length < 2) {
                document.getElementById('search_results_dropdown').style.display = 'none';
                return;
            }

            searchTimer = setTimeout(() => {
                const formData = new FormData();
                formData.append('action', 'sm_search_students');
                formData.append('query', query);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const results = res.data;
                        const dropdown = document.getElementById('search_results_dropdown');
                        dropdown.innerHTML = '';
                        if (results.length === 0) {
                            dropdown.innerHTML = '<div style="padding:15px; color:var(--sm-text-gray); text-align:center; font-size:11px; font-weight:700;">لم يتم العثور على أية نتائج مطابقة.</div>';
                        } else {
                            results.forEach(s => {
                                const div = document.createElement('div');
                                div.className = 'sm-search-result-item';
                                div.style = "padding:10px 15px; border-bottom:1px solid var(--sm-border-color); cursor:pointer; display:flex; align-items:center; gap:10px; transition: background 0.2s;";
                                div.onmouseover = () => div.style.background = 'var(--sm-bg-light)';
                                div.onmouseout = () => div.style.background = '#fff';
                                div.innerHTML = `
                                    ${s.photo_url ? `<img src="${s.photo_url}" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid var(--sm-border-color);">` : '<span class="dashicons dashicons-admin-users" style="font-size:18px; width:18px; height:18px; color:var(--sm-text-gray);"></span>'}
                                    <div style="min-width:0; flex:1;">
                                        <div style="font-weight:800; font-size:12px; color:var(--sm-dark-color);">${s.name}</div>
                                        <div style="font-size:10px; color:var(--sm-text-gray); font-weight:600; margin-top:2px;">كود الطالب: ${s.student_code} | الفصل: ${s.class_name} ${s.section || ''}</div>
                                    </div>
                                `;
                                div.onclick = () => selectStudent(s);
                                dropdown.appendChild(div);
                            });
                        }
                        dropdown.style.display = 'block';
                    }
                });
            }, 300);
        });
    }

    let selectedStudents = [];

    window.selectStudent = function(s) {
        if (selectedStudents.find(x => x.id === s.id)) return;

        selectedStudents.push(s);
        renderSelectedStudents();
        document.getElementById('student_unified_search').value = '';
        document.getElementById('search_results_dropdown').style.display = 'none';

        if (selectedStudents.length === 1) {
            fetchIntelligence(s.id);
        } else {
            document.getElementById('student-intelligence-panel').style.display = 'none';
        }
    };

    window.renderSelectedStudents = function() {
        const container = document.getElementById('selected_students_container');
        container.innerHTML = '';
        const ids = [];

        selectedStudents.forEach(s => {
            ids.push(s.id);
            const tag = document.createElement('div');
            tag.style = "background: #f0fdf4; padding: 4px 12px; border-radius: 9999px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; color: #16a34a;";
            tag.innerHTML = `
                <span>${s.name}</span>
                <span onclick="removeStudent(${s.id})" style="cursor:pointer; color:#dc2626; font-size: 10px;">✖</span>
            `;
            container.appendChild(tag);
        });

        document.getElementById('selected_student_ids').value = ids.join(',');
    };

    window.removeStudent = function(id) {
        if (!confirm('هل أنت متأكد من إزالة هذا الطالب من القائمة؟')) return;
        selectedStudents = selectedStudents.filter(x => x.id !== id);
        renderSelectedStudents();
        if (selectedStudents.length === 1) fetchIntelligence(selectedStudents[0].id);
        else document.getElementById('student-intelligence-panel').style.display = 'none';
    };

    window.clearStudentSelection = function() {
        selectedStudents = [];
        renderSelectedStudents();
        document.getElementById('student-intelligence-panel').style.display = 'none';
    };

    const startScannerBtn = document.getElementById('start-scanner');
    if (startScannerBtn) {
        startScannerBtn.addEventListener('click', function() {
            const reader = document.getElementById('reader');
            reader.style.display = 'block';
            const html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start({ facingMode: "environment" }, { fps: 15, qrbox: 250 }, onScanSuccess);

            function onScanSuccess(decodedText) {
                html5QrCode.stop().then(() => {
                    reader.style.display = 'none';

                    const formData = new FormData();
                    formData.append('action', 'sm_get_student');
                    formData.append('code', decodedText);

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            selectStudent(res.data);
                        } else {
                            alert('عذراً، كود غير معروف: ' + decodedText);
                        }
                    });
                });
            }
        });
    }

    window.fetchIntelligence = function(studentId) {
        if (!studentId) {
            document.getElementById('student-intelligence-panel').style.display = 'none';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sm_get_student_intelligence');
        formData.append('student_id', studentId);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const data = res.data;
                document.getElementById('student-intelligence-panel').style.display = 'block';

                let photoHtml = data.photo_url ? `<img src="${data.photo_url}" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid #d97706; flex-shrink:0;">` : '';

                let intelHtml = `
                    <div style="grid-column: 1 / -1; display:flex; align-items:center; gap:12px; margin-bottom:10px; border-bottom:1px solid #fde68a; padding-bottom:8px;">
                        ${photoHtml}
                        <h4 style="margin:0; font-size:12px; font-weight:800; color: #b45309;">تحليل الملف السلوكي والأكاديمي للطالب</h4>
                    </div>
                    <div><strong>إجمالي المخالفات المرصودة:</strong> <span style="color:#dc2626; font-size:12px; font-weight:800;">${data.stats.total} مخالفة</span></div>
                    <div><strong>بند المخالفة الأكثر تكراراً:</strong> <span style="color:var(--sm-primary-color);">${data.labels[data.stats.frequent_type] || 'لا يوجد'}</span></div>
                    <div><strong>آخر إجراء تم اعتماده سابقاً:</strong> <span style="color:var(--sm-primary-color);">${data.stats.last_action || 'لا يوجد'}</span></div>
                `;
                document.getElementById('intel-content').innerHTML = intelHtml;

                let historyHtml = '<strong>آخر 3 ملاحظات مسجلة بملفه:</strong> ';
                if (data.recent.length === 0) historyHtml += 'ملف الطالب نظيف بالكامل ولا توجد مخالفات مسجلة سابقاً.';
                data.recent.forEach(r => {
                    historyHtml += `<span style="margin-left:15px; display:inline-block; background:#fff; padding:2px 8px; border-radius:4px; border:1px solid #fde68a; margin-top:4px;">• ${r.created_at.split(' ')[0]}: ${data.labels[r.type]} (${r.severity})</span>`;
                });
                document.getElementById('intel-history').innerHTML = historyHtml;

                const actionSelect = document.getElementById('action_taken');
                const warningBox = document.getElementById('action-progression-warning');

                if (actionSelect) {
                    const nextIndex = data.last_action_index + 1;

                    for (let i = 0; i < actionSelect.options.length; i++) {
                        const opt = actionSelect.options[i];
                        const level = parseInt(opt.getAttribute('data-level') || 0);

                        if (level > 0) {
                            opt.disabled = false;
                            opt.text = opt.text.replace('(سابق) ', '').replace('(تخطي) ', '');

                            if (level === nextIndex) {
                                opt.text = '⭐ ' + opt.text + ' (مقترح)';
                            }
                        }
                    }

                    if (nextIndex <= 8) {
                        for (let i = 0; i < actionSelect.options.length; i++) {
                            if (parseInt(actionSelect.options[i].getAttribute('data-level')) === nextIndex) {
                                actionSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    if (data.last_action_index > 0) warningBox.style.display = 'block';
                    else warningBox.style.display = 'none';
                }

                if (data.stats.high_severity_count > 2) {
                    const sEl = document.getElementById('violation_severity');
                    if (sEl) sEl.value = 'high';
                }
            }
        });
    };

    const violForm = document.getElementById('violation-form');
    if (violForm) {
        violForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');

            btn.innerText = 'جاري تسجيل الحالة وإرسال التنبيهات...';
            btn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'sm_save_record_ajax');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const overlay = document.createElement('div');
                    overlay.style = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; z-index:100005; backdrop-filter: blur(2px);";
                    overlay.innerHTML = `
                        <div style="background:white; padding:40px 60px; border-radius:16px; text-align:center; box-shadow: var(--sm-shadow); border:1px solid var(--sm-border-color); font-family:'Cairo', sans-serif;">
                            <div style="font-size:45px; margin-bottom:15px; color:#16a34a;">✅</div>
                            <h3 style="margin:0; color:var(--sm-dark-color); font-weight:800; font-size:16px;">تم حفظ وتسجيل الحالة بنجاح</h3>
                            <p style="margin-top:8px; color:var(--sm-text-gray); font-weight:700; font-size:12px;">يتم الآن تحديث مؤشرات اللوحة وتحديث البيانات السلوكية...</p>
                        </div>
                    `;
                    document.body.appendChild(overlay);

                    setTimeout(() => {
                        overlay.remove();
                        if (typeof smCloseViolationModal === 'function') {
                            smCloseViolationModal();
                        } else if (document.getElementById('sm-global-violation-modal')) {
                            document.getElementById('sm-global-violation-modal').style.display = 'none';
                        }
                        location.reload();
                    }, 1800);

                    this.reset();
                    clearStudentSelection();
                } else {
                    smShowNotification('خطأ: ' + (res.data || 'فشل في حفظ السجل السلوكي'), true);
                    btn.innerText = 'حفظ وتسجيل المخالفة الآن';
                    btn.disabled = false;
                }
            });
        });
    }
})();
</script>
