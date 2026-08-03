<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <!-- Navigation Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid var(--sm-border-color); padding-bottom: 5px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('doc-library-tab', this)">📂 مكتبة الوثائق والتقارير المعتمدة</button>
        <?php if (current_user_can('تسجيل_مخالفة')): ?>
            <button class="sm-tab-btn" onclick="smOpenInternalTab('regulation-custom-tab', this)">📜 تخصيص اللائحة التنظيمية والمخالفات</button>
        <?php endif; ?>
    </div>

    <!-- Documents Library Tab -->
    <div id="doc-library-tab" class="sm-internal-tab">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 20px;">مكتبة الوثائق والتقارير الرسمية</h2>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">تحميل وطباعة النماذج الرسمية، اللوائح المعتمدة، ووثائق المنصة الحكومية</p>
            </div>
            <?php if (current_user_can('إدارة_النظام')): ?>
                <button onclick="document.getElementById('add-doc-modal').style.display='flex'" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700; width: auto; padding: 0 20px;">
                    <span class="dashicons dashicons-plus" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> إضافة مستند جديد
                </button>
            <?php endif; ?>
        </div>

        <?php
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}sm_documents";
        if (!current_user_can('إدارة_النظام')) {
            $query .= " WHERE status = 'published'";
        }
        $query .= " ORDER BY created_at DESC";
        $docs = $wpdb->get_results($query);
        ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <?php if (empty($docs)): ?>
                <div style="grid-column: 1 / -1; background: #ffffff; padding: 60px; border-radius: 12px; text-align: center; border: 2px dashed var(--sm-border-color); box-shadow: var(--sm-shadow);">
                    <span class="dashicons dashicons-media-document" style="font-size: 40px; width: 40px; height: 40px; color: var(--sm-text-gray); margin-bottom: 12px;"></span>
                    <p style="color: var(--sm-text-gray); font-weight: 700; font-size: 13px; margin: 0;">لا توجد مستندات أو وثائق متوفرة في المكتبة حالياً.</p>
                </div>
            <?php else: ?>
                <?php foreach ($docs as $doc): ?>
                    <div class="sm-doc-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; overflow: hidden; box-shadow: var(--sm-shadow); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.25s ease;">
                        <div style="padding: 20px; border-bottom: 1px solid var(--sm-border-color); background: var(--sm-bg-light); display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; background: #ffffff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ef4444; border: 1px solid var(--sm-border-color);">
                                <span class="dashicons dashicons-pdf" style="font-size: 22px; width: 22px; height: 22px;"></span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($doc->title); ?></h4>
                                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700; margin-top: 3px;"><?php echo date_i18n('Y-m-d', strtotime($doc->created_at)); ?></div>
                            </div>
                            <?php if (current_user_can('إدارة_النظام')): ?>
                                <div style="display: flex; gap: 6px;">
                                    <button onclick='editDoc(<?php echo json_encode($doc); ?>)' style="background: none; border: none; cursor: pointer; color: var(--sm-text-gray); transition: 0.2s;" onmouseover="this.style.color='var(--sm-primary-color)'" onmouseout="this.style.color='var(--sm-text-gray)'"><span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span></button>
                                    <button onclick="deleteDoc(<?php echo $doc->id; ?>)" style="background: none; border: none; cursor: pointer; color: #ef4444; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"><span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <p style="margin: 0 0 20px 0; font-size: 12px; color: var(--sm-dark-color); font-weight: 600; line-height: 1.6; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?php echo esc_html($doc->description ?: 'لا يوجد وصف متاح لهذا المستند بعد.'); ?>
                            </p>
                            <div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <a href="<?php echo esc_url($doc->file_url); ?>" download class="sm-btn" style="height: 34px; font-size: 11px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px; font-weight: 700;">
                                        <span class="dashicons dashicons-download" style="font-size: 14px; width: 14px; height: 14px;"></span> تحميل الملف
                                    </a>
                                    <button onclick="printPDF('<?php echo esc_url($doc->file_url); ?>')" class="sm-btn sm-btn-outline" style="height: 34px; font-size: 11px; display: flex; align-items: center; justify-content: center; gap: 5px; font-weight: 700; background: #fff;">
                                        <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px;"></span> طباعة فورية
                                    </button>
                                </div>
                                <?php if (current_user_can('إدارة_النظام')): ?>
                                    <div style="margin-top: 15px; font-size: 10px; text-align: center;">
                                        <span class="sm-badge" style="<?php echo $doc->status === 'published' ? 'background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5;'; ?>">
                                            <?php echo $doc->status === 'published' ? 'منشور للجميع' : 'مخفي (للمشرفين فقط)'; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (current_user_can('إدارة_النظام')): ?>
    <!-- Add Document Modal -->
    <div id="add-doc-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 480px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">إضافة مستند جديد للمكتبة</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-doc-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-doc-form">
                <?php wp_nonce_field('sm_admin_action', 'sm_nonce'); ?>
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">عنوان المستند:</label>
                    <input type="text" name="title" class="sm-input" required placeholder="مثال: دليل قواعد السلوك والمواظبة" style="height: 38px; font-size: 12px;">
                </div>
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">وصف مختصر للوثيقة:</label>
                    <textarea name="description" class="sm-textarea" rows="3" placeholder="أدخل ملخصاً للغرض من هذا الملف..." style="font-size: 12px;"></textarea>
                </div>
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">رابط الملف المرفق (PDF):</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="file_url" id="doc_file_url" class="sm-input" required placeholder="ارفع الملف أو ألصق الرابط..." style="height: 38px; font-size: 12px;">
                        <button type="button" onclick="smOpenMediaUploader('doc_file_url')" class="sm-btn sm-btn-secondary" style="width: auto; height: 38px; font-size: 11px; white-space: nowrap; font-weight: 700;">رفع ملف</button>
                    </div>
                </div>
                <div class="sm-form-group" style="margin-bottom: 20px;">
                    <label class="sm-label">حالة ظهور المستند:</label>
                    <select name="status" class="sm-select" style="height: 38px; font-size: 12px;">
                        <option value="published">منشور للجميع على المنصة</option>
                        <option value="hidden">مخفي ومقيد للمشرفين والمدراء</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="height: 38px; font-size: 12px;">حفظ وإدراج الوثيقة</button>
                    <button type="button" onclick="document.getElementById('add-doc-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 38px; font-size: 12px;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Document Modal -->
    <div id="edit-doc-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 480px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">تعديل بيانات المستند المدرج</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-doc-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-doc-form">
                <?php wp_nonce_field('sm_admin_action', 'sm_nonce'); ?>
                <input type="hidden" name="doc_id" id="edit_doc_id">
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">عنوان المستند:</label>
                    <input type="text" name="title" id="edit_doc_title" class="sm-input" required style="height: 38px; font-size: 12px;">
                </div>
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">وصف مختصر للوثيقة:</label>
                    <textarea name="description" id="edit_doc_description" class="sm-textarea" rows="3" style="font-size: 12px;"></textarea>
                </div>
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">رابط الملف المرفق (PDF):</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="file_url" id="edit_doc_file_url" class="sm-input" required style="height: 38px; font-size: 12px;">
                        <button type="button" onclick="smOpenMediaUploader('edit_doc_file_url')" class="sm-btn sm-btn-secondary" style="width: auto; height: 38px; font-size: 11px; white-space: nowrap; font-weight: 700;">تغيير الملف</button>
                    </div>
                </div>
                <div class="sm-form-group" style="margin-bottom: 20px;">
                    <label class="sm-label">حالة الظهور:</label>
                    <select name="status" id="edit_doc_status" class="sm-select" style="height: 38px; font-size: 12px;">
                        <option value="published">منشور للجميع على المنصة</option>
                        <option value="hidden">مخفي ومقيد للمشرفين والمدراء</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="height: 38px; font-size: 12px;">حفظ التغييرات</button>
                    <button type="button" onclick="document.getElementById('edit-doc-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 38px; font-size: 12px;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    function printPDF(url) {
        const win = window.open(url, '_blank');
        win.onload = function() {
            win.print();
        };
    }

    <?php if (current_user_can('إدارة_النظام')): ?>
    document.getElementById('add-doc-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'sm_add_document_ajax');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت إضافة المستند بنجاح للمكتبة');
                location.reload();
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    });

    window.editDoc = function(doc) {
        document.getElementById('edit_doc_id').value = doc.id;
        document.getElementById('edit_doc_title').value = doc.title;
        document.getElementById('edit_doc_description').value = doc.description;
        document.getElementById('edit_doc_file_url').value = doc.file_url;
        document.getElementById('edit_doc_status').value = doc.status;
        document.getElementById('edit-doc-modal').style.display = 'flex';
    };

    document.getElementById('edit-doc-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'sm_update_document_ajax');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث وثيقة المكتبة بنجاح');
                location.reload();
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    });

    window.deleteDoc = function(id) {
        if (!confirm('هل أنت متأكد من رغبتك في حذف هذا المستند نهائياً من المكتبة؟')) return;
        const formData = new FormData();
        formData.append('action', 'sm_delete_document_ajax');
        formData.append('doc_id', id);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف المستند بنجاح من المكتبة');
                location.reload();
            } else {
                smShowNotification('خطأ في الحذف', true);
            }
        });
    };
    <?php endif; ?>
    </script>

    <!-- Regulation Customization Block -->
    <?php if (current_user_can('تسجيل_مخالفة')):
        $can_edit_regulation = current_user_can('إدارة_النظام') || current_user_can('sm_principal') || current_user_can('sm_supervisor');
    ?>
    <div id="regulation-custom-tab" class="sm-internal-tab" style="display:none;">
        <div style="background:#ffffff; border:1px solid var(--sm-border-color); border-radius:12px; padding:25px; margin-bottom:30px; box-shadow: var(--sm-shadow);">
            <h4 style="margin-top:0; border-bottom:1px solid var(--sm-border-color); padding-bottom:12px; font-weight:800; font-size:14px; margin-bottom: 20px; color: var(--sm-dark-color);">خيارات وتصنيفات المخالفات العامة بالمدرسة</h4>
            <form id="sm-violation-settings-form">
                <?php wp_nonce_field('sm_admin_action', 'sm_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="sm-form-group">
                        <label class="sm-label">أنواع المخالفات العامة المتاحة بالنموذج (مفتاح|اسم):</label>
                        <textarea name="violation_types" class="sm-textarea" rows="5" placeholder="مثال: late_attendance|التأخر عن الطابور الصباحي" style="font-size: 12px;" <?php if (!$can_edit_regulation) echo 'readonly style="background:var(--sm-bg-light); cursor:not-allowed;"'; ?>><?php foreach(SM_Settings::get_violation_types() as $k=>$v) echo "$k|$v\n"; ?></textarea>
                    </div>
                    <div class="sm-form-group">
                        <?php $actions = SM_Settings::get_suggested_actions(); ?>
                        <label class="sm-label">إجراءات العقوبات الافتراضية المقترحة حسب المستويات:</label>

                        <div style="font-size:10px; font-weight:800; color: #16a34a; margin-bottom:3px;">المخالفات المنخفضة / البسيطة:</div>
                        <textarea name="suggested_low" class="sm-textarea" rows="2" style="font-size: 11px; margin-bottom: 8px;" <?php if (!$can_edit_regulation) echo 'readonly style="background:var(--sm-bg-light); cursor:not-allowed;"'; ?>><?php echo esc_textarea($actions['low']); ?></textarea>

                        <div style="font-size:10px; font-weight:800; color: #d97706; margin-bottom:3px;">المخالفات المتوسطة:</div>
                        <textarea name="suggested_medium" class="sm-textarea" rows="2" style="font-size: 11px; margin-bottom: 8px;" <?php if (!$can_edit_regulation) echo 'readonly style="background:var(--sm-bg-light); cursor:not-allowed;"'; ?>><?php echo esc_textarea($actions['medium']); ?></textarea>

                        <div style="font-size:10px; font-weight:800; color: #dc2626; margin-bottom:3px;">المخالفات الخطيرة جداً:</div>
                        <textarea name="suggested_high" class="sm-textarea" rows="2" style="font-size: 11px;" <?php if (!$can_edit_regulation) echo 'readonly style="background:var(--sm-bg-light); cursor:not-allowed;"'; ?>><?php echo esc_textarea($actions['high']); ?></textarea>
                    </div>
                </div>
                <?php if ($can_edit_regulation): ?>
                    <button type="submit" class="sm-btn" style="width:auto; height:38px; font-size:12px; font-weight: 700;">حفظ خيارات اللائحة</button>
                <?php endif; ?>
            </form>
        </div>

        <form id="sm-hierarchical-violations-form">
            <?php wp_nonce_field('sm_admin_action', 'sm_nonce');
            $h_violations = SM_Settings::get_hierarchical_violations();
            ?>
            <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 25px; box-shadow: var(--sm-shadow);">
                <h4 style="margin-top:0; border-bottom: 1px solid var(--sm-border-color); padding-bottom:12px; margin-bottom:10px; font-weight:800; font-size:14px; color: var(--sm-dark-color);">إدارة اللائحة التنظيمية الموحدة للمخالفات الهرمية والدرجات</h4>
                <p style="font-size:12px; color: var(--sm-text-gray); margin-bottom:24px; font-weight: 600;">تعديل تفاصيل بنود اللائحة السلوكية للمدرسة، النقاط المستقطعة من ملف الطالب، والإجراءات الافتراضية لكل بند. التحديثات تطبق فوراً عبر المنصة.</p>

                <?php for($i=1; $i<=4; $i++): ?>
                    <div style="background: var(--sm-bg-light); border: 1px solid var(--sm-border-color); border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.01);">
                        <div style="font-weight:800; color: var(--sm-primary-color); margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; font-size: 13px;">
                            <span>اللائحة السلوكية: مخالفات المستوى الدراسي <?php echo $i; ?> (الدرجة <?php echo $i; ?>)</span>
                            <span class="sm-badge" style="background:#fff; color:var(--sm-primary-color); border: 1px solid var(--sm-border-color); font-size:10px; font-weight:800; padding:2px 10px;">عدد البنود المدرجة: <?php echo count($h_violations[$i]); ?> بند</span>
                        </div>
                        <div style="display:grid; grid-template-columns: 80px 1.5fr 60px 1fr <?php echo $can_edit_regulation ? '40px' : ''; ?>; gap:10px; font-weight:800; font-size:11px; margin-bottom:12px; border-bottom:1px solid var(--sm-border-color); padding-bottom:8px; color: var(--sm-dark-color);">
                            <div>رمز البند (كود)</div>
                            <div>تفصيل ووصف بند المخالفة السلوكية</div>
                            <div style="text-align: center;">النقاط</div>
                            <div>العقوبة / الإجراء المعتمد الافتراضي</div>
                            <?php if ($can_edit_regulation): ?><div style="text-align: center;">حذف</div><?php endif; ?>
                        </div>
                        <div class="violation-rows-container" data-level="<?php echo $i; ?>">
                            <?php foreach($h_violations[$i] as $code => $v): ?>
                                <div style="display:grid; grid-template-columns: 80px 1.5fr 60px 1fr <?php echo $can_edit_regulation ? '40px' : ''; ?>; gap:10px; margin-bottom:8px;">
                                    <input type="text" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][code]" value="<?php echo esc_attr($code); ?>" class="sm-input" style="padding:5px 8px; font-size:12px; font-weight:700;" <?php if (!$can_edit_regulation) echo 'readonly style="background:#fff; cursor:not-allowed;"'; ?>>
                                    <input type="text" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][name]" value="<?php echo esc_attr($v['name']); ?>" class="sm-input" style="padding:5px 8px; font-size:12px; font-weight:600;" <?php if (!$can_edit_regulation) echo 'readonly style="background:#fff; cursor:not-allowed;"'; ?>>
                                    <input type="number" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][points]" value="<?php echo esc_attr($v['points']); ?>" class="sm-input" style="padding:5px 5px; font-size:12px; text-align: center; font-weight:800;" <?php if (!$can_edit_regulation) echo 'readonly style="background:#fff; cursor:not-allowed;"'; ?>>
                                    <input type="text" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][action]" value="<?php echo esc_attr($v['action']); ?>" class="sm-input" style="padding:5px 8px; font-size:12px; font-weight:600;" <?php if (!$can_edit_regulation) echo 'readonly style="background:#fff; cursor:not-allowed;"'; ?>>
                                    <?php if ($can_edit_regulation): ?>
                                        <button type="button" onclick="this.parentElement.remove()" class="sm-btn sm-btn-outline" style="padding:0; width:28px; height:28px; color:#ef4444; border-color:#fca5a5; display: inline-flex; align-items: center; justify-content: center; background: #fff;" title="حذف هذا البند"><span class="dashicons dashicons-no-alt"></span></button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($can_edit_regulation): ?>
                            <button type="button" class="sm-btn sm-btn-outline" style="font-size:11px; margin-top:10px; background:#fff; height: 28px;" onclick="addViolationRow(<?php echo $i; ?>, this)">+ إضافة بند سلوكي جديد للمستوى <?php echo $i; ?></button>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>

                <?php if ($can_edit_regulation): ?>
                    <button type="submit" class="sm-btn" style="width:auto; margin-top:15px; height: 38px; font-size: 12px; font-weight: 700;">✓ حفظ واعتماد تعديلات اللائحة التنظيمية</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    function addViolationRow(level, btn) {
        const container = btn.previousElementSibling;
        const div = document.createElement('div');
        div.style = "display:grid; grid-template-columns: 80px 1.5fr 60px 1fr auto; gap:10px; margin-bottom:8px;";
        const id = 'new_' + Math.random().toString(36).substr(2, 5);
        div.innerHTML = `
            <input type="text" name="h_viol[${level}][${id}][code]" placeholder="رمز البند" class="sm-input" style="padding:5px 8px; font-size:12px; font-weight:700;">
            <input type="text" name="h_viol[${level}][${id}][name]" placeholder="تفصيل المخالفة..." class="sm-input" style="padding:5px 8px; font-size:12px; font-weight:600;">
            <input type="number" name="h_viol[${level}][${id}][points]" value="0" class="sm-input" style="padding:5px 5px; font-size:12px; text-align: center; font-weight:800;">
            <input type="text" name="h_viol[${level}][${id}][action]" placeholder="الإجراء الافتراضي..." class="sm-input" style="padding:5px 8px; font-size:12px; font-weight:600;">
            <button type="button" onclick="this.parentElement.remove()" class="sm-btn sm-btn-outline" style="padding:0; width:28px; height:28px; color:#ef4444; border-color:#fca5a5; display: inline-flex; align-items: center; justify-content: center; background:#fff;"><span class="dashicons dashicons-no-alt"></span></button>
        `;
        container.appendChild(div);
    }

    (function() {
        const vSettingsForm = document.getElementById('sm-violation-settings-form');
        if (vSettingsForm) {
            vSettingsForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_save_regulation_settings_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => {
                    if (res.success) {
                        smShowNotification('تم حفظ تصنيفات وإعدادات المخالفات بنجاح');
                    } else {
                        smShowNotification('خطأ في الحفظ', true);
                    }
                });
            };
        }

        const hViolForm = document.getElementById('sm-hierarchical-violations-form');
        if (hViolForm) {
            hViolForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_save_hierarchical_violations_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => {
                    if (res.success) {
                        smShowNotification('تم حفظ وتحديث بنود اللائحة التنظيمية بنجاح عبر النظام');
                    } else {
                        smShowNotification('خطأ في التحديث', true);
                    }
                });
            };
        }
    })();
    </script>
    <?php endif; ?>
</div>
