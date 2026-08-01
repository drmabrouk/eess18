<?php

class SM_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function hide_admin_bar_for_non_admins($show) {
        if (!current_user_can('manage_options') || current_user_can('إدارة_النظام')) {
            // System Admins (sm_system_admin) have manage_system/إدارة_النظام
            // User wants admin bar hidden for System Administrator too.
            // Central Control is the only one with 'administrator' role.
            if (!current_user_can('administrator')) {
                return false;
            }
        }
        return $show;
    }

    public function restrict_admin_access() {
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'sm_account_status', true);
            if ($status === 'restricted') {
                wp_logout();
                wp_redirect(home_url('/sm-login?login=failed'));
                exit;
            }
        }

        if (is_admin() && !defined('DOING_AJAX') && !current_user_can('manage_options')) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode', array(), '2.3.8', true);
        wp_enqueue_style($this->plugin_name, SM_PLUGIN_URL . 'assets/css/sm-public.css', array('dashicons'), $this->version, 'all');

        $appearance = SM_Settings::get_appearance();
        $custom_css = "
            :root {
                --sm-primary-color: {$appearance['primary_color']};
                --sm-secondary-color: {$appearance['secondary_color']};
                --sm-accent-color: {$appearance['accent_color']};
                --sm-dark-color: {$appearance['dark_color']};
                --sm-radius: {$appearance['border_radius']};
            }
            .sm-content-wrapper, .sm-admin-dashboard, .sm-container,
            .sm-content-wrapper *:not(.dashicons), .sm-admin-dashboard *:not(.dashicons), .sm-container *:not(.dashicons) {
                font-family: 'Rubik', sans-serif !important;
            }
            .sm-admin-dashboard { font-size: {$appearance['font_size']}; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sm_class_attendance', array($this, 'shortcode_class_attendance'));
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }
        $school = SM_Settings::get_school_info();
        $output = '<div class="sm-login-wrapper" style="max-width: 450px; margin: 60px auto; padding: 40px; background: #fff; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;" dir="rtl">';

        // Logo & Name
        $output .= '<div style="text-align: center; margin-bottom: 35px;">';
        if (!empty($school['school_logo'])) {
            $output .= '<img src="'.esc_url($school['school_logo']).'" style="max-height: 80px; margin-bottom: 15px;">';
        }
        $output .= '<h2 style="margin: 0; font-weight: 900; color: #111F35; font-size: 1.6em;">'.esc_html($school['school_name']).'</h2>';
        $output .= '<p style="margin-top: 5px; color: #718096; font-size: 0.9em;">نظام إدارة السلوك والنتائج الأكاديمية</p>';
        $output .= '</div>';

        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
            $output .= '<div style="background: #fff5f5; color: #c53030; padding: 12px; border-radius: 8px; border: 1px solid #feb2b2; margin-bottom: 20px; font-size: 0.9em; text-align: center;">خطأ في اسم المستخدم أو كلمة المرور.</div>';
        }

        $args = array(
            'echo' => false,
            'redirect' => home_url('/sm-admin'),
            'form_id' => 'sm_login_form',
            'label_username' => 'اسم المستخدم أو الكود',
            'label_password' => 'كلمة المرور',
            'label_remember' => 'تذكرني على هذا الجهاز',
            'label_log_in' => 'دخول النظام الآمن',
            'remember' => true
        );
        $output .= wp_login_form($args);

        // Notice
        $output .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #edf2f7; text-align: center;">';
        $output .= '<p style="font-size: 0.85em; color: #718096; line-height: 1.6;">في حال نسيان بيانات الدخول، يرجى التواصل مع إدارة المدرسة أو المشرف التربوي لإعادة تعيين كلمة المرور الخاصة بك.</p>';
        $output .= '</div>';

        $output .= '</div>';
        return $output;
    }


    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';
        
        // Data Preparation based on tab
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);
        $is_student = in_array('sm_student', $roles);
        $is_parent = in_array('sm_parent', $roles);

        // Security / Capability check for tabs
        if ($active_tab === 'record' && !current_user_can('تسجيل_مخالفة')) $active_tab = 'summary';
        if ($active_tab === 'students' && !current_user_can('إدارة_الطلاب')) $active_tab = 'summary';
        if ($active_tab === 'teachers' && !current_user_can('إدارة_المستخدمين')) $active_tab = 'summary';
        if ($active_tab === 'teacher-reports' && !current_user_can('إدارة_المخالفات')) $active_tab = 'summary';
        if ($active_tab === 'confiscated' && !current_user_can('إدارة_المخالفات')) $active_tab = 'summary';
        if ($active_tab === 'printing' && !current_user_can('طباعة_التقارير')) $active_tab = 'summary';
        if ($active_tab === 'attendance' && !current_user_can('إدارة_الطلاب')) $active_tab = 'summary';
        if ($active_tab === 'clinic' && !current_user_can('إدارة_العيادة')) $active_tab = 'summary';
        if ($active_tab === 'global-settings' && !current_user_can('إدارة_النظام')) $active_tab = 'summary';
        if ($active_tab === 'lesson-plans' && !($is_coordinator || $is_teacher)) $active_tab = 'summary';
        if ($active_tab === 'assignments' && !($is_teacher || $is_student)) $active_tab = 'summary';

        // Fetch data based on tab
        switch ($active_tab) {
            case 'summary':
                if ($is_student) {
                    $student = SM_DB::get_student_by_parent($user->ID);
                    $student_id = $student ? $student->id : 0;
                    $stats = SM_DB::get_student_stats($student_id);
                    $student_assignments = SM_DB::get_assignments($user->ID);

                    // Find assigned supervisor
                    $supervisor = null;
                    if ($student) {
                        $supervisors = get_users(array('role' => 'sm_supervisor'));
                        foreach ($supervisors as $s) {
                            $supervised = get_user_meta($s->ID, 'sm_supervised_classes', true);
                            if (is_array($supervised) && in_array($student->class_name . '|' . $student->section, $supervised)) {
                                $supervisor = $s;
                                break;
                            }
                        }
                    }
                } else {
                    $stats = SM_DB::get_statistics($is_teacher && !$is_admin ? ['teacher_id' => $user->ID] : []);
                }
                break;

            case 'students':
                $args = array();
                if (isset($_GET['student_search'])) $args['search'] = sanitize_text_field($_GET['student_search']);
                if (isset($_GET['class_filter'])) $args['class_name'] = sanitize_text_field($_GET['class_filter']);
                if (isset($_GET['section_filter'])) $args['section'] = sanitize_text_field($_GET['section_filter']);
                if (isset($_GET['teacher_filter']) && !empty($_GET['teacher_filter'])) $args['teacher_id'] = intval($_GET['teacher_filter']);
                if ($is_teacher && !$is_admin) $args['teacher_id'] = $user->ID;
                $students = SM_DB::get_students($args);
                break;

            case 'stats':
                $filters = array();
                if ($is_parent || $is_student) {
                    $my_stu = SM_DB::get_students_by_parent($user->ID);
                    $filters['student_id'] = isset($_GET['student_id']) ? intval($_GET['student_id']) : ($my_stu[0]->id ?? 0);
                } else {
                    if (isset($_GET['student_filter'])) $filters['student_id'] = intval($_GET['student_filter']);
                    if ($is_teacher && !$is_admin) $filters['teacher_id'] = $user->ID;

                    if (isset($_GET['class_filter'])) $filters['class_name'] = sanitize_text_field($_GET['class_filter']);
                    if (isset($_GET['section_filter'])) $filters['section'] = sanitize_text_field($_GET['section_filter']);
                    if (isset($_GET['student_search'])) $filters['search'] = sanitize_text_field($_GET['student_search']);
                }
                if (isset($_GET['start_date'])) $filters['start_date'] = sanitize_text_field($_GET['start_date']);
                if (isset($_GET['end_date'])) $filters['end_date'] = sanitize_text_field($_GET['end_date']);
                if (isset($_GET['type_filter'])) $filters['type'] = sanitize_text_field($_GET['type_filter']);

                // If no filters are applied, limit to latest 20 for quick access
                $is_filtering = !empty($_GET['student_search']) || !empty($_GET['class_filter']) || !empty($_GET['section_filter']) || !empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['type_filter']);
                if (!$is_filtering && !$is_parent) {
                    $filters['limit'] = 20;
                }

                $records = SM_DB::get_records($filters);
                break;

            case 'reports':
                $stats = SM_DB::get_statistics();
                $records = SM_DB::get_records();
                break;

            case 'teacher-reports':
                $records = SM_DB::get_records(array('status' => 'pending'));
                break;

            case 'confiscated':
                $records = SM_DB::get_confiscated_items();
                break;

            case 'attendance':
                $attendance_date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : current_time('Y-m-d');
                $attendance_summary = SM_DB::get_attendance_summary($attendance_date);
                break;
        }

        ob_start();
        include SM_PLUGIN_DIR . 'templates/public-admin-panel.php';
        return ob_get_clean();
    }

    public function login_failed($username) {
        SM_Logger::log('فشل تسجيل الدخول', "محاولة دخول فاشلة للمستخدم: $username");
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function log_successful_login($user_login, $user) {
        SM_Logger::log('تسجيل دخول ناجح', "المستخدم: $user_login (ID: {$user->ID})");
    }

    public function handle_print() {
        $user = wp_get_current_user();
        $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

        if (in_array('sm_parent', (array) $user->roles)) {
            $my_students = SM_DB::get_students_by_parent($user->ID);
            $is_mine = false;
            foreach ($my_students as $ms) {
                if ($ms->id == $student_id) $is_mine = true;
            }
            if (!$is_mine) wp_die('Unauthorized');
        } elseif (!current_user_can('طباعة_التقارير')) {
            wp_die('Unauthorized');
        }

        $type = sanitize_text_field($_GET['print_type']);
        $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

        if ($type === 'id_card') {
            if ($student_id) {
                $students = array(SM_DB::get_student_by_id($student_id));
            } else {
                $filters = array();
                if (!empty($_GET['class_name'])) {
                    $filters['class_name'] = sanitize_text_field($_GET['class_name']);
                }
                $students = SM_DB::get_students($filters);
            }
            include SM_PLUGIN_DIR . 'templates/print-id-cards.php';
        } elseif ($type === 'disciplinary_report') {
            if (!$student_id) wp_die('Student ID missing');
            $student = SM_DB::get_student_by_id($student_id);
            $records = SM_DB::get_records(array('student_id' => $student_id));
            $stats = SM_DB::get_student_stats($student_id);
            include SM_PLUGIN_DIR . 'templates/print-student-report.php';
        } elseif ($type === 'single_violation') {
            $record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;
            if (!$record_id) wp_die('Record ID missing');
            $record = SM_DB::get_record_by_id($record_id);
            if (!$record) wp_die('Record not found');
            
            // Security check for parents
            if (in_array('sm_parent', (array) $user->roles)) {
                $student = SM_DB::get_student_by_parent($user->ID);
                if (!$student || $record->student_id != $student->id) wp_die('Unauthorized');
            }

            include SM_PLUGIN_DIR . 'templates/print-single-violation.php';
        } elseif ($type === 'general_log') {
            $filters = array(
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? ''
            );
            $records = SM_DB::get_records($filters);
            include SM_PLUGIN_DIR . 'templates/print-general-log.php';
        } elseif ($type === 'attendance_sheet') {
            $date = sanitize_text_field($_GET['date']);
            $scope = sanitize_text_field($_GET['scope']); // all, grade, section
            $grade = sanitize_text_field($_GET['grade'] ?? '');
            $section = sanitize_text_field($_GET['section'] ?? '');

            global $wpdb;
            $query = "SELECT s.id, s.name, s.student_code, s.class_name, s.section, a.status
                      FROM {$wpdb->prefix}sm_students s
                      LEFT JOIN {$wpdb->prefix}sm_attendance a ON s.id = a.student_id AND a.date = %s
                      WHERE 1=1";
            $params = array($date);

            if ($scope === 'grade' && $grade) {
                $query .= " AND s.class_name = %s";
                $params[] = $grade;
            } elseif ($scope === 'section' && $grade && $section) {
                $query .= " AND s.class_name = %s AND s.section = %s";
                $params[] = $grade;
                $params[] = $section;
            }

            $query .= " ORDER BY s.class_name ASC, s.section ASC, s.name ASC";
            $all_students = $wpdb->get_results($wpdb->prepare($query, $params));

            $grouped_data = array();
            foreach ($all_students as $s) {
                $key = $s->class_name . '|' . $s->section;
                $grouped_data[$key][] = $s;
            }

            include SM_PLUGIN_DIR . 'templates/print-attendance.php';
        } elseif ($type === 'absence_report') {
            $report_type = sanitize_text_field($_GET['type']); // daily or term
            $date = sanitize_text_field($_GET['date'] ?? current_time('Y-m-d'));

            global $wpdb;
            $data = array();
            $title = '';
            $subtitle = '';

            if ($report_type === 'daily') {
                $title = 'تقرير الغياب اليومي - ' . $date;
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.id, s.name, s.student_code, s.class_name, s.section,
                     (SELECT COUNT(*) FROM {$wpdb->prefix}sm_attendance WHERE student_id = s.id AND status = 'absent') as total_absences
                     FROM {$wpdb->prefix}sm_students s
                     JOIN {$wpdb->prefix}sm_attendance a ON s.id = a.student_id
                     WHERE a.date = %s AND a.status = 'absent'
                     ORDER BY s.class_name ASC, s.section ASC, s.name ASC",
                    $date
                ));
            } else {
                $academic = SM_Settings::get_academic_structure();
                $current_term = null;
                $today = current_time('Y-m-d');
                foreach ($academic['term_dates'] as $t_key => $t_dates) {
                    if (!empty($t_dates['start']) && !empty($t_dates['end'])) {
                        if ($today >= $t_dates['start'] && $today <= $t_dates['end']) {
                            $current_term = $t_dates;
                            $subtitle = 'الفصل الدراسي: ' . $t_key . ' (من ' . $t_dates['start'] . ' إلى ' . $t_dates['end'] . ')';
                            break;
                        }
                    }
                }

                if ($current_term) {
                    $title = 'أكثر الطلاب غياباً خلال الفصل الدراسي';
                    $data = $wpdb->get_results($wpdb->prepare(
                        "SELECT s.id, s.name, s.student_code, s.class_name, s.section, COUNT(a.id) as absence_count
                         FROM {$wpdb->prefix}sm_students s
                         JOIN {$wpdb->prefix}sm_attendance a ON s.id = a.student_id
                         WHERE a.status = 'absent' AND a.date >= %s AND a.date <= %s
                         GROUP BY s.id
                         HAVING absence_count > 0
                         ORDER BY absence_count DESC, s.name ASC
                         LIMIT 50",
                        $current_term['start'], $current_term['end']
                    ));
                } else {
                    $title = 'لا يوجد فصل دراسي حالي محدد في الإعدادات';
                }
            }

            include SM_PLUGIN_DIR . 'templates/print-absence-report.php';
        } elseif ($type === 'student_credentials') {
            $filters = array();
            if (!empty($_GET['class_name'])) {
                $filters['class_name'] = sanitize_text_field($_GET['class_name']);
            }
            $students = SM_DB::get_students($filters);
            include SM_PLUGIN_DIR . 'templates/print-student-credentials.php';
        } elseif ($type === 'student_credentials_card') {
            include SM_PLUGIN_DIR . 'templates/print-student-credentials-card.php';
        } elseif ($type === 'violation_report') {
            $filters = array();
            if (!empty($_GET['search'])) $filters['search'] = sanitize_text_field($_GET['search']);
            if (!empty($_GET['class_filter'])) $filters['class_name'] = sanitize_text_field($_GET['class_filter']);
            if (!empty($_GET['section_filter'])) $filters['section'] = sanitize_text_field($_GET['section_filter']);
            if (!empty($_GET['type_filter'])) $filters['type'] = sanitize_text_field($_GET['type_filter']);

            $range = $_GET['range'] ?? '';
            if ($range === 'today') {
                $filters['start_date'] = current_time('Y-m-d');
                $filters['end_date'] = current_time('Y-m-d');
            } elseif ($range === 'week') {
                $filters['start_date'] = date('Y-m-d', strtotime('-7 days'));
                $filters['end_date'] = current_time('Y-m-d');
            } elseif ($range === 'month') {
                $filters['start_date'] = date('Y-m-d', strtotime('-30 days'));
                $filters['end_date'] = current_time('Y-m-d');
            }

            $records = SM_DB::get_records($filters);
            include SM_PLUGIN_DIR . 'templates/print-violation-report.php';
        }
        exit;
    }

    public function ajax_get_student() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        $code = sanitize_text_field($_POST['code']);
        $student = SM_DB::get_student_by_code($code);
        if ($student) {
            wp_send_json_success($student);
        } else {
            wp_send_json_error('Student not found');
        }
    }

    public function ajax_search_students() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        $query = sanitize_text_field($_POST['query']);
        if (strlen($query) < 2) wp_send_json_success(array());

        $args = array('search' => $query);
        // Teachers can search all students as per new requirements
        $students = SM_DB::get_students($args);
        wp_send_json_success($students);
    }

    public function ajax_get_student_intelligence() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        $student_id = intval($_POST['student_id']);
        if (!$student_id) wp_send_json_error('Invalid ID');

        $stats = SM_DB::get_student_stats($student_id);
        $records = SM_DB::get_records(array('student_id' => $student_id));
        $latest = array_slice($records, 0, 3); // Get 3 latest records
        $student = SM_DB::get_student_by_id($student_id);

        $actions = SM_Settings::get_disciplinary_actions();
        $last_action_index = 0;
        if (!empty($stats['last_action'])) {
            $last_action_index = array_search($stats['last_action'], $actions);
            if ($last_action_index === false) $last_action_index = 0;
        }

        wp_send_json_success(array(
            'stats' => $stats,
            'recent' => $latest,
            'labels' => SM_Settings::get_violation_types(),
            'disciplinary_actions' => $actions,
            'last_action_index' => (int)$last_action_index,
            'is_admin' => current_user_can('manage_options') || current_user_can('إدارة_النظام'),
            'photo_url' => $student ? $student->photo_url : ''
        ));
    }

    public function ajax_refresh_dashboard() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        
        $user_id = get_current_user_id();
        $stats = SM_DB::get_statistics();
        $records = SM_DB::get_records();
        $logs = SM_Logger::get_logs(50);
        
        global $wpdb;
        $unread_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_messages WHERE receiver_id = %d AND is_read = 0", $user_id));

        wp_send_json_success(array(
            'stats' => $stats,
            'records' => $records,
            'logs' => $logs,
            'unread_messages' => intval($unread_count),
            'violation_labels' => SM_Settings::get_violation_types(),
            'severity_labels' => SM_Settings::get_severities()
        ));
    }

    public function ajax_save_record() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) wp_send_json_error('Security check failed');

        $student_ids = array_filter(array_map('intval', explode(',', $_POST['student_ids'])));
        $last_record_id = 0;
        $count = 0;
        
        foreach ($student_ids as $sid) {
            $data = $_POST;
            $data['student_id'] = $sid;
            $rid = SM_DB::add_record($data, true); // Skip individual logs
            if ($rid) {
                $last_record_id = $rid;
                $count++;
                SM_Notifications::send_violation_alert($rid);
            }
        }

        if ($count > 0) {
            SM_Logger::log('تسجيل مخالفة جماعية', "تم تسجيل مخالفة لعدد ($count) من الطلاب بنجاح.");
        }

        if ($last_record_id) {
            wp_send_json_success(array(
                'record_id' => $last_record_id,
                'print_url' => admin_url('admin-ajax.php?action=sm_print&print_type=single_violation&record_id=' . $last_record_id)
            ));
        } else {
            wp_send_json_error('Failed to save records');
        }
    }

    public function ajax_update_student_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_photo_nonce'], 'sm_photo_action')) wp_send_json_error('Security check failed');
        
        $user_id = get_current_user_id();
        $student_id = intval($_POST['student_id']);
        
        // Security: Parent can only update their children, Admin can update anyone
        if (!current_user_can('إدارة_الطلاب')) {
            $my_children = SM_DB::get_students_by_parent($user_id);
            $is_mine = false;
            foreach ($my_children as $child) {
                if ($child->id == $student_id) $is_mine = true;
            }
            if (!$is_mine) wp_send_json_error('Permission denied');
        }

        if (empty($_FILES['student_photo'])) wp_send_json_error('No file provided');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('student_photo', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        $photo_url = wp_get_attachment_url($attachment_id);
        $student_id = intval($_POST['student_id']);
        
        SM_DB::update_student_photo($student_id, $photo_url);
        wp_send_json_success(array('photo_url' => $photo_url));
    }

    public function ajax_send_message() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_message_nonce'], 'sm_message_action')) wp_send_json_error('Security check failed');

        $sender_id = get_current_user_id();
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : null;

        if (SM_DB::send_message($sender_id, $receiver_id, $message, $student_id)) {
            wp_send_json_success('Message sent');
        } else {
            wp_send_json_error('Failed to send message');
        }
    }

    public function ajax_get_conversation() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_message_action')) wp_send_json_error('Security check');

        $my_id = get_current_user_id();
        $other_id = intval($_POST['other_user_id']);

        $messages = SM_DB::get_conversation_messages($my_id, $other_id);
        wp_send_json_success($messages);
    }

    public function ajax_mark_read() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_message_action')) wp_send_json_error('Security check');

        $my_id = get_current_user_id();
        $other_id = intval($_POST['other_user_id']);

        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}sm_messages",
            array('is_read' => 1),
            array('receiver_id' => $my_id, 'sender_id' => $other_id)
        );
        wp_send_json_success();
    }


    public function ajax_update_record_status() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $record_id = intval($_POST['record_id']);
        $status = sanitize_text_field($_POST['status']);

        if (SM_DB::update_record_status($record_id, $status)) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }

    public function ajax_send_group_message() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_message_action')) wp_send_json_error('Security check');

        $role = sanitize_text_field($_POST['target_role']);
        $subject = "رسالة جماعية من إدارة المدرسة";
        $message = sanitize_textarea_field($_POST['message']);

        SM_Notifications::send_group_notification($role, $subject, $message);
        wp_send_json_success('Group messages sent');
    }

    public function ajax_add_student() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) wp_send_json_error('Security check failed');

        $name = sanitize_text_field($_POST['name'] ?? '');
        $class = sanitize_text_field($_POST['class'] ?? '');

        if (empty($name) || empty($class)) {
            wp_send_json_error('الاسم والصف حقول إجبارية');
        }

        $parent_user_id = !empty($_POST['parent_user_id']) ? intval($_POST['parent_user_id']) : null;
        $section = !empty($_POST['section']) ? sanitize_text_field($_POST['section']) : '';
        $email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';

        $extra = array(
            'guardian_phone' => sanitize_text_field($_POST['guardian_phone'] ?? ''),
            'nationality' => sanitize_text_field($_POST['nationality'] ?? ''),
            'registration_date' => sanitize_text_field($_POST['registration_date'] ?? '')
        );

        // Check if student exists
        if (SM_DB::student_exists($name, $class, $section)) {
            wp_send_json_error('هذا الطالب مسجل بالفعل في هذا الصف والشعبة.');
        }

        $id = SM_DB::add_student($name, $class, $email, '', $parent_user_id, null, $section, $extra);

        if ($id) {
            wp_send_json_success($id);
        } else {
            wp_send_json_error('فشل في إضافة الطالب. يرجى التحقق من البيانات والمحاولة مرة أخرى.');
        }
    }

    public function ajax_update_student() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) wp_send_json_error('Security check failed');

        if (SM_DB::update_student(intval($_POST['student_id']), $_POST)) {
            wp_send_json_success('Updated');
        } else {
            wp_send_json_error('Failed to update');
        }
    }

    public function ajax_delete_student() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_delete_student')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id']);
        $student = SM_DB::get_student_by_id($student_id);

        if ($student && SM_DB::delete_student($student_id)) {
            SM_Logger::log('حذف طالب', "تم حذف الطالب: {$student->name} (كود: {$student->student_code})");
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }

    public function ajax_add_confiscated() {
        if (!current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_confiscated_action')) wp_send_json_error('Security check failed');

        $data = $_POST;
        if ($data['item_name'] === 'other' && !empty($data['item_name_other'])) {
            $data['item_name'] = $data['item_name_other'];
        }

        if (SM_DB::add_confiscated_item($data)) {
            // Optional violation linking
            if (!empty($data['link_violation']) && $data['link_violation'] == '1') {
                $violation_code = sanitize_text_field($data['violation_code'] ?? '');
                $reg = SM_Settings::get_regulation_by_code($violation_code);

                $violation_data = array(
                    'student_id' => intval($data['student_id']),
                    'type' => 'behavior',
                    'severity' => 'medium',
                    'degree' => 1,
                    'violation_code' => $violation_code,
                    'points' => $reg ? $reg['points'] : 0,
                    'details' => 'تم تسجيل مخالفة مرتبطة بمصادرة مادة: ' . $data['item_name'],
                    'action_taken' => $reg ? $reg['action'] : 'مصادرة المادة'
                );

                SM_DB::add_record($violation_data);
            }
            wp_send_json_success('Added');
        } else {
            wp_send_json_error('Failed to add');
        }
    }

    public function ajax_update_confiscated() {
        if (!current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_confiscated_action')) wp_send_json_error('Security check failed');

        if (SM_DB::update_confiscated_item_status(intval($_POST['item_id']), sanitize_text_field($_POST['status']))) {
            wp_send_json_success('Updated');
        } else {
            wp_send_json_error('Failed to update');
        }
    }

    public function ajax_delete_confiscated() {
        if (!current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_confiscated_action')) wp_send_json_error('Security check failed');

        if (SM_DB::delete_confiscated_item(intval($_POST['item_id']))) {
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }

    public function ajax_delete_record() {
        if (!current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check failed');

        $record_id = intval($_POST['record_id']);
        $record = SM_DB::get_record_by_id($record_id);

        if ($record && SM_DB::delete_record($record_id)) {
            SM_Logger::log('حذف مخالفة', "تم حذف مخالفة ID: $record_id للطالب ID: {$record->student_id}");
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }

    public function ajax_get_counts() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(array(
            'pending_reports' => intval(SM_DB::get_pending_reports_count()),
            'expired_items' => intval(SM_DB::get_expired_items_count())
        ));
    }

    public function ajax_add_parent() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) wp_send_json_error('Security check failed');

        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        if (empty($email)) $email = $username . '@parent.local';

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => 'sm_parent'
        ));

        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
        else {
            SM_Logger::log('إضافة ولي أمر', "تم إنشاء حساب ولي أمر جديد: {$_POST['display_name']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_add_user() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) wp_send_json_error('Security check failed');

        $username = sanitize_user($_POST['user_login']);
        $email = $username . '@school-system.local'; // Automated email generation

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => sanitize_text_field($_POST['user_role'])
        );
        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
        else {
            if (!empty($_POST['specialization'])) {
                update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
            }
            SM_Logger::log('إضافة مستخدم جديد', "تم إنشاء مستخدم باسم: {$_POST['display_name']} ورتبة: {$_POST['user_role']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_update_generic_user() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_user_id']);
        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        
        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        $u = new WP_User($user_id);
        $u->set_role(sanitize_text_field($_POST['user_role']));
        
        SM_Logger::log('تعديل بيانات مستخدم', "تم تحديث بيانات المستخدم: {$_POST['display_name']} (ID: $user_id)");
        wp_send_json_success('Updated');
    }

    public function ajax_add_teacher() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $pass = $_POST['user_pass'];
        if (empty($pass)) {
            $pass = '';
            for($i=0; $i<10; $i++) $pass .= rand(0,9);
        }

        $username = sanitize_user($_POST['user_login']);
        $email = $username . '@school-system.local'; // Automated

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $pass,
            'role' => sanitize_text_field($_POST['role'] ?: 'sm_teacher')
        );
        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());

        update_user_meta($user_id, 'sm_temp_pass', $pass);
        update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        if (isset($_POST['assigned'])) {
            $assigned = array_map('sanitize_text_field', $_POST['assigned']);
            if ($_POST['role'] === 'sm_teacher') {
                update_user_meta($user_id, 'sm_assigned_sections', $assigned);
            } elseif ($_POST['role'] === 'sm_supervisor') {
                update_user_meta($user_id, 'sm_supervised_classes', $assigned);
            }
        }

        wp_send_json_success($user_id);
    }

    public function ajax_update_profile() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_profile_action')) wp_send_json_error('Security check failed');

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $is_restricted = in_array('sm_student', (array)$user->roles) || in_array('sm_parent', (array)$user->roles);

        $user_data = array(
            'ID' => $user_id
        );

        if (!$is_restricted) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
        }

        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']); // Store as visible
        }

        if (count($user_data) <= 1) {
            wp_send_json_error('No data to update');
        }

        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        else wp_send_json_success('Profile updated');
    }

    public function ajax_bulk_delete() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $type = sanitize_text_field($_POST['delete_type']);
        $count = 0;

        switch ($type) {
            case 'students':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
                SM_Logger::log('مسح كافة الطلاب والسجلات', 'إجراء جماعي');
                break;
            case 'teachers':
                $teachers = get_users(array('role' => 'sm_teacher'));
                foreach ($teachers as $t) {
                    wp_delete_user($t->ID);
                    $count++;
                }
                SM_Logger::log('مسح كافة المعلمين', 'إجراء جماعي');
                break;
            case 'parents':
                $parents = get_users(array('role' => 'sm_parent'));
                foreach ($parents as $p) {
                    wp_delete_user($p->ID);
                    $count++;
                }
                SM_Logger::log('مسح كافة أولياء الأمور', 'إجراء جماعي');
                break;
            case 'records':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
                SM_Logger::log('مسح كافة المخالفات', 'إجراء جماعي');
                break;
        }

        wp_send_json_success('تم مسح البيانات بنجاح');
    }

    public function ajax_get_students_attendance() {
        $class_name = sanitize_text_field($_POST['class_name'] ?? '');
        $section = sanitize_text_field($_POST['section'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));
        $code = sanitize_text_field($_POST['security_code'] ?? '');

        // Security Check: Either Staff or Valid Class Code
        $is_staff = is_user_logged_in() && current_user_can('إدارة_الطلاب');

        if (!$is_staff) {
            if (empty($code)) wp_send_json_error('Security code required');

            if (empty($class_name) || empty($section)) {
                // Visitor mode: Search for class by code
                $all_codes = SM_Settings::get_class_security_codes();
                $found_key = array_search($code, $all_codes);
                if (!$found_key) wp_send_json_error('Invalid security code');

                list($class_name, $section) = explode('|', $found_key);
            } else {
                $valid_code = (SM_Settings::get_class_security_code($class_name, $section) === $code);
                if (!$valid_code) wp_send_json_error('Invalid security code');
            }
        }

        if (empty($class_name) || empty($section)) wp_send_json_error('Missing class information');

        $students = SM_DB::get_students_attendance($class_name, $section, $date);
        wp_send_json_success($students);
    }

    public function shortcode_class_attendance() {
        ob_start();
        include SM_PLUGIN_DIR . 'templates/shortcode-class-attendance.php';
        return ob_get_clean();
    }

    public function ajax_save_attendance() {
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id']);
        $status = sanitize_text_field($_POST['status']);
        $date = sanitize_text_field($_POST['date']);
        $code = sanitize_text_field($_POST['security_code'] ?? '');

        // Get student info to check class
        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('Student not found');

        $is_staff = is_user_logged_in() && current_user_can('إدارة_الطلاب');
        $valid_code = (SM_Settings::get_class_security_code($student->class_name, $student->section) === $code);

        if (!$is_staff && !$valid_code) {
            wp_send_json_error('Unauthorized');
        }

        $teacher_id = get_current_user_id(); // 0 for public

        if (SM_DB::save_attendance($student_id, $status, $date, $teacher_id)) {
            wp_send_json_success('Saved');
        } else {
            wp_send_json_error('Failed to save');
        }
    }

    public function ajax_save_attendance_batch() {
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $batch = json_decode(stripslashes($_POST['batch'] ?? '[]'), true);
        if (empty($batch)) wp_send_json_error('Empty batch');

        $first_sid = intval($batch[0]['student_id']);
        $student = SM_DB::get_student_by_id($first_sid);
        if (!$student) wp_send_json_error('Student not found');

        $code = sanitize_text_field($_POST['security_code'] ?? '');
        $is_staff = is_user_logged_in() && current_user_can('إدارة_الطلاب');
        $valid_code = (SM_Settings::get_class_security_code($student->class_name, $student->section) === $code);

        if (!$is_staff && !$valid_code) {
            wp_send_json_error('Unauthorized');
        }

        $date = sanitize_text_field($_POST['date']);
        $teacher_id = get_current_user_id();

        if (!is_array($batch)) wp_send_json_error('Invalid batch data');

        $success_count = 0;
        foreach ($batch as $item) {
            if (SM_DB::save_attendance(intval($item['student_id']), sanitize_text_field($item['status']), $date, $teacher_id)) {
                $success_count++;
            }
        }

        wp_send_json_success($success_count);
    }

    public function ajax_reset_class_code() {
        if (!is_user_logged_in() || !current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $grade = sanitize_text_field($_POST['grade']);
        $section = sanitize_text_field($_POST['section']);

        $new_code = SM_Settings::reset_class_security_code($grade, $section);
        wp_send_json_success($new_code);
    }

    public function ajax_toggle_attendance_status() {
        if (!is_user_logged_in() || !current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $status = sanitize_text_field($_POST['status']);
        update_option('sm_attendance_manual_status', $status);
        wp_send_json_success();
    }

    public function ajax_filter_violations() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $filters = array();
        if (isset($_POST['student_search'])) $filters['search'] = sanitize_text_field($_POST['student_search']);
        if (isset($_POST['class_filter'])) $filters['class_name'] = sanitize_text_field($_POST['class_filter']);
        if (isset($_POST['section_filter'])) $filters['section'] = sanitize_text_field($_POST['section_filter']);
        if (isset($_POST['type_filter'])) $filters['type'] = sanitize_text_field($_POST['type_filter']);

        $records = SM_DB::get_records($filters);

        ob_start();
        include SM_PLUGIN_DIR . 'templates/partials/violations-table-rows.php';
        $rows_html = ob_get_clean();

        wp_send_json_success(array('html' => $rows_html));
    }

    public function ajax_mark_contacted() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $record_id = intval($_POST['record_id']);
        if (SM_DB::mark_record_contacted($record_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update status');
        }
    }

    public function ajax_add_document() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->insert("{$wpdb->prefix}sm_documents", array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'file_url' => esc_url_raw($_POST['file_url']),
            'status' => sanitize_text_field($_POST['status']),
            'created_by' => get_current_user_id()
        ));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to save');
    }

    public function ajax_update_document() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->update("{$wpdb->prefix}sm_documents", array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'file_url' => esc_url_raw($_POST['file_url']),
            'status' => sanitize_text_field($_POST['status'])
        ), array('id' => intval($_POST['doc_id'])));

        if ($result !== false) wp_send_json_success();
        else wp_send_json_error('Failed to update');
    }

    public function ajax_delete_document() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->delete("{$wpdb->prefix}sm_documents", array('id' => intval($_POST['doc_id'])));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete');
    }

    public function ajax_save_regulation_settings() {
        if (!current_user_can('manage_options') && !current_user_can('sm_principal') && !current_user_can('sm_supervisor')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
        $types = array();
        foreach ($types_raw as $line) {
            $parts = explode("|", $line);
            if (count($parts) == 2) {
                $types[trim($parts[0])] = trim($parts[1]);
            }
        }
        if (!empty($types)) {
            SM_Settings::save_violation_types($types);
        }
        SM_Settings::save_suggested_actions(array(
            'low' => sanitize_textarea_field($_POST['suggested_low']),
            'medium' => sanitize_textarea_field($_POST['suggested_medium']),
            'high' => sanitize_textarea_field($_POST['suggested_high'])
        ));

        SM_Logger::log('تحديث إعدادات اللائحة', 'قام المستخدم بتحديث أنواع المخالفات العامة واقتراحات الإجراءات.');

        wp_send_json_success();
    }

    public function ajax_save_hierarchical_violations() {
        if (!current_user_can('manage_options') && !current_user_can('sm_principal') && !current_user_can('sm_supervisor')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $processed = array();
        if (isset($_POST['h_viol']) && is_array($_POST['h_viol'])) {
            foreach ($_POST['h_viol'] as $level => $items) {
                $processed[$level] = array();
                foreach ($items as $item) {
                    if (!empty($item['name'])) {
                        $code = !empty($item['code']) ? $item['code'] : 'V'.rand(100,999);
                        $processed[$level][$code] = array(
                            'name' => sanitize_text_field($item['name']),
                            'points' => intval($item['points']),
                            'action' => sanitize_text_field($item['action'])
                        );
                    }
                }
            }
        }
        SM_Settings::save_hierarchical_violations($processed);
        SM_Logger::log('تحديث لائحة المخالفات الهرمية', 'تم تحديث بنود اللائحة والنقاط والإجراءات لجميع المستويات.');

        wp_send_json_success();
    }

    public function ajax_delete_log() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $log_id = intval($_POST['log_id']);
        $result = $wpdb->delete("{$wpdb->prefix}sm_logs", array('id' => $log_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete log');
    }

    public function ajax_delete_all_logs() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");

        if ($result !== false) {
            SM_Logger::log('مسح كافة النشاطات', 'قام المستخدم بمسح سجل النشاطات بالكامل');
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete logs');
        }
    }

    public function ajax_rollback_log() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        $log_id = intval($_POST['log_id']);
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_logs WHERE id = %d", $log_id));

        if (!$log || strpos($log->details, 'ROLLBACK_DATA:') !== 0) {
            wp_send_json_error('لا يمكن استعادة هذه العملية');
        }

        $json = substr($log->details, strlen('ROLLBACK_DATA:'));
        $data_obj = json_decode($json, true);

        if (!$data_obj || !isset($data_obj['table']) || !isset($data_obj['data'])) {
            wp_send_json_error('بيانات الاستعادة تالفة');
        }

        $table = $data_obj['table'];
        $data = $data_obj['data'];

        // Remove 'id' if we want to insert as new, or keep if we want to restore exact ID (risky if ID taken)
        // For students/records, restoring exact ID is better for relations.

        $table_name = $wpdb->prefix . 'sm_' . $table;

        // Check if ID already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $data['id']));
        if ($exists) {
            wp_send_json_error('البيانات موجودة بالفعل أو تم استخدام المعرف');
        }

        $result = $wpdb->insert($table_name, $data);

        if ($result) {
            $wpdb->delete("{$wpdb->prefix}sm_logs", array('id' => $log_id)); // Remove log after rollback
            SM_Logger::log('استعادة عملية محذوفة', "الجدول: $table، المعرف الأصلي: {$data['id']}");
            wp_send_json_success('تمت الاستعادة بنجاح');
        } else {
            wp_send_json_error('فشلت عملية الاستعادة في قاعدة البيانات');
        }
    }

    public function ajax_initialize_system() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        if ($_POST['confirm_code'] !== '1011996') {
            wp_send_json_error('كود التأكيد غير صحيح');
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_confiscated_items");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");

        $teachers = get_users(array('role' => 'sm_teacher'));
        foreach ($teachers as $t) wp_delete_user($t->ID);

        $parents = get_users(array('role' => 'sm_parent'));
        foreach ($parents as $p) wp_delete_user($p->ID);

        SM_Logger::log('تهيأة النظام بالكامل', 'تم مسح كافة البيانات والجداول');
        wp_send_json_success('تمت تهيأة النظام بالكامل بنجاح');
    }

    public function ajax_update_teacher() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_teacher_id']);
        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']);
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());

        $u = new WP_User($user_id);
        $role = sanitize_text_field($_POST['role']);
        $u->set_role($role);

        update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'sm_account_status', sanitize_text_field($_POST['account_status']));

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        // Clean old assignments
        delete_user_meta($user_id, 'sm_assigned_sections');
        delete_user_meta($user_id, 'sm_supervised_classes');

        if (isset($_POST['assigned'])) {
            $assigned = array_map('sanitize_text_field', $_POST['assigned']);
            if ($role === 'sm_teacher') {
                update_user_meta($user_id, 'sm_assigned_sections', $assigned);
            } elseif ($role === 'sm_supervisor') {
                update_user_meta($user_id, 'sm_supervised_classes', $assigned);
            }
        }

        wp_send_json_success('Updated');
    }

    public function ajax_add_assignment() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_assignment_action')) wp_send_json_error('Security check');

        $data = array(
            'sender_id' => get_current_user_id(),
            'receiver_id' => intval($_POST['receiver_id']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'file_url' => esc_url_raw($_POST['file_url']),
            'type' => sanitize_text_field($_POST['type'] ?? 'assignment')
        );

        if (SM_DB::add_assignment($data)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed');
        }
    }

    public function ajax_approve_plan() {
        if (!current_user_can('مراجعة_التحضير')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_assignment_action')) wp_send_json_error('Security check');

        global $wpdb;
        $plan_id = intval($_POST['plan_id']);
        $result = $wpdb->update("{$wpdb->prefix}sm_assignments",
            array('receiver_id' => get_current_user_id()), // Mark as approved by current coordinator
            array('id' => $plan_id, 'type' => 'lesson_plan')
        );

        if ($result) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_bulk_delete_users() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_teacher_action')) wp_send_json_error('Security check');

        $ids = array_map('intval', explode(',', $_POST['user_ids']));
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $count = 0;
        foreach ($ids as $id) {
            if ($id != get_current_user_id()) {
                if (wp_delete_user($id)) $count++;
            }
        }
        SM_Logger::log('حذف مستخدمين (جماعي)', "تم حذف عدد ($count) مستخدم من النظام.");
        wp_send_json_success();
    }

    public function ajax_add_clinic_referral() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $student_id = intval($_POST['student_id']);
        $referrer_id = get_current_user_id();

        $result = $wpdb->insert("{$wpdb->prefix}sm_clinic", array(
            'student_id' => $student_id,
            'referrer_id' => $referrer_id,
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            $student = SM_DB::get_student_by_id($student_id);
            SM_Logger::log('تحويل للعيادة', "تم تحويل الطالب: {$student->name} للعيادة");
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to add referral');
        }
    }

    public function ajax_confirm_clinic_arrival() {
        if (!is_user_logged_in() || !current_user_can('إدارة_العيادة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $referral_id = intval($_POST['referral_id']);

        $result = $wpdb->update("{$wpdb->prefix}sm_clinic", array(
            'arrival_confirmed' => 1,
            'arrival_at' => current_time('mysql')
        ), array('id' => $referral_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to confirm arrival');
    }

    public function ajax_update_clinic_record() {
        if (!is_user_logged_in() || !current_user_can('إدارة_العيادة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $referral_id = intval($_POST['referral_id']);
        $health_condition = sanitize_textarea_field($_POST['health_condition']);
        $action_taken = sanitize_textarea_field($_POST['action_taken']);

        $result = $wpdb->update("{$wpdb->prefix}sm_clinic", array(
            'health_condition' => $health_condition,
            'action_taken' => $action_taken
        ), array('id' => $referral_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to update record');
    }

    public function ajax_get_clinic_reports() {
        if (!is_user_logged_in() || !current_user_can('إدارة_العيادة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_clinic_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $type = sanitize_text_field($_GET['report_type']); // day, week, month, term, year
        $start_date = '';
        $end_date = current_time('Y-m-d') . ' 23:59:59';

        switch ($type) {
            case 'day': $start_date = current_time('Y-m-d') . ' 00:00:00'; break;
            case 'week': $start_date = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'; break;
            case 'month': $start_date = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'; break;
            case 'term':
                $academic = SM_Settings::get_academic_structure();
                $today = current_time('Y-m-d');
                foreach ($academic['term_dates'] as $t) {
                    if ($today >= $t['start'] && $today <= $t['end']) {
                        $start_date = $t['start'] . ' 00:00:00';
                        $end_date = $t['end'] . ' 23:59:59';
                        break;
                    }
                }
                if (empty($start_date)) $start_date = date('Y-m-01') . ' 00:00:00';
                break;
            case 'year': $start_date = date('Y-01-01') . ' 00:00:00'; break;
        }

        $query = "SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
                  FROM {$wpdb->prefix}sm_clinic c
                  JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
                  JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
                  WHERE c.created_at BETWEEN %s AND %s
                  ORDER BY c.created_at DESC";

        $records = $wpdb->get_results($wpdb->prepare($query, $start_date, $end_date));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clinic_report_'.$type.'_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
        fputcsv($output, array('التاريخ', 'اسم الطالب', 'الصف', 'الشعبة', 'المحول', 'تأكيد الوصول', 'الحالة الصحية', 'الإجراء المتخذ'));

        foreach ($records as $r) {
            fputcsv($output, array(
                $r->created_at,
                $r->student_name,
                $r->class_name,
                $r->section,
                $r->referrer_name,
                $r->arrival_confirmed ? 'نعم' : 'لا',
                $r->health_condition,
                $r->action_taken
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_save_grade_ajax() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security check failed');

        $subject = sanitize_text_field($_POST['subject']);
        $user = wp_get_current_user();
        if (in_array('sm_teacher', (array)$user->roles) && !current_user_can('manage_options')) {
            $spec = get_user_meta($user->ID, 'sm_specialization', true);
            if ($spec && $spec !== $subject) {
                wp_send_json_error('غير مسموح لك برصد درجات لمادة غير مخصص لك.');
            }
        }

        global $wpdb;
        $result = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
            'student_id' => intval($_POST['student_id']),
            'subject' => $subject,
            'term' => sanitize_text_field($_POST['term']),
            'grade_val' => sanitize_text_field($_POST['grade_val']),
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            SM_Logger::log('رصد درجة', "تم رصد درجة للطالب ID: {$_POST['student_id']} في مادة $subject");
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save grade');
        }
    }

    public function ajax_get_student_grades_ajax() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sm_grade_action')) wp_send_json_error('Security');

        global $wpdb;
        $student_id = intval($_POST['student_id']);

        // Security check: if student, can only see own. If staff, can see all.
        if (in_array('sm_student', (array)wp_get_current_user()->roles)) {
            $student = SM_DB::get_student_by_parent(get_current_user_id());
            if (!$student || $student->id != $student_id) wp_send_json_error('Unauthorized access to grades');
        }

        $grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_grades WHERE student_id = %d ORDER BY created_at DESC", $student_id));
        wp_send_json_success($grades);
    }

    public function ajax_delete_grade_ajax() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $result = $wpdb->delete("{$wpdb->prefix}sm_grades", array('id' => intval($_POST['grade_id'])));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete grade');
    }

    public function ajax_add_subject() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $name = sanitize_text_field($_POST['name']);
        $grade_ids = isset($_POST['grade_ids']) ? array_map('intval', $_POST['grade_ids']) : array();

        if (empty($grade_ids) && isset($_POST['grade_id'])) {
            $grade_ids = array(intval($_POST['grade_id']));
        }

        if (SM_DB::add_subject($name, $grade_ids)) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_delete_subject() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        if (SM_DB::delete_subject(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_get_subjects() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $grade_id = isset($_GET['grade_id']) ? intval($_GET['grade_id']) : null;
        wp_send_json_success(SM_DB::get_subjects($grade_id));
    }

    public function ajax_save_class_grades() {
        if (!current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security');

        $subject = sanitize_text_field($_POST['subject']);
        $user = wp_get_current_user();
        if (in_array('sm_teacher', (array)$user->roles) && !current_user_can('manage_options')) {
            $spec = get_user_meta($user->ID, 'sm_specialization', true);
            if ($spec && $spec !== $subject) {
                wp_send_json_error('غير مسموح لك برصد درجات لمادة غير مخصص لك.');
            }
        }

        $term = sanitize_text_field($_POST['term']);
        $grades = json_decode(stripslashes($_POST['grades']), true);

        global $wpdb;
        $success = 0;
        foreach ($grades as $student_id => $val) {
            if ($val === '') continue;
            $res = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
                'student_id' => intval($student_id),
                'subject' => $subject,
                'term' => $term,
                'grade_val' => sanitize_text_field($val),
                'created_at' => current_time('mysql')
            ));
            if ($res) $success++;
        }
        wp_send_json_success($success);
    }

    public function ajax_bulk_delete_students() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_delete_student')) wp_send_json_error('Security');

        $ids = array_map('intval', explode(',', $_POST['student_ids']));
        $count = 0;
        foreach ($ids as $id) {
            if (SM_DB::delete_student($id)) $count++;
        }
        SM_Logger::log('حذف طلاب (جماعي)', "تم حذف عدد ($count) طالب من النظام.");
        wp_send_json_success($count);
    }

    public function ajax_add_survey() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_supervisor', (array)wp_get_current_user()->roles)) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $title = sanitize_text_field($_POST['title']);
        $questions = json_decode(stripslashes($_POST['questions']), true);
        $recipients = sanitize_text_field($_POST['recipients']); // role or 'all'

        $survey_id = SM_DB::add_survey($title, $questions, $recipients, get_current_user_id());
        if ($survey_id) wp_send_json_success($survey_id);
        else wp_send_json_error('Failed to add survey');
    }

    public function ajax_cancel_survey() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_supervisor', (array)wp_get_current_user()->roles)) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $survey_id = intval($_POST['id']);
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_surveys", array('status' => 'cancelled'), array('id' => $survey_id));
        wp_send_json_success();
    }

    public function ajax_submit_survey_response() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security');

        $survey_id = intval($_POST['survey_id']);
        $responses = json_decode(stripslashes($_POST['responses']), true);
        $user_id = get_current_user_id();

        if (SM_DB::save_survey_response($survey_id, $user_id, $responses)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save response');
        }
    }

    public function ajax_get_survey_results() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_supervisor', (array)wp_get_current_user()->roles)) {
            wp_send_json_error('Unauthorized');
        }
        $survey_id = intval($_GET['id']);
        $results = SM_DB::get_survey_results($survey_id);
        wp_send_json_success($results);
    }

    public function ajax_export_survey_results() {
         if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_supervisor', (array)wp_get_current_user()->roles)) {
            wp_send_json_error('Unauthorized');
        }
        $survey_id = intval($_GET['id']);
        $survey = SM_DB::get_survey($survey_id);
        if (!$survey) wp_send_json_error('Survey not found');

        $responses = SM_DB::get_survey_responses($survey_id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=survey_results_'.$survey_id.'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

        $questions = json_decode($survey->questions, true);
        $header = array('المجيب', 'الدور');
        foreach($questions as $q) $header[] = $q;
        fputcsv($output, $header);

        foreach ($responses as $r) {
            $user = get_userdata($r->user_id);
            $role = !empty($user->roles) ? $user->roles[0] : '';
            $row = array($user->display_name, $role);
            $res_data = json_decode($r->responses, true);
            foreach($questions as $index => $q) {
                $row[] = $res_data[$index] ?? '';
            }
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    public function ajax_update_timetable_entry() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_supervisor', (array)wp_get_current_user()->roles)) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $classes_json = $_POST['classes'] ?? '';
        $classes = json_decode(stripslashes($classes_json), true);

        if (empty($classes) && !empty($_POST['class_name'])) {
            $classes = array(array('class_name' => $_POST['class_name'], 'section' => $_POST['section']));
        }

        $day = sanitize_text_field($_POST['day']);
        $period = intval($_POST['period']);
        $subject_id = intval($_POST['subject_id']);
        $teacher_id = intval($_POST['teacher_id']);

        $success_count = 0;
        foreach ($classes as $c) {
            if (SM_DB::update_timetable($c['class_name'], $c['section'], $day, $period, $subject_id, $teacher_id)) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            wp_send_json_success($success_count);
        } else {
            wp_send_json_error('Failed');
        }
    }

    public function ajax_save_timetable_settings() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles)) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $periods = intval($_POST['periods']);
        $days = isset($_POST['days']) ? array_map('sanitize_text_field', $_POST['days']) : array();

        SM_Settings::save_timetable_settings(array(
            'periods' => $periods,
            'days' => $days
        ));

        wp_send_json_success();
    }

    public function ajax_download_plans_zip() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_coordinator', (array)wp_get_current_user()->roles)) {
            wp_die('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'], 'sm_admin_action')) wp_die('Security');

        global $wpdb;
        $plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_assignments WHERE type = 'lesson_plan'");

        if (empty($plans)) wp_die('No plans to download');

        if (!class_exists('ZipArchive')) {
            wp_die('ZipArchive extension not enabled on this server.');
        }

        $zip = new ZipArchive();
        $zip_name = 'lesson_plans_' . date('Y-m-d') . '.zip';
        $upload_dir = wp_upload_dir();
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die('Could not create zip file');
        }

        foreach ($plans as $p) {
            if (empty($p->file_url)) continue;

            // Try to get local path
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $p->file_url);
            if (file_exists($file_path)) {
                $zip->addFile($file_path, basename($file_path));
            }
        }

        $zip->close();

        if (file_exists($zip_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name . '"');
            header('Content-Length: ' . filesize($zip_path));
            readfile($zip_path);
            unlink($zip_path);
            exit;
        } else {
            wp_die('Failed to generate zip');
        }
    }

    public function ajax_export_violations_csv() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_export_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $range = sanitize_text_field($_GET['range']); // today, week, month, all
        $start_date = '';
        $end_date = current_time('Y-m-d') . ' 23:59:59';
        $student_code = $_GET['student_code'] ?? '';

        if ($range !== 'all') {
            switch ($range) {
                case 'today': $start_date = current_time('Y-m-d') . ' 00:00:00'; break;
                case 'week': $start_date = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'; break;
                case 'month': $start_date = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'; break;
            }
        }

        $query = "SELECT r.*, s.name as student_name, s.class_name, s.section, s.student_code
                  FROM {$wpdb->prefix}sm_records r
                  JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id
                  WHERE 1=1";

        $params = array();
        if ($start_date) {
            $query .= " AND r.created_at BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        if ($student_code) {
            $query .= " AND s.student_code = %s";
            $params[] = $student_code;
        }

        $query .= " ORDER BY r.created_at DESC";

        $records = empty($params) ? $wpdb->get_results($query) : $wpdb->get_results($wpdb->prepare($query, $params));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=violations_'.$range.'_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, array('التاريخ', 'اسم الطالب', 'كود الطالب', 'الصف', 'الشعبة', 'النوع', 'الحدة', 'الدرجة', 'النقاط', 'التفاصيل', 'الإجراء المتخذ'));

        foreach ($records as $r) {
            // Dynamic Linking
            $reg = SM_Settings::get_regulation_by_code($r->violation_code);
            $display_type = $reg ? $reg['name'] : $r->type;
            $display_action = $reg ? $reg['action'] : $r->action_taken;

            fputcsv($output, array(
                $r->created_at,
                $r->student_name,
                $r->student_code,
                $r->class_name,
                $r->section,
                $display_type,
                $r->severity,
                $r->degree,
                $r->points,
                $r->details,
                $display_action
            ));
        }
        fclose($output);
        exit;
    }

    public function handle_form_submission() {
        // Handle Hierarchical Violations Save
        if (isset($_POST['sm_save_hierarchical_violations']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $processed = array();
                if (isset($_POST['h_viol']) && is_array($_POST['h_viol'])) {
                    foreach ($_POST['h_viol'] as $level => $items) {
                        $processed[$level] = array();
                        foreach ($items as $item) {
                            if (!empty($item['name'])) {
                                $code = !empty($item['code']) ? $item['code'] : 'V'.rand(100,999);
                                $processed[$level][$code] = array(
                                    'name' => sanitize_text_field($item['name']),
                                    'points' => intval($item['points']),
                                    'action' => sanitize_text_field($item['action'])
                                );
                            }
                        }
                    }
                }
                SM_Settings::save_hierarchical_violations($processed);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Parent Call-in Request
        if (isset($_POST['sm_send_call_in']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_message_action')) {
            if (current_user_can('إدارة_أولياء_الأمور')) {
                $receiver_id = intval($_POST['receiver_id']);
                $message = "🔴 طلب استدعاء رسمي: " . sanitize_textarea_field($_POST['message']);
                SM_DB::send_message(get_current_user_id(), $receiver_id, $message);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Generic User Update
        if (isset($_POST['sm_update_generic_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('إدارة_المستخدمين')) {
                $user_id = intval($_POST['edit_user_id']);
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name'])
                );
                if (!empty($_POST['user_pass'])) {
                    $user_data['user_pass'] = $_POST['user_pass'];
                }
                wp_update_user($user_data);
                
                $u = new WP_User($user_id);
                $u->set_role(sanitize_text_field($_POST['user_role']));

                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Record Saving
        if (isset($_POST['sm_save_record']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) {
            $record_id = SM_DB::add_record($_POST);
            if ($record_id) {
                SM_Notifications::send_violation_alert($record_id);
                $url = add_query_arg(array('sm_msg' => 'success', 'last_id' => $record_id), $_SERVER['REQUEST_URI']);
                wp_redirect($url);
                exit;
            }
        }

        // Handle Generic User Addition
        if (isset($_POST['sm_add_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('إدارة_المستخدمين')) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['user_login']),
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'user_pass' => $_POST['user_pass'],
                    'role' => sanitize_text_field($_POST['user_role'])
                );
                wp_insert_user($user_data);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Generic User Deletion
        if (isset($_POST['sm_delete_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('إدارة_المستخدمين')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user(intval($_POST['delete_user_id']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher Addition from Public Admin
        if (isset($_POST['sm_add_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('إدارة_المعلمين')) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['user_login']),
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'user_pass' => $_POST['user_pass'],
                    'role' => 'sm_teacher'
                );
                $user_id = wp_insert_user($user_data);
                if (!is_wp_error($user_id)) {
                    update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
                    update_user_meta($user_id, 'sm_job_title', sanitize_text_field($_POST['job_title']));
                    update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
                    wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }

        // Handle Teacher Update
        if (isset($_POST['sm_update_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('إدارة_المعلمين')) {
                $user_id = intval($_POST['edit_teacher_id']);
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name'])
                );
                if (!empty($_POST['user_pass'])) {
                    $user_data['user_pass'] = $_POST['user_pass'];
                }
                wp_update_user($user_data);
                update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
                update_user_meta($user_id, 'sm_job_title', sanitize_text_field($_POST['job_title']));
                update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher Deletion
        if (isset($_POST['sm_delete_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('إدارة_المعلمين')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user(intval($_POST['delete_teacher_id']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Record Update
        if (isset($_POST['sm_update_record']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) {
            if (current_user_can('إدارة_المخالفات')) {
                SM_DB::update_record(intval($_POST['record_id']), $_POST);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Addition from Public Admin
        if (isset($_POST['add_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('إدارة_الطلاب')) {
                $parent_user_id = !empty($_POST['parent_user_id']) ? intval($_POST['parent_user_id']) : null;
                $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
                SM_DB::add_student($_POST['name'], $_POST['class'], $_POST['email'], $_POST['code'], $parent_user_id, $teacher_id);
                wp_redirect(add_query_arg('sm_admin_msg', 'student_added', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Deletion from Public Admin
        if (isset($_POST['delete_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('إدارة_الطلاب')) {
                SM_DB::delete_student($_POST['delete_student_id']);
                wp_redirect(add_query_arg('sm_admin_msg', 'student_deleted', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Update from Public Admin
        if (isset($_POST['sm_update_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('إدارة_الطلاب')) {
                SM_DB::update_student(intval($_POST['student_id']), $_POST);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Backup Download
        if (isset($_POST['sm_download_backup']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                if (ob_get_length()) ob_clean();
                SM_Settings::record_backup_download();
                $data = SM_DB::get_backup_data();
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="sm_backup_'.date('Y-m-d').'.json"');
                header('Pragma: no-cache');
                header('Expires: 0');
                echo $data;
                exit;
            }
        }

        // Handle Restore
        if (isset($_POST['sm_restore_backup']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام') && !empty($_FILES['backup_file']['tmp_name'])) {
                $json = file_get_contents($_FILES['backup_file']['tmp_name']);
                if (SM_DB::restore_backup($json)) {
                    SM_Settings::record_backup_import();
                    wp_redirect(add_query_arg('sm_admin_msg', 'restored', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }

        // Handle Academic Structure Save
        if (isset($_POST['sm_save_academic_structure']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $academic_data = array(
                    'term_dates' => $_POST['term_dates'],
                    'academic_stages' => $_POST['academic_stages'],
                    'grades_count' => intval($_POST['grades_count']),
                    'active_grades' => isset($_POST['active_grades']) ? array_map('intval', $_POST['active_grades']) : array(),
                    'grade_sections' => $_POST['grade_sections'] ?? array(),
                    'sections_count' => intval($_POST['sections_count']),
                    'section_letters' => sanitize_text_field($_POST['section_letters'])
                );
                SM_Settings::save_academic_structure($academic_data);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Unified Settings Save (School Info)
        if (isset($_POST['sm_save_settings_unified']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                SM_Settings::save_school_info(array(
                    'school_name' => sanitize_text_field($_POST['school_name']),
                    'school_principal_name' => sanitize_text_field($_POST['school_principal_name']),
                    'school_logo' => esc_url_raw($_POST['school_logo']),
                    'address' => sanitize_text_field($_POST['school_address']),
                    'email' => sanitize_email($_POST['school_email']),
                    'phone' => sanitize_text_field($_POST['school_phone']),
                    'working_schedule' => array(
                        'staff' => isset($_POST['work_staff']) ? array_map('sanitize_text_field', $_POST['work_staff']) : array(),
                        'students' => isset($_POST['work_students']) ? array_map('sanitize_text_field', $_POST['work_students']) : array()
                    )
                ));
                SM_Logger::log('تحديث بيانات السلطة', "تم تحديث بيانات المدرسة والمدير: {$_POST['school_name']}");
                SM_Settings::save_academic_structure(array(
                    'terms_count' => intval($_POST['terms_count']),
                    'grades_count' => intval($_POST['grades_count']),
                    'grade_options' => sanitize_text_field($_POST['grade_options']),
                    'semester_start' => sanitize_text_field($_POST['semester_start']),
                    'semester_end' => sanitize_text_field($_POST['semester_end']),
                    'academic_stages' => sanitize_text_field($_POST['academic_stages'])
                ));
                SM_Settings::save_retention_settings(array(
                    'message_retention_days' => intval($_POST['message_retention_days'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Appearance Settings Save
        if (isset($_POST['sm_save_appearance']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                SM_Settings::save_appearance(array(
                    'primary_color' => sanitize_hex_color($_POST['primary_color']),
                    'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                    'accent_color' => sanitize_hex_color($_POST['accent_color']),
                    'dark_color' => sanitize_hex_color($_POST['dark_color']),
                    'font_size' => sanitize_text_field($_POST['font_size']),
                    'border_radius' => sanitize_text_field($_POST['border_radius']),
                    'table_style' => sanitize_text_field($_POST['table_style']),
                    'button_style' => sanitize_text_field($_POST['button_style'])
                ));
                SM_Logger::log('تحديث تصميم النظام', "تم تغيير إعدادات الألوان والمظهر العام.");
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Violation Settings Save
        if (isset($_POST['sm_save_violation_settings']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                SM_Logger::log('تحديث إعدادات المخالفات', "تم تحديث أنواع المخالفات والإجراءات المقترحة.");
                $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
                $types = array();
                foreach ($types_raw as $line) {
                    $parts = explode("|", $line);
                    if (count($parts) == 2) {
                        $types[trim($parts[0])] = trim($parts[1]);
                    }
                }
                if (!empty($types)) {
                    SM_Settings::save_violation_types($types);
                }
                SM_Settings::save_suggested_actions(array(
                    'low' => sanitize_textarea_field($_POST['suggested_low']),
                    'medium' => sanitize_textarea_field($_POST['suggested_medium']),
                    'high' => sanitize_textarea_field($_POST['suggested_high'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Print Templates Save
        if (isset($_POST['sm_save_print_templates']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                update_option('sm_print_settings', array(
                    'header' => $_POST['print_header'], // Allowing HTML as requested for customization
                    'footer' => $_POST['print_footer'],
                    'custom_css' => $_POST['print_css']
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Sidebar Visibility Settings Save
        if (isset($_POST['sm_save_sidebar_visibility']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $roles_to_process = array('sm_system_admin', 'sm_principal', 'sm_supervisor', 'sm_coordinator', 'sm_teacher', 'sm_student', 'sm_parent');
                $sections_to_process = array('stats', 'students', 'teachers', 'parents', 'grades', 'teacher-reports', 'confiscated', 'printing', 'surveys', 'timetables', 'attendance', 'lesson-plans', 'assignments', 'clinic', 'messaging');

                $visibility = array();
                $input = isset($_POST['sidebar_visibility']) ? $_POST['sidebar_visibility'] : array();

                foreach ($roles_to_process as $role) {
                    $visibility[$role] = array();
                    foreach ($sections_to_process as $sec) {
                        // Explicitly save false if not checked to prevent falling back to defaults
                        $visibility[$role][$sec] = !empty($input[$role][$sec]);
                    }
                }

                SM_Settings::save_sidebar_visibility($visibility);
                SM_Logger::log('تحديث إعدادات ظهور القائمة', 'تم تخصيص الأقسام المرئية لكل رتبة في النظام.');
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Notifications Settings Save
        if (isset($_POST['sm_save_notif']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                SM_Settings::save_notifications(array(
                    'email_subject' => sanitize_text_field($_POST['email_subject']),
                    'email_template' => sanitize_textarea_field($_POST['email_template']),
                    'whatsapp_template' => sanitize_textarea_field($_POST['whatsapp_template']),
                    'internal_template' => sanitize_textarea_field($_POST['internal_template'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Full Reset
        if (isset($_POST['sm_full_reset']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                if ($_POST['reset_password'] === '1011996') {
                    SM_DB::delete_all_data();
                    wp_redirect(add_query_arg('sm_admin_msg', 'demo_deleted', $_SERVER['REQUEST_URI']));
                    exit;
                } else {
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }


        // Handle Teacher CSV Upload
        if (isset($_POST['sm_import_teachers_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_المعلمين') && !empty($_FILES['csv_file']['tmp_name'])) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 3) {
                        // username, email, name, teacher_id, job_title, phone, pass
                        $user_id = wp_insert_user(array(
                            'user_login' => $data[0],
                            'user_email' => $data[1],
                            'display_name' => $data[2],
                            'user_pass' => isset($data[6]) ? $data[6] : wp_generate_password(),
                            'role' => 'sm_teacher'
                        ));
                        if (!is_wp_error($user_id)) {
                            $count++;
                            update_user_meta($user_id, 'sm_teacher_id', isset($data[3]) ? $data[3] : '');
                            update_user_meta($user_id, 'sm_job_title', isset($data[4]) ? $data[4] : '');
                            update_user_meta($user_id, 'sm_phone', isset($data[5]) ? $data[5] : '');
                        }
                    }
                }
                fclose($handle);
                SM_Logger::log('استيراد معلمين (جماعي)', "تم استيراد ($count) معلم بنجاح.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Violation CSV Upload
        if (isset($_POST['sm_import_violations_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_المخالفات')) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 4) {
                        // code, type, severity, details, action, reward
                        $student = SM_DB::get_student_by_code($data[0]);
                        if ($student) {
                            $rid = SM_DB::add_record(array(
                                'student_id' => $student->id,
                                'type' => $data[1],
                                'severity' => $data[2],
                                'details' => $data[3],
                                'action_taken' => isset($data[4]) ? $data[4] : '',
                                'reward_penalty' => isset($data[5]) ? $data[5] : ''
                            ), true); // Skip individual logs
                            if ($rid) {
                                $count++;
                                SM_Notifications::send_violation_alert($rid);
                            }
                        }
                    }
                }
                fclose($handle);
                SM_Logger::log('استيراد مخالفات (جماعي)', "تم استيراد ($count) مخالفة بنجاح.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }
    }

    public function ajax_upload_import_csv() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        if (empty($_FILES['csv_file']['tmp_name'])) wp_send_json_error('No file uploaded');

        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sm_temp';
        if (!file_exists($temp_dir)) wp_mkdir_p($temp_dir);

        $file_name = 'import_' . get_current_user_id() . '_' . time() . '.csv';
        $file_path = $temp_dir . '/' . $file_name;

        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $file_path)) {
            // Count rows
            $handle = fopen($file_path, "r");
            $total_rows = 0;
            while (fgetcsv($handle) !== FALSE) {
                $total_rows++;
            }
            fclose($handle);

            // Initialize Results Transient
            $results = array(
                'total'     => $total_rows - 1, // minus header
                'success'   => 0,
                'warning'   => 0,
                'error'     => 0,
                'duplicate' => 0,
                'generated' => 0, // Count of auto-generated IDs
                'details'   => array()
            );
            set_transient('sm_import_results_' . get_current_user_id(), $results, HOUR_IN_SECONDS);

            wp_send_json_success(array(
                'file_path' => $file_path,
                'total'     => $total_rows - 1
            ));
        } else {
            wp_send_json_error('Failed to move uploaded file');
        }
    }

    public function ajax_process_import_chunk() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        $file_path = sanitize_text_field($_POST['file_path']);
        $offset = intval($_POST['offset']);
        $chunk_size = 20;

        if (!file_exists($file_path)) wp_send_json_error('Temp file not found');

        $results = get_transient('sm_import_results_' . get_current_user_id());
        if (!$results) wp_send_json_error('Session expired');

        $handle = fopen($file_path, "r");

        // Detect delimiter
        $first_line = fgets($handle);
        rewind($handle);
        $delimiters = [',', ';', "\t", '|'];
        $delimiter = ',';
        $max_count = -1;
        foreach ($delimiters as $d) {
            $count = substr_count($first_line, $d);
            if ($count > $max_count) {
                $max_count = $count;
                $delimiter = $d;
            }
        }

        // Skip header and seek to offset
        fgetcsv($handle, 0, $delimiter);
        for ($i = 0; $i < $offset; $i++) {
            fgetcsv($handle, 0, $delimiter);
        }

        $processed = 0;
        $next_sort_order = SM_DB::get_next_sort_order();
        $academic = SM_Settings::get_academic_structure();

        while ($processed < $chunk_size && ($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $processed++;
            $row_index = $offset + $processed + 1;

            $errors = array();
            $warnings = array();

            // Encoding
            foreach ($data as $k => $v) {
                $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                if ($encoding && $encoding != 'UTF-8') {
                    $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                }
            }

            // Mapping: A:Name, B:Grade, C:Section, D:Nationality, E:Email, F:Phone, G:NationalID
            $name         = isset($data[0]) ? trim($data[0]) : '';
            $class_name   = isset($data[1]) ? trim($data[1]) : '';
            $section      = isset($data[2]) ? trim($data[2]) : '';
            $nationality  = isset($data[3]) ? trim($data[3]) : '';
            $email        = isset($data[4]) ? trim($data[4]) : '';
            $phone        = isset($data[5]) ? trim($data[5]) : '';
            $national_id  = isset($data[6]) ? trim($data[6]) : null;

            if (empty($name)) {
                $errors[] = "الاسم الكامل مفقود في السطر $row_index";
            }
            if (empty($class_name)) {
                $errors[] = "الصف الدراسي مفقود في السطر $row_index";
            }

            if (!empty($errors)) {
                $results['error']++;
                foreach ($errors as $err) $results['details'][] = array('type' => 'error', 'msg' => $err);
            } else {
                // Normalize Grade
                $grade_number = preg_replace('/[^0-9]/', '', $class_name);
                if (!empty($grade_number)) {
                    $class_name = 'الصف ' . $grade_number;
                    $grade_val = (int)$grade_number;
                    if (!in_array($grade_val, $academic['active_grades'])) {
                        $warnings[] = "الصف ($grade_number) غير مفعل في الهيكل المعتمد.";
                    }
                }

                $existing_id = SM_DB::student_exists($name, $class_name, $section, $national_id);
                $extra = array(
                    'guardian_phone' => $phone,
                    'nationality' => $nationality,
                    'national_id' => $national_id
                );

                try {
                    if ($existing_id) {
                        $update_data = array(
                            'name' => $name,
                            'class_name' => $class_name,
                            'section' => $section,
                            'parent_email' => $email,
                            'guardian_phone' => $phone,
                            'nationality' => $nationality,
                            'national_id' => $national_id
                        );
                        // Force student_code to match national_id if provided
                        if (!empty($national_id)) {
                            $update_data['student_code'] = $national_id;
                        }

                        SM_DB::update_student($existing_id, $update_data);
                        $results['success']++;
                        $results['duplicate']++;
                        $results['details'][] = array('type' => 'info', 'msg' => "تم تحديث سجل ($name) في السطر $row_index");
                    } else {
                        $extra['sort_order'] = $next_sort_order++;
                        $final_id_to_use = !empty($national_id) ? $national_id : '';

                        $imported_id = SM_DB::add_student($name, $class_name, $email, $final_id_to_use, null, null, $section, $extra);
                        if ($imported_id) {
                            if (empty($national_id)) {
                                $results['generated']++;
                                SM_DB::update_student_meta($imported_id, 'sm_incomplete_identity', '1');
                            }
                            $results['success']++;
                            foreach ($warnings as $warn) $results['details'][] = array('type' => 'warning', 'msg' => $warn);
                        } else {
                            throw new Exception("فشل حفظ البيانات في قاعدة البيانات");
                        }
                    }
                } catch (Exception $e) {
                    $results['error']++;
                    $results['details'][] = array('type' => 'error', 'msg' => "خطأ في السطر $row_index: " . $e->getMessage());
                }
            }
        }

        fclose($handle);
        set_transient('sm_import_results_' . get_current_user_id(), $results, HOUR_IN_SECONDS);

        $is_finished = ($processed < $chunk_size);
        if ($is_finished) {
            unlink($file_path);
            SM_Logger::log('استيراد طلاب (AJAX)', "تم استيراد {$results['success']} طالب بنجاح.");
        }

        wp_send_json_success(array(
            'processed' => $processed,
            'finished'  => $is_finished,
            'total_so_far' => $offset + $processed
        ));
    }
}
