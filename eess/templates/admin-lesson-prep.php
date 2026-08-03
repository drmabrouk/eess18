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
        // If coordinator exists in department, or default to principal / administrator
        $supervisors = get_users(array('role__in' => array('sm_supervisor', 'sm_principal', 'administrator')));
        if (!empty($supervisors)) {
            return $supervisors[0]->ID; // Default to first available supervisor
        }
        return 1; // Default fallback to user ID 1
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

    // Compute Late Submission status if submitted
    $delay_seconds = 0;
    $final_status = $status;
    $submission_time = null;

    if ($status === 'submitted') {
        $submission_time = current_time('mysql');
        $submit_timestamp = strtotime($submission_time);
        $deadline_today = strtotime(date('Y-m-d', $submit_timestamp) . ' ' . $deadline_time);

        // Exemption check for Physical Education
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

        // Preserve history by incrementing version if resubmitting from revision_required
        $version = 1;
        $parent_id = 0;
        if ($existing_status === 'revision_required' && $status === 'submitted') {
            $version_data = $wpdb->get_row($wpdb->prepare("SELECT version, parent_id FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            $version = intval($version_data->version) + 1;
            $parent_id = $version_data->parent_id == 0 ? $prep_id : $version_data->parent_id;

            // Create a new version record
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
            // Update old record status to resubmitted
            $wpdb->update("{$wpdb->prefix}sm_lesson_preps", array('status' => 'resubmitted'), array('id' => $prep_id));
        } else {
            // Standard update
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
        // Insert new prep
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
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:15px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:20px; font-weight:700;">تم حفظ التحضير بنجاح.</div>';
}

// Handle Supervisor Actions (Approve, Reject, Request Revision, Comment)
if (isset($_POST['eess_supervisor_action']) && wp_verify_nonce($_POST['eess_supervisor_nonce'], 'eess_supervisor_action_nonce')) {
    $prep_id = intval($_POST['prep_id']);
    $action  = sanitize_text_field($_POST['prep_status_action']); // approved, revision_required, rejected
    $comment = sanitize_textarea_field($_POST['supervisor_comment']);

    // Update status
    $wpdb->update(
        "{$wpdb->prefix}sm_lesson_preps",
        array('status' => $action, 'updated_at' => current_time('mysql')),
        array('id' => $prep_id)
    );

    // Save comment
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
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:15px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:20px; font-weight:700;">تم تحديث حالة التحضير وإضافة الملاحظات بنجاح.</div>';
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
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:15px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:20px; font-weight:700;">تم حفظ إعدادات منظومة التحضير بنجاح.</div>';
}

// Load edit prep details if requested
$edit_prep = null;
if (isset($_GET['edit_prep_id'])) {
    $edit_prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['edit_prep_id']), $user_id));
}

// Load duplicate prep details if requested
if (isset($_GET['duplicate_prep_id'])) {
    $dup_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['duplicate_prep_id']), $user_id));
    if ($dup_source) {
        $edit_prep = $dup_source;
        $edit_prep->id = 0; // Clear ID to make it a new insert
        $edit_prep->title .= ' (نسخة)';
        $edit_prep->lesson_date = current_time('Y-m-d');
        $edit_prep->status = 'draft';
    }
}
?>

