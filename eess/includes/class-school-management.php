<?php

class School_Management {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'school-management';
        $this->version = SM_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SM_PLUGIN_DIR . 'includes/class-sm-loader.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-db.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-settings.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-logger.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-notifications.php';
        require_once SM_PLUGIN_DIR . 'admin/class-sm-admin.php';
        require_once SM_PLUGIN_DIR . 'public/class-sm-public.php';
        $this->loader = new SM_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new SM_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new SM_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('init', 'SM_DB', 'cleanup_old_messages');
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('admin_init', $plugin_public, 'restrict_admin_access');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_login_failed', $plugin_public, 'login_failed');
        $this->loader->add_action('wp_login', $plugin_public, 'log_successful_login', 10, 2);
        $this->loader->add_action('wp_ajax_sm_get_student', $plugin_public, 'ajax_get_student');
        $this->loader->add_action('wp_ajax_sm_search_students', $plugin_public, 'ajax_search_students');
        $this->loader->add_action('wp_ajax_sm_get_student_intelligence', $plugin_public, 'ajax_get_student_intelligence');
        $this->loader->add_action('wp_ajax_sm_refresh_dashboard', $plugin_public, 'ajax_refresh_dashboard');
        $this->loader->add_action('wp_ajax_sm_save_record_ajax', $plugin_public, 'ajax_save_record');
        $this->loader->add_action('wp_ajax_sm_update_student_photo', $plugin_public, 'ajax_update_student_photo');
        $this->loader->add_action('wp_ajax_sm_send_message_ajax', $plugin_public, 'ajax_send_message');
        $this->loader->add_action('wp_ajax_sm_get_conversation_ajax', $plugin_public, 'ajax_get_conversation');
        $this->loader->add_action('wp_ajax_sm_mark_read', $plugin_public, 'ajax_mark_read');
        $this->loader->add_action('wp_ajax_sm_update_record_status', $plugin_public, 'ajax_update_record_status');
        $this->loader->add_action('wp_ajax_sm_send_group_message_ajax', $plugin_public, 'ajax_send_group_message');
        $this->loader->add_action('wp_ajax_sm_print', $plugin_public, 'handle_print');
        $this->loader->add_action('wp_ajax_sm_add_student_ajax', $plugin_public, 'ajax_add_student');
        $this->loader->add_action('wp_ajax_sm_update_student_ajax', $plugin_public, 'ajax_update_student');
        $this->loader->add_action('wp_ajax_sm_delete_student_ajax', $plugin_public, 'ajax_delete_student');
        $this->loader->add_action('wp_ajax_sm_add_confiscated_ajax', $plugin_public, 'ajax_add_confiscated');
        $this->loader->add_action('wp_ajax_sm_update_confiscated_ajax', $plugin_public, 'ajax_update_confiscated');
        $this->loader->add_action('wp_ajax_sm_delete_confiscated_ajax', $plugin_public, 'ajax_delete_confiscated');
        $this->loader->add_action('wp_ajax_sm_delete_record_ajax', $plugin_public, 'ajax_delete_record');
        $this->loader->add_action('wp_ajax_sm_get_counts_ajax', $plugin_public, 'ajax_get_counts');
        $this->loader->add_action('wp_ajax_sm_add_user_ajax', $plugin_public, 'ajax_add_user');
        $this->loader->add_action('wp_ajax_sm_update_generic_user_ajax', $plugin_public, 'ajax_update_generic_user');
        $this->loader->add_action('wp_ajax_sm_add_teacher_ajax', $plugin_public, 'ajax_add_teacher');
        $this->loader->add_action('wp_ajax_sm_update_teacher_ajax', $plugin_public, 'ajax_update_teacher');
        $this->loader->add_action('wp_ajax_sm_add_parent_ajax', $plugin_public, 'ajax_add_parent');
        $this->loader->add_action('wp_ajax_sm_update_profile_ajax', $plugin_public, 'ajax_update_profile');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_ajax', $plugin_public, 'ajax_bulk_delete');
        $this->loader->add_action('wp_ajax_sm_initialize_system_ajax', $plugin_public, 'ajax_initialize_system');
        $this->loader->add_action('wp_ajax_sm_rollback_log_ajax', $plugin_public, 'ajax_rollback_log');
        $this->loader->add_action('wp_ajax_sm_delete_log_ajax', $plugin_public, 'ajax_delete_log');
        $this->loader->add_action('wp_ajax_sm_save_regulation_settings_ajax', $plugin_public, 'ajax_save_regulation_settings');
        $this->loader->add_action('wp_ajax_sm_save_hierarchical_violations_ajax', $plugin_public, 'ajax_save_hierarchical_violations');
        $this->loader->add_action('wp_ajax_sm_delete_all_logs_ajax', $plugin_public, 'ajax_delete_all_logs');
        $this->loader->add_action('wp_ajax_sm_get_students_attendance_ajax', $plugin_public, 'ajax_get_students_attendance');
        $this->loader->add_action('wp_ajax_nopriv_sm_get_students_attendance_ajax', $plugin_public, 'ajax_get_students_attendance');
        $this->loader->add_action('wp_ajax_sm_save_attendance_ajax', $plugin_public, 'ajax_save_attendance');
        $this->loader->add_action('wp_ajax_nopriv_sm_save_attendance_ajax', $plugin_public, 'ajax_save_attendance');
        $this->loader->add_action('wp_ajax_sm_save_attendance_batch_ajax', $plugin_public, 'ajax_save_attendance_batch');
        $this->loader->add_action('wp_ajax_nopriv_sm_save_attendance_batch_ajax', $plugin_public, 'ajax_save_attendance_batch');
        $this->loader->add_action('wp_ajax_sm_upload_import_csv', $plugin_public, 'ajax_upload_import_csv');
        $this->loader->add_action('wp_ajax_sm_process_import_chunk', $plugin_public, 'ajax_process_import_chunk');
        $this->loader->add_action('wp_ajax_sm_reset_class_code_ajax', $plugin_public, 'ajax_reset_class_code');
        $this->loader->add_action('wp_ajax_sm_toggle_attendance_status_ajax', $plugin_public, 'ajax_toggle_attendance_status');
        $this->loader->add_action('wp_ajax_sm_filter_violations', $plugin_public, 'ajax_filter_violations');
        $this->loader->add_action('wp_ajax_sm_mark_contacted', $plugin_public, 'ajax_mark_contacted');
        $this->loader->add_action('wp_ajax_sm_add_document_ajax', $plugin_public, 'ajax_add_document');
        $this->loader->add_action('wp_ajax_sm_update_document_ajax', $plugin_public, 'ajax_update_document');
        $this->loader->add_action('wp_ajax_sm_delete_document_ajax', $plugin_public, 'ajax_delete_document');
        $this->loader->add_action('wp_ajax_sm_add_assignment_ajax', $plugin_public, 'ajax_add_assignment');
        $this->loader->add_action('wp_ajax_sm_approve_plan_ajax', $plugin_public, 'ajax_approve_plan');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_users_ajax', $plugin_public, 'ajax_bulk_delete_users');
        $this->loader->add_action('wp_ajax_sm_add_clinic_referral', $plugin_public, 'ajax_add_clinic_referral');
        $this->loader->add_action('wp_ajax_sm_confirm_clinic_arrival', $plugin_public, 'ajax_confirm_clinic_arrival');
        $this->loader->add_action('wp_ajax_sm_update_clinic_record', $plugin_public, 'ajax_update_clinic_record');
        $this->loader->add_action('wp_ajax_sm_get_clinic_reports', $plugin_public, 'ajax_get_clinic_reports');
        $this->loader->add_action('wp_ajax_sm_export_violations_csv', $plugin_public, 'ajax_export_violations_csv');
        $this->loader->add_action('wp_ajax_sm_save_grade_ajax', $plugin_public, 'ajax_save_grade_ajax');
        $this->loader->add_action('wp_ajax_sm_get_student_grades_ajax', $plugin_public, 'ajax_get_student_grades_ajax');
        $this->loader->add_action('wp_ajax_sm_delete_grade_ajax', $plugin_public, 'ajax_delete_grade_ajax');
        $this->loader->add_action('wp_ajax_sm_add_subject', $plugin_public, 'ajax_add_subject');
        $this->loader->add_action('wp_ajax_sm_delete_subject', $plugin_public, 'ajax_delete_subject');
        $this->loader->add_action('wp_ajax_sm_get_subjects', $plugin_public, 'ajax_get_subjects');
        $this->loader->add_action('wp_ajax_sm_save_class_grades', $plugin_public, 'ajax_save_class_grades');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_students_ajax', $plugin_public, 'ajax_bulk_delete_students');
        $this->loader->add_action('wp_ajax_sm_add_survey', $plugin_public, 'ajax_add_survey');
        $this->loader->add_action('wp_ajax_sm_cancel_survey', $plugin_public, 'ajax_cancel_survey');
        $this->loader->add_action('wp_ajax_sm_submit_survey_response', $plugin_public, 'ajax_submit_survey_response');
        $this->loader->add_action('wp_ajax_sm_get_survey_results', $plugin_public, 'ajax_get_survey_results');
        $this->loader->add_action('wp_ajax_sm_export_survey_results', $plugin_public, 'ajax_export_survey_results');
        $this->loader->add_action('wp_ajax_sm_update_timetable_entry', $plugin_public, 'ajax_update_timetable_entry');
        $this->loader->add_action('wp_ajax_sm_save_timetable_settings', $plugin_public, 'ajax_save_timetable_settings');
        $this->loader->add_action('wp_ajax_sm_download_plans_zip', $plugin_public, 'ajax_download_plans_zip');
    }

    public function run() {
        $this->check_version_updates();
        $this->loader->run();
    }

    private function check_version_updates() {
        $db_version = get_option('sm_plugin_version', '1.0.0');
        if (version_compare($db_version, SM_VERSION, '<')) {
            require_once SM_PLUGIN_DIR . 'includes/class-sm-activator.php';
            SM_Activator::activate(); // Run full activation logic including dbDelta
            update_option('sm_plugin_version', SM_VERSION);
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
