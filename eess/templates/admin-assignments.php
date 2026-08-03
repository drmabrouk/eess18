<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 20px;">الواجبات المدرسية والمرفقات</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">إسناد الواجبات الأكاديمية والمهام للطلاب، وتلقي تسليمات الواجبات والمرفقات الدراسية</p>
        </div>
        <?php if ($is_teacher || $is_student): ?>
            <button onclick="document.getElementById('add-assignment-modal').style.display='flex'" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700; width: auto; padding: 0 20px;">
                <span class="dashicons dashicons-plus" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> إضافة واجب أو تسليم جديد
            </button>
        <?php endif; ?>
    </div>

    <!-- Navigation Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid var(--sm-border-color); padding-bottom: 5px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('received-assignments', this)">📥 الواجبات المستلمة</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('sent-assignments', this)">📤 الواجبات المرسلة</button>
    </div>

    <!-- Tab 1: Received -->
    <div id="received-assignments" class="sm-internal-tab">
        <div class="sm-table-container" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; box-shadow: var(--sm-shadow); overflow: hidden; margin-bottom: 30px;">
            <table class="sm-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--sm-bg-light); border-bottom: 1px solid var(--sm-border-color);">
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">تاريخ الإرسال</th>
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">من المرسل</th>
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">العنوان والموضوع</th>
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">الملف المرفق</th>
                        <th style="padding: 15px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">الخيارات المتاحة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $received = SM_DB::get_assignments($user->ID, 'assignment');
                    if (empty($received)): ?>
                        <tr>
                            <td colspan="5" style="padding: 60px 20px; text-align: center;">
                                <div style="width: 50px; height: 50px; background: var(--sm-bg-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--sm-text-gray); border: 1px solid var(--sm-border-color);">
                                    <span class="dashicons dashicons-download" style="font-size: 22px; width: 22px; height: 22px;"></span>
                                </div>
                                <h4 style="margin: 0; font-size: 13px; font-weight: 800; color: var(--sm-dark-color);">لا يوجد واجبات مستلمة حالياً</h4>
                                <p style="margin: 5px 0 0 0; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">لم يتم استلام أي تكاليف أو واجبات مدرسية في حسابك بعد.</p>
                            </td>
                        </tr>
                    <?php else: foreach($received as $a): ?>
                        <tr style="border-bottom: 1px solid var(--sm-border-color); transition: background 0.2s;" onmouseover="this.style.background='var(--sm-bg-light)'" onmouseout="this.style.background='#ffffff'">
                            <td style="padding: 15px 20px; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;"><?php echo date_i18n('Y-m-d', strtotime($a->created_at)); ?></td>
                            <td style="padding: 15px 20px; font-weight: 700; color: var(--sm-primary-color); font-size: 12px;"><?php echo esc_html($a->sender_name); ?></td>
                            <td style="padding: 15px 20px; font-weight: 800; color: var(--sm-dark-color); font-size: 12px;"><?php echo esc_html($a->title); ?></td>
                            <td style="padding: 15px 20px;">
                                <?php if ($a->file_url): ?>
                                    <a href="<?php echo esc_url($a->file_url); ?>" target="_blank" class="sm-btn sm-btn-outline" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 10px; font-weight: 700; width: auto; display: inline-flex; align-items: center; gap: 4px; background: #fff;">
                                        <span class="dashicons dashicons-admin-links" style="font-size: 12px; width: 12px; height: 12px; margin: 0;"></span> عرض الملف
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">لا يوجد مرفق</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px; text-align: left;">
                                <button onclick='viewAssignment(<?php echo json_encode($a); ?>)' class="sm-btn" style="height: 28px; padding: 0 12px; font-size: 11px; font-weight: 700; width: auto;">عرض التفاصيل</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: Sent -->
    <div id="sent-assignments" class="sm-internal-tab" style="display: none;">
        <div class="sm-table-container" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; box-shadow: var(--sm-shadow); overflow: hidden; margin-bottom: 30px;">
            <table class="sm-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--sm-bg-light); border-bottom: 1px solid var(--sm-border-color);">
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">تاريخ الإرسال</th>
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">إلى المستلم</th>
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">العنوان والموضوع</th>
                        <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">الملف المرفق</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sent = SM_DB::get_sent_assignments($user->ID);
                    if (empty($sent)): ?>
                        <tr>
                            <td colspan="4" style="padding: 60px 20px; text-align: center;">
                                <div style="width: 50px; height: 50px; background: var(--sm-bg-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--sm-text-gray); border: 1px solid var(--sm-border-color);">
                                    <span class="dashicons dashicons-upload" style="font-size: 22px; width: 22px; height: 22px;"></span>
                                </div>
                                <h4 style="margin: 0; font-size: 13px; font-weight: 800; color: var(--sm-dark-color);">لم تقم بإرسال أي واجبات</h4>
                                <p style="margin: 5px 0 0 0; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">سجل الواجبات المرسلة من قبلك فارغ حالياً.</p>
                            </td>
                        </tr>
                    <?php else: foreach($sent as $a): ?>
                        <tr style="border-bottom: 1px solid var(--sm-border-color); transition: background 0.2s;" onmouseover="this.style.background='var(--sm-bg-light)'" onmouseout="this.style.background='#ffffff'">
                            <td style="padding: 15px 20px; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;"><?php echo date_i18n('Y-m-d', strtotime($a->created_at)); ?></td>
                            <td style="padding: 15px 20px; font-weight: 700; color: var(--sm-primary-color); font-size: 12px;"><?php echo esc_html($a->receiver_name); ?></td>
                            <td style="padding: 15px 20px; font-weight: 800; color: var(--sm-dark-color); font-size: 12px;"><?php echo esc_html($a->title); ?></td>
                            <td style="padding: 15px 20px;">
                                <?php if ($a->file_url): ?>
                                    <a href="<?php echo esc_url($a->file_url); ?>" target="_blank" class="sm-btn sm-btn-outline" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 10px; font-weight: 700; width: auto; display: inline-flex; align-items: center; gap: 4px; background: #fff;">
                                        <span class="dashicons dashicons-admin-links" style="font-size: 12px; width: 12px; height: 12px; margin: 0;"></span> عرض الملف
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">لا يوجد مرفق</span>
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
    <div class="sm-modal-content" style="max-width: 550px; padding: 25px;">
        <div class="sm-modal-header">
            <h3 style="font-size: 15px; font-weight: 800;">إضافة واجب أو تكليف دراسي</h3>
            <button class="sm-modal-close" onclick="document.getElementById('add-assignment-modal').style.display='none'">&times;</button>
        </div>
        <form id="add-assignment-form">
            <?php wp_nonce_field('sm_assignment_action', 'sm_nonce'); ?>

            <div class="sm-form-group" style="margin-bottom: 12px;">
                <label class="sm-label">عنوان الواجب / الموضوع الرئيسي:</label>
                <input type="text" name="title" class="sm-input" required placeholder="مثال: حل صفحة 45 في كتاب الرياضيات" style="height: 38px; font-size: 12px;">
            </div>

            <div class="sm-form-group" style="margin-bottom: 12px;">
                <label class="sm-label">التفاصيل أو التعليمات المطلوبة:</label>
                <textarea name="description" class="sm-textarea" rows="4" placeholder="اكتب التعليمات، الخطوات، والدروس المرتبطة بهذا التكليف..." style="font-size: 12px;"></textarea>
            </div>

            <div class="sm-form-group" style="margin-bottom: 12px;">
                <label class="sm-label">إرسال التكليف إلى:</label>
                <select name="receiver_id" class="sm-select" required style="height: 38px; font-size: 12px;">
                    <?php if ($is_teacher || $is_admin || $is_sys_admin || $is_principal): ?>
                        <option value="">-- اختر الطالب المستهدف --</option>
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

            <div class="sm-form-group" style="margin-bottom: 25px;">
                <label class="sm-label">رابط الملف أو ورقة العمل المرفقة (اختياري):</label>
                <div style="display:flex; gap:8px;">
                    <input type="text" name="file_url" id="assignment_file_url" class="sm-input" placeholder="ارفع ورقة العمل أو الصق الرابط..." style="height: 38px; font-size: 12px;">
                    <button type="button" onclick="smOpenMediaUploader('assignment_file_url')" class="sm-btn sm-btn-secondary" style="width:auto; height: 38px; font-size:11px; white-space: nowrap; font-weight: 700;">رفع ملف</button>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="submit" class="sm-btn" style="height: 38px; font-size: 12px; font-weight: 700;">إرسال التكليف الآن</button>
                <button type="button" onclick="document.getElementById('add-assignment-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 38px; font-size: 12px; font-weight: 700;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<!-- Details Modal -->
