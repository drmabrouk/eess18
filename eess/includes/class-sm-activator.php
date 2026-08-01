<?php

class SM_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_students = $wpdb->prefix . 'sm_students';
        $table_records = $wpdb->prefix . 'sm_records';
        $table_logs = $wpdb->prefix . 'sm_logs';
        $table_messages = $wpdb->prefix . 'sm_messages';

        $sql = "CREATE TABLE $table_students (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            class_name varchar(100) NOT NULL,
            section varchar(50) DEFAULT '',
            parent_email varchar(100),
            guardian_phone varchar(50) DEFAULT '',
            nationality varchar(100) DEFAULT '',
            registration_date date DEFAULT NULL,
            student_code varchar(50),
            parent_user_id bigint(20) DEFAULT NULL,
            teacher_id bigint(20) DEFAULT NULL,
            photo_url varchar(255) DEFAULT '',
            national_id varchar(50) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            behavior_points int(11) DEFAULT 0,
            case_file_active tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY student_code (student_code),
            KEY teacher_id (teacher_id),
            KEY sort_order (sort_order),
            UNIQUE KEY national_id (national_id)
        ) $charset_collate;

        CREATE TABLE $table_records (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            teacher_id bigint(20) NOT NULL,
            type varchar(100) NOT NULL,
            classification varchar(100) DEFAULT 'general',
            severity varchar(50) NOT NULL,
            degree int(11) DEFAULT 1,
            violation_code varchar(50) DEFAULT '',
            points int(11) DEFAULT 0,
            recurrence_count int(11) DEFAULT 1,
            details text NOT NULL,
            action_taken text,
            reward_penalty text,
            contacted tinyint(1) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'accepted' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY teacher_id (teacher_id),
            KEY status (status),
            KEY degree (degree),
            KEY violation_code (violation_code)
        ) $charset_collate;

        CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action text NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            student_id bigint(20) DEFAULT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_confiscated_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            item_name varchar(255) NOT NULL,
            holding_period int(11) DEFAULT 30,
            status varchar(50) DEFAULT 'held',
            is_returnable tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_attendance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL,
            date date NOT NULL,
            teacher_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY date (date)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_assignments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            student_id bigint(20) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text,
            file_url varchar(255) DEFAULT '',
            type varchar(50) DEFAULT 'assignment',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_clinic (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            referrer_id bigint(20) NOT NULL,
            arrival_confirmed tinyint(1) DEFAULT 0,
            health_condition text,
            action_taken text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            arrival_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY referrer_id (referrer_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_grades (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            subject varchar(100) NOT NULL,
            term varchar(50) NOT NULL,
            grade_val varchar(20) NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_subjects (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            grade_id int(11) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_surveys (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            target_roles varchar(255) NOT NULL,
            questions text NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_survey_responses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            survey_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            answers text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY survey_id (survey_id),
            KEY user_id (user_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_timetables (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            class_name varchar(100) NOT NULL,
            section varchar(50) NOT NULL,
            day varchar(20) NOT NULL,
            period int(11) NOT NULL,
            subject_id bigint(20) NOT NULL,
            teacher_id bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY class_section (class_name, section)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_student_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY meta_key (meta_key)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_documents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            file_url varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'published',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        self::add_custom_roles();
        self::seed_demo_data();
        self::create_default_pages();
        self::cleanup_legacy_pages();
        self::migrate_old_roles();
    }

    private static function cleanup_legacy_pages() {
        $legacy_page = get_page_by_path('sm-system');
        if ($legacy_page) {
            wp_delete_post($legacy_page->ID, true);
        }
    }

    private static function create_default_pages() {
        $pages = array(
            'sm-login' => array(
                'title'   => 'تسجيل الدخول',
                'content' => '[sm_login]',
            ),
            'sm-admin' => array(
                'title'   => 'لوحة التحكم المدرسية',
                'content' => '[sm_admin]',
            ),
            'attendance' => array(
                'title'   => 'تسجيل حضور الفصول',
                'content' => '[sm_class_attendance]',
            ),
        );

        foreach ($pages as $slug => $page_data) {
            $page_exists = get_page_by_path($slug);
            if (!$page_exists) {
                wp_insert_post(array(
                    'post_title'   => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_name'    => $slug,
                ));
            }
        }
    }

    public static function add_custom_roles() {
        // Remove old roles
        $old_roles = array(
            'school_admin', 'discipline_officer', 'sm_school_admin',
            'sm_discipline_officer', 'sm_teacher', 'sm_parent'
        );
        foreach ($old_roles as $role) {
            remove_role($role);
        }

        // Capabilities
        $caps = array(
            'manage_system' => 'إدارة_النظام',
            'manage_clinic' => 'إدارة_العيادة',
            'manage_users' => 'إدارة_المستخدمين',
            'manage_students' => 'إدارة_الطلاب',
            'manage_teachers' => 'إدارة_المعلمين',
            'manage_violations' => 'إدارة_المخالفات',
            'add_violation' => 'تسجيل_مخالفة',
            'print_reports' => 'طباعة_التقارير',
            'review_plans' => 'مراجعة_التحضير',
            'manage_grades' => 'إدارة_الدرجات',
            'manage_assignments' => 'إدارة_الواجبات',
            'manage_parents' => 'إدارة_أولياء_الأمور',
            'view_own_data' => 'عرض_بياناتي',
            'submit_complaint' => 'تقديم_شكوى'
        );

        // Add Caps to Administrator
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }

        // 1. مدير النظام (System Administrator) - Access to all, including settings
        add_role('sm_system_admin', 'مدير النظام', array('read' => true));
        $sys_admin = get_role('sm_system_admin');
        if ($sys_admin) {
            foreach ($caps as $cap) $sys_admin->add_cap($cap);
        }

        // 2. مدير المدرسة (School Principal) - All except settings
        add_role('sm_principal', 'مدير المدرسة', array('read' => true));
        $principal = get_role('sm_principal');
        if ($principal) {
            foreach ($caps as $key => $cap) {
                if ($key !== 'manage_system') $principal->add_cap($cap);
            }
        }

        // 3. مشرف (Supervisor) - Same as Principal (filtered by classes in logic)
        add_role('sm_supervisor', 'مشرف', array('read' => true));
        $supervisor = get_role('sm_supervisor');
        if ($supervisor) {
            foreach ($caps as $key => $cap) {
                if ($key !== 'manage_system') $supervisor->add_cap($cap);
            }
        }

        // 4. منسق (Coordinator) - Review lesson plans
        add_role('sm_coordinator', 'منسق المادة', array('read' => true));
        $coordinator = get_role('sm_coordinator');
        if ($coordinator) {
            $coordinator->add_cap($caps['review_plans']);
            $coordinator->add_cap($caps['manage_grades']);
            $coordinator->add_cap('read');
        }

        // 5. معلم (Teacher) - Complaints, search all students, assignments, assigned sections
        add_role('sm_teacher', 'معلم', array('read' => true));
        $teacher = get_role('sm_teacher');
        if ($teacher) {
            $teacher->add_cap($caps['add_violation']);
            $teacher->add_cap($caps['submit_complaint']);
            $teacher->add_cap($caps['manage_assignments']);
            $teacher->add_cap($caps['manage_students']);
            $teacher->add_cap($caps['manage_grades']);
        }

        // 6. طالب (Student) - View own results/attendance, assignments, personal photo
        add_role('sm_student', 'طالب', array('read' => true));
        $student = get_role('sm_student');
        if ($student) {
            $student->add_cap($caps['view_own_data']);
            $student->add_cap($caps['manage_assignments']);
        }

        // 7. العيادة (Clinic) - Referral list and history
        add_role('sm_clinic', 'العيادة المدرسية', array('read' => true));
        $clinic = get_role('sm_clinic');
        if ($clinic) {
            $clinic->add_cap($caps['manage_clinic']);
            $clinic->add_cap('read');
        }

        // 8. ولي أمر (Parent) - View own children's data
        add_role('sm_parent', 'ولي أمر', array('read' => true));
        $parent = get_role('sm_parent');
        if ($parent) {
            $parent->add_cap($caps['view_own_data']);
            $parent->add_cap('read');
        }
    }

    public static function migrate_old_roles() {
        $migration_map = array(
            'discipline_officer'    => 'sm_supervisor',
            'school_admin'          => 'sm_principal',
            'sm_discipline_officer' => 'sm_supervisor',
            'sm_school_admin'       => 'sm_principal'
        );

        foreach ($migration_map as $old_slug => $new_slug) {
            $users = get_users(array('role' => $old_slug));
            foreach ($users as $user) {
                $user->remove_role($old_slug);
                $user->add_role($new_slug);
            }
        }
    }

    private static function seed_demo_data() {
        global $wpdb;
        $table_students = $wpdb->prefix . 'sm_students';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_students");
        if ($count > 0) return;

        $demo_students = array(
            array('name' => 'أحمد محمد', 'class_name' => 'الصف الأول', 'parent_email' => 'parent1@example.com', 'student_code' => 'STU001'),
            array('name' => 'سارة علي', 'class_name' => 'الصف الأول', 'parent_email' => 'parent2@example.com', 'student_code' => 'STU002'),
            array('name' => 'خالد محمود', 'class_name' => 'الصف الثاني', 'parent_email' => 'parent3@example.com', 'student_code' => 'STU003'),
            array('name' => 'ليلى يوسف', 'class_name' => 'الصف الثاني', 'parent_email' => 'parent4@example.com', 'student_code' => 'STU004'),
            array('name' => 'عمر حسن', 'class_name' => 'الصف الثالث', 'parent_email' => 'parent5@example.com', 'student_code' => 'STU005'),
            array('name' => 'مريم إبراهيم', 'class_name' => 'الصف الثالث', 'parent_email' => 'parent6@example.com', 'student_code' => 'STU006'),
            array('name' => 'ياسين كمال', 'class_name' => 'الصف الرابع', 'parent_email' => 'parent7@example.com', 'student_code' => 'STU007'),
            array('name' => 'نور الهدى', 'class_name' => 'الصف الرابع', 'parent_email' => 'parent8@example.com', 'student_code' => 'STU008'),
            array('name' => 'عبد الله فهد', 'class_name' => 'الصف الخامس', 'parent_email' => 'parent9@example.com', 'student_code' => 'STU009'),
            array('name' => 'هند سعادة', 'class_name' => 'الصف الخامس', 'parent_email' => 'parent10@example.com', 'student_code' => 'STU010'),
        );

        foreach ($demo_students as $student) {
            $wpdb->insert($table_students, $student);
        }
    }
}
