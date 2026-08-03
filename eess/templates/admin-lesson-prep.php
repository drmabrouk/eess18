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

// Handle Supervisor Actions (Approve, Request Revision, Comment, Reject)
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

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif; background-color: #f8fafc; padding: 25px; min-height: 100vh;">

    <!-- Top Title Header & Quick Analytics Dashboard -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 900; color: #1e293b; font-size: 22px; letter-spacing: -0.5px;">تحضير وتدقيق الدروس</h2>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b; font-weight: 500;">إعداد واعتماد خطط الدروس اليومية، ومتابعة الالتزام الأكاديمي والتعليمي للمدرسة</p>
        </div>

        <!-- Real-Time Metric Badges (Rounded pill chips) -->
        <?php
        $stats_total_required = count(get_users(array('role' => 'sm_teacher')));
        $stats_submitted      = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status IN ('submitted', 'approved', 'revision_required', 'rejected', 'late', 'resubmitted')");
        $stats_pending        = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status IN ('submitted', 'resubmitted')");
        $stats_approved       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'approved'");
        $stats_late           = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'late'");
        $stats_on_time        = $stats_submitted - $stats_late;
        $on_time_pct          = $stats_submitted > 0 ? round(($stats_on_time / $stats_submitted) * 100) : 100;
        ?>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #334155; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 15px; width: 15px; height: 15px; color: #64748b; margin: 0;"></span>
                <span>إجمالي التحضيرات: <strong style="color: #1e293b;"><?php echo $stats_submitted; ?></strong></span>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #16a34a; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <span class="dashicons dashicons-yes-alt" style="font-size: 15px; width: 15px; height: 15px; color: #16a34a; margin: 0;"></span>
                <span>المعتمدة: <strong style="color: #15803d;"><?php echo $stats_approved; ?></strong></span>
            </div>
            <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #b45309; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <span class="dashicons dashicons-clock" style="font-size: 15px; width: 15px; height: 15px; color: #d97706; margin: 0;"></span>
                <span>بانتظار المراجعة: <strong style="color: #b45309;"><?php echo $stats_pending; ?></strong></span>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <span class="dashicons dashicons-calendar" style="font-size: 15px; width: 15px; height: 15px; color: #2563eb; margin: 0;"></span>
                <span>التسليم في الموعد: <strong style="color: #1d4ed8;"><?php echo $on_time_pct; ?>%</strong></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid Layout -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Section: Teacher Preparation Entry (Only visible to teachers to create/edit) -->
        <?php if ($is_teacher): ?>
        <div style="background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <h3 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-edit-page" style="font-size: 18px; width: 18px; height: 18px; color: #64748b;"></span>
                <?php echo ($edit_prep && $edit_prep->id > 0) ? 'تعديل خطة تحضير الدرس الحالية' : 'إعداد خطة تحضير درس جديدة'; ?>
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
                        <input type="text" name="lesson_title" value="<?php echo esc_attr($edit_prep->title ?? ''); ?>" class="sm-input" required placeholder="مثال: تركيب الخلية الحيوانية" style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">المادة الدراسية <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_subject" value="<?php echo esc_attr($edit_prep->subject ?? ''); ?>" class="sm-input" required placeholder="مثال: العلوم العامة" style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">الصف الدراسي <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_grade" value="<?php echo esc_attr($edit_prep->grade_level ?? ''); ?>" class="sm-input" required placeholder="مثال: الصف التاسع" style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">الشعبة / الفصل <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_section" value="<?php echo esc_attr($edit_prep->class_section ?? ''); ?>" class="sm-input" required placeholder="مثال: 1" style="height: 38px; font-size:12px;">
                    </div>
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label">تاريخ إعطاء الدرس <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="lesson_date" value="<?php echo esc_attr($edit_prep->lesson_date ?? current_time('Y-m-d')); ?>" class="sm-input" required style="height: 38px; font-size:12px;">
                    </div>
                </div>

                <!-- Fields -->
                <div class="sm-form-group" style="margin-bottom: 15px;">
                    <label class="sm-label">الأهداف السلوكية والتعليمية المحددة (Objectives) <span style="color:#ef4444;">*</span></label>
                    <textarea name="objectives" class="sm-textarea" style="height: 80px; font-size:12px;" required placeholder="أن يحدد الطالب الأجزاء الأساسية لغشاء الخلية..."><?php echo esc_textarea($data['objectives'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group" style="margin-bottom: 15px;">
                    <label class="sm-label">التمهيد والتهيئة الحافزة للدرس (Warm-up) <span style="color:#ef4444;">*</span></label>
                    <textarea name="warmup" class="sm-textarea" style="height: 80px; font-size:12px;" required placeholder="عرض صورة خلية مجهرية وطرح تساؤل تفاعلي على الطلاب..."><?php echo esc_textarea($data['warmup'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group" style="margin-bottom: 15px;">
                    <label class="sm-label">الاستراتيجيات، الأنشطة والخطوات التعليمية الشاملة (Strategies & Activities) <span style="color:#ef4444;">*</span></label>
                    <textarea name="activities" class="sm-textarea" style="height: 100px; font-size:12px;" required placeholder="تقسيم الطلاب لمجموعات عمل ثنائية، استخدام التعلم النشط..."><?php echo esc_textarea($data['activities'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group" style="margin-bottom: 15px;">
                    <label class="sm-label">التقويم الصفي وأدوات القياس التكويني (Evaluation & Assessment) <span style="color:#ef4444;">*</span></label>
                    <textarea name="evaluation" class="sm-textarea" style="height: 80px; font-size:12px;" required placeholder="حل تمرين صفي سريع فردياً لتقييم استيعاب الأهداف..."><?php echo esc_textarea($data['evaluation'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group" style="margin-bottom: 15px;">
                    <label class="sm-label">الواجبات المنزلية والمهام الأكاديمية المقررة (Homework)</label>
                    <textarea name="homework" class="sm-textarea" style="height: 70px; font-size:12px;" placeholder="حل السؤال رقم 3 في الكراسة العملية..."><?php echo esc_textarea($data['homework'] ?? ''); ?></textarea>
                </div>

                <div class="sm-form-group" style="margin-bottom: 25px;">
                    <label class="sm-label">التأملات المهنية وملاحظات التطوير والتعديل المستقبلي</label>
                    <textarea name="notes" class="sm-textarea" style="height: 70px; font-size:12px;" placeholder="تدوين تحديات التنفيذ أو المقترحات التطويرية للحصة القادمة..."><?php echo esc_textarea($data['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Form Buttons -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="submit" name="eess_save_lesson_prep" onclick="document.getElementById('lesson_status').value='submitted'" class="sm-btn" style="width: auto; height: 38px; font-size:12px; font-weight:700; background: #16a34a; border-color: #16a34a;">إرسال وثيقة التحضير للمراجعة والاعتماد</button>
                    <button type="submit" name="eess_save_lesson_prep" onclick="document.getElementById('lesson_status').value='draft'" class="sm-btn sm-btn-secondary" style="width: auto; height: 38px; font-size:12px; font-weight:700;">حفظ كمسودة مؤقتة</button>
                    <?php if ($edit_prep): ?>
                        <a href="<?php echo remove_query_arg('edit_prep_id', home_url('/lesson-prep')); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; font-size:12px; font-weight:700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; background: #fff;">إلغاء التعديل</a>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="lesson_status" id="lesson_status" value="submitted">
            </form>
        </div>
        <?php endif; ?>

        <!-- Section: Search Filters and Dynamic Lesson Cards Container -->
        <div style="background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">

            <!-- Unified Single-Row Control & Filter Toolbar -->
            <form id="sm-lesson-filters-form" method="get" style="display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
                <!-- Retain other query parameter values -->
                <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? esc_attr($_GET['page']) : ''; ?>">
                <?php
                $active_sort = isset($_GET['sort_order']) && $_GET['sort_order'] === 'ASC' ? 'ASC' : 'DESC';
                ?>
                <input type="hidden" name="sort_order" id="sort_order_param" value="<?php echo $active_sort; ?>">

                <!-- Flexible Search Field with Icon & Clear -->
                <div style="flex: 1; min-width: 200px; position: relative; display: flex; align-items: center;">
                    <span class="dashicons dashicons-search" style="position: absolute; right: 10px; font-size: 16px; width: 16px; height: 16px; color: #94a3b8; pointer-events: none;"></span>
                    <input type="text" name="s_query" id="s_query_input" value="<?php echo isset($_GET['s_query']) ? esc_attr($_GET['s_query']) : ''; ?>" placeholder="بحث باسم المعلم، الدرس، المادة..." class="sm-input" style="height: 36px; font-size: 12px; padding: 0 35px 0 30px; border-radius: 6px;">
                    <?php if (isset($_GET['s_query']) && !empty($_GET['s_query'])): ?>
                        <button type="button" onclick="document.getElementById('s_query_input').value=''; document.getElementById('sm-lesson-filters-form').submit();" style="position: absolute; left: 10px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 14px; font-weight: bold; line-height: 1; display: flex; align-items: center; justify-content: center; height: 100%; padding: 0;">✖</button>
                    <?php endif; ?>
                </div>

                <!-- Embedded Field Label Filters (selects with internal labels) -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <!-- Date Filter -->
                    <div style="position: relative;">
                        <input type="date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? esc_attr($_GET['filter_date']) : ''; ?>" class="sm-input" style="height: 36px; font-size: 11px; padding: 0 10px; border-radius: 6px; width: 130px;" onchange="document.getElementById('sm-lesson-filters-form').submit();">
                    </div>

                    <!-- Status Filter -->
                    <select name="filter_status" class="sm-select" style="height: 36px; font-size: 11px; padding: 0 10px; border-radius: 6px; width: 155px; font-weight: 700; background-color: #ffffff;" onchange="document.getElementById('sm-lesson-filters-form').submit();">
                        <option value="">حالة الاعتماد: الكل</option>
                        <option value="draft" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'draft'); ?>>الاعتماد: مسودة</option>
                        <option value="submitted" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'submitted'); ?>>الاعتماد: بانتظار المراجعة</option>
                        <option value="approved" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'approved'); ?>>الاعتماد: معتمد</option>
                        <option value="revision_required" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'revision_required'); ?>>الاعتماد: طلب تعديل</option>
                        <option value="rejected" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'rejected'); ?>>الاعتماد: مرفوض</option>
                        <option value="late" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'late'); ?>>الاعتماد: تسليم متأخر</option>
                    </select>

                    <!-- Minimalist Sort Toggle Button with Tooltip -->
                    <button type="button" onclick="toggleDateSort();" class="sm-btn sm-btn-outline" style="height: 36px; width: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; background: #ffffff;" title="<?php echo $active_sort === 'DESC' ? 'الترتيب: الأحدث أولاً (انقر للعكس)' : 'الترتيب: الأقدم أولاً (انقر للعكس)'; ?>">
                        <span class="dashicons <?php echo $active_sort === 'DESC' ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                    </button>

                    <!-- Reset Trigger -->
                    <a href="<?php echo home_url('/lesson-prep'); ?>" class="sm-btn sm-btn-outline" style="height: 36px; padding: 0 12px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; background: #ffffff; text-decoration: none;">إعادة ضبط</a>
                </div>
            </form>

            <!-- Grid of Lesson Cards -->
            <?php
            // Prepare Query
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

            // Order by toggle choice
            $query .= " ORDER BY p.lesson_date " . $active_sort . ", p.created_at " . $active_sort;

            if (!empty($params)) {
                $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
            } else {
                $submissions = $wpdb->get_results($query);
            }
            ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 20px;">
                <?php if (empty($submissions)): ?>
                    <div style="grid-column: 1 / -1; background: #ffffff; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 60px 20px; text-align: center;">
                        <span class="dashicons dashicons-media-text" style="font-size: 36px; width: 36px; height: 36px; color: #94a3b8; margin-bottom: 12px;"></span>
                        <h4 style="margin: 0; font-size: 13px; font-weight: 800; color: #1e293b;">لا توجد خطط تحضير مدرجة ومسجلة حالياً</h4>
                        <p style="margin: 5px 0 0 0; font-size: 11px; color: #64748b; font-weight: 600;">يرجى التحقق من معايير التصفية أو البدء بإضافة تحضير جديد.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $school_info = SM_Settings::get_school_info();
                    $school_name = $school_info['school_name'] ?? 'المدرسة الافتراضية';

                    foreach ($submissions as $sub):
                        $avatar_url = get_user_meta($sub->teacher_id, 'eess_profile_photo', true) ?: get_avatar_url($sub->teacher_id);
                        $teacher_title = 'معلم المادة';

                        // Status classification colors
                        $status_styles = array(
                            'draft' => 'background: #f1f5f9; color: #475569; border-color: #cbd5e1;',
                            'submitted' => 'background: #fffbeb; color: #d97706; border-color: #fde68a;',
                            'approved' => 'background: #ecfdf5; color: #047857; border-color: #a7f3d0;',
                            'revision_required' => 'background: #fff7ed; color: #c2410c; border-color: #fed7aa;',
                            'rejected' => 'background: #fef2f2; color: #b91c1c; border-color: #fca5a5;',
                            'late' => 'background: #fef2f2; color: #dc2626; border-color: #fca5a5;',
                            'resubmitted' => 'background: #f0f9ff; color: #0369a1; border-color: #bae6fd;',
                        );
                        $status_label = array(
                            'draft' => 'مسودة',
                            'submitted' => 'بانتظار المراجعة',
                            'approved' => 'معتمد',
                            'revision_required' => 'تعديل مطلوب',
                            'rejected' => 'مرفوض',
                            'late' => 'تسليم متأخر',
                            'resubmitted' => 'معدل ومستلم',
                        )[$sub->status] ?? $sub->status;

                        $delay_label = 'في الموعد';
                        if ($sub->delay_seconds > 0) {
                            $days = floor($sub->delay_seconds / 86400);
                            $hours = floor(($sub->delay_seconds % 86400) / 3600);
                            $minutes = floor(($sub->delay_seconds % 3600) / 60);

                            $delay_parts = array();
                            if ($days > 0) $delay_parts[] = $days . ' يوم';
                            if ($hours > 0) $delay_parts[] = $hours . ' ساعة';
                            if ($minutes > 0) $delay_parts[] = $minutes . ' دقيقة';
                            $delay_label = 'متأخر: ' . implode(' و', $delay_parts);
                        }
                    ?>
                        <!-- Premium Lesson Card Component Architecture -->
                        <div class="sm-lesson-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.25s ease;" onmouseover="this.style.borderColor='#cbd5e1'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">

                            <!-- Header Row -->
                            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                                <img src="<?php echo esc_url($avatar_url); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; flex-shrink: 0;" alt="صورة المعلم">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <strong style="font-size: 13px; color: #1e293b; font-weight: 800;"><?php echo esc_html($sub->teacher_name); ?></strong>
                                        <span class="sm-badge" style="background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px;"><?php echo esc_html($teacher_title); ?></span>
                                    </div>
                                    <!-- Inline metadata row separated by bullets -->
                                    <div style="font-size: 10px; color: #64748b; font-weight: 700; margin-top: 3px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; line-height: 1;">
                                        <span><?php echo esc_html($sub->subject); ?></span>
                                        <span>•</span>
                                        <span><?php echo esc_html($sub->grade_level); ?></span>
                                        <span>•</span>
                                        <span><?php echo esc_html($school_name); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Content Body -->
                            <div style="flex-grow: 1; margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
                                    <h4 style="margin: 0; font-size: 14px; font-weight: 900; color: #1e293b; line-height: 1.4;"><?php echo esc_html($sub->title); ?></h4>
                                    <span style="font-size: 10px; font-weight: 800; color: #64748b; white-space: nowrap; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 4px;">إصدار <?php echo $sub->version; ?></span>
                                </div>

                                <div style="font-size: 11px; font-weight: 700; color: #64748b; display: flex; align-items: center; gap: 4px; margin-bottom: 12px;">
                                    <span class="dashicons dashicons-calendar-alt" style="font-size: 14px; width: 14px; height: 14px; color: #94a3b8; margin: 0;"></span>
                                    <span>تاريخ الإعطاء: <?php echo date_i18n('Y-m-d', strtotime($sub->lesson_date)); ?></span>
                                </div>

                                <!-- Delivery status indicators -->
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    <span class="sm-badge" style="<?php echo $status_styles[$sub->status] ?? $status_styles['draft']; ?> font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid;">
                                        <?php echo esc_html($status_label); ?>
                                    </span>
                                    <?php if ($sub->delay_seconds > 0): ?>
                                        <span class="sm-badge" style="background: #fef2f2; color: #dc2626; border-color: #fca5a5; font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid;">
                                            ⚠️ <?php echo esc_html($delay_label); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="sm-badge" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0; font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid;">
                                            ✓ في الموعد المعتمد
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Footer containing primary and secondary action triggers -->
                            <div style="display: flex; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 12px; flex-wrap: wrap;">
                                <!-- Interactive Slide Presentation Button -->
                                <button onclick="smOpenPrepViewer(<?php echo $sub->id; ?>)" class="sm-btn sm-btn-outline" style="flex: 1; height: 32px; font-size: 11px; font-weight: 700; border-radius: 6px; background: #f0f9ff; border-color: #bae6fd; color: #0369a1; display: inline-flex; align-items: center; justify-content: center; gap: 4px; transition: all 0.2s;" onmouseover="this.style.background='#e0f2fe'; this.style.transform='scale(1.02)';" onmouseout="this.style.background='#f0f9ff'; this.style.transform='scale(1)';">
                                    <span class="dashicons dashicons-slides" style="font-size: 14px; width: 14px; height: 14px; margin: 0; color: #0284c7;"></span>
                                    <span>عرض الدرس</span>
                                </button>

                                <?php if ($is_teacher): ?>
                                    <?php if ($sub->status === 'draft' || $sub->status === 'revision_required'): ?>
                                        <a href="<?php echo add_query_arg('edit_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn" style="height: 32px; font-size: 11px; font-weight: 700; padding: 0 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">تعديل</a>
                                    <?php endif; ?>
                                    <a href="<?php echo add_query_arg('duplicate_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn sm-btn-secondary" style="height: 32px; font-size: 11px; font-weight: 700; padding: 0 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">نسخ</a>
                                <?php endif; ?>

                                <?php if ($can_review): ?>
                                    <?php if ($sub->status === 'submitted' || $sub->status === 'late' || $sub->status === 'resubmitted'): ?>
                                        <!-- Dynamic Approval Button -->
                                        <button onclick="smOpenReviewModal(<?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-btn" style="height: 32px; font-size: 11px; font-weight: 700; border-radius: 6px; background: #16a34a; border-color: #16a34a; display: inline-flex; align-items: center; justify-content: center; width: auto; padding: 0 12px;">اعتماد الدرس</button>
                                    <?php elseif ($sub->status === 'approved'): ?>
                                        <button class="sm-btn" disabled style="height: 32px; font-size: 11px; font-weight: 700; border-radius: 6px; background: #047857; border-color: #047857; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; width: auto; padding: 0 12px; cursor: not-allowed; opacity: 1;">تم إعتماده بالفعل</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Administrative Settings Block -->
    <?php if ($is_admin || $is_sys_admin): ?>
    <div style="background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px;">
            <span class="dashicons dashicons-admin-generic" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
            <h3 style="margin: 0; font-size: 14px; font-weight: 800; color: #1e293b;">إعدادات وجدولة تسليم التحضيرات والأوقات الرسمية</h3>
        </div>

        <form method="post">
            <?php wp_nonce_field('eess_settings_action', 'eess_settings_nonce'); ?>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom: 20px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">دورية التسليم الرسمية:</label>
                    <select name="submission_frequency" class="sm-select" style="height:38px; font-size:12px; background: #fff;">
                        <option value="daily" <?php selected($prep_settings['submission_frequency'] == 'daily'); ?>>تسليم وثيقة تحضير يومية (درس لكل يوم عمل)</option>
                        <option value="weekly" <?php selected($prep_settings['submission_frequency'] == 'weekly'); ?>>تسليم وثيقة تحضير أسبوعية (كل يوم أحد)</option>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">موعد الإغلاق اليومي واستحقاق التأخير:</label>
                    <input type="time" name="submission_deadline" value="<?php echo esc_attr($prep_settings['submission_deadline']); ?>" class="sm-input" style="height:38px; font-size:12px;">
                    <p style="font-size:10px; color: #64748b; margin-top:5px; font-weight:700;">أي تحضير يُسلّم بعد هذا التوقيت يُعتبر تسليماً متأخراً (التوقيت الافتراضي 10:00 صباحاً).</p>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">استثناءات مادة التربية الرياضية (Physical Education):</label>
                    <select name="pe_monday_only" class="sm-select" style="height:38px; font-size:12px; background: #fff;">
                        <option value="yes" <?php selected($prep_settings['pe_monday_only'] == 'yes'); ?>>نعم - يُعفى معلمو التربية الرياضية ويُطلب منهم تحضير يوم الاثنين فقط</option>
                        <option value="no" <?php selected($prep_settings['pe_monday_only'] == 'no'); ?>>لا - يُعامل معلمو التربية الرياضية معاملة بقية المواد</option>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">أيام العمل والتحضير الأسبوعية المعتمدة:</label>
                    <div style="display:flex; gap:15px; flex-wrap:wrap; background:#ffffff; padding:8px 12px; border-radius:6px; border:1px solid #e2e8f0; height: 38px; box-sizing: border-box; align-items: center;">
                        <?php
                        $days_list = array('sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس');
                        foreach ($days_list as $key => $lbl): ?>
                            <label style="font-size:11px; display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-weight: 700; color: #1e293b;">
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
    <div class="sm-modal-content" style="max-width: 750px; padding: 25px; border-radius: 12px;">
        <div class="sm-modal-header" style="border-bottom: 2px solid var(--sm-primary-color); padding-bottom: 15px; margin-bottom: 20px;">
            <h3 id="view-modal-title" style="margin:0; font-size:15px; font-weight:800; color:var(--sm-primary-color); font-family: 'Cairo', sans-serif;">عنوان التحضير المختار</h3>
            <button onclick="document.getElementById('prep-viewer-modal').style.display='none'" class="sm-modal-close" style="position:static; margin:0;">&times;</button>
        </div>
        <div class="sm-modal-body" id="prep-viewer-body" style="max-height: 480px; overflow-y:auto; line-height: 1.6; font-size:13px; text-align:right; font-family: 'Cairo', sans-serif;">
            <!-- Rendered dynamically via JS -->
        </div>
    </div>
</div>

<!-- Supervisor Review Action Modal Dialog -->
<div id="prep-review-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 500px; padding: 25px; border-radius: 12px;">
        <div class="sm-modal-header" style="margin-bottom: 20px;">
            <h3 style="margin:0; font-size: 15px; font-weight:800; font-family: 'Cairo', sans-serif;">مراجعة واعتماد خطة التحضير المحددة</h3>
            <button onclick="document.getElementById('prep-review-modal').style.display='none'" class="sm-modal-close" style="position:static; margin:0;">&times;</button>
        </div>
        <div class="sm-modal-body" style="text-align:right; font-family: 'Cairo', sans-serif;">
            <form method="post">
                <?php wp_nonce_field('eess_supervisor_action', 'eess_supervisor_nonce'); ?>
                <input type="hidden" name="prep_id" id="review-prep-id">

                <div class="sm-form-group">
                    <label class="sm-label">عنوان خطة التحضير:</label>
                    <input type="text" id="review-prep-title" class="sm-input" readonly style="background:#f8fafc; color:#64748b; font-weight: 700; height: 38px; font-size:12px;">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">القرار الإداري الفني والاعتماد:</label>
                    <select name="prep_status_action" class="sm-select" required style="height: 38px; font-size:12px; background: #fff;">
                        <option value="approved">✓ اعتماد وإجازة التحضير (معتمد)</option>
                        <option value="revision_required">⚠ طلب مراجعة وتعديل (تعديل مطلوب)</option>
                        <option value="rejected">✗ رفض وإلغاء وثيقة التحضير (مرفوض)</option>
                    </select>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">الملاحظات والتوجيهات الفنية:</label>
                    <textarea name="supervisor_comment" class="sm-textarea" style="height: 90px; font-size:12px;" placeholder="أدخل ملحوظاتك الفنية وتوجيهاتك للمعلم..."></textarea>
                </div>

                <button type="submit" name="eess_supervisor_action" class="sm-btn" style="background:#16a34a; border-color:#16a34a; width:100%; height: 38px; font-size:12px; font-weight: 700;">تطبيق القرار وإبلاغ المدرس</button>
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

window.smOpenPrepViewer = function(id) {
    const data = eessSubmissions[id];
    if (!data) return;

    document.getElementById('view-modal-title').innerText = data.title;

    let html = `
        <div style="background: #f8fafc; padding: 12px 15px; border-radius: 8px; border:1px solid #e2e8f0; margin-bottom:20px; display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-weight:700; color:#1e293b;">
            <div>المادة الدراسية: <span style="color:#0284c7;">${data.subject}</span></div>
            <div>الصف الدراسي: <span style="color:#0284c7;">${data.grade} (${data.section})</span></div>
            <div style="grid-column: span 2;">تاريخ الدرس المقرر: <span style="color:#0284c7;">${data.date}</span></div>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #334155; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#334155; font-weight:800; font-size:13px;">الأهداف السلوكية والتعليمية المحددة:</h4>
            <p style="margin:0; color:#1e293b; font-weight:600; line-height:1.5;">${data.objectives.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #475569; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#475569; font-weight:800; font-size:13px;">التمهيد والتهيئة الحافزة للدرس:</h4>
            <p style="margin:0; color:#1e293b; font-weight:600; line-height:1.5;">${data.warmup.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #64748b; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#64748b; font-weight:800; font-size:13px;">الأنشطة والخطوات التعليمية الاستراتيجية:</h4>
            <p style="margin:0; color:#1e293b; font-weight:600; line-height:1.5;">${data.activities.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #1e293b; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#1e293b; font-weight:800; font-size:13px;">التقويم الصفي وأدوات القياس التكويني:</h4>
            <p style="margin:0; color:#1e293b; font-weight:600; line-height:1.5;">${data.evaluation.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #b91c1c; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#b91c1c; font-weight:800; font-size:13px;">الواجبات المنزلية والمهام الأكاديمية:</h4>
            <p style="margin:0; color:#1e293b; font-weight:600; line-height:1.5;">${data.homework ? data.homework.replace(/\n/g, '<br>') : 'لا يوجد واجب صفي مقرر'}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #4b5563; padding-right:12px;">
            <h4 style="margin:0 0 5px 0; color:#4b5563; font-weight:800; font-size:13px;">ملاحظات تربوية وتأملات مهنية إضافية:</h4>
            <p style="margin:0; color:#1e293b; font-weight:600; line-height:1.5;">${data.notes ? data.notes.replace(/\n/g, '<br>') : 'لا توجد ملاحظات إضافية'}</p>
        </div>
    `;

    if (data.comments && data.comments.length > 0) {
        html += `
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #e2e8f0;">
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
};

window.smOpenReviewModal = function(id, title) {
    document.getElementById('review-prep-id').value = id;
    document.getElementById('review-prep-title').value = title;
    document.getElementById('prep-review-modal').style.display = 'flex';
};

window.toggleDateSort = function() {
    const currentSort = document.getElementById('sort_order_param').value;
    const newSort = currentSort === 'DESC' ? 'ASC' : 'DESC';
    document.getElementById('sort_order_param').value = newSort;
    document.getElementById('sm-lesson-filters-form').submit();
};
</script>
