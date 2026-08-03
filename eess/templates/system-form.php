<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-form-container" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
        <h3 class="sm-form-title" style="margin:0; border:none; padding:0; font-size: 1.2em; font-weight: 800; color: var(--sm-primary-color);">بيانات المخالفة الجديدة</h3>
        <div id="barcode-scanner-section" style="display: flex; align-items: center; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label class="sm-label" style="margin:0; font-size: 12px; font-weight: 800; color: #475569;">التاريخ:</label>
                <input type="date" form="violation-form" name="custom_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" required style="padding: 4px 8px; font-size: 12px; width: auto; border: none; background: transparent;">
            </div>
            <button id="start-scanner" type="button" class="sm-btn" style="width: auto; padding: 10px 20px; background: var(--sm-dark-color); font-size: 13px; font-weight: 700;"><span class="dashicons dashicons-barcode" style="vertical-align: middle; margin-left: 5px;"></span> استخدام الماسح الضوئي</button>
        </div>
    </div>

    <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto 20px auto; display: none; border-radius: 8px; overflow: hidden; border: 2px solid var(--sm-primary-color);"></div>
    
    <div id="student-intelligence-panel" style="display:none; background: #fdfdfd; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 30px; border-right: 4px solid var(--sm-primary-color);">
        <h4 style="margin-top:0; color:var(--sm-primary-color);">تحليل سلوك الطالب الذكي</h4>
        <div id="intel-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
            <!-- Content loaded via AJAX -->
        </div>
        <div id="intel-history" style="margin-top: 15px; font-size: 0.85em; color: #666; border-top: 1px dashed #eee; padding-top: 10px;">
            <!-- Latest violations -->
        </div>
    </div>

    <div id="sm-ajax-response" style="display:none; margin-bottom: 25px;"></div>

    <form method="post" id="violation-form">
        <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
        
        <div class="sm-form-group" style="position:relative;">
            <label class="sm-label">البحث عن الطلاب (يمكنك اختيار أكثر من طالب):</label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="student_unified_search" class="sm-input" placeholder="اكتب اسم الطالب أو الكود..." autocomplete="off">
            </div>
            <div id="search_results_dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid var(--sm-border-color); border-radius:0 0 8px 8px; z-index:1000; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); max-height:250px; overflow-y:auto;">
                <!-- Results via AJAX -->
            </div>
            <div id="selected_students_container" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;">
                <!-- Selected students tags -->
            </div>
            <input type="hidden" name="student_ids" id="selected_student_ids" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">درجة المخالفة (المستوى):</label>
                <select name="degree" id="violation_degree" class="sm-select" onchange="updateHierarchicalViolations()" required>
                    <option value="">-- اختر الدرجة --</option>
                    <option value="1">المستوى الأول (بسيطة)</option>
                    <option value="2">المستوى الثاني (متوسطة)</option>
                    <option value="3">المستوى الثالث (جسيمة)</option>
                    <option value="4">المستوى الرابع (شديدة الخطورة)</option>
                </select>
            </div>

            <div class="sm-form-group" style="margin-bottom:0;">
                <label class="sm-label">البند القانوني / نوع المخالفة:</label>
                <select name="violation_code" id="violation_code_select" class="sm-select" onchange="onViolationSelected()" required disabled>
                    <option value="">-- اختر البند --</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="sm-form-group">
                <label class="sm-label">تصنيف الموقف:</label>
                <select name="classification" class="sm-select">
                    <option value="general">عام</option>
                    <option value="inside_class">داخل الفصل</option>
                    <option value="yard">في الساحة</option>
                    <option value="labs">في المختبرات</option>
                    <option value="bus">الحافلة المدرسية</option>
                </select>
            </div>

            <div class="sm-form-group">
                <label class="sm-label">النقاط المستحقة:</label>
                <input type="number" name="points" id="violation_points" class="sm-input" value="0">
            </div>

            <input type="hidden" name="severity" id="violation_severity" value="low">
            <input type="hidden" name="type" id="hidden_violation_type">
        </div>

        <div class="sm-form-group">
            <label class="sm-label">الإجراء المتخذ:</label>
            <select name="action_taken" id="action_taken" class="sm-select" required>
                <option value="">-- اختر الإجراء --</option>
                <?php foreach (SM_Settings::get_disciplinary_actions() as $level => $act): ?>
                    <option value="<?php echo esc_attr($act); ?>" data-level="<?php echo $level; ?>"><?php echo $level . '. ' . esc_html($act); ?></option>
                <?php endforeach; ?>
            </select>
            <div id="action-progression-warning" style="display:none; margin-top:8px; padding:10px; background:#fffaf0; border:1px solid #feebc8; border-radius:6px; font-size:12px; color:#975a16;">
                <span class="dashicons dashicons-info" style="font-size:14px; width:14px; height:14px; margin-left:5px;"></span>
                ملاحظة: هذا الطالب تلقى إجراءات سابقة. يمكنك اختيار الإجراء المناسب بحرية.
            </div>
        </div>

        <div class="sm-form-group">
            <label class="sm-label">التفاصيل:</label>
            <textarea name="details" class="sm-textarea" placeholder="اشرح الموقف بالتفصيل..." rows="3"></textarea>
        </div>

        <button type="submit" id="submit-btn" class="sm-btn" style="width: 100%; height: 50px; font-weight: 800; font-size: 1.1em; border-radius: 10px;">حفظ وتسجيل المخالفة الآن</button>
    </form>
