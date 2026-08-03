<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-assignments-container" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-weight: 800;">الواجبات المدرسية والمرفقات</h3>
        <?php if ($is_teacher || $is_student): ?>
            <button onclick="document.getElementById('add-assignment-modal').style.display='flex'" class="sm-btn" style="width: auto;">+ إضافة واجب / تسليم</button>
        <?php endif; ?>
    </div>

    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('received-assignments', this)">الواجبات المستلمة</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('sent-assignments', this)">الواجبات المرسلة</button>
    </div>

    <div id="received-assignments" class="sm-internal-tab">
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>من</th>
                        <th>العنوان</th>
                        <th>المرفقات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $received = SM_DB::get_assignments($user->ID, 'assignment');
                    if (empty($received)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px;">لا يوجد واجبات مستلمة.</td></tr>
                    <?php else: foreach($received as $a): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($a->created_at)); ?></td>
                            <td><?php echo esc_html($a->sender_name); ?></td>
                            <td style="font-weight: 700;"><?php echo esc_html($a->title); ?></td>
                            <td>
                                <?php if ($a->file_url): ?>
                                    <a href="<?php echo esc_url($a->file_url); ?>" target="_blank" class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 8px;">فتح المرفق</a>
                                <?php else: ?>
                                    ---
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick='viewAssignment(<?php echo json_encode($a); ?>)' class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 8px;">عرض التفاصيل</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="sent-assignments" class="sm-internal-tab" style="display: none;">
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>إلى</th>
                        <th>العنوان</th>
                        <th>المرفقات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sent = SM_DB::get_sent_assignments($user->ID);
                    if (empty($sent)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px;">لم تقم بإرسال أي واجبات بعد.</td></tr>
                    <?php else: foreach($sent as $a): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($a->created_at)); ?></td>
                            <td><?php echo esc_html($a->receiver_name); ?></td>
                            <td style="font-weight: 700;"><?php echo esc_html($a->title); ?></td>
                            <td>
                                <?php if ($a->file_url): ?>
                                    <a href="<?php echo esc_url($a->file_url); ?>" target="_blank" class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 8px;">فتح المرفق</a>
                                <?php else: ?>
                                    ---
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Assignment Modal -->
<div id="add-assignment-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 600px;">
        <div class="sm-modal-header">
            <h3>إضافة واجب جديد</h3>
            <button class="sm-modal-close" onclick="document.getElementById('add-assignment-modal').style.display='none'">&times;</button>
        </div>
        <form id="add-assignment-form">
            <?php wp_nonce_field('sm_assignment_action', 'sm_nonce'); ?>
            <div class="sm-form-group">
                <label class="sm-label">عنوان الواجب:</label>
                <input type="text" name="title" class="sm-input" required>
            </div>
            <div class="sm-form-group">
                <label class="sm-label">التفاصيل / التعليمات:</label>
                <textarea name="description" class="sm-textarea" rows="4"></textarea>
            </div>
            <div class="sm-form-group">
                <label class="sm-label">إرسال إلى:</label>
                <select name="receiver_id" class="sm-select" required>
                    <?php if ($is_teacher || $is_admin || $is_sys_admin || $is_principal): ?>
                        <option value="">-- اختر الطالب --</option>
                        <?php
                        $my_students = SM_DB::get_students();
                        foreach($my_students as $s) {
                            if ($s->parent_user_id) {
                                echo "<option value='{$s->parent_user_id}'>{$s->name} ({$s->class_name})</option>";
                            }
                        }
                        ?>
                    <?php elseif ($is_student): ?>
                        <option value="">-- اختر المعلم المختص --</option>
                        <?php
                        $stu = SM_DB::get_student_by_parent($user->ID);
                        if ($stu) {
                            $grade_num = (int)str_replace('الصف ', '', $stu->class_name);
                            $my_teachers = SM_DB::get_staff_by_section($grade_num, $stu->section);
                            foreach($my_teachers as $t) {
                                echo "<option value='{$t->ID}'>{$t->display_name}</option>";
                            }
                        }
                        ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="sm-form-group">
                <label class="sm-label">رابط الملف المرفق (اختياري):</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="file_url" id="assignment_file_url" class="sm-input">
                    <button type="button" onclick="smOpenMediaUploader('assignment_file_url')" class="sm-btn" style="width:auto; font-size:12px; background:var(--sm-secondary-color);">رفع ملف</button>
                </div>
            </div>
            <button type="submit" class="sm-btn">إرسال الواجب الآن</button>
        </form>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('add-assignment-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_add_assignment_ajax');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification('تم إرسال الواجب بنجاح');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
    }
})();

function viewAssignment(a) {
    alert("التفاصيل:\n" + a.description);
}
</script>
