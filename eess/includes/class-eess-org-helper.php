<?php
if (!defined('ABSPATH')) exit;

class EESS_Org_Helper {

    /**
     * Seeds initial institutions and schools if none exist
     */
    public static function seed_default_structure() {
        global $wpdb;

        $inst_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eess_institutions");
        if ($inst_count == 0) {
            // Seed default institution
            $wpdb->insert("{$wpdb->prefix}eess_institutions", array(
                'name' => 'مؤسسة الإمارات للتعليم المدرسي',
                'status' => 'active'
            ));
            $inst_id = $wpdb->insert_id;

            // Seed default schools
            $schools = array(
                'مدرسة الأمل للتعليم الأساسي والثانوي',
                'مدرسة النخبة النموذجية',
                'مدرسة الريادة للتعليم الثانوي'
            );

            foreach ($schools as $school_name) {
                $wpdb->insert("{$wpdb->prefix}eess_schools", array(
                    'institution_id' => $inst_id,
                    'name' => $school_name,
                    'status' => 'active'
                ));
                $school_id = $wpdb->insert_id;

                // Seed default Grade levels & Classes for each school
                for ($g = 1; $g <= 12; $g++) {
                    $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                        'school_id' => $school_id,
                        'name' => 'الصف ' . $g
                    ));
                    $grade_id = $wpdb->insert_id;

                    // Seed default Classes/sections
                    $classes = array('أ', 'ب', 'ج');
                    foreach ($classes as $c_name) {
                        $wpdb->insert("{$wpdb->prefix}eess_classes", array(
                            'grade_id' => $grade_id,
                            'name' => $c_name
                        ));
                    }
                }

                // Seed default Departments
                $depts = array('العلوم العامة', 'الرياضيات والفيزياء', 'اللغات والآداب', 'التربية الرياضية');
                foreach ($depts as $d_name) {
                    $wpdb->insert("{$wpdb->prefix}eess_departments", array(
                        'school_id' => $school_id,
                        'name' => $d_name
                    ));
                }
            }
        }
    }

    /**
     * Retrieves the organizational scope for a given user
     */
    public static function get_user_scope($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        global $wpdb;

        $user = get_userdata($user_id);
        if (!$user) return array('unrestricted' => false, 'schools' => array(), 'grades' => array(), 'classes' => array(), 'subjects' => array(), 'departments' => array());

        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || in_array('sm_system_admin', $roles);

        if ($is_admin) {
            // Unrestricted access for System Admin
            $all_schools = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_schools WHERE status='active'");
            return array(
                'unrestricted' => true,
                'schools' => $all_schools,
                'grades' => array(),
                'classes' => array(),
                'subjects' => array(),
                'departments' => array()
            );
        }

        // Fetch user assignments
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d",
            $user_id
        ));

        $schools = array();
        $grades = array();
        $classes = array();
        $subjects = array();
        $departments = array();

        foreach ($assignments as $asn) {
            if ($asn->school_id) $schools[] = intval($asn->school_id);
            if ($asn->grade_id) $grades[] = intval($asn->grade_id);
            if ($asn->class_id) $classes[] = intval($asn->class_id);
            if ($asn->subject_id) $subjects[] = intval($asn->subject_id);
            if ($asn->department_id) $departments[] = intval($asn->department_id);
        }

        return array(
            'unrestricted' => false,
            'schools' => array_unique($schools),
            'grades' => array_unique($grades),
            'classes' => array_unique($classes),
            'subjects' => array_unique($subjects),
            'departments' => array_unique($departments)
        );
    }

    /**
     * Centralized Assignment Saver
     */
    public static function save_user_assignments($user_id, $data) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));

        $inst_ids = !empty($data['institutions']) ? array_map('intval', (array)$data['institutions']) : array();
        $school_ids = !empty($data['schools']) ? array_map('intval', (array)$data['schools']) : array();
        $grade_ids = !empty($data['grades']) ? array_map('intval', (array)$data['grades']) : array();
        $class_ids = !empty($data['classes']) ? array_map('intval', (array)$data['classes']) : array();
        $subject_ids = !empty($data['subjects']) ? array_map('intval', (array)$data['subjects']) : array();
        $dept_ids = !empty($data['departments']) ? array_map('intval', (array)$data['departments']) : array();

        $max_count = max(count($inst_ids), count($school_ids), count($grade_ids), count($class_ids), count($subject_ids), count($dept_ids), 1);

        for ($i = 0; $i < $max_count; $i++) {
            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                'user_id' => $user_id,
                'institution_id' => $inst_ids[$i] ?? ($inst_ids[0] ?? null),
                'school_id' => $school_ids[$i] ?? ($school_ids[0] ?? null),
                'grade_id' => $grade_ids[$i] ?? ($grade_ids[0] ?? null),
                'class_id' => $class_ids[$i] ?? ($class_ids[0] ?? null),
                'subject_id' => $subject_ids[$i] ?? ($subject_ids[0] ?? null),
                'department_id' => $dept_ids[$i] ?? ($dept_ids[0] ?? null)
            ));
        }

        clean_user_cache($user_id);
        wp_cache_flush();
    }

    /**
     * Standardized SQL Filter Injector for any table querying students
     */
    public static function filter_students_query($query_alias = '') {
        global $wpdb;
        $scope = self::get_user_scope();
        if ($scope['unrestricted']) return " 1=1 ";

        $school_ids = !empty($scope['schools']) ? implode(',', array_map('intval', $scope['schools'])) : '0';
        $class_ids = !empty($scope['classes']) ? implode(',', array_map('intval', $scope['classes'])) : '0';

        $prefix = !empty($query_alias) ? $query_alias . '.' : '';

        // Principal / Supervisor can access all students in their assigned schools
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_hr = in_array('sm_hr', $roles);

        if ($is_principal || $is_supervisor || $is_hr) {
            return " {$prefix}school_id IN ($school_ids) ";
        }

        // Teachers can only access their assigned classes/sections
        return " {$prefix}class_id IN ($class_ids) ";
    }
}