</div>

<script>
const hViolations = <?php echo json_encode(SM_Settings::get_hierarchical_violations()); ?>;

function updateHierarchicalViolations() {
    const degree = document.getElementById('violation_degree').value;
    const select = document.getElementById('violation_code_select');

    select.innerHTML = '<option value="">-- اختر البند --</option>';
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
}

function onViolationSelected() {
    const degree = document.getElementById('violation_degree').value;
    const code = document.getElementById('violation_code_select').value;

    if (!degree || !code || !hViolations[degree][code]) return;

    const v = hViolations[degree][code];
    document.getElementById('violation_points').value = v.points;

    // Auto-select based on severity but allow structured override
    const actionSelect = document.getElementById('action_taken');
    if (actionSelect && v.action) {
        // Find if v.action matches any of our structured levels
        let found = false;
        for (let i = 0; i < actionSelect.options.length; i++) {
            if (actionSelect.options[i].value === v.action) {
                actionSelect.selectedIndex = i;
                found = true;
                break;
            }
        }
        // If not found in structured, maybe just set value if it's open (but it's a select now)
    }

    document.getElementById('hidden_violation_type').value = v.name;

    // Auto severity
    const sev = document.getElementById('violation_severity');
    if (degree == 1) sev.value = 'low';
    else if (degree == 2) sev.value = 'medium';
    else sev.value = 'high';

    if (typeof updateSuggestions === 'function') updateSuggestions(sev.value);
}

(function() {

let searchTimer;
document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('student_unified_search');
    if (searchInput && !searchInput.contains(e.target)) {
        const dropdown = document.getElementById('search_results_dropdown');
        if (dropdown) dropdown.style.display = 'none';
    }
});

