<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();

// Determine layout and view options
$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_teacher = in_array('sm_teacher', $roles);

$can_review = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator;

// Auto-assign supervisor helper
if (!function_exists('eess_get_teacher_supervisor')) {
    function eess_get_teacher_supervisor($teacher_id) {
        $supervisors = get_users(array('role__in' => array('sm_supervisor', 'sm_principal', 'administrator')));
        if (!empty($supervisors)) {
            return $supervisors[0]->ID;
        }
        return 1;
    }
}

// Fetch general settings
$prep_settings = get_option('sm_lesson_prep_settings', array(
    'submission_frequency' => 'daily',
    'submission_deadline'  => '10:00',
    'working_days'         => array('sun', 'mon', 'tue', 'wed', 'thu'),
    'holidays'             => array(),
    'pe_monday_only'       => 'yes'
));

$deadline_time = $prep_settings['submission_deadline'] . ':00';

// Handle Form Submissions
if (isset($_POST['eess_save_lesson_prep']) && wp_verify_nonce($_POST['eess_lesson_prep_nonce'], 'eess_lesson_prep_action')) {
    $title         = sanitize_text_field($_POST['lesson_title']);
    $subject       = sanitize_text_field($_POST['lesson_subject']);
    $grade_level   = sanitize_text_field($_POST['lesson_grade']);
    $class_section = sanitize_text_field($_POST['lesson_section']);
    $lesson_date   = sanitize_text_field($_POST['lesson_date']);
    $status        = sanitize_text_field($_POST['lesson_status']); // draft or submitted

    $lesson_data = array(
        'objectives' => sanitize_textarea_field($_POST['objectives']),
        'warmup'     => sanitize_textarea_field($_POST['warmup']),
        'activities' => sanitize_textarea_field($_POST['activities']),
        'evaluation' => sanitize_textarea_field($_POST['evaluation']),
        'homework'   => sanitize_textarea_field($_POST['homework']),
        'notes'      => sanitize_textarea_field($_POST['notes']),
    );

    $delay_seconds = 0;
    $final_status = $status;
    $submission_time = null;

    if ($status === 'submitted') {
        $submission_time = current_time('mysql');
        $submit_timestamp = strtotime($submission_time);
        $deadline_today = strtotime(date('Y-m-d', $submit_timestamp) . ' ' . $deadline_time);

        $is_pe = (strpos(strtolower($subject), 'رياضة') !== false || strpos(strtolower($subject), 'pe') !== false || strpos(strtolower($subject), 'physical') !== false);
        $is_monday = (date('N', strtotime($lesson_date)) == 1);
        $exempt = false;

        if ($is_pe && $prep_settings['pe_monday_only'] === 'yes' && !$is_monday) {
            $exempt = true;
        }

        if ($submit_timestamp > $deadline_today && !$exempt) {
            $delay_seconds = $submit_timestamp - $deadline_today;
            $final_status = 'late';
        } else {
            $final_status = 'submitted';
        }
    }

    $supervisor_id = eess_get_teacher_supervisor($user_id);

    if (isset($_POST['prep_id']) && !empty($_POST['prep_id'])) {
        $prep_id = intval($_POST['prep_id']);
        $existing_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));

        $version = 1;
        $parent_id = 0;
        if ($existing_status === 'revision_required' && $status === 'submitted') {
            $version_data = $wpdb->get_row($wpdb->prepare("SELECT version, parent_id FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            $version = intval($version_data->version) + 1;
            $parent_id = $version_data->parent_id == 0 ? $prep_id : $version_data->parent_id;

            $wpdb->insert(
                "{$wpdb->prefix}sm_lesson_preps",
                array(
                    'teacher_id'      => $user_id,
                    'supervisor_id'   => $supervisor_id,
                    'title'           => $title,
                    'subject'         => $subject,
                    'grade_level'     => $grade_level,
                    'class_section'   => $class_section,
                    'lesson_date'     => $lesson_date,
                    'submission_time' => $submission_time,
                    'status'          => $final_status,
                    'delay_seconds'   => $delay_seconds,
                    'lesson_data'     => json_encode($lesson_data),
                    'version'         => $version,
                    'parent_id'       => $parent_id,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql')
                )
            );
            $wpdb->update("{$wpdb->prefix}sm_lesson_preps", array('status' => 'resubmitted'), array('id' => $prep_id));
        } else {
            $wpdb->update(
                "{$wpdb->prefix}sm_lesson_preps",
                array(
                    'title'           => $title,
                    'subject'         => $subject,
                    'grade_level'     => $grade_level,
                    'class_section'   => $class_section,
                    'lesson_date'     => $lesson_date,
                    'submission_time' => $submission_time,
                    'status'          => $final_status,
                    'delay_seconds'   => $delay_seconds,
                    'lesson_data'     => json_encode($lesson_data),
                    'updated_at'      => current_time('mysql')
                ),
                array('id' => $prep_id)
            );
        }
    } else {
        $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'teacher_id'      => $user_id,
                'supervisor_id'   => $supervisor_id,
                'title'           => $title,
                'subject'         => $subject,
                'grade_level'     => $grade_level,
                'class_section'   => $class_section,
                'lesson_date'     => $lesson_date,
                'submission_time' => $submission_time,
                'status'          => $final_status,
                'delay_seconds'   => $delay_seconds,
                'lesson_data'     => json_encode($lesson_data),
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            )
        );
    }
    echo '<div class="updated" style="background:#f0fdf4; color:#15803d; padding:15px; border-radius:8px; border:1px solid #bbf7d0; margin-bottom:20px; font-weight:700; font-family:\'Cairo\', sans-serif;">تم حفظ خطة التحضير بنجاح.</div>';
}