<div id="assignment-details-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 480px; padding: 25px;">
        <div class="sm-modal-header">
            <h3 style="font-size: 15px; font-weight: 800;">تفاصيل الواجب المدرسي</h3>
            <button class="sm-modal-close" onclick="document.getElementById('assignment-details-modal').style.display='none'">&times;</button>
        </div>
        <div style="font-size: 12px; color: var(--sm-dark-color); line-height: 1.6; margin-bottom: 20px;">
            <div style="font-weight: 800; margin-bottom: 6px; color: var(--sm-primary-color);">تعليمات التكليف:</div>
            <p id="assignment_details_desc" style="white-space: pre-wrap; background: var(--sm-bg-light); border: 1px solid var(--sm-border-color); border-radius: 8px; padding: 15px; font-weight: 600; font-style: italic; margin: 0;"></p>
        </div>
        <div style="display: flex; justify-content: flex-end;">
            <button type="button" onclick="document.getElementById('assignment-details-modal').style.display='none'" class="sm-btn" style="height: 38px; font-size: 12px; font-weight: 700; width: auto; padding: 0 20px;">حسناً، إغلاق</button>
        </div>
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
                    smShowNotification('تم إرسال الواجب بنجاح للجهة المحددة');
                    setTimeout(() => location.reload(), 500);
                } else {
                    smShowNotification('خطأ: ' + res.data, true);
                }
            });
        });
    }
})();

window.viewAssignment = function(a) {
    document.getElementById('assignment_details_desc').innerText = a.description || 'لا يوجد تفاصيل أو تعليمات مكتوبة.';
    document.getElementById('assignment-details-modal').style.display = 'flex';
};
</script>