document.getElementById('student_unified_search').addEventListener('input', function() {
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
                    dropdown.innerHTML = '<div style="padding:10px; color:#666; text-align:center;">لم يتم العثور على نتائج.</div>';
                } else {
                    results.forEach(s => {
                        const div = document.createElement('div');
                        div.className = 'sm-search-result-item';
                        div.style = "padding:12px 15px; border-bottom:1px solid #eee; cursor:pointer; display:flex; align-items:center; gap:10px; transition: background 0.2s;";
                        div.onmouseover = () => div.style.background = '#f8fafc';
                        div.onmouseout = () => div.style.background = '#fff';
                        div.innerHTML = `
                            ${s.photo_url ? `<img src="${s.photo_url}" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">` : '<span class="dashicons dashicons-admin-users"></span>'}
                            <div>
                                <div style="font-weight:700;">${s.name}</div>
                                <div style="font-size:11px; color:#666;">كود: ${s.student_code} | فصل: ${s.class_name} ${s.section || ''}</div>
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

let selectedStudents = [];

function selectStudent(s) {
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
}

function renderSelectedStudents() {
    const container = document.getElementById('selected_students_container');
    container.innerHTML = '';
    const ids = [];
    
    selectedStudents.forEach(s => {
        ids.push(s.id);
        const tag = document.createElement('div');
        tag.style = "background:#f0f7ff; padding:5px 12px; border-radius:20px; border:1px solid #c3dafe; display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--sm-primary-color);";
        tag.innerHTML = `
            <span>${s.name}</span>
            <span onclick="removeStudent(${s.id})" style="cursor:pointer; color:#e53e3e;">✖</span>
        `;
        container.appendChild(tag);
    });
    
    document.getElementById('selected_student_ids').value = ids.join(',');
}

function removeStudent(id) {
    if (!confirm('هل أنت متأكد من إزالة هذا الطالب من القائمة؟')) return;
    selectedStudents = selectedStudents.filter(x => x.id !== id);
    renderSelectedStudents();
    if (selectedStudents.length === 1) fetchIntelligence(selectedStudents[0].id);
    else document.getElementById('student-intelligence-panel').style.display = 'none';
}

function clearStudentSelection() {
    selectedStudents = [];
    renderSelectedStudents();
    document.getElementById('student-intelligence-panel').style.display = 'none';
}

document.getElementById('start-scanner').addEventListener('click', function() {
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

function fetchIntelligence(studentId) {
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
            
            let photoHtml = data.photo_url ? `<img src="${data.photo_url}" style="width:60px; height:60px; border-radius:50%; object-fit:cover; margin-bottom:10px; border:2px solid var(--sm-primary-color);">` : '';

            let intelHtml = `
                <div style="grid-column: 1 / -1; display:flex; align-items:center; gap:15px; margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:10px;">
                    ${photoHtml}
                    <h4 style="margin:0;">تحليل بيانات الطالب</h4>
                </div>
                <div><strong>إجمالي المخالفات:</strong> <span style="color:red;">${data.stats.total}</span></div>
                <div><strong>النوع الأكثر تكراراً:</strong> <span>${data.labels[data.stats.frequent_type] || 'لا يوجد'}</span></div>
                <div><strong>آخر إجراء متخذ:</strong> <span>${data.stats.last_action || 'لا يوجد'}</span></div>
            `;
            document.getElementById('intel-content').innerHTML = intelHtml;

            let historyHtml = '<strong>آخر 3 ملاحظات:</strong> ';
            if (data.recent.length === 0) historyHtml += 'لا يوجد سجل سابق.';
            data.recent.forEach(r => {
                historyHtml += `<span style="margin-left:15px;">• ${r.created_at.split(' ')[0]}: ${data.labels[r.type]} (${r.severity})</span>`;
            });
            document.getElementById('intel-history').innerHTML = historyHtml;

            // Handle Action Selection (Removed restrictions)
            const actionSelect = document.getElementById('action_taken');
            const warningBox = document.getElementById('action-progression-warning');

            if (actionSelect) {
                const nextIndex = data.last_action_index + 1;

                for (let i = 0; i < actionSelect.options.length; i++) {
                    const opt = actionSelect.options[i];
                    const level = parseInt(opt.getAttribute('data-level') || 0);

                    if (level > 0) {
                        // Enable all levels for supervisors and admins
                        opt.disabled = false;
                        opt.text = opt.text.replace('(سابق) ', '').replace('(تخطي) ', '');

                        if (level === nextIndex) {
                            opt.text = '⭐ ' + opt.text + ' (مقترح)';
                        }
                    }
                }

                // Auto-recommend next level but don't force
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

            // Smart Auto-select based on history
            if (data.stats.high_severity_count > 2) {
                const sEl = document.getElementById('violation_severity');
                if (sEl) sEl.value = 'high';
            }
        }
    });
}

// Handle Form Submission via AJAX
document.getElementById('violation-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    
    btn.innerText = 'جاري الحفظ...';
    btn.disabled = true;

    const formData = new FormData(this);
    formData.append('action', 'sm_save_record_ajax');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Large Centered Success Notification
            const overlay = document.createElement('div');
            overlay.style = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:10002; animation: fadeIn 0.3s;";
            overlay.innerHTML = `
                <div style="background:white; padding:50px 80px; border-radius:20px; text-align:center; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
                    <div style="font-size:60px; color:#38a169; margin-bottom:20px;">✅</div>
                    <h2 style="margin:0; color:#1a202c; font-weight:900;">تم تسجيل المخالفة بنجاح</h2>
                    <p style="margin-top:10px; color:#718096; font-weight:700;">يتم إرسال التنبيهات الآن وإغلاق النافذة...</p>
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
                location.reload(); // To update the dashboard
            }, 2000);

            this.reset();
            clearStudentSelection();
        } else {
            smShowNotification('خطأ: ' + (res.data || 'فشل في حفظ السجل'), true);
            btn.innerText = 'حفظ وإرسال تنبيه فوري';
            btn.disabled = false;
        }
    });
});
})();
</script>