// Handle Supervisor Actions (Approve, Reject, Request Revision, Comment)
if (isset($_POST['eess_supervisor_action']) && wp_verify_nonce($_POST['eess_supervisor_nonce'], 'eess_supervisor_action_nonce')) {
    $prep_id = intval($_POST['prep_id']);
    $action  = sanitize_text_field($_POST['prep_status_action']);
    $comment = sanitize_textarea_field($_POST['supervisor_comment']);

    $wpdb->update(
        "{$wpdb->prefix}sm_lesson_preps",
        array('status' => $action, 'updated_at' => current_time('mysql')),
        array('id' => $prep_id)
    );

    if (!empty($comment)) {
        $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_comments",
            array(
                'prep_id'      => $prep_id,
                'user_id'      => $user_id,
                'comment_text' => $comment,
                'created_at'   => current_time('mysql')
            )
        );
    }
    echo '<div class="updated" style="background:#f0fdf4; color:#15803d; padding:15px; border-radius:8px; border:1px solid #bbf7d0; margin-bottom:20px; font-weight:700; font-family:\'Cairo\', sans-serif;">تم تحديث حالة التحضير وإرسال الملاحظات للمدرس بنجاح.</div>';
}

// Handle Settings Update
if (isset($_POST['eess_save_prep_settings']) && wp_verify_nonce($_POST['eess_settings_nonce'], 'eess_settings_action')) {
    $new_settings = array(
        'submission_frequency' => sanitize_text_field($_POST['submission_frequency']),
        'submission_deadline'  => sanitize_text_field($_POST['submission_deadline']),
        'working_days'         => isset($_POST['working_days']) ? array_map('sanitize_text_field', $_POST['working_days']) : array(),
        'pe_monday_only'       => sanitize_text_field($_POST['pe_monday_only']),
    );
    update_option('sm_lesson_prep_settings', $new_settings);
    $prep_settings = $new_settings;
    $deadline_time = $prep_settings['submission_deadline'] . ':00';
    echo '<div class="updated" style="background:#f0fdf4; color:#15803d; padding:15px; border-radius:8px; border:1px solid #bbf7d0; margin-bottom:20px; font-weight:700; font-family:\'Cairo\', sans-serif;">تم حفظ إعدادات منظومة التحضير بنجاح.</div>';
}

$edit_prep = null;
if (isset($_GET['edit_prep_id'])) {
    $edit_prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['edit_prep_id']), $user_id));
}

