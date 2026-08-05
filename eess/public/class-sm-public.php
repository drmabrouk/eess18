<?php

class SM_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function hide_admin_bar_for_non_admins($show) {
        $user = wp_get_current_user();
        if (in_array('sm_system_admin', (array)$user->roles) || in_array('administrator', (array)$user->roles)) {
            return $show;
        }
        return false;
    }

    public function custom_user_avatar($avatar, $id_or_email, $args) {
        $user_id = 0;
        if (is_numeric($id_or_email)) {
            $user_id = (int)$id_or_email;
        } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
            $user_id = (int)$id_or_email->user_id;
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if ($user) $user_id = $user->ID;
        }

        if ($user_id) {
            $custom_avatar = get_user_meta($user_id, 'eess_profile_photo', true);
            if ($custom_avatar) {
                $class = isset($args['class']) ? implode(' ', (array)$args['class']) : '';
                $style = isset($args['style']) ? $args['style'] : '';
                $avatar = sprintf(
                    "<img src='%s' class='%s' style='%s' width='%d' height='%d' />",
                    esc_url($custom_avatar),
                    esc_attr($class),
                    esc_attr($style),
                    (int)$args['width'],
                    (int)$args['height']
                );
            }
        }
        return $avatar;
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

        if (is_admin() && !defined('DOING_AJAX')) {
            $user = wp_get_current_user();
            if (!in_array('sm_system_admin', (array)$user->roles) && !in_array('administrator', (array)$user->roles)) {
                wp_redirect(home_url('/sm-admin'));
                exit;
            }
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-cairo', 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap', array(), null);
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
                font-family: 'Cairo', 'Noto Kufi Arabic', sans-serif !important;
            }
            .sm-admin-dashboard { font-size: calc({$appearance['font_size']} * 0.93); }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        if (isset($_GET['sm_action']) && $_GET['sm_action'] === 'logout') {
            wp_logout();
            wp_redirect(home_url('/sm-login'));
            exit;
        }

        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sm_class_attendance', array($this, 'shortcode_class_attendance'));
        add_shortcode('sm_lesson_prep', array($this, 'shortcode_lesson_prep'));
    }

    public function shortcode_lesson_prep() {
        if (!is_user_logged_in()) {
            wp_redirect(add_query_arg('redirect_to', home_url('/lesson-prep'), home_url('/sm-login')));
            exit;
        }

        // Runtime DB table presence check and automatic creation
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}sm_lesson_preps'");
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$wpdb->prefix}sm_lesson_preps (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                teacher_id bigint(20) NOT NULL,
                supervisor_id bigint(20) NOT NULL,
                title varchar(255) NOT NULL,
                subject varchar(100) NOT NULL,
                grade_level varchar(50) NOT NULL,
                class_section varchar(50) NOT NULL,
                lesson_date date NOT NULL,
                submission_time datetime DEFAULT NULL,
                status varchar(50) DEFAULT 'draft' NOT NULL,
                delay_seconds int(11) DEFAULT 0 NOT NULL,
                lesson_data longtext,
                version int(11) DEFAULT 1 NOT NULL,
                parent_id bigint(20) DEFAULT 0 NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY teacher_id (teacher_id),
                KEY status (status)
            ) $charset_collate;

            CREATE TABLE {$wpdb->prefix}sm_lesson_comments (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                prep_id bigint(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                comment_text text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY prep_id (prep_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);

        ob_start();
        include SM_PLUGIN_DIR . 'templates/admin-lesson-prep.php';
        return ob_get_clean();
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }

        $output = '
        <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap\');

        .eess-login-page-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 99999;
            overflow-y: auto;
            background: #ffffff;
            font-family: \'Cairo\', \'Noto Kufi Arabic\', sans-serif !important;
            direction: rtl;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .eess-login-split-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Left Panel (Branding) */
        .eess-login-left-panel {
            width: 50%;
            background-color: #0d0d0d;
            background-image: linear-gradient(135deg, #0d0d0d 0%, #16161a 100%);
            color: #ffffff;
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            direction: ltr !important;
            text-align: left !important;
        }

        /* Modal Overlays and dialogs */
        .eess-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .eess-modal-dialog {
            background: #ffffff;
            border-radius: 12px;
            max-width: 520px;
            width: 100%;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: eessFadeIn 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-align: right;
            box-sizing: border-box;
        }

        @keyframes eessFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .eess-modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .eess-modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        .eess-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            margin: 0;
            transition: color 0.15s ease;
        }

        .eess-modal-close:hover {
            color: #475569;
        }

        .eess-modal-body {
            padding: 24px;
            box-sizing: border-box;
        }

        /* Steps progress indicator */
        .eess-step-progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            position: relative;
        }

        .eess-step-progress-bar::before {
            content: \'\';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }

        .eess-step-node {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: #64748b;
            z-index: 2;
            transition: all 0.25s ease;
        }

        .eess-step-node.active {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
        }

        .eess-step-node.completed {
            border-color: #10b981;
            background: #10b981;
            color: #ffffff;
        }

        .eess-wizard-step {
            display: none;
        }

        .eess-wizard-step.active {
            display: block;
        }

        .eess-modal-msg {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: none;
        }

        .eess-modal-msg.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .eess-modal-msg.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        /* Official Website Badge */
        .eess-official-badge-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .eess-official-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 6px 14px;
            color: #e2e8f0;
            text-decoration: none !important;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .eess-official-badge:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .eess-official-badge .badge-icon-globe {
            margin-left: 8px;
            font-size: 0.9rem;
            color: #ef4444;
        }
        .eess-official-badge .badge-text-main {
            opacity: 0.8;
            margin-left: 5px;
        }
        .eess-official-badge .badge-text-domain {
            font-weight: bold;
            color: #ffffff;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.4);
            margin-left: 5px;
        }

        /* Branding Logo */
        .eess-branding-header {
            margin-top: 10px;
            position: relative;
            z-index: 2;
        }
        .eess-branding-logo-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
        }
        .eess-logo-text-col {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        .eess-logo-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 1px;
            line-height: 1;
            color: #ffffff;
            font-family: sans-serif;
        }
        .eess-logo-subtitle {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
            font-family: sans-serif;
            color: #cbd5e1;
        }
        .eess-logo-icon-col {
            background-color: #8b1e1e;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mortarboard-svg {
            width: 26px;
            height: 26px;
            fill: #ffffff;
        }
        .eess-branding-divider {
            height: 4px;
            width: 60px;
            background-color: #8b1e1e;
            margin-top: 15px;
            margin-left: 0 !important;
            margin-right: auto !important;
        }

        /* Main Headline */
        .eess-main-headline {
            font-size: 2.3rem;
            font-weight: 800;
            line-height: 1.45;
            margin: 40px 0;
            z-index: 2;
            color: #ffffff;
        }
        .underline-red {
            border-bottom: 3px solid #8b1e1e;
            padding-bottom: 2px;
        }

        /* About Box */
        .eess-about-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px 24px;
            margin-top: auto;
            position: relative;
            z-index: 2;
        }
        .eess-about-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .eess-about-desc {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #cbd5e1;
            margin: 0;
        }
        .eess-about-desc a {
            color: #ffffff;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Giant Watermark */
        .eess-watermark {
            position: absolute;
            bottom: -30px;
            left: -20px;
            font-size: 11rem;
            font-weight: 900;
            font-family: sans-serif;
            color: #ffffff;
            opacity: 0.03;
            pointer-events: none;
            line-height: 1;
            user-select: none;
        }

        /* Right Panel (Form) */
        .eess-login-right-panel {
            width: 50%;
            background: #ffffff;
            padding: 40px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-sizing: border-box;
        }
        .eess-login-form-inner {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }

        .eess-login-form-title {
            font-size: 1.9rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .eess-login-form-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0 0 25px 0;
            font-weight: 400;
        }

        /* Compact Forms spacing as per prompt request */
        .eess-form-group {
            margin-bottom: 12px;
        }
        .eess-field-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }
        .eess-form-input {
            width: 100%;
            height: 42px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0 14px;
            font-size: 0.9rem;
            color: #0f172a;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .eess-form-input:focus {
            outline: none;
            border-color: #334155;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(51, 65, 85, 0.1);
        }

        .eess-lost-pwd-link {
            font-size: 0.8rem;
            font-weight: 700;
            color: #8b1e1e !important;
            text-decoration: underline !important;
        }
        .eess-lost-pwd-link:hover {
            color: #b91c1c !important;
        }

        /* Remember me styling */
        .eess-form-row-remember {
            margin: 10px 0 15px 0;
        }
        .eess-remember-checkbox-label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .eess-remember-checkbox-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .eess-checkbox-custom {
            height: 18px;
            width: 18px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-left: 8px;
            position: relative;
            transition: all 0.15s ease;
        }
        .eess-remember-checkbox-label:hover input ~ .eess-checkbox-custom {
            border-color: #94a3b8;
        }
        .eess-remember-checkbox-label input:checked ~ .eess-checkbox-custom {
            background-color: #000000;
            border-color: #000000;
        }
        .eess-checkbox-custom:after {
            content: "";
            position: absolute;
            display: none;
        }
        .eess-remember-checkbox-label input:checked ~ .eess-checkbox-custom:after {
            display: block;
        }
        .eess-remember-checkbox-label .eess-checkbox-custom:after {
            left: 6px;
            top: 2px;
            width: 4px;
            height: 9px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .eess-checkbox-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }

        /* Main Login Button (Dark Black background) */
        .eess-btn-login {
            width: 100%;
            height: 44px;
            background-color: #000000 !important;
            color: #ffffff !important;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .eess-btn-login:hover {
            background-color: #1e1e1e !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.15);
        }

        /* Password Reset Card */
        .eess-reset-pwd-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            box-sizing: border-box;
        }
        .eess-reset-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .eess-reset-card-icon {
            font-size: 1rem;
            color: #8b1e1e;
        }
        .eess-reset-card-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }
        .eess-reset-card-desc {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0 0 12px 0;
        }
        /* Reset Password button - Dark Red background */
        .eess-btn-reset-pwd {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 38px;
            background-color: #8b1e1e !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(139, 30, 30, 0.1);
        }
        .eess-btn-reset-pwd:hover {
            background-color: #a82525 !important;
            transform: translateY(-1px);
        }

        /* Error notice */
        .eess-error-notice {
            background: #fff5f5;
            color: #c53030;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #feb2b2;
            margin-bottom: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Footer elements */
        .eess-login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .eess-footer-left {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .eess-footer-left .lock-icon {
            font-size: 0.85rem;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .eess-login-left-panel {
                width: 40%;
                padding: 30px 40px;
            }
            .eess-login-right-panel {
                width: 60%;
                padding: 30px 50px;
            }
            .eess-main-headline {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .eess-login-split-layout {
                flex-direction: column;
            }
            .eess-login-left-panel {
                width: 100%;
                min-height: auto;
                padding: 30px 24px;
            }
            .eess-login-right-panel {
                width: 100%;
                min-height: auto;
                padding: 40px 24px;
            }
            .eess-main-headline {
                font-size: 1.6rem;
                margin: 20px 0;
            }
            .eess-about-box {
                margin-top: 20px;
            }
            .eess-watermark {
                display: none;
            }
        }
        </style>

        <div class="eess-login-page-container">
            <div class="eess-login-split-layout">
                <!-- Right Side (Form - Light) -->
                <div class="eess-login-right-panel">
                    <div class="eess-login-form-inner">
                        <!-- Title & Subtitle -->
                        <h1 class="eess-login-form-title">تسجيل الدخول</h1>
                        <p class="eess-login-form-subtitle">أدخل بيانات الاعتماد الخاصة بك للوصول إلى لوحة التحكم.</p>

                        <!-- Error notice if failed -->
                        ';
                        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                            $output .= '<div class="eess-error-notice">خطأ في اسم المستخدم أو كلمة المرور. يرجى التحقق وإعادة المحاولة.</div>';
                        }
                        $output .= '

                        <!-- Custom login form -->
                        <form name="loginform" id="sm_login_form" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post">
                            <!-- Email / Acad ID field -->
                            <div class="eess-form-group">
                                <label for="user_login" class="eess-field-label">البريد الإلكتروني / الرقم الأكاديمي <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="log" id="user_login" class="eess-form-input" placeholder="name@company.com" required>
                            </div>

                            <!-- Password field with lost-password link inline -->
                            <div class="eess-form-group">
                                <div class="eess-field-label-wrapper" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <label for="user_pass" class="eess-field-label" style="margin: 0;">كلمة المرور <span style="color:#ef4444;">*</span></label>
                                    <a href="' . esc_url(wp_lostpassword_url(home_url('/sm-login'))) . '" class="eess-lost-pwd-link">نسيت كلمة المرور؟</a>
                                </div>
                                <input type="password" name="pwd" id="user_pass" class="eess-form-input" placeholder="••••••" required style="margin-top: 5px;">
                            </div>

                            <!-- Remember me row -->
                            <div class="eess-form-row-remember">
                                <label class="eess-remember-checkbox-label">
                                    <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                                    <span class="eess-checkbox-custom"></span>
                                    <span class="eess-checkbox-text">تذكر بياناتي على هذا الجهاز</span>
                                </label>
                            </div>

                            <!-- Login Submit Button -->
                            <div class="eess-form-group" style="margin-top: 20px;">
                                <button type="submit" name="wp-submit" id="wp-submit" class="eess-btn-login">
                                    <span>دخول النظام</span>
                                    <span style="margin-right: 8px;">[→</span>
                                </button>
                            </div>

                            <input type="hidden" name="redirect_to" value="' . esc_url(isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url('/sm-admin')) . '">
                        </form>

                        <!-- Unified Helper Services Card -->
                        <div class="eess-reset-pwd-card" style="margin-top: 15px; border-color: #cbd5e1;">
                            <div class="eess-reset-card-header" style="margin-bottom: 10px;">
                                <span class="eess-reset-card-icon">⚙️</span>
                                <span class="eess-reset-card-title">إدارة الحساب والخدمات المساندة</span>
                            </div>
                            <p class="eess-reset-card-desc" style="margin-bottom: 12px; font-size: 12px; color: #64748b;">أختر إحدى الخدمات التالية لاستعادة كلمة المرور أو البدء في تسجيل حساب جديد بالمنصة:</p>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" onclick="eessOpenForgotModal()" class="eess-btn-reset-pwd" style="flex: 1; font-size: 11px; height: 36px; background-color: #8b1e1e !important;">
                                    🔑 استعادة كلمة المرور
                                </button>
                                <button type="button" onclick="eessOpenRegisterModal()" class="eess-btn-reset-pwd" style="flex: 1; font-size: 11px; height: 36px; background-color: #000000 !important;">
                                    👤 تسجيل حساب جديد
                                </button>
                            </div>
                        </div>

                        <!-- Footer under right panel -->
                        <div class="eess-login-footer">
                            <div class="eess-footer-left">
                                <span class="lock-icon">🔒</span>
                                <span>دخول مشفر bit-256</span>
                                <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                                <a href="javascript:void(0)" onclick="eessOpenSupportModal()" style="color: #8b1e1e !important; text-decoration: underline !important; font-weight: bold; cursor: pointer;">المساعدة والدعم الفني</a>
                            </div>
                            <div class="eess-footer-right">
                                <span>© 2026 EESS. جميع الحقوق محفوظة</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Side (Branding - Dark) -->
                <div class="eess-login-left-panel">
                    <!-- Official website link badge -->
                    <div class="eess-official-badge-container">
                        <a href="https://eess.online" target="_blank" class="eess-official-badge">
                            <span class="badge-icon-globe">
                                <svg style="width: 14px; height: 14px; fill: currentColor; margin-left: 6px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.53c-.26-.81-1-1.4-1.9-1.4h-1v-3c0-.55-.45-1-1-1h-6v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.4z"/>
                                </svg>
                            </span>
                            <span class="badge-text-main">الموقع الرسمي للنظام:</span>
                            <span class="badge-text-domain">eess.online</span>
                            <span class="badge-icon-link">
                                <svg style="width: 12px; height: 12px; fill: currentColor; margin-right: 6px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                </svg>
                            </span>
                        </a>
                    </div>

                    <!-- Branding logo/icon/text -->
                    <div class="eess-branding-header">
                        <div class="eess-branding-logo-box">
                            <div class="eess-logo-text-col">
                                <span class="eess-logo-title">EESS</span>
                                <span class="eess-logo-subtitle">Educational Electronic Systems Services</span>
                            </div>
                            <div class="eess-logo-icon-col">
                                <svg class="mortarboard-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                    <path d="M5 13.18v4c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-4l-7 3.82-7-3.82z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="eess-branding-divider"></div>
                    </div>

                    <!-- Big Title -->
                    <div class="eess-main-headline">
                        منظومة الخدمات <span class="underline-red">التعليمية</span><br>وإدارة الأنظمة <span class="underline-red">الإلكترونية</span>
                    </div>

                    <!-- About EESS box -->
                    <div class="eess-about-box">
                        <div class="eess-about-title">نبذة عن نظام EESS:</div>
                        <p class="eess-about-desc">
                            منظومة EESS هي البوابة الإلكترونية الموحدة لإدارة المناهج والخدمات التعليمية والأكاديمية، تهدف إلى توفير بيئة رقمية آمنة وموثوقة للوصول المباشر لكافة الأنظمة والأدوات المتاحة عبر الموقع الرسمي <a href="https://eess.online" target="_blank">eess.online</a>.
                        </p>
                    </div>

                    <!-- Huge Watermark EESS -->
                    <div class="eess-watermark">EESS</div>
                </div>
            </div>
        </div>

        <!-- Forgot Password Modal Overhaul -->
        <div id="eess-forgot-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog">
                <div class="eess-modal-header">
                    <h3>استعادة كلمة المرور الآمنة</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseForgotModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <div id="eess-forgot-msg" class="eess-modal-msg"></div>

                    <!-- Step 1: Enter Email -->
                    <div id="eess-forgot-step-1" class="eess-wizard-step active">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">يرجى كتابة بريدك الإلكتروني المسجل في النظام وسوف نرسل لك رمز تحقق آمن OTP مكون من 6 أرقام لتأكيد الهوية وتحديث كلمة المرور.</p>
                        <div class="eess-form-group">
                            <label class="eess-field-label">البريد الإلكتروني المعتمد <span style="color:#ef4444;">*</span></label>
                            <input type="email" id="eess-forgot-email" class="eess-form-input" placeholder="name@company.com">
                        </div>
                        <button type="button" onclick="eessSendForgotOTP()" class="eess-btn-login" style="margin-top: 15px;">إرسال رمز التحقق OTP</button>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div id="eess-forgot-step-2" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">تم إرسال رمز التحقق بنجاح. يرجى مراجعة بريدك الإلكتروني وإدخال الرمز المكون من 6 أرقام بالأسفل لتأكيد هويتك.</p>
                        <div class="eess-form-group">
                            <label class="eess-field-label">رمز التحقق OTP <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="eess-forgot-otp" class="eess-form-input" placeholder="••••••" maxlength="6" style="text-align: center; letter-spacing: 6px; font-size: 20px;">
                        </div>
                        <button type="button" onclick="eessVerifyForgotOTP()" class="eess-btn-login" style="margin-top: 15px;">تحقق وتأكيد الهوية</button>
                    </div>

                    <!-- Step 3: Enter New Password -->
                    <div id="eess-forgot-step-3" class="eess-wizard-step">
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h4 id="eess-forgot-welcome-msg" style="margin: 0; color: #000000; font-weight: 800; font-size: 14px;">أهلاً بك!</h4>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.6;">تم تأكيد هويتك بنجاح. يرجى إدخال كلمة المرور الجديدة وتأكيدها لحفظ التغييرات والدخول المباشر للمنصة.</p>
                        </div>
                        <div class="eess-form-group">
                            <label class="eess-field-label">كلمة المرور الجديدة <span style="color:#ef4444;">*</span></label>
                            <input type="password" id="eess-forgot-pass" class="eess-form-input" placeholder="••••••">
                        </div>
                        <div class="eess-form-group" style="margin-top: 10px;">
                            <label class="eess-field-label">تأكيد كلمة المرور <span style="color:#ef4444;">*</span></label>
                            <input type="password" id="eess-forgot-pass-conf" class="eess-form-input" placeholder="••••••">
                        </div>
                        <button type="button" onclick="eessResetPassword()" class="eess-btn-login" style="margin-top: 20px;">تحديث كلمة المرور والدخول للمنصة</button>
                    </div>

                    <!-- Support Card Inside Modal -->
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; line-height: 1.6;">
                        💬 إذا واجهت أي صعوبة في الدخول أو استعادة حسابك، يرجى الاتصال بقسم الدعم الفني لشركة EESS عبر البريد الرسمي <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; text-decoration: underline;">info@eess.online</a>.
                    </div>
                </div>
            </div>
        </div>

        <!-- Help & Support Modal -->
        <div id="eess-support-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 450px;">
                <div class="eess-modal-header">
                    <h3>المساعدة والدعم الفني</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseSupportModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin-top: 0;">إذا كنت تواجه أي صعوبة في الدخول إلى حسابك أو استعادة كلمة المرور، يرجى التكرم بمراسلة إدارة المنصة عبر البريد الإلكتروني الرسمي مباشرة:</p>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0;">
                        <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; font-size: 16px; text-decoration: none;">info@eess.online</a>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.6;">سوف يقوم مهندسو الدعم الفني بالرد عليك وحل المشكلة في أقرب وقت ممكن.</p>
                </div>
            </div>
        </div>

        <!-- Registration Wizard Modal -->
        <div id="eess-register-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog">
                <div class="eess-modal-header">
                    <h3>تسجيل حساب جديد - بوابة EESS</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseRegisterModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <!-- Step Indicator -->
                    <div class="eess-step-progress-bar">
                        <div class="eess-step-node active" id="node-1">1</div>
                        <div class="eess-step-node" id="node-2">2</div>
                        <div class="eess-step-node" id="node-3">3</div>
                        <div class="eess-step-node" id="node-4">4</div>
                    </div>

                    <div id="eess-register-msg" class="eess-modal-msg"></div>

                    <!-- Step 1: Email Address -->
                    <div id="eess-reg-step-1" class="eess-wizard-step active">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">يرجى البدء بإدخال بريدك الإلكتروني المعتمد بالمنصة للتحقق منه وإكمال بقية خطوات التسجيل.</p>
                        <div class="eess-form-group">
                            <label class="eess-field-label">البريد الإلكتروني الرسمي <span style="color:#ef4444;">*</span></label>
                            <input type="email" id="eess-reg-email" class="eess-form-input" placeholder="name@company.com">
                        </div>
                        <button type="button" onclick="eessRegisterStep1Next()" class="eess-btn-login" style="margin-top: 15px;">التالي (تأكيد البريد)</button>
                    </div>

                    <!-- Step 2: Employee ID and Password -->
                    <div id="eess-reg-step-2" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">الآن، يرجى كتابة الرقم الوظيفي / الأكاديمي الموحد الخاص بك، وتعيين كلمة مرور آمنة لحسابك.</p>
                        <div class="eess-form-group">
                            <label class="eess-field-label">الرقم الوظيفي / رقم الموظف <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="eess-reg-emp-num" class="eess-form-input" placeholder="مثال: EESS-10992">
                        </div>
                        <div class="eess-form-group" style="margin-top: 10px;">
                            <label class="eess-field-label">كلمة المرور <span style="color:#ef4444;">*</span></label>
                            <input type="password" id="eess-reg-pass" class="eess-form-input" placeholder="••••••">
                        </div>
                        <div class="eess-form-group" style="margin-top: 10px;">
                            <label class="eess-field-label">تأكيد كلمة المرور <span style="color:#ef4444;">*</span></label>
                            <input type="password" id="eess-reg-pass-conf" class="eess-form-input" placeholder="••••••">
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(1)" class="eess-btn-reset-pwd" style="width: 30%; background-color:#64748b !important;">السابق</button>
                            <button type="button" onclick="eessRegisterStep2Next()" class="eess-btn-login" style="width: 70%;">التالي (التحقق الأكاديمي)</button>
                        </div>
                    </div>

                    <!-- Step 3: Job Title & School -->
                    <div id="eess-reg-step-3" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">الرجاء تحديد المسمى الوظيفي الخاص بك والمدرسة أو الهيئة الأكاديمية التابع لها بالكامل.</p>
                        <div class="eess-form-group">
                            <label class="eess-field-label">المسمى الوظيفي / الرتبة <span style="color:#ef4444;">*</span></label>
                            <select id="eess-reg-role" class="eess-form-input" style="height: 42px; padding: 0 10px;">
                                <option value="sm_teacher">معلم</option>
                                <option value="sm_coordinator">منسق مادة</option>
                                <option value="sm_supervisor">مشرف تربوي</option>
                                <option value="sm_clinic">ممرض عيادة</option>
                            </select>
                        </div>
                        <div class="eess-form-group" style="margin-top: 10px;">
                            <label class="eess-field-label">المدرسة التابع لها <span style="color:#ef4444;">*</span></label>
                            <select id="eess-reg-school" class="eess-form-input" style="height: 42px; padding: 0 10px;">
                                ';
                                $school_info = SM_Settings::get_school_info();
                                $output .= '<option value="' . esc_attr($school_info['school_name']) . '">' . esc_html($school_info['school_name']) . '</option>';
                                $output .= '
                            </select>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(2)" class="eess-btn-reset-pwd" style="width: 30%; background-color:#64748b !important;">السابق</button>
                            <button type="button" onclick="eessRegisterStep3Next()" class="eess-btn-login" style="width: 70%;">إرسال رمز التفعيل OTP</button>
                        </div>
                    </div>

                    <!-- Step 4: OTP Verification & Final Submit -->
                    <div id="eess-reg-step-4" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">تم إرسال رمز تفعيل آمن OTP لبريدك الإلكتروني المكتوب. يرجى إدخال الرمز لتأكيد ملكية البريد وتأكيد طلب التسجيل بالمنصة.</p>
                        <div class="eess-form-group">
                            <label class="eess-field-label">رمز التفعيل OTP <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="eess-reg-otp" class="eess-form-input" placeholder="••••••" maxlength="6" style="text-align: center; letter-spacing: 6px; font-size: 20px;">
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(3)" class="eess-btn-reset-pwd" style="width: 30%; background-color:#64748b !important;">السابق</button>
                            <button type="button" onclick="eessRegisterSubmitFinal()" class="eess-btn-login" style="width: 70%;">تأكيد وإرسال طلب التسجيل</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom JS Client logic for Password Recovery and Registration -->
        <script>
        function eessShowForgotMsg(text, isError) {
            const el = document.getElementById(\'eess-forgot-msg\');
            el.innerText = text;
            el.style.display = \'block\';
            if (isError) {
                el.className = \'eess-modal-msg error\';
            } else {
                el.className = \'eess-modal-msg success\';
            }
        }

        function eessShowRegMsg(text, isError) {
            const el = document.getElementById(\'eess-register-msg\');
            el.innerText = text;
            el.style.display = \'block\';
            if (isError) {
                el.className = \'eess-modal-msg error\';
            } else {
                el.className = \'eess-modal-msg success\';
            }
        }

        // Modal triggers
        function eessOpenForgotModal() {
            document.getElementById(\'eess-forgot-modal\').style.display = \'flex\';
            eessGoToForgotStep(1);
        }
        function eessCloseForgotModal() {
            document.getElementById(\'eess-forgot-modal\').style.display = \'none\';
        }

        function eessOpenSupportModal() {
            document.getElementById(\'eess-support-modal\').style.display = \'flex\';
        }
        function eessCloseSupportModal() {
            document.getElementById(\'eess-support-modal\').style.display = \'none\';
        }

        function eessOpenRegisterModal() {
            document.getElementById(\'eess-register-modal\').style.display = \'flex\';
            eessGoToRegStep(1);
        }
        function eessCloseRegisterModal() {
            document.getElementById(\'eess-register-modal\').style.display = \'none\';
        }

        // Recovery navigation
        function eessGoToForgotStep(stepNum) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';
            for (let i = 1; i <= 3; i++) {
                document.getElementById(\'eess-forgot-step-\' + i).style.display = i === stepNum ? \'block\' : \'none\';
            }
        }

        // Send OTP
        function eessSendForgotOTP() {
            const email = document.getElementById(\'eess-forgot-email\').value;
            if (!email) {
                eessShowForgotMsg(\'يرجى إدخال البريد الإلكتروني.\', true);
                return;
            }
            eessShowForgotMsg(\'جاري إرسال رمز التحقق...\', false);

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_otp\');
            data.append(\'email\', email);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessGoToForgotStep(2);
                    eessShowForgotMsg(res.data, false);
                } else {
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Verify OTP
        function eessVerifyForgotOTP() {
            const email = document.getElementById(\'eess-forgot-email\').value;
            const otp = document.getElementById(\'eess-forgot-otp\').value;
            if (!otp) {
                eessShowForgotMsg(\'يرجى كتابة الرمز.\', true);
                return;
            }

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_verify\');
            data.append(\'email\', email);
            data.append(\'otp\', otp);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    document.getElementById(\'eess-forgot-welcome-msg\').innerText = \'أهلاً بك يا \' + res.data.display_name + \'!\';
                    eessGoToForgotStep(3);
                } else {
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Reset password
        function eessResetPassword() {
            const email = document.getElementById(\'eess-forgot-email\').value;
            const otp = document.getElementById(\'eess-forgot-otp\').value;
            const pass = document.getElementById(\'eess-forgot-pass\').value;
            const conf = document.getElementById(\'eess-forgot-pass-conf\').value;

            if (!pass || !conf) {
                eessShowForgotMsg(\'يرجى ملء كلمتي المرور.\', true);
                return;
            }

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_reset\');
            data.append(\'email\', email);
            data.append(\'otp\', otp);
            data.append(\'password\', pass);
            data.append(\'password_conf\', conf);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessShowForgotMsg(\'تم تحديث كلمة المرور بنجاح. جاري تحويلك للوحة التحكم...\', false);
                    setTimeout(() => {
                        window.location.href = res.data.redirect;
                    }, 1500);
                } else {
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Wizard navigation
        function eessGoToRegStep(stepNum) {
            document.getElementById(\'eess-register-msg\').style.display = \'none\';
            for (let i = 1; i <= 4; i++) {
                document.getElementById(\'eess-reg-step-\' + i).className = i === stepNum ? \'eess-wizard-step active\' : \'eess-wizard-step\';

                const node = document.getElementById(\'node-\' + i);
                node.className = \'eess-step-node\';
                if (i === stepNum) {
                    node.classList.add(\'active\');
                } else if (i < stepNum) {
                    node.classList.add(\'completed\');
                    node.innerText = \'✓\';
                } else {
                    node.innerText = i;
                }
            }
        }

        function eessRegisterStep1Next() {
            const email = document.getElementById(\'eess-reg-email\').value;
            if (!email) {
                eessShowRegMsg(\'يرجى إدخال البريد الإلكتروني.\', true);
                return;
            }
            eessGoToRegStep(2);
        }

        function eessRegisterStep2Next() {
            const empNum = document.getElementById(\'eess-reg-emp-num\').value;
            const pass = document.getElementById(\'eess-reg-pass\').value;
            const conf = document.getElementById(\'eess-reg-pass-conf\').value;

            if (!empNum || !pass || !conf) {
                eessShowRegMsg(\'جميع حقول هذه الخطوة إلزامية.\', true);
                return;
            }
            if (pass !== conf) {
                eessShowRegMsg(\'كلمتا المرور غير متطابقتين.\', true);
                return;
            }
            eessGoToRegStep(3);
        }

        function eessRegisterStep3Next() {
            const email = document.getElementById(\'eess-reg-email\').value;
            eessShowRegMsg(\'جاري إرسال الرمز التعريفي OTP للبريد الإلكتروني...\', false);

            const data = new FormData();
            data.append(\'action\', \'eess_register_otp\');
            data.append(\'email\', email);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessGoToRegStep(4);
                    eessShowRegMsg(res.data, false);
                } else {
                    eessShowRegMsg(res.data, true);
                }
            });
        }

        function eessRegisterSubmitFinal() {
            const email = document.getElementById(\'eess-reg-email\').value;
            const empNum = document.getElementById(\'eess-reg-emp-num\').value;
            const pass = document.getElementById(\'eess-reg-pass\').value;
            const conf = document.getElementById(\'eess-reg-pass-conf\').value;
            const role = document.getElementById(\'eess-reg-role\').value;
            const school = document.getElementById(\'eess-reg-school\').value;
            const otp = document.getElementById(\'eess-reg-otp\').value;

            if (!otp) {
                eessShowRegMsg(\'يرجى كتابة رمز التحقق OTP المرسل لبريدك الإلكتروني.\', true);
                return;
            }

            const data = new FormData();
            data.append(\'action\', \'eess_register_submit\');
            data.append(\'email\', email);
            data.append(\'emp_num\', empNum);
            data.append(\'password\', pass);
            data.append(\'password_conf\', conf);
            data.append(\'role\', role);
            data.append(\'school\', school);
            data.append(\'otp\', otp);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessShowRegMsg(res.data, false);
                    setTimeout(() => {
                        eessCloseRegisterModal();
                    }, 4000);
                } else {
                    eessShowRegMsg(res.data, true);
                }
            });
        }
        </script>
        ';
        return $output;
    }


    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';
        
        // Sidebar Visibility Block check
        $visibility = SM_Settings::get_sidebar_visibility();
        $user_role_key = !empty($roles) ? $roles[0] : '';
        $tab_section_map = array(
            'stats'           => 'stats',
            'students'        => 'students',
            'teachers'        => 'teachers',
            'parents'         => 'parents',
            'grades'          => 'grades',
            'work-profile'    => 'work-profile',
            'hr-management'   => 'hr-management',
            'hr-evaluation'   => 'hr-evaluation',
            'attendance'      => 'attendance',
            'lesson-plans'    => 'lesson-plans',
            'assignments'     => 'assignments',
            'clinic'          => 'clinic',
            'documents'       => 'documents'
        );

        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');

        if (!$is_admin && isset($tab_section_map[$active_tab])) {
            $sec_key = $tab_section_map[$active_tab];
            if (isset($visibility[$user_role_key]) && empty($visibility[$user_role_key][$sec_key])) {
                ob_start();
                ?>
                <div class="sm-container" style="padding:60px 20px; text-align:center; max-width:550px; margin: 0 auto; font-family: 'Cairo', sans-serif;" dir="rtl">
                    <div style="background:#ffffff; padding:45px 30px; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 10px 15px -3px rgba(0,0,0,0.05);">
                        <div style="font-size:75px; color:#ea580c; line-height:1; margin-bottom:20px;">🔒</div>
                        <h2 style="margin:0 0 10px 0; font-weight:800; color:#0f172a; font-size:1.6rem;">عفواً، الدخول غير مصرح به</h2>
                        <p style="margin:0 0 30px 0; font-size:14px; color:#64748b; line-height:1.7;">يرجى العلم بأنك لا تملك الصلاحيات الكافية للوصول إلى هذا القسم. إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع إدارة النظام.</p>
                        <a href="<?php echo home_url('/sm-admin'); ?>" class="sm-btn" style="width:100%; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; color:white !important; background-color:#000000 !important; border:1px solid #000000;">العودة للوحة الإدارة الرئيسية</a>
                    </div>
                </div>
                <?php
                return ob_get_clean();
            }
        }

        // Data Preparation based on tab
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
        if ($active_tab === 'hr-management' && !($is_admin || $is_sys_admin || in_array('sm_hr', $roles) || current_user_can('manage_hr'))) $active_tab = 'summary';
        if ($active_tab === 'hr-evaluation' && !($is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || in_array('sm_hr', $roles) || current_user_can('manage_hr'))) $active_tab = 'summary';
        if ($active_tab === 'confiscated' && !current_user_can('إدارة_المخالفات')) $active_tab = 'summary';
        if ($active_tab === 'attendance' && !current_user_can('إدارة_الطلاب')) $active_tab = 'summary';
        if ($active_tab === 'clinic' && !current_user_can('إدارة_العيادة')) $active_tab = 'summary';
        if ($active_tab === 'global-settings' && !current_user_can('إدارة_النظام')) $active_tab = 'summary';
        if ($active_tab === 'lesson-plans' && !($is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_teacher)) $active_tab = 'summary';
        if ($active_tab === 'assignments' && !($is_teacher || $is_student || $is_admin || $is_sys_admin)) $active_tab = 'summary';

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
        
        $stats = SM_DB::get_statistics();
        $records = SM_DB::get_records();
        $logs = SM_Logger::get_logs(50);
        
        wp_send_json_success(array(
            'stats' => $stats,
            'records' => $records,
            'logs' => $logs,
            'unread_messages' => 0,
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
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local');

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
            if (!empty($_FILES['profile_photo']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('profile_photo', 0);
                if (!is_wp_error($attachment_id)) {
                    $photo_url = wp_get_attachment_url($attachment_id);
                    update_user_meta($user_id, 'eess_profile_photo', $photo_url);
                }
            }
            clean_user_cache($user_id);
            wp_cache_flush();
            SM_Logger::log('إضافة مستخدم جديد', "تم إنشاء مستخدم باسم: {$_POST['display_name']} ورتبة: {$_POST['user_role']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_hr_add_employee() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

        if (!$is_admin && !$is_sys_admin && !$is_hr) {
            wp_send_json_error('غير مصرح لك بالوصول.');
        }

        if (!wp_verify_nonce($_POST['sm_nonce'], 'eess_hr_add_employee_nonce')) {
            wp_send_json_error('انتهت صلاحية الجلسة، يرجى تحديث الصفحة.');
        }

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local');

        if (username_exists($username)) {
            wp_send_json_error('اسم المستخدم مسجل مسبقاً في النظام.');
        }

        if (email_exists($email)) {
            wp_send_json_error('البريد الإلكتروني مسجل مسبقاً في النظام.');
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => sanitize_text_field($_POST['user_role'])
        );

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Save as pending!
        update_user_meta($user_id, 'eess_approval_status', 'pending');
        update_user_meta($user_id, 'eess_employee_number', sanitize_text_field($_POST['employee_number']));
        update_user_meta($user_id, 'eess_school_name', sanitize_text_field($_POST['institution']));
        update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        // Handle profile photo upload if provided
        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
            }
        }

        clean_user_cache($user_id);
        wp_cache_flush();

        SM_Logger::log('إضافة موظف معلق', "تم إنشاء حساب موظف معلق باسم: {$_POST['display_name']} للرتبة: {$_POST['user_role']}");
        wp_send_json_success(array('user_id' => $user_id));
    }

    public function ajax_update_generic_user() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_user_id']);
        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_email']) && is_email($_POST['user_email'])) {
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
        }
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

        if (isset($_POST['delete_photo_flag']) && $_POST['delete_photo_flag'] === '1') {
            delete_user_meta($user_id, 'eess_profile_photo');
        }

        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
            }
        }

        clean_user_cache($user_id);
        wp_cache_flush();
        
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
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local'); // Automated

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
            'category' => sanitize_text_field($_POST['category'] ?? 'الوثائق الإدارية'),
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
            'status' => sanitize_text_field($_POST['status']),
            'category' => sanitize_text_field($_POST['category'] ?? 'الوثائق الإدارية')
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

    public function ajax_refresh_system() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        // 1. Delete all plugin transients from options table
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");

        // 2. Clear general WordPress cache
        wp_cache_flush();

        // 3. Clear user capability transients or meta caches
        $users = get_users(array('fields' => array('ID')));
        foreach ($users as $u) {
            clean_user_cache($u->ID);
        }

        // 4. Force reload settings & metrics
        $school_info = SM_Settings::get_school_info();
        $stats = SM_DB::get_statistics();

        wp_send_json_success(array(
            'message' => 'تم تحديث كافة الملفات المؤقتة والذاكرة المؤقتة للخدمات والنظام بنجاح مباشرة من قاعدة البيانات.',
            'school_name' => $school_info['school_name'],
            'stats' => $stats
        ));
    }

    public function ajax_export_users_csv() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المستخدمين')) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        $all_users = get_users();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_export_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        fputcsv($output, array('اسم المستخدم', 'البريد الإلكتروني', 'الاسم الكامل', 'الدور / الرتبة', 'رقم الهاتف', 'كلمة المرور', 'رابط الصورة الشخصية', 'المادة التخصصية'));

        foreach ($all_users as $u) {
            $role = reset($u->roles);
            $phone = get_user_meta($u->ID, 'sm_phone', true);
            $password = get_user_meta($u->ID, 'sm_temp_pass', true) ?: '';
            $photo = get_user_meta($u->ID, 'eess_profile_photo', true) ?: '';
            $specialization = get_user_meta($u->ID, 'sm_specialization', true) ?: '';

            fputcsv($output, array(
                $u->user_login,
                $u->user_email,
                $u->display_name,
                $role,
                $phone,
                $password,
                $photo,
                $specialization
            ));
        }
        fclose($output);
        exit;
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
                $existing = SM_Settings::get_school_info();
                SM_Settings::save_school_info(array(
                    'school_name' => sanitize_text_field($_POST['school_name']),
                    'school_principal_name' => isset($_POST['school_principal_name']) ? sanitize_text_field($_POST['school_principal_name']) : ($existing['school_principal_name'] ?? ''),
                    'school_logo' => esc_url_raw($_POST['school_logo']),
                    'address' => isset($_POST['school_address']) ? sanitize_text_field($_POST['school_address']) : ($existing['address'] ?? ''),
                    'email' => sanitize_email($_POST['school_email']),
                    'phone' => sanitize_text_field($_POST['school_phone']),
                    'working_schedule' => array(
                        'staff' => isset($_POST['work_staff']) ? array_map('sanitize_text_field', $_POST['work_staff']) : ($existing['working_schedule']['staff'] ?? array()),
                        'students' => isset($_POST['work_students']) ? array_map('sanitize_text_field', $_POST['work_students']) : ($existing['working_schedule']['students'] ?? array())
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
        // Handle Sidebar Visibility Settings Save
        if (isset($_POST['sm_save_sidebar_visibility']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $roles_to_process = array('sm_system_admin', 'sm_principal', 'sm_supervisor', 'sm_coordinator', 'sm_teacher', 'sm_student', 'sm_parent', 'sm_discipline_supervisor', 'sm_activities_supervisor', 'sm_transportation_supervisor', 'sm_bus_supervisor');
                $sections_to_process = array('stats', 'students', 'teachers', 'parents', 'grades', 'teacher-reports', 'attendance', 'lesson-plans', 'assignments', 'clinic', 'documents');

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


        // Handle Unified Users CSV Import
        if (isset($_POST['sm_import_users_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_المستخدمين') && !empty($_FILES['csv_file']['tmp_name'])) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 3) {
                        $username = sanitize_user($data[0]);
                        $email = !empty($data[1]) ? sanitize_email($data[1]) : $username . '@school-system.local';
                        $display_name = sanitize_text_field($data[2]);
                        $role = isset($data[3]) ? sanitize_text_field($data[3]) : 'sm_teacher';
                        $phone = isset($data[4]) ? sanitize_text_field($data[4]) : '';
                        $password = !empty($data[5]) ? $data[5] : wp_generate_password();
                        $photo_url = isset($data[6]) ? esc_url_raw($data[6]) : '';
                        $specialization = isset($data[7]) ? sanitize_text_field($data[7]) : '';

                        // If user exists, update them; otherwise, insert them!
                        $user = get_user_by('login', $username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array(
                                'ID' => $user_id,
                                'user_email' => $email,
                                'display_name' => $display_name,
                                'user_pass' => $password
                            ));
                            $u_obj = new WP_User($user_id);
                            $u_obj->set_role($role);
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $username,
                                'user_email' => $email,
                                'display_name' => $display_name,
                                'user_pass' => $password,
                                'role' => $role
                            ));
                        }

                        if (!is_wp_error($user_id)) {
                            $count++;
                            update_user_meta($user_id, 'sm_temp_pass', $password);
                            if (!empty($phone)) {
                                update_user_meta($user_id, 'sm_phone', $phone);
                            }
                            if (!empty($photo_url)) {
                                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
                            }
                            if (!empty($specialization)) {
                                update_user_meta($user_id, 'sm_specialization', $specialization);
                            }
                            clean_user_cache($user_id);
                        }
                    }
                }
                fclose($handle);
                wp_cache_flush();
                SM_Logger::log('استيراد مستخدمين (جماعي)', "تم استيراد ($count) مستخدم بنجاح من ملف CSV.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
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

    public function ajax_export_students_csv() {
        if (!current_user_can('إدارة_الطلاب')) {
            wp_die('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_admin_action')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $records = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_students ORDER BY name ASC");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=students_export_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        fputcsv($output, array('الاسم الكامل', 'الصف', 'الشعبة', 'الجنسية', 'البريد الإلكتروني لولي الأمر', 'رقم هاتف ولي الأمر', 'رقم الهوية الوطنية / الكود'));

        foreach ($records as $r) {
            fputcsv($output, array(
                $r->name,
                $r->class_name,
                $r->section,
                $r->nationality,
                $r->parent_email,
                $r->guardian_phone,
                $r->student_code
            ));
        }
        fclose($output);
        exit;
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

    // Branded EESS Email Helper
    private function send_branded_email($to, $subject, $title, $body_content) {
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: EESS Platform <info@eess.online>');

        $html = '
        <div dir="rtl" style="font-family: \'Cairo\', \'Noto Kufi Arabic\', Arial, sans-serif; background-color: #f8fafc; padding: 40px 20px; text-align: right; direction: rtl;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #cbd5e1; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <div style="background: #0d0d0d; padding: 25px; text-align: center; border-bottom: 4px solid #8b1e1e;">
                    <h2 style="color: #ffffff; margin: 0; font-weight: 800; font-size: 1.5rem; letter-spacing: 1px;">EESS</h2>
                    <div style="color: #cbd5e1; font-size: 11px; margin-top: 5px;">Educational Electronic Systems Services</div>
                </div>
                <div style="padding: 30px; box-sizing: border-box;">
                    <h3 style="color: #0f172a; margin-top: 0; font-weight: 800; font-size: 1.2rem;">' . esc_html($title) . '</h3>
                    <div style="color: #334155; font-size: 14px; line-height: 1.8; margin-bottom: 25px;">
                        ' . $body_content . '
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 25px; font-size: 12px; color: #64748b;">
                        إذا واجهت أي صعوبة في الدخول أو استخدام الخدمة، يمكنك دائماً مراجعة قسم الدعم الفني عبر البريد الإلكتروني الرسمي: <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; text-decoration: none;">info@eess.online</a>.
                    </div>
                </div>
                <div style="background: #f1f5f9; padding: 15px 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <div>جميع الحقوق محفوظة © 2026 EESS. خدمات الأنظمة الإلكترونية التعليمية</div>
                    <div style="margin-top: 5px;"><a href="https://eess.online" target="_blank" style="color: #94a3b8; text-decoration: underline;">eess.online</a></div>
                </div>
            </div>
        </div>
        ';
        return wp_mail($to, $subject, $html, $headers);
    }

    // Block Pending Approval & Restricted users from logging in
    public function block_pending_users_login($user, $password) {
        if ($user instanceof WP_User) {
            $status = get_user_meta($user->ID, 'eess_approval_status', true);
            $restricted = get_user_meta($user->ID, 'eess_access_restricted', true);
            $reason = get_user_meta($user->ID, 'eess_restriction_reason', true) ?: 'غير محدد';

            if ($status === 'pending') {
                return new WP_Error(
                    'pending_approval',
                    'حسابك قيد المراجعة الإدارية. يرجى الانتظار لحين اعتماد وتفعيل الحساب من قبل قسم إدارة المستخدمين.'
                );
            }
            if ($status === 'restricted' || $restricted === 'yes') {
                return new WP_Error(
                    'restricted_access',
                    'عذراً، تم تقييد دخولك إلى المنصة من قبل إدارة الموارد البشرية لسبب: ' . esc_html($reason)
                );
            }
        }
        return $user;
    }

    // Forgot Password OTP Generator
    public function ajax_forgot_otp() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (empty($email)) {
            wp_send_json_error('يرجى إدخال البريد الإلكتروني.');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error('عفواً، لا يوجد حساب مسجل بهذا البريد الإلكتروني.');
        }

        $otp = sprintf('%06d', rand(100000, 999999));
        set_transient('eess_reset_otp_' . md5($email), $otp, 15 * MINUTE_IN_SECONDS);

        $title = 'رمز التحقق لإعادة تعيين كلمة المرور - EESS';
        $body = '
        <p>مرحباً بك يا <strong>' . esc_html($user->display_name) . '</strong>،</p>
        <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك على منصة EESS الإلكترونية.</p>
        <p>رمز التحقق الآمن (OTP) الخاص بك هو:</p>
        <div style="text-align: center; margin: 20px 0;">
            <span style="display: inline-block; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 12px 30px; font-size: 24px; font-weight: 800; letter-spacing: 5px; color: #000000; border-radius: 6px;">' . $otp . '</span>
        </div>
        <p style="color: #64748b; font-size: 12px;">ملاحظة: هذا الرمز صالح لمدة 15 دقيقة فقط. يرجى عدم مشاركة هذا الرمز مع أي شخص لضمان أمان حسابك.</p>
        ';

        if ($this->send_branded_email($email, $title, 'رمز استعادة كلمة المرور', $body)) {
            wp_send_json_success('تم إرسال رمز التحقق بنجاح إلى بريدك الإلكتروني.');
        } else {
            wp_send_json_error('فشل إرسال البريد الإلكتروني. يرجى مراجعة خادم البريد.');
        }
    }

    // Forgot Password OTP Verifier
    public function ajax_forgot_verify() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

        if (empty($email) || empty($otp)) {
            wp_send_json_error('جميع الحقول مطلوبة.');
        }

        $saved_otp = get_transient('eess_reset_otp_' . md5($email));
        if ($saved_otp === false || $saved_otp !== $otp) {
            wp_send_json_error('رمز التحقق غير صحيح أو انتهت صلاحيته.');
        }

        $user = get_user_by('email', $email);
        wp_send_json_success(array(
            'display_name' => $user->display_name
        ));
    }

    // Forgot Password Reset & Autologin
    public function ajax_forgot_reset() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_conf = isset($_POST['password_conf']) ? $_POST['password_conf'] : '';

        if (empty($email) || empty($otp) || empty($password) || empty($password_conf)) {
            wp_send_json_error('جميع الحقول مطلوبة.');
        }

        if ($password !== $password_conf) {
            wp_send_json_error('كلمتا المرور غير متطابقتين.');
        }

        if (strlen($password) < 6) {
            wp_send_json_error('يجب ألا تقل كلمة المرور عن 6 أحرف.');
        }

        $saved_otp = get_transient('eess_reset_otp_' . md5($email));
        if ($saved_otp === false || $saved_otp !== $otp) {
            wp_send_json_error('انتهت صلاحية الجلسة الآمنة. يرجى البدء من جديد.');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error('خطأ غير متوقع. لم يتم العثور على المستخدم.');
        }

        // Reset password
        wp_set_password($password, $user->ID);
        delete_transient('eess_reset_otp_' . md5($email));

        // Autologin
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        wp_send_json_success(array(
            'redirect' => home_url('/sm-admin')
        ));
    }

    // Registration Wizard OTP generator
    public function ajax_register_otp() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (empty($email)) {
            wp_send_json_error('يرجى إدخال البريد الإلكتروني.');
        }

        if (email_exists($email)) {
            wp_send_json_error('عفواً، هذا البريد الإلكتروني مستخدم بالفعل ومسجل بالنظام.');
        }

        $otp = sprintf('%06d', rand(100000, 999999));
        set_transient('eess_register_otp_' . md5($email), $otp, 15 * MINUTE_IN_SECONDS);

        $title = 'رمز التحقق لتسجيل حساب جديد - EESS';
        $body = '
        <p>مرحباً بك،</p>
        <p>يسعدنا انضمامك إلى منصة EESS الإلكترونية لإدارة وتطوير الخدمات التعليمية.</p>
        <p>رمز تفعيل البريد الإلكتروني والتحقق الخاص بك هو:</p>
        <div style="text-align: center; margin: 20px 0;">
            <span style="display: inline-block; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 12px 30px; font-size: 24px; font-weight: 800; letter-spacing: 5px; color: #000000; border-radius: 6px;">' . $otp . '</span>
        </div>
        <p style="color: #64748b; font-size: 12px;">ملاحظة: الرمز صالح لمدة 15 دقيقة فقط. يرجى إدخال الرمز بالمكان المخصص لإتمام عملية التسجيل.</p>
        ';

        if ($this->send_branded_email($email, $title, 'رمز تفعيل البريد الإلكتروني', $body)) {
            wp_send_json_success('تم إرسال رمز التحقق بنجاح إلى البريد الإلكتروني المكتوب.');
        } else {
            wp_send_json_error('فشل إرسال البريد الإلكتروني. يرجى مراجعة خادم البريد.');
        }
    }

    // Registration Wizard Submit
    public function ajax_register_submit() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $emp_num = isset($_POST['emp_num']) ? sanitize_text_field($_POST['emp_num']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_conf = isset($_POST['password_conf']) ? $_POST['password_conf'] : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $school = isset($_POST['school']) ? sanitize_text_field($_POST['school']) : '';
        $otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

        if (empty($email) || empty($emp_num) || empty($password) || empty($password_conf) || empty($role) || empty($otp)) {
            wp_send_json_error('جميع الحقول إلزامية لإكمال عملية التسجيل.');
        }

        if ($password !== $password_conf) {
            wp_send_json_error('كلمتا المرور غير متطابقتين.');
        }

        $saved_otp = get_transient('eess_register_otp_' . md5($email));
        if ($saved_otp === false || $saved_otp !== $otp) {
            wp_send_json_error('رمز التحقق (OTP) غير صحيح أو انتهت صلاحيته.');
        }

        // Insert pending user
        $username = strstr($email, '@', true) . rand(10, 99);
        while (username_exists($username)) {
            $username .= rand(0, 9);
        }

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => $role
        ));

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Set pending status and metadata
        update_user_meta($user_id, 'eess_approval_status', 'pending');
        update_user_meta($user_id, 'eess_employee_number', $emp_num);
        update_user_meta($user_id, 'eess_school_name', $school);

        delete_transient('eess_register_otp_' . md5($email));

        // Notify System User Management
        $admin_email = get_option('admin_email') ?: 'info@eess.online';
        $admin_title = 'طلب تسجيل حساب جديد قيد الانتظار - EESS';
        $admin_body = '
        <p>مرحباً بقسم إدارة المستخدمين،</p>
        <p>تم استلام طلب تسجيل حساب جديد بالمنصة وينتظر المراجعة والاعتماد.</p>
        <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border:1px solid #e2e8f0; line-height: 1.8;">
            <strong>البريد الإلكتروني:</strong> ' . esc_html($email) . '<br>
            <strong>رقم الموظف:</strong> ' . esc_html($emp_num) . '<br>
            <strong>الرتبة / المسمى الوظيفي:</strong> ' . esc_html($role) . '<br>
            <strong>المدرسة المنتسب لها:</strong> ' . esc_html($school) . '<br>
        </div>
        <p>يمكنكم مراجعة الطلب والموافقة عليه أو رفضه مباشرة من خلال تبويب إدارة المستخدمين بلوحة التحكم.</p>
        ';
        $this->send_branded_email($admin_email, $admin_title, 'طلب تسجيل حساب قيد الانتظار', $admin_body);

        wp_send_json_success('تم تسجيل الحساب بنجاح. حسابك حالياً قيد المراجعة الإدارية وسوف نرسل لك تفعيلاً فور الاعتماد.');
    }

    // Admin Action: Approve user
    public function ajax_approve_user() {
        check_ajax_referer('eess_admin_action', 'nonce');
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بإجراء هذه العملية.');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$target_user_id) {
            wp_send_json_error('معرف المستخدم غير صحيح.');
        }

        update_user_meta($target_user_id, 'eess_approval_status', 'approved');

        $user = get_user_by('id', $target_user_id);
        $title = 'اعتماد وتفعيل حسابك الإلكتروني - EESS Account Activation';

        $body = '
        <table dir="rtl" style="text-align: right; width: 100%; font-family: \'Cairo\', sans-serif; border-collapse: collapse; margin-bottom: 25px;">
            <tr>
                <td>
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0 0 10px 0; font-weight: bold;">أهلاً بك يا ' . esc_html($user->display_name ?: $user->user_email) . '،</h3>
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin: 0 0 15px 0;">يسعدنا إبلاغك بأنه تم مراجعة واعتماد حسابك بنجاح على منصة <strong>خدمات الأنظمة الإلكترونية التعليمية (EESS)</strong>. حسابك الآن نشط بالكامل وجاهز للاستخدام الفوري.</p>

                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                        <strong>تفاصيل الحساب / Account Details:</strong><br>
                        • اسم المستخدم: <span style="font-family: monospace; font-weight: bold;">' . esc_html($user->user_login) . '</span><br>
                        • البريد الإلكتروني: <span style="font-family: monospace; font-weight: bold;">' . esc_html($user->user_email) . '</span><br>
                    </div>

                    <p style="font-size: 13px; color: #334155; line-height: 1.8;"><strong>توصيات أمنية هامة:</strong> يرجى التأكد من عدم مشاركة بيانات دخولك أو رمز التفعيل مع أي شخص آخر، واحرص على استخدام كلمة مرور قوية لضمان أمان معلوماتك.</p>
                </td>
            </tr>
        </table>

        <hr style="border: none; border-top: 1px solid #cbd5e1; margin: 25px 0;">

        <table dir="ltr" style="text-align: left; width: 100%; font-family: \'Cairo\', sans-serif; border-collapse: collapse;">
            <tr>
                <td>
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0 0 10px 0; font-weight: bold;">Welcome, ' . esc_html($user->display_name ?: $user->user_email) . ',</h3>
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin: 0 0 15px 0;">We are pleased to inform you that your account has been reviewed and successfully approved on the <strong>Educational Electronic Systems Services (EESS)</strong> platform. Your account is now fully active and ready for use.</p>

                    <p style="font-size: 13px; color: #334155; line-height: 1.8;"><strong>Important Security Recommendations:</strong> Please ensure never to share your login credentials or OTP with anyone else, and use a strong password to ensure the security of your private data.</p>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin: 30px 0;">
            <a href="' . home_url('/sm-login') . '" style="display:inline-block; background:#000000; color:#ffffff !important; text-decoration:none; padding:12px 35px; font-weight:bold; border-radius:6px; font-size:14px; font-family:\'Cairo\', sans-serif;">تسجيل الدخول للمنصة / Login to Platform</a>
        </div>
        ';

        $this->send_branded_email($user->user_email, $title, 'تنشيط حسابك بالمنصة', $body);

        wp_send_json_success('تم اعتماد وتنشيط الحساب وإخطار المستخدم بنجاح.');
    }

    // Admin Action: Reject user
    public function ajax_reject_user() {
        check_ajax_referer('eess_admin_action', 'nonce');
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بإجراء هذه العملية.');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$target_user_id) {
            wp_send_json_error('معرف المستخدم غير صحيح.');
        }

        $user = get_user_by('id', $target_user_id);
        if ($user) {
            $title = 'بخصوص طلب تسجيل حسابك - EESS';
            $body = '
            <p>مرحباً بك،</p>
            <p>نأسف لإبلاغك بأنه تم رفض طلب التسجيل الخاص بك على منصة EESS الإلكترونية بعد المراجعة الإدارية.</p>
            <p>في حال كنت تعتقد أن هناك خطأً، يرجى التواصل مجدداً مع الدعم الفني أو مراجعة إدارة شؤون الموظفين.</p>
            ';
            $this->send_branded_email($user->user_email, $title, 'مراجعة طلب التسجيل', $body);

            // Delete user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($target_user_id);
        }

        wp_send_json_success('تم رفض طلب التسجيل وحذف الحساب المعلق بنجاح.');
    }

    // Admin Action: Save user notes
    public function ajax_save_user_notes() {
        check_ajax_referer('eess_admin_action', 'nonce');
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بإجراء هذه العملية.');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (!$target_user_id) {
            wp_send_json_error('معرف المستخدم غير صحيح.');
        }

        update_user_meta($target_user_id, 'eess_admin_notes', $notes);
        wp_send_json_success('تم حفظ الملاحظات الداخلية بنجاح.');
    }
}