<div class="sm-container" style="padding: 20px; font-family: 'Cairo', 'Almarai', sans-serif !important; direction: rtl;">

    <!-- Top Navigation Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: #1e293b;">منظومة تحضير الدروس والخطط التعليمية</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #64748b;">متابعة، إعداد واعتماد التحضيرات الأكاديمية اليومية والأسبوعية.</p>
        </div>
        <a href="<?php echo home_url('/sm-admin'); ?>" class="sm-btn sm-btn-secondary" style="width: auto; text-decoration: none; color: white !important; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-dashboard" style="margin: 0;"></span> العودة للوحة الإدارة
        </a>
    </div>

    <!-- Administrative Statistics Dashboard -->
    <?php if ($can_review):
        // Metrics computations
        $stats_total_required = count(get_users(array('role' => 'sm_teacher'))); // Simple 1 per teacher
        $stats_submitted      = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status IN ('submitted', 'approved', 'revision_required', 'rejected', 'late')");
        $stats_pending        = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'submitted'");
        $stats_approved       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'approved'");
        $stats_rejected       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'rejected'");
        $stats_revision       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'revision_required'");
        $stats_late           = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'late'");

        $submission_pct = $stats_total_required > 0 ? round(($stats_submitted / $stats_total_required) * 100) : 0;
    ?>
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
        <h3 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">إحصائيات الامتثال ومتابعة التحضير</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
            <div class="sm-stat-card" style="border-top: 4px solid #334155; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">التحضيرات المطلوبة</div>
                <div style="font-size: 24px; font-weight: 800; color: #334155;"><?php echo $stats_total_required; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #475569; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">التحضيرات المقدمة</div>
                <div style="font-size: 24px; font-weight: 800; color: #475569;"><?php echo $stats_submitted; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #eab308; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">قيد المراجعة والتدقيق</div>
                <div style="font-size: 24px; font-weight: 800; color: #eab308;"><?php echo $stats_pending; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #16a34a; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">التحضيرات المعتمدة</div>
                <div style="font-size: 24px; font-weight: 800; color: #16a34a;"><?php echo $stats_approved; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #ea580c; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">طلب مراجعة/تعديل</div>
                <div style="font-size: 24px; font-weight: 800; color: #ea580c;"><?php echo $stats_revision; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #dc2626; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">التحضيرات المرفوضة</div>
                <div style="font-size: 24px; font-weight: 800; color: #dc2626;"><?php echo $stats_rejected; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #8b1e1e; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">التحضيرات المتأخرة</div>
                <div style="font-size: 24px; font-weight: 800; color: #8b1e1e;"><?php echo $stats_late; ?></div>
            </div>
            <div class="sm-stat-card" style="border-top: 4px solid #0284c7; text-align: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 13px; color: #64748b; font-weight: 700; margin-bottom: 5px;">نسبة الالتزام والامتثال</div>
                <div style="font-size: 24px; font-weight: 800; color: #0284c7;"><?php echo $submission_pct; ?>%</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">

        <!-- Form Tab (Always available or visible on edit/new for teachers) -->
        <?php if ($is_teacher): ?>
        <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin: 0 0 25px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                <?php echo ($edit_prep && $edit_prep->id > 0) ? 'تعديل وثيقة تحضير درس' : 'إعداد وثيقة تحضير درس جديدة'; ?>
            </h3>

            <form method="post">
                <?php wp_nonce_field('eess_lesson_prep_action', 'eess_lesson_prep_nonce'); ?>
                <?php if ($edit_prep): ?>
                    <input type="hidden" name="prep_id" value="<?php echo $edit_prep->id; ?>">
                <?php endif; ?>

                <?php
                $data = $edit_prep ? json_decode($edit_prep->lesson_data, true) : array();
                ?>

                <!-- Meta Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label class="sm-label" style="font-weight: 700;">عنوان الدرس <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_title" value="<?php echo esc_attr($edit_prep->title ?? ''); ?>" class="sm-input" required placeholder="مثال: الجملة الاسمية ونواسخها">
                    </div>
                    <div>
                        <label class="sm-label" style="font-weight: 700;">المادة الدراسية <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_subject" value="<?php echo esc_attr($edit_prep->subject ?? ''); ?>" class="sm-input" required placeholder="مثال: لغتي الجميلة">
                    </div>
                    <div>
                        <label class="sm-label" style="font-weight: 700;">الصف الدراسي <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_grade" value="<?php echo esc_attr($edit_prep->grade_level ?? ''); ?>" class="sm-input" required placeholder="مثال: الصف الخامس">
                    </div>
                    <div>
                        <label class="sm-label" style="font-weight: 700;">الشعبة / الفصل <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lesson_section" value="<?php echo esc_attr($edit_prep->class_section ?? ''); ?>" class="sm-input" required placeholder="مثال: أ / 1">
                    </div>
                    <div>
                        <label class="sm-label" style="font-weight: 700;">تاريخ إعطاء الدرس <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="lesson_date" value="<?php echo esc_attr($edit_prep->lesson_date ?? current_time('Y-m-d')); ?>" class="sm-input" required>
                    </div>
                </div>

                <!-- Structured Lesson Planning Fields -->
                <div style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-weight: 700;">1. الأهداف السلوكية والتعليمية (Objectives) <span style="color:#ef4444;">*</span></label>
                    <textarea name="objectives" class="sm-input" style="height: 100px;" required placeholder="أدخل الأهداف السلوكية المحددة والواضحة للدرس..."><?php echo esc_textarea($data['objectives'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-weight: 700;">2. التمهيد والتهيئة الحافزة (Warm-up) <span style="color:#ef4444;">*</span></label>
                    <textarea name="warmup" class="sm-input" style="height: 80px;" required placeholder="نشاط تمهيدي لجذب انتباه الطلاب للمفهوم الجديد..."><?php echo esc_textarea($data['warmup'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-weight: 700;">3. الاستراتيجيات، الأنشطة والخطوات التعليمية (Strategies & Activities) <span style="color:#ef4444;">*</span></label>
                    <textarea name="activities" class="sm-input" style="height: 120px;" required placeholder="شرح طريقة عرض المفهوم والوسائل والأنشطة المتبعة..."><?php echo esc_textarea($data['activities'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-weight: 700;">4. التقويم الصفي وأدوات القياس (Evaluation & Assessment) <span style="color:#ef4444;">*</span></label>
                    <textarea name="evaluation" class="sm-input" style="height: 80px;" required placeholder="أسئلة وأدوات تقييم فهم واستيعاب الطلاب خلال الحصة..."><?php echo esc_textarea($data['evaluation'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-weight: 700;">5. الواجبات المنزلية والمهام الأكاديمية (Homework)</label>
                    <textarea name="homework" class="sm-input" style="height: 70px;" placeholder="حدد المهام أو الواجبات المطلوبة من الطلاب بعد انتهاء الدرس..."><?php echo esc_textarea($data['homework'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom: 25px;">
                    <label class="sm-label" style="font-weight: 700;">6. ملاحظات وإرشادات وتأملات مهنية إضافية</label>
                    <textarea name="notes" class="sm-input" style="height: 70px;" placeholder="أي ملاحظات أو إرشادات تربوية إضافية..."><?php echo esc_textarea($data['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Submission Actions -->
                <div style="display: flex; gap: 15px;">
                    <button type="submit" name="eess_save_lesson_prep" onclick="document.getElementById('lesson_status').value='submitted'" class="sm-btn" style="width: auto; background: var(--sm-primary-color);">إرسال واعتماد وثيقة التحضير</button>
                    <button type="submit" name="eess_save_lesson_prep" onclick="document.getElementById('lesson_status').value='draft'" class="sm-btn sm-btn-secondary" style="width: auto; background: var(--sm-secondary-color); color: white !important;">حفظ كمسودة (مسودة)</button>
                    <?php if ($edit_prep): ?>
                        <a href="<?php echo home_url('/lesson-prep'); ?>" class="sm-btn sm-btn-outline" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">إلغاء التعديل</a>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="lesson_status" id="lesson_status" value="submitted">
            </form>
        </div>
        <?php endif; ?>

        <!-- History/List Panel (For teachers to view their list, for supervisors to view/review submissions) -->
        <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin: 0 0 25px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                <?php echo $can_review ? 'استعراض واعتماد خطط تحضير المعلمين' : 'أرشيف وسجل تحضير الدروس الخاص بي'; ?>
            </h3>

            <!-- Search and Filter bar -->
            <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? esc_attr($_GET['page']) : ''; ?>">

                <div>
                    <label style="font-size: 11px; font-weight: bold; color: #475569;">البحث الفوري</label>
                    <input type="text" name="s_query" value="<?php echo isset($_GET['s_query']) ? esc_attr($_GET['s_query']) : ''; ?>" placeholder="اسم المعلم أو العنوان..." class="sm-input" style="height:32px; font-size:12px;">
                </div>

                <div>
                    <label style="font-size: 11px; font-weight: bold; color: #475569;">تاريخ الدرس</label>
                    <input type="date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? esc_attr($_GET['filter_date']) : ''; ?>" class="sm-input" style="height:32px; font-size:12px;">
                </div>

                <div>
                    <label style="font-size: 11px; font-weight: bold; color: #475569;">حالة التحضير</label>
                    <select name="filter_status" class="sm-input" style="height:32px; font-size:12px; padding: 0 10px;">
                        <option value="">كافة الحالات</option>
                        <option value="draft" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'draft'); ?>>مسودة</option>
                        <option value="submitted" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'submitted'); ?>>مقدم للاعتماد</option>
                        <option value="approved" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'approved'); ?>>معتمد</option>
                        <option value="revision_required" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'revision_required'); ?>>تعديل مطلوب</option>
                        <option value="rejected" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'rejected'); ?>>مرفوض</option>
                        <option value="late" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'late'); ?>>تسليم متأخر</option>
                    </select>
                </div>

                <div style="display: flex; align-items: flex-end; gap: 5px;">
                    <button type="submit" class="sm-btn" style="height: 32px; font-size:12px; padding:0 12px; width:auto; background:var(--sm-secondary-color);">تصفية</button>
                    <a href="<?php echo home_url('/lesson-prep'); ?>" class="sm-btn sm-btn-outline" style="height: 32px; font-size:12px; padding:0 12px; width:auto; display:flex; align-items:center; justify-content:center; text-decoration:none;">إعادة ضبط</a>
                </div>
            </form>

            <!-- Table of Submissions -->
            <div class="sm-table-container">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <?php if ($can_review): ?>
                                <th>المعلم</th>
                            <?php endif; ?>
                            <th>العنوان / المادة</th>
                            <th>الصف / الشعبة</th>
                            <th>النسخة</th>
                            <th>التأخير</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Construct query based on filters
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
                            <td colspan="<?php echo $can_review ? 8 : 7; ?>" style="text-align: center; color: #94a3b8; padding: 25px;">لا توجد خطط تحضير مسجلة حالياً تطابق شروط التصفية.</td>
                        </tr>
                        <?php
                        else:
                            foreach ($submissions as $sub):
                                // Delay calculations
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
                            <td style="font-weight: 700;"><?php echo date_i18n('Y-m-d', strtotime($sub->lesson_date)); ?></td>
                            <?php if ($can_review): ?>
                                <td><?php echo esc_html($sub->teacher_name); ?></td>
                            <?php endif; ?>
                            <td>
                                <div style="font-weight:700; color:var(--sm-dark-color);"><?php echo esc_html($sub->title); ?></div>
                                <div style="font-size:11px; color:#64748b;"><?php echo esc_html($sub->subject); ?></div>
                            </td>
                            <td><?php echo esc_html($sub->grade_level . ' (' . $sub->class_section . ')'); ?></td>
                            <td><span style="font-weight:bold; color: #64748b;">إصدار <?php echo $sub->version; ?></span></td>
                            <td>
                                <?php if ($sub->delay_seconds > 0): ?>
                                    <span style="color: #dc2626; font-weight: 700; font-size: 11px;">⚠️ متأخر: <?php echo $delay_desc; ?></span>
                                <?php else: ?>
                                    <span style="color: #16a34a; font-weight: 700; font-size: 11px;">✓ في الموعد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_labels = array(
                                    'draft' => array('label' => 'مسودة', 'bg' => '#f1f5f9', 'color' => '#475569'),
                                    'submitted' => array('label' => 'بانتظار المراجعة', 'bg' => '#fef9c3', 'color' => '#a16207'),
                                    'approved' => array('label' => 'معتمد', 'bg' => '#dcfce7', 'color' => '#15803d'),
                                    'revision_required' => array('label' => 'طلب تعديل', 'bg' => '#ffedd5', 'color' => '#c2410c'),
                                    'rejected' => array('label' => 'مرفوض', 'bg' => '#fee2e2', 'color' => '#b91c1c'),
                                    'late' => array('label' => 'تسليم متأخر', 'bg' => '#ffedd5', 'color' => '#8b1e1e'),
                                    'resubmitted' => array('label' => 'معدل ومستلم', 'bg' => '#e0f2fe', 'color' => '#0369a1'),
                                );
                                $badge = $status_labels[$sub->status] ?? array('label' => $sub->status, 'bg' => '#f1f5f9', 'color' => '#475569');
                                ?>
                                <span style="display:inline-block; padding:3px 10px; border-radius:50px; font-size:11px; font-weight:bold; background:<?php echo $badge['bg']; ?>; color:<?php echo $badge['color']; ?>;">
                                    <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <!-- View/Review button -->
                                    <button onclick="smOpenPrepViewer(<?php echo $sub->id; ?>)" class="sm-btn" style="padding: 4px 8px; font-size:11px; width:auto; background:var(--sm-secondary-color);">عرض المستند</button>

                                    <?php if ($is_teacher): ?>
                                        <?php if ($sub->status === 'draft' || $sub->status === 'revision_required'): ?>
                                            <a href="<?php echo add_query_arg('edit_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn" style="padding: 4px 8px; font-size:11px; width:auto; background:var(--sm-primary-color); text-decoration:none; color:white !important;">تعديل</a>
                                        <?php endif; ?>
                                        <a href="<?php echo add_query_arg('duplicate_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn" style="padding: 4px 8px; font-size:11px; width:auto; background:#475569; text-decoration:none; color:white !important;">نسخ/تكرار</a>
                                    <?php endif; ?>

                                    <?php if ($can_review && ($sub->status === 'submitted' || $sub->status === 'late')): ?>
                                        <button onclick="smOpenReviewModal(<?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-btn" style="padding: 4px 8px; font-size:11px; width:auto; background:#16a34a;">اعتماد / مراجعة</button>
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

    <!-- Administrative Settings Tab/Block (Only for administrators) -->
    <?php if ($is_admin || $is_sys_admin): ?>
    <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 30px;">
        <h3 style="margin: 0 0 25px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">إعدادات وجدولة تسليم التحضيرات</h3>
        <form method="post">
            <?php wp_nonce_field('eess_settings_action', 'eess_settings_nonce'); ?>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom: 20px;">
                <div>
                    <label class="sm-label" style="font-weight: 700;">دورية التسليم الرسمية</label>
                    <select name="submission_frequency" class="sm-input">
                        <option value="daily" <?php selected($prep_settings['submission_frequency'] == 'daily'); ?>>تسليم وثيقة تحضير يومية (درس لكل يوم عمل)</option>
                        <option value="weekly" <?php selected($prep_settings['submission_frequency'] == 'weekly'); ?>>تسليم وثيقة تحضير أسبوعية (كل يوم أحد)</option>
                    </select>
                </div>
                <div>
                    <label class="sm-label" style="font-weight: 700;">موعد الإغلاق اليومي واستحقاق التأخير</label>
                    <input type="time" name="submission_deadline" value="<?php echo esc_attr($prep_settings['submission_deadline']); ?>" class="sm-input">
                    <p style="font-size:11px; color:#64748b; margin-top:5px;">أي تحضير يُسلّم بعد هذا التوقيت يُعتبر تسليماً متأخراً (التوقيت الافتراضي 10:00 صباحاً).</p>
                </div>
                <div>
                    <label class="sm-label" style="font-weight: 700;">استثناءات مادة التربية الرياضية (Physical Education)</label>
                    <select name="pe_monday_only" class="sm-input">
                        <option value="yes" <?php selected($prep_settings['pe_monday_only'] == 'yes'); ?>>نعم - يُعفى معلمو التربية الرياضية ويُطلب منهم تحضير يوم الاثنين فقط</option>
                        <option value="no" <?php selected($prep_settings['pe_monday_only'] == 'no'); ?>>لا - يُعامل معلمو التربية الرياضية معاملة بقية المواد</option>
                    </select>
                </div>
                <div>
                    <label class="sm-label" style="font-weight: 700;">أيام العمل والتحضير الأسبوعية</label>
                    <div style="display:flex; gap:15px; flex-wrap:wrap; background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #e2e8f0;">
                        <?php
                        $days_list = array('sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس');
                        foreach ($days_list as $key => $lbl): ?>
                            <label style="font-size:12px; display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                <input type="checkbox" name="working_days[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $prep_settings['working_days'])); ?>> <?php echo $lbl; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button type="submit" name="eess_save_prep_settings" class="sm-btn" style="width: auto;">حفظ وتعميم جدولة منظومة التحضير</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<!-- Document Viewer Modal (Displays full lesson details) -->
<div id="prep-viewer-modal" class="sm-modal-overlay" style="display: none; position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center;">
    <div class="sm-modal-content" style="background:#fff; max-width: 800px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 id="view-modal-title" style="margin:0; font-weight:800; color:var(--sm-primary-color);">عنوان التحضير</h3>
            <button onclick="document.getElementById('prep-viewer-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 12px; margin:0;">إغلاق ×</button>
        </div>
        <div class="sm-modal-body" id="prep-viewer-body" style="max-height: 500px; overflow-y:auto; line-height: 1.6; font-size:14px; text-align:right;">
            <!-- Rendered dynamically via JS -->
        </div>
    </div>
</div>

<!-- Supervisor Review Action Modal -->
<div id="prep-review-modal" class="sm-modal-overlay" style="display: none; position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center;">
    <div class="sm-modal-content" style="background:#fff; max-width: 600px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 style="margin:0; font-weight:800;">مراجعة واعتماد وثيقة التحضير</h3>
            <button onclick="document.getElementById('prep-review-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 12px; margin:0;">إغلاق ×</button>
        </div>
        <div class="sm-modal-body" style="text-align:right;">
            <form method="post">
                <?php wp_nonce_field('eess_supervisor_action', 'eess_supervisor_nonce'); ?>
                <input type="hidden" name="prep_id" id="review-prep-id">

                <div style="margin-bottom: 15px;">
                    <label class="sm-label" style="font-weight: 700;">اسم التحضير المختار</label>
                    <input type="text" id="review-prep-title" class="sm-input" readonly style="background:#f1f5f9; color:#475569;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="sm-label" style="font-weight: 700;">القرار النهائي والاعتماد</label>
                    <select name="prep_status_action" class="sm-input" required>
                        <option value="approved">✓ اعتماد وإجازة التحضير (معتمد)</option>
                        <option value="revision_required">⚠ طلب مراجعة وتعديل (تعديل مطلوب)</option>
                        <option value="rejected">✗ رفض وإلغاء وثيقة التحضير (مرفوض)</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-weight: 700;">الملاحظات، التوصيات والتوجيهات الفنية</label>
                    <textarea name="supervisor_comment" class="sm-input" style="height: 100px;" placeholder="أدخل ملحوظاتك الفنية وتوجيهاتك للمعلم..."></textarea>
                </div>

                <button type="submit" name="eess_supervisor_action" class="sm-btn" style="background:#16a34a;">تطبيق القرار وحفظ الملاحظات</button>
            </form>
        </div>
    </div>
</div>

<script>
// Array containing all submission data for direct viewer rendering
const eessSubmissions = <?php
    $preps_for_js = array();
    if (!empty($submissions)) {
        foreach ($submissions as $sub) {
            $parsed_data = json_decode($sub->lesson_data, true) ?: array();

            // Comments load
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
        <div style="background:#f8fafc; padding: 15px; border-radius: 8px; border:1px solid #e2e8f0; margin-bottom:20px; display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div><strong>المادة:</strong> ${data.subject}</div>
            <div><strong>الصف الدراسي:</strong> ${data.grade} (${data.section})</div>
            <div><strong>تاريخ الدرس:</strong> ${data.date}</div>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-primary-color); padding-right:10px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-primary-color);">الأهداف السلوكية والتعليمية</h4>
            <p style="margin:0;">${data.objectives.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-secondary-color); padding-right:10px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-secondary-color);">التمهيد والتهيئة الحافزة</h4>
            <p style="margin:0;">${data.warmup.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-accent-color); padding-right:10px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-accent-color);">الأنشطة والخطوات التعليمية الاستراتيجية</h4>
            <p style="margin:0;">${data.activities.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid var(--sm-dark-color); padding-right:10px;">
            <h4 style="margin:0 0 5px 0; color:var(--sm-dark-color);">التقويم الصفي وأدوات القياس</h4>
            <p style="margin:0;">${data.evaluation.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #8b1e1e; padding-right:10px;">
            <h4 style="margin:0 0 5px 0; color:#8b1e1e;">الواجبات المنزلية والمهام الأكاديمية</h4>
            <p style="margin:0;">${data.homework ? data.homework.replace(/\n/g, '<br>') : 'لا يوجد واجب صفي مقرر'}</p>
        </div>
        <div style="margin-bottom: 15px; border-right: 3px solid #64748b; padding-right:10px;">
            <h4 style="margin:0 0 5px 0; color:#64748b;">ملاحظات تربوية وتأملات إضافية</h4>
            <p style="margin:0;">${data.notes ? data.notes.replace(/\n/g, '<br>') : 'لا توجد ملاحظات إضافية'}</p>
        </div>
    `;

    // Comments list rendering
    if (data.comments && data.comments.length > 0) {
        html += `
            <div style="margin-top: 25px; padding-top: 15px; border-top: 2px dashed #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; color:#dc2626;">سجل التوجيهات والملاحظات من المشرفين</h4>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    ${data.comments.map(c => `
                        <div style="background:#fff5f5; border:1px solid #fca5a5; padding:12px; border-radius:8px;">
                            <div style="display:flex; justify-content:space-between; font-size:11px; color:#c53030; font-weight:800; margin-bottom:5px;">
                                <span>المشرف الفني: ${c.author}</span>
                                <span>${c.date}</span>
                            </div>
                            <p style="margin:0; font-size:13px; color:#991b1b; line-height:1.5;">${c.text.replace(/\n/g, '<br>')}</p>
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