if (isset($_GET['duplicate_prep_id'])) {
    $dup_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['duplicate_prep_id']), $user_id));
    if ($dup_source) {
        $edit_prep = $dup_source;
        $edit_prep->id = 0;
        $edit_prep->title .= ' (نسخة)';
        $edit_prep->lesson_date = current_time('Y-m-d');
        $edit_prep->status = 'draft';
    }
}
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <!-- Top Title Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 22px;">منظومة تحضير الدروس والخطط التعليمية</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">إعداد واعتماد خطط الدروس، متابعة نسب الالتزام والامتثال الأكاديمي اليومي والأسبوعي</p>
        </div>
    </div>

    <!-- Administrative Statistics Dashboard -->
    <?php if ($can_review):
        $stats_total_required = count(get_users(array('role' => 'sm_teacher')));
        $stats_submitted      = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status IN ('submitted', 'approved', 'revision_required', 'rejected', 'late')");
        $stats_pending        = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'submitted'");
        $stats_approved       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'approved'");
        $stats_rejected       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'rejected'");
        $stats_revision       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'revision_required'");
        $stats_late           = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'late'");

        $submission_pct = $stats_total_required > 0 ? round(($stats_submitted / $stats_total_required) * 100) : 0;
    ?>
    <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; box-shadow: var(--sm-shadow);">
        <h3 style="margin: 0 0 20px 0; font-weight: 800; color: var(--sm-dark-color); border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; font-size: 15px;">متابعة خطط التحضير ومستويات الالتزام</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box;">
                <div style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700;">المعلمون المطالبون</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--sm-dark-color); line-height:1;"><?php echo $stats_total_required; ?></div>
            </div>
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid var(--sm-secondary-color);">
                <div style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700;">التحضيرات المستلمة</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--sm-secondary-color); line-height:1;"><?php echo $stats_submitted; ?></div>
            </div>
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid #f59e0b;">
                <div style="font-size: 11px; color: #b45309; font-weight: 700;">بانتظار المراجعة</div>
                <div style="font-size: 24px; font-weight: 800; color: #d97706; line-height:1;"><?php echo $stats_pending; ?></div>
            </div>
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid #10b981;">
                <div style="font-size: 11px; color: #15803d; font-weight: 700;">التحضيرات المعتمدة</div>
                <div style="font-size: 24px; font-weight: 800; color: #16a34a; line-height:1;"><?php echo $stats_approved; ?></div>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 15px;">
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid #f97316;">
                <div style="font-size: 11px; color: #c2410c; font-weight: 700;">طلب مراجعة/تعديل</div>
                <div style="font-size: 24px; font-weight: 800; color: #ea580c; line-height:1;"><?php echo $stats_revision; ?></div>
            </div>
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid #ef4444;">
                <div style="font-size: 11px; color: #b91c1c; font-weight: 700;">التحضيرات المرفوضة</div>
                <div style="font-size: 24px; font-weight: 800; color: #dc2626; line-height:1;"><?php echo $stats_rejected; ?></div>
            </div>
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid #ef4444;">
                <div style="font-size: 11px; color: #991b1b; font-weight: 700;">تسليمات متأخرة</div>
                <div style="font-size: 24px; font-weight: 800; color: #8b1e1e; line-height:1;"><?php echo $stats_late; ?></div>
            </div>
            <div class="sm-stat-card" style="text-align: right; background: #ffffff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; flex-direction: column; justify-content: space-between; height: 80px; box-sizing: border-box; border-right: 4px solid #3b82f6;">
                <div style="font-size: 11px; color: #1d4ed8; font-weight: 700;">نسبة الامتثال الكلية</div>
                <div style="font-size: 24px; font-weight: 800; color: #2563eb; line-height:1;"><?php echo $submission_pct; ?>%</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Layout -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Form Tab (Only visible to teachers to create/edit) -->
        <?php if ($is_teacher): ?>
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
            <h3 style="margin: 0 0 20px 0; font-weight: 800; color: var(--sm-dark-color); border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; font-size: 15px;">
                <?php echo ($edit_prep && $edit_prep->id > 0) ? 'تعديل وثيقة خطة تحضير الدرس الحالية' : 'إعداد نموذج خطة تحضير درس جديدة'; ?>
            </h3>

            <form method="post">
                <?php wp_nonce_field('eess_lesson_prep_action', 'eess_lesson_prep_nonce'); ?>
                <?php if ($edit_prep): ?>
                    <input type="hidden" name="prep_id" value="<?php echo $edit_prep->id; ?>">
                <?php endif; ?>

                <?php
                $data = $edit_prep ? json_decode($edit_prep->lesson_data, true) : array();
                ?>

                <!-- Metas Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">عنوان الدرس <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_title" value="<?php echo esc_attr($edit_prep->title ?? ''); ?>" class="sm-input" required placeholder="العنوان بالتفصيل..." style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">المادة الدراسية <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_subject" value="<?php echo esc_attr($edit_prep->subject ?? ''); ?>" class="sm-input" required placeholder="اسم المادة..." style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">الصف الدراسي <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_grade" value="<?php echo esc_attr($edit_prep->grade_level ?? ''); ?>" class="sm-input" required placeholder="مثال: الصف التاسع" style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">الشعبة / الفصل <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_section" value="<?php echo esc_attr($edit_prep->class_section ?? ''); ?>" class="sm-input" required placeholder="مثال: أ / 1" style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">تاريخ إعطاء الدرس <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="lesson_date" value="<?php echo esc_attr($edit_prep->lesson_date ?? current_time('Y-m-d')); ?>" class="sm-input" required style="height: 38px; font-size:12px;">
                    </div>
                </div>

                <!-- Fields -->
                <div class="sm-form-group">
                    <label class="sm-label">الأهداف السلوكية والتعليمية المحددة (Objectives) <span style="color:#ef4444;">*</span></label>
                    <textarea name="objectives" class="sm-textarea" style="height: 90px; font-size:12px;" required placeholder="صياغة أهداف الدرس بوضوح وقابلية للقياس (أن يستنتج الطالب...)..."><?php echo esc_textarea($data['objectives'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">التمهيد والتهيئة الحافزة للدرس (Warm-up) <span style="color:#ef4444;">*</span></label>
                    <textarea name="warmup" class="sm-textarea" style="height: 80px; font-size:12px;" required placeholder="أنشطة استهلالية لربط المفهوم وتنشيط المعرفة السابقة..."><?php echo esc_textarea($data['warmup'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">الاستراتيجيات، الأنشطة والخطوات التعليمية الشاملة (Strategies & Activities) <span style="color:#ef4444;">*</span></label>
                    <textarea name="activities" class="sm-textarea" style="height: 120px; font-size:12px;" required placeholder="خطة سير الحصة الدراسية، الأنشطة الفردية والجماعية، واستراتيجيات التعلّم النشط..."><?php echo esc_textarea($data['activities'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">التقويم الصفي وأدوات القياس التكويني (Evaluation & Assessment) <span style="color:#ef4444;">*</span></label>
                    <textarea name="evaluation" class="sm-textarea" style="height: 80px; font-size:12px;" required placeholder="الأسئلة والتقييمات الذاتية للتحقق من بلوغ الأهداف الصيفية..."><?php echo esc_textarea($data['evaluation'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">الواجبات المنزلية والمهام الأكاديمية المقررة (Homework)</label>
                    <textarea name="homework" class="sm-textarea" style="height: 70px; font-size:12px;" placeholder="الواجبات أو المشروعات التكميلية في نهاية الحصة..."><?php echo esc_textarea($data['homework'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group" style="margin-bottom: 24px;">
                    <label class="sm-label">التأملات المهنية وملاحظات التطوير والتعديل المستقبلي</label>
                    <textarea name="notes" class="sm-textarea" style="height: 70px; font-size:12px;" placeholder="تدوين أي معوقات أو فرص للتطوير المهني في الدرس..."><?php echo esc_textarea($data['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Form Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="eess_save_lesson_prep" onclick="document.getElementById('lesson_status').value='submitted'" class="sm-btn" style="width: auto; height: 38px; font-size:12px; font-weight:700;">حفظ وإرسال وثيقة التحضير للمراجعة</button>
                    <button type="submit" name="eess_save_lesson_prep" onclick="document.getElementById('lesson_status').value='draft'" class="sm-btn sm-btn-secondary" style="width: auto; height: 38px; font-size:12px; font-weight:700;">حفظ كمسودة مؤقتة</button>
                    <?php if ($edit_prep): ?>
                        <a href="<?php echo home_url('/lesson-prep'); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; font-size:12px; font-weight:700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; background: #fff;">إلغاء التعديل</a>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="lesson_status" id="lesson_status" value="submitted">
            </form>
        </div>
        <?php endif; ?>

        <!-- History/List Panel -->
        <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
            <div style="display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 20px;">
                <span class="dashicons dashicons-welcome-write-blog" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
                <h3 style="margin:0; font-size: 15px; font-weight: 800; color: var(--sm-dark-color);">
                    <?php echo $can_review ? 'استعراض واعتماد خطط تحضير المعلمين والتحقق من الامتثال' : 'أرشيف وسجل تحضير الدروس والخطط السابقة الخاص بي'; ?>
                </h3>
            </div>

            <!-- Inline search filters -->
            <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 20px; background: var(--sm-bg-light); padding: 15px; border-radius: 8px; border: 1px solid var(--sm-border-color);">
                <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? esc_attr($_GET['page']) : ''; ?>">

                <div class="sm-form-group" style="margin-bottom:0;">
                    <label style="font-size: 11px; font-weight: bold; color: var(--sm-text-gray); display: block; margin-bottom: 4px;">البحث بالكلمات المفتاحية</label>
                    <input type="text" name="s_query" value="<?php echo isset($_GET['s_query']) ? esc_attr($_GET['s_query']) : ''; ?>" placeholder="اسم المعلم أو عنوان الدرس..." class="sm-input" style="height:32px; font-size:12px;">
                </div>

                <div class="sm-form-group" style="margin-bottom:0;">
                    <label style="font-size: 11px; font-weight: bold; color: var(--sm-text-gray); display: block; margin-bottom: 4px;">تاريخ الدرس</label>
                    <input type="date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? esc_attr($_GET['filter_date']) : ''; ?>" class="sm-input" style="height:32px; font-size:12px;">
                </div>

                <div class="sm-form-group" style="margin-bottom:0;">
                    <label style="font-size: 11px; font-weight: bold; color: var(--sm-text-gray); display: block; margin-bottom: 4px;">حالة الاعتماد</label>
                    <select name="filter_status" class="sm-select" style="height:32px; font-size:12px; padding: 0 10px;">
                        <option value="">كافة الحالات</option>
                        <option value="draft" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'draft'); ?>>مسودة</option>
                        <option value="submitted" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'submitted'); ?>>مقدم للاعتماد</option>
                        <option value="approved" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'approved'); ?>>معتمد</option>
                        <option value="revision_required" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'revision_required'); ?>>طلب تعديل</option>
                        <option value="rejected" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'rejected'); ?>>مرفوض</option>
                        <option value="late" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'late'); ?>>تسليم متأخر</option>
                    </select>
                </div>

                <div style="display: flex; align-items: flex-end; gap: 5px;">
                    <button type="submit" class="sm-btn" style="height: 32px; font-size:11px; padding:0 12px; width:auto;">تصفية</button>
                    <a href="<?php echo home_url('/lesson-prep'); ?>" class="sm-btn sm-btn-outline" style="height: 32px; font-size:11px; padding:0 12px; width:auto; display:flex; align-items:center; justify-content:center; text-decoration:none; background:#fff;">إعادة ضبط</a>
                </div>
            </form>

            <!-- Table of Submissions -->
            <div class="sm-table-container">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th style="font-weight: 700; width: 110px;">تاريخ الدرس</th>
                            <?php if ($can_review): ?>
                                <th style="font-weight: 700; width: 140px;">اسم المعلم</th>
                            <?php endif; ?>
                            <th style="font-weight: 700;">عنوان الدرس والمادة</th>
                            <th style="font-weight: 700; width: 120px;">الصف والشعبة</th>
                            <th style="font-weight: 700; width: 90px;">الإصدار</th>
                            <th style="font-weight: 700; width: 140px;">حالة الاستحقاق</th>
                            <th style="font-weight: 700; width: 125px;">حالة الاعتماد</th>
                            <th style="text-align: left; padding-left: 20px; font-weight: 700; width: 220px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT p.*, u.display_name as teacher_name
                                  FROM {$wpdb->prefix}sm_lesson_preps p
                                  JOIN {$wpdb->prefix}users u ON p.teacher_id = u.ID";

                        $conditions = array();
                        $params = array();

                        if (!$can_review) {
                            $conditions[] = "p.teacher_id = %d";
                            $params[] = $user_id;
                        }

                        if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                            $conditions[] = "p.lesson_date = %s";
                            $params[] = sanitize_text_field($_GET['filter_date']);
                        }

                        if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
                            $conditions[] = "p.status = %s";
                            $params[] = sanitize_text_field($_GET['filter_status']);
                        }

                        if (isset($_GET['s_query']) && !empty($_GET['s_query'])) {
                            $conditions[] = "(p.title LIKE %s OR u.display_name LIKE %s OR p.subject LIKE %s)";
                            $like_param = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s_query'])) . '%';
                            $params[] = $like_param;
                            $params[] = $like_param;
                            $params[] = $like_param;
                        }

                        if (!empty($conditions)) {
                            $query .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $query .= " ORDER BY p.lesson_date DESC, p.created_at DESC";

                        if (!empty($params)) {
                            $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
                        } else {
                            $submissions = $wpdb->get_results($query);
                        }

                        if (empty($submissions)):
                        ?>
                        <tr>
                            <td colspan="<?php echo $can_review ? 8 : 7; ?>" style="text-align: center; color: var(--sm-text-gray); padding: 40px; font-weight:700;">لا توجد خطط تحضير مدرجة ومسجلة حالياً تطابق شروط البحث.</td>
                        </tr>
                        <?php
                        else:
                            foreach ($submissions as $sub):
                                $delay_desc = 'في الموعد';
                                if ($sub->delay_seconds > 0) {
                                    $days = floor($sub->delay_seconds / 86400);
                                    $hours = floor(($sub->delay_seconds % 86400) / 3600);
                                    $minutes = floor(($sub->delay_seconds % 3600) / 60);

                                    $delay_parts = array();
                                    if ($days > 0) $delay_parts[] = $days . ' يوم';
                                    if ($hours > 0) $delay_parts[] = $hours . ' ساعة';
                                    if ($minutes > 0) $delay_parts[] = $minutes . ' دقيقة';
                                    $delay_desc = implode(' و', $delay_parts);
                                }
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--sm-dark-color); font-size:12px;"><?php echo date_i18n('Y-m-d', strtotime($sub->lesson_date)); ?></td>
                            <?php if ($can_review): ?>
                                <td style="font-weight: 700; color: var(--sm-secondary-color);"><?php echo esc_html($sub->teacher_name); ?></td>
                            <?php endif; ?>
                            <td>
                                <div style="font-weight:700; color:var(--sm-dark-color);"><?php echo esc_html($sub->title); ?></div>
                                <div style="font-size:11px; color:var(--sm-text-gray); font-weight:700; margin-top:2px;"><?php echo esc_html($sub->subject); ?></div>
                            </td>
                            <td style="font-weight: 600;"><?php echo esc_html($sub->grade_level . ' (' . $sub->class_section . ')'); ?></td>
                            <td><span style="font-weight:bold; color: var(--sm-text-gray); font-size:11px;">إصدار <?php echo $sub->version; ?></span></td>
                            <td>
                                <?php if ($sub->delay_seconds > 0): ?>
                                    <span style="color: #ef4444; font-weight: 700; font-size: 11px;">⚠️ متأخر: <?php echo $delay_desc; ?></span>
                                <?php else: ?>
                                    <span style="color: #10b981; font-weight: 700; font-size: 11px;">✓ في الموعد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_labels = array(
                                    'draft' => array('label' => 'مسودة', 'bg' => '#f1f5f9', 'color' => '#475569'),
                                    'submitted' => array('label' => 'بانتظار المراجعة', 'bg' => '#fffbeb', 'color' => '#b45309'),
                                    'approved' => array('label' => 'معتمد', 'bg' => '#f0fdf4', 'color' => '#16a34a'),
                                    'revision_required' => array('label' => 'تعديل مطلوب', 'bg' => '#fff7ed', 'color' => '#c2410c'),
                                    'rejected' => array('label' => 'مرفوض', 'bg' => '#fef2f2', 'color' => '#b91c1c'),
                                    'late' => array('label' => 'تسليم متأخر', 'bg' => '#fef2f2', 'color' => '#b91c1c'),
                                    'resubmitted' => array('label' => 'معدل ومستلم', 'bg' => '#f0f9ff', 'color' => '#0369a1'),
                                );
                                $badge = $status_labels[$sub->status] ?? array('label' => $sub->status, 'bg' => '#f1f5f9', 'color' => '#475569');
                                ?>
                                <span class="sm-badge" style="background:<?php echo $badge['bg']; ?>; color:<?php echo $badge['color']; ?>; border: 1px solid currentColor;">
                                    <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td style="text-align: left; padding-left: 20px;">
                                <div style="display:flex; gap:5px; justify-content: flex-end;">
                                    <button onclick="smOpenPrepViewer(<?php echo $sub->id; ?>)" class="sm-btn sm-btn-outline" style="padding: 4px 10px; font-size:11px; height:28px; background:#fff; font-weight: 700;">عرض المستند</button>

                                    <?php if ($is_teacher): ?>
                                        <?php if ($sub->status === 'draft' || $sub->status === 'revision_required'): ?>
                                            <a href="<?php echo add_query_arg('edit_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn" style="padding: 4px 10px; font-size:11px; height:28px; font-weight:700; text-decoration:none;">تعديل</a>
                                        <?php endif; ?>
                                        <a href="<?php echo add_query_arg('duplicate_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn sm-btn-secondary" style="padding: 4px 10px; font-size:11px; height:28px; font-weight:700; text-decoration:none;">نسخ</a>
                                    <?php endif; ?>

                                    <?php if ($can_review && ($sub->status === 'submitted' || $sub->status === 'late')): ?>
                                        <button onclick="smOpenReviewModal(<?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-btn" style="padding: 4px 10px; font-size:11px; height:28px; font-weight:700; background:#10b981;">اعتماد</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Administrative Settings Block -->
    <?php if ($is_admin || $is_sys_admin): ?>
    <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-top: 24px; box-shadow: var(--sm-shadow);">
        <div style="display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 20px;">
            <span class="dashicons dashicons-admin-generic" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--sm-dark-color);">إعدادات وجدولة تسليم التحضيرات والأوقات الرسمية</h3>
        </div>

        <form method="post">
            <?php wp_nonce_field('eess_settings_action', 'eess_settings_nonce'); ?>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom: 20px; background: var(--sm-bg-light); padding: 20px; border-radius: 8px; border: 1px solid var(--sm-border-color);">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">دورية التسليم الرسمية:</label>
                    <select name="submission_frequency" class="sm-select" style="height:38px; font-size:12px;">
                        <option value="daily" <?php selected($prep_settings['submission_frequency'] == 'daily'); ?>>تسليم وثيقة تحضير يومية (درس لكل يوم عمل)</option>
                        <option value="weekly" <?php selected($prep_settings['submission_frequency'] == 'weekly'); ?>>تسليم وثيقة تحضير أسبوعية (كل يوم أحد)</option>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">موعد الإغلاق اليومي واستحقاق التأخير:</label>
                    <input type="time" name="submission_deadline" value="<?php echo esc_attr($prep_settings['submission_deadline']); ?>" class="sm-input" style="height:38px; font-size:12px;">
                    <p style="font-size:10px; color: var(--sm-text-gray); margin-top:5px; font-weight:700;">أي تحضير يُسلّم بعد هذا التوقيت يُعتبر تسليماً متأخراً (التوقيت الافتراضي 10:00 صباحاً).</p>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">استثناءات مادة التربية الرياضية (Physical Education):</label>
                    <select name="pe_monday_only" class="sm-select" style="height:38px; font-size:12px;">
                        <option value="yes" <?php selected($prep_settings['pe_monday_only'] == 'yes'); ?>>نعم - يُعفى معلمو التربية الرياضية ويُطلب منهم تحضير يوم الاثنين فقط</option>
                        <option value="no" <?php selected($prep_settings['pe_monday_only'] == 'no'); ?>>لا - يُعامل معلمو التربية الرياضية معاملة بقية المواد</option>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">أيام العمل والتحضير الأسبوعية المعتمدة:</label>
                    <div style="display:flex; gap:15px; flex-wrap:wrap; background:#ffffff; padding:8px 12px; border-radius:6px; border:1px solid var(--sm-border-color); height: 38px; box-sizing: border-box; align-items: center;">
                        <?php
                        $days_list = array('sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس');
                        foreach ($days_list as $key => $lbl): ?>
                            <label style="font-size:11px; display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-weight: 700; color: var(--sm-dark-color);">
                                <input type="checkbox" name="working_days[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $prep_settings['working_days'])); ?>> <?php echo $lbl; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button type="submit" name="eess_save_prep_settings" class="sm-btn" style="width: auto; height: 38px; font-size:12px; font-weight:700;">حفظ وتعميم جدولة منظومة التحضير</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<!-- Document Viewer Modal Dialog -->
<div id="prep-viewer-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 750px; padding: 25px;">
        <div class="sm-modal-header" style="border-bottom: 2px solid var(--sm-primary-color); padding-bottom: 15px; margin-bottom: 20px;">
            <h3 id="view-modal-title" style="margin:0; font-size:15px; font-weight:800; color:var(--sm-primary-color);">عنوان التحضير المختار</h3>
            <button onclick="document.getElementById('prep-viewer-modal').style.display='none'" class="sm-modal-close" style="position:static; margin:0;">&times;</button>
        </div>
        <div class="sm-modal-body" id="prep-viewer-body" style="max-height: 480px; overflow-y:auto; line-height: 1.6; font-size:13px; text-align:right;">
            <!-- Rendered dynamically via JS -->
        </div>
    </div>
</div>

<!-- Supervisor Review Action Modal Dialog -->
<div id="prep-review-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 500px; padding: 25px;">
        <div class="sm-modal-header" style="margin-bottom: 20px;">
            <h3 style="margin:0; font-size: 15px; font-weight:800;">مراجعة واعتماد خطة التحضير المحددة</h3>
            <button onclick="document.getElementById('prep-review-modal').style.display='none'" class="sm-modal-close" style="position:static; margin:0;">&times;</button>
        </div>
        <div class="sm-modal-body" style="text-align:right;">
            <form method="post">
                <?php wp_nonce_field('eess_supervisor_action', 'eess_supervisor_nonce'); ?>
                <input type="hidden" name="prep_id" id="review-prep-id">

                <div class="sm-form-group">
                    <label class="sm-label">عنوان خطة التحضير:</label>
                    <input type="text" id="review-prep-title" class="sm-input" readonly style="background:var(--sm-bg-light); color:var(--sm-text-gray); font-weight: 700; height: 38px; font-size:12px;">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">القرار الإداري الفني والاعتماد:</label>
                    <select name="prep_status_action" class="sm-select" required style="height: 38px; font-size:12px;">
                        <option value="approved">✓ اعتماد وإجازة التحضير (معتمد)</option>
                        <option value="revision_required">⚠ طلب مراجعة وتعديل (تعديل مطلوب)</option>
                        <option value="rejected">✗ رفض وإلغاء وثيقة التحضير (مرفوض)</option>
                    </select>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">الملاحظات والتوجيهات الفنية:</label>
                    <textarea name="supervisor_comment" class="sm-textarea" style="height: 90px; font-size:12px;" placeholder="أدخل ملحوظاتك الفنية وتوجيهاتك للمعلم..."></textarea>
                </div>

                <button type="submit" name="eess_supervisor_action" class="sm-btn" style="background:#10b981; width:100%; height: 38px; font-size:12px; font-weight: 700;">تطبيق القرار وإبلاغ المدرس</button>
            </form>
        </div>
    </div>
</div>

<script>
const eessSubmissions = <?php
    $preps_for_js = array();
    if (!empty($submissions)) {
        foreach ($submissions as $sub) {
            $parsed_data = json_decode($sub->lesson_data, true) ?: array();

            $comments = $wpdb->get_results($wpdb->prepare("SELECT c.*, u.display_name FROM {$wpdb->prefix}sm_lesson_comments c JOIN {$wpdb->prefix}users u ON c.user_id = u.ID WHERE c.prep_id = %d ORDER BY c.created_at ASC", $sub->id));
            $comments_array = array();
            if (!empty($comments)) {
                foreach ($comments as $com) {
                    $comments_array[] = array(
                        'author' => $com->display_name,
                        'text' => $com->comment_text,
                        'date' => date_i18n('Y-m-d H:i', strtotime($com->created_at))
                    );
                }
            }

            $preps_for_js[$sub->id] = array(
                'title' => $sub->title,
                'subject' => $sub->subject,
                'grade' => $sub->grade_level,
                'section' => $sub->class_section,
                'date' => $sub->lesson_date,
                'objectives' => $parsed_data['objectives'] ?? '',
                'warmup' => $parsed_data['warmup'] ?? '',
                'activities' => $parsed_data['activities'] ?? '',
                'evaluation' => $parsed_data['evaluation'] ?? '',
                'homework' => $parsed_data['homework'] ?? '',
                'notes' => $parsed_data['notes'] ?? '',
                'comments' => $comments_array
            );
        }
    }
    echo json_encode($preps_for_js);
?>;

function smOpenPrepViewer(id) {
    const data = eessSubmissions[id];
    if (!data) return;

    document.getElementById('view-modal-title').innerText = data.title;

    let html = `
        <div style="background: var(--sm-bg-light); padding: 12px 15px; border-radius: 8px; border:1px solid var(--sm-border-color); margin-bottom:20px; display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-weight:700; color:var(--sm-dark-color);">
            <div>المادة الدراسية: <span style="color:var(--sm-primary-color);">${data.subject}</span></div>
            <div>الصف الدراسي: <span style="color:var(--sm-primary-color);">${data.grade} (${data.section})</span></div>
            <div style="grid-column: span 2;">تاريخ الدرس المقرر: <span style="color:var(--sm-primary-color);">${data.date}</span></div>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-primary-color); padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-primary-color); font-weight:800; font-size:13px;">الأهداف السلوكية والتعليمية المحددة:</h4>
            <p style="margin:0; color:var(--sm-dark-color); font-weight:600; line-height:1.5;">${data.objectives.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-secondary-color); padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-secondary-color); font-weight:800; font-size:13px;">التمهيد والتهيئة الحافزة للدرس:</h4>
            <p style="margin:0; color:var(--sm-dark-color); font-weight:600; line-height:1.5;">${data.warmup.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-accent-color); padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-accent-color); font-weight:800; font-size:13px;">الأنشطة والخطوات التعليمية الاستراتيجية:</h4>
            <p style="margin:0; color:var(--sm-dark-color); font-weight:600; line-height:1.5;">${data.activities.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-dark-color); padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-dark-color); font-weight:800; font-size:13px;">التقويم الصفي وأدوات القياس التكويني:</h4>
            <p style="margin:0; color:var(--sm-dark-color); font-weight:600; line-height:1.5;">${data.evaluation.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #b91c1c; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#b91c1c; font-weight:800; font-size:13px;">الواجبات المنزلية والمهام الأكاديمية:</h4>
            <p style="margin:0; color:var(--sm-dark-color); font-weight:600; line-height:1.5;">${data.homework ? data.homework.replace(/\n/g, '<br>') : 'لا يوجد واجب صفي مقرر'}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #4b5563; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#4b5563; font-weight:800; font-size:13px;">ملاحظات تربوية وتأملات مهنية إضافية:</h4>
            <p style="margin:0; color:var(--sm-dark-color); font-weight:600; line-height:1.5;">${data.notes ? data.notes.replace(/\n/g, '<br>') : 'لا توجد ملاحظات إضافية'}</p>
        </div>
    `;

    if (data.comments && data.comments.length > 0) {
        html += `
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed var(--sm-border-color);">
                <h4 style="margin: 0 0 15px 0; color:#b91c1c; font-weight:800; font-size:13px;">سجل التوجيهات والملاحظات من المشرفين:</h4>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    ${data.comments.map(c => `
                        <div style="background:#fffbeb; border:1px solid #fde68a; padding:12px; border-radius:8px;">
                            <div style="display:flex; justify-content:space-between; font-size:10px; color:#b45309; font-weight:800; margin-bottom:5px;">
                                <span>المشرف المتابع: ${c.author}</span>
                                <span>${c.date}</span>
                            </div>
                            <p style="margin:0; font-size:12px; color:#92400e; font-weight:600; line-height:1.5;">${c.text.replace(/\n/g, '<br>')}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    document.getElementById('prep-viewer-body').innerHTML = html;
    document.getElementById('prep-viewer-modal').style.display = 'flex';
}

function smOpenReviewModal(id, title) {
    document.getElementById('review-prep-id').value = id;
    document.getElementById('review-prep-title').value = title;
    document.getElementById('prep-review-modal').style.display = 'flex';
}
</script>
