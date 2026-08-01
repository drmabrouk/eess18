<?php

class SM_Settings {
    public static function get_violation_types() {
        $default = array(
            'behavior' => 'سلوك',
            'lateness' => 'تأخر',
            'absence' => 'غياب',
            'other' => 'أخرى'
        );
        return get_option('sm_violation_types', $default);
    }

    public static function get_severities() {
        return array(
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'خطيرة'
        );
    }

    public static function save_violation_types($types) {
        update_option('sm_violation_types', $types);
    }

    public static function get_appearance() {
        $default = array(
            'primary_color' => '#F63049',
            'secondary_color' => '#D02752',
            'accent_color' => '#8A244B',
            'dark_color' => '#111F35',
            'font_size' => '15px',
            'border_radius' => '12px',
            'table_style' => 'modern',
            'button_style' => 'flat'
        );
        return wp_parse_args(get_option('sm_appearance', array()), $default);
    }

    public static function save_appearance($data) {
        update_option('sm_appearance', $data);
    }

    public static function get_notifications() {
        $default = array(
            'email_subject' => 'تنبيه بخصوص سلوك الطالب: {student_name}',
            'email_template' => "تم تسجيل ملاحظة بخصوص الطالب: {student_name}\nنوع المخالفة: {type}\nالحدة: {severity}\nالتفاصيل: {details}\nالإجراء المتخذ: {action_taken}",
            'whatsapp_template' => "تنبيه من المدرسة: تم تسجيل ملاحظة سلوكية بحق الطالب {student_name}. نوع الملاحظة: {type}. تفاصيل: {details}. الإجراء: {action_taken}",
            'internal_template' => "إشعار نظام: تم تسجيل مخالفة {type} للطالب {student_name}. الرجاء مراجعة سجل الطالب."
        );
        return get_option('sm_notification_settings', $default);
    }

    public static function save_notifications($data) {
        update_option('sm_notification_settings', $data);
    }

    public static function get_school_info() {
        $default = array(
            'school_name' => 'مدرستي النموذجية',
            'school_principal_name' => 'أحمد علي',
            'school_logo' => '',
            'address' => 'الرياض، المملكة العربية السعودية',
            'email' => 'info@school.edu',
            'phone' => '0123456789',
            'working_schedule' => array(
                'staff' => array('mon', 'tue', 'wed', 'thu', 'fri'),
                'students' => array('mon', 'tue', 'wed', 'thu')
            ),
            'map_link' => '',
            'extra_details' => ''
        );
        return get_option('sm_school_info', $default);
    }

    public static function save_school_info($data) {
        update_option('sm_school_info', $data);
    }

    public static function get_academic_structure() {
        $default = array(
            'terms_count' => 3,
            'term_dates' => array(
                'term1' => array('start' => '', 'end' => ''),
                'term2' => array('start' => '', 'end' => ''),
                'term3' => array('start' => '', 'end' => '')
            ),
            'grades_count' => 12,
            'active_grades' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
            'grade_sections' => array(), // Per-grade sections: [grade_num => [count => 5, letters => "أ, ب..."]]
            'sections_count' => 5,
            'section_letters' => "أ, ب, ج, د, هـ",
            'academic_stages' => array(
                array('name' => 'المرحلة الابتدائية', 'start' => 1, 'end' => 4),
                array('name' => 'المرحلة المتوسطة', 'start' => 5, 'end' => 8),
                array('name' => 'المرحلة الثانوية', 'start' => 9, 'end' => 12)
            )
        );
        return wp_parse_args(get_option('sm_academic_structure', array()), $default);
    }

    public static function save_academic_structure($data) {
        update_option('sm_academic_structure', $data);
    }

    /**
     * Standardized Naming for Grades and Sections
     */
    public static function format_grade_name($grade, $section = '', $format = 'full') {
        if (empty($grade)) return '---';

        // Remove "الصف" prefix if it exists in data
        $grade_num = str_replace('الصف ', '', $grade);

        if ($format === 'short') {
            return trim($grade_num . ' ' . $section);
        }

        // Full format: "Grade + Number + Section"
        $output = 'الصف ' . $grade_num;
        if (!empty($section)) {
            $output .= ' شعبة ' . $section;
        }
        return $output;
    }

    public static function get_retention_settings() {
        $default = array(
            'message_retention_days' => 90
        );
        return get_option('sm_retention_settings', $default);
    }

    public static function save_retention_settings($data) {
        update_option('sm_retention_settings', $data);
    }

    public static function record_backup_download() {
        update_option('sm_last_backup_download', current_time('mysql'));
    }

    public static function record_backup_import() {
        update_option('sm_last_backup_import', current_time('mysql'));
    }

    public static function get_last_backup_info() {
        return array(
            'export' => get_option('sm_last_backup_download', 'لم يتم التصدير مسبقاً'),
            'import' => get_option('sm_last_backup_import', 'لم يتم الاستيراد مسبقاً')
        );
    }

    public static function get_suggested_actions() {
        $default = array(
            'low' => "تنبيه شفوي\nتسجيل ملاحظة\nنصيحة تربوية",
            'medium' => "إنذار خطي\nاستدعاء ولي أمر\nحسم درجات سلوك",
            'high' => "فصل مؤقت\nمجلس انضباط\nتعهد خطي شديد"
        );
        return get_option('sm_suggested_actions', $default);
    }

    public static function save_suggested_actions($actions) {
        update_option('sm_suggested_actions', $actions);
    }

    public static function get_disciplinary_actions() {
        return array(
            1 => 'تنبيه شفوي',
            2 => 'إنذار خطي',
            3 => 'إخطار رسمي',
            4 => 'استدعاء ولي أمر',
            5 => 'تعهد ولي أمر',
            6 => 'حسم من درجات السلوك',
            7 => 'فصل مؤقت',
            8 => 'فصل نهائي'
        );
    }

    public static function get_hierarchical_violations() {
        $default = array(
            1 => array(
                '1.1' => array('name' => 'التأخر عن الطابور الصباحي', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.2' => array('name' => 'التأخر عن بداية الحصة الدراسية', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.3' => array('name' => 'عدم الالتزام بالزي المدرسي أو الرياضي', 'points' => 2, 'action' => 'تسجيل ملاحظة'),
                '1.4' => array('name' => 'مخالفة قصات الشعر أو المظهر العام', 'points' => 2, 'action' => 'تسجيل ملاحظة'),
                '1.5' => array('name' => 'عدم إحضار الكتب أو الأدوات المدرسية', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.6' => array('name' => 'إثارة الفوضى داخل الفصل', 'points' => 2, 'action' => 'نصيحة تربوية'),
                '1.7' => array('name' => 'النوم أثناء الحصة الدراسية', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.8' => array('name' => 'تناول الطعام أو العلكة أثناء الحصص', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.9' => array('name' => 'سوء استخدام الأجهزة الإلكترونية الشخصية', 'points' => 3, 'action' => 'مصادرة المادة'),
                '1.10' => array('name' => 'إهمال الواجبات المدرسية المتكرر', 'points' => 2, 'action' => 'نصيحة تربوية'),
                '1.11' => array('name' => 'عدم اتباع تعليمات المناوبين في الساحة', 'points' => 2, 'action' => 'تنبيه شفوي'),
            ),
            2 => array(
                '2.1' => array('name' => 'الغياب عن المدرسة بدون عذر مقبول', 'points' => 4, 'action' => 'إنذار خطي واستدعاء ولي أمر'),
                '2.2' => array('name' => 'الدخول أو الخروج من الفصل بدون استئذان', 'points' => 3, 'action' => 'إنذار خطي'),
                '2.3' => array('name' => 'عدم حضور الأنشطة المدرسية الإلزامية', 'points' => 3, 'action' => 'إنذار خطي'),
                '2.4' => array('name' => 'التحريض على الشجار أو التخويف', 'points' => 5, 'action' => 'استدعاء ولي أمر وتعهد'),
                '2.5' => array('name' => 'مخالفة الزي التي تخدش قيم المدرسة', 'points' => 4, 'action' => 'إنذار خطي وتغيير الملابس'),
                '2.6' => array('name' => 'الكتابة على الجدران أو الأثاث المدرسي', 'points' => 5, 'action' => 'إصلاح الضرر وإنذار خطي'),
                '2.7' => array('name' => 'استخدام ألفاظ غير لائقة تجاه الزملاء', 'points' => 4, 'action' => 'اعتذار خطي وإنذار'),
            ),
            3 => array(
                '3.1' => array('name' => 'التنمر أو المضايقات الجسدية/اللفظية', 'points' => 10, 'action' => 'فصل مؤقت ومجلس انضباط'),
                '3.2' => array('name' => 'الغش في الامتحانات أو التزوير الأكاديمي', 'points' => 8, 'action' => 'إلغاء الدرجة وإنذار نهائي'),
                '3.3' => array('name' => 'الهروب من المدرسة أثناء الدوام الرسمي', 'points' => 12, 'action' => 'فصل مؤقت واستدعاء ولي أمر'),
                '3.6' => array('name' => 'العبث بممتلكات المدرسة أو تخريبها', 'points' => 15, 'action' => 'دفع قيمة التلفيات وفصل مؤقت'),
                '3.7' => array('name' => 'تعريض سلامة الطلاب أو الكادر للخطر', 'points' => 15, 'action' => 'مجلس انضباط وإيقاف عن الدراسة'),
                '3.8' => array('name' => 'التطاول اللفظي على أحد أعضاء الكادر', 'points' => 12, 'action' => 'اعتذار رسمي وفصل مؤقت'),
                '3.9' => array('name' => 'حيازة مواد ممنوعة (تبغ أو سجائر)', 'points' => 10, 'action' => 'مصادرة المادة وإنذار نهائي'),
                '3.10' => array('name' => 'التصوير داخل المدرسة بدون إذن', 'points' => 10, 'action' => 'حذف المحتوى ومصادرة الهاتف'),
                '3.11' => array('name' => 'التحريض على الهروب أو التغيب الجماعي', 'points' => 10, 'action' => 'استدعاء ولي أمر وفصل مؤقت'),
            ),
            4 => array(
                '4.1' => array('name' => 'الاستخدام غير القانوني لوسائل التواصل', 'points' => 20, 'action' => 'إيقاف فوري وتصعيد للجهات المختصة'),
                '4.2' => array('name' => 'حيازة أو استخدام الأسلحة أو الأدوات الحادة', 'points' => 25, 'action' => 'فصل نهائي وتصعيد أمني'),
                '4.3' => array('name' => 'السلوك الأخلاقي المشين أو التحرش', 'points' => 25, 'action' => 'فصل نهائي وتحقيق رسمي'),
                '4.4' => array('name' => 'السرقة أو الاستيلاء على ممتلكات الغير', 'points' => 20, 'action' => 'إعادة المسروقات وفصل نهائي'),
                '4.5' => array('name' => 'التخريب العمدي للمرافق الحيوية بالمدرسة', 'points' => 20, 'action' => 'تحميل التكاليف وفصل نهائي'),
                '4.6' => array('name' => 'الاعتداء الجسدي العنيف على الطلاب أو الكادر', 'points' => 25, 'action' => 'إيقاف عن الدراسة وتصعيد قانوني'),
                '4.7' => array('name' => 'ترويج أو تعاطي المخدرات والممنوعات', 'points' => 30, 'action' => 'فصل نهائي وتسليم للشرطة'),
                '4.10' => array('name' => 'الإساءة للرموز الوطنية أو الدينية', 'points' => 30, 'action' => 'فصل نهائي وتصعيد للجهات العليا'),
                '4.11' => array('name' => 'حيازة مواد مخلة بالآداب العامة', 'points' => 20, 'action' => 'فصل نهائي وتحقيق تربوي'),
                '4.12' => array('name' => 'انتحال صفة الغير في معاملات رسمية', 'points' => 15, 'action' => 'إيقاف عن الدراسة وتحقيق'),
                '4.13' => array('name' => 'التهديد المباشر بالقتل أو الأذى الجسيم', 'points' => 30, 'action' => 'فصل فوري وإبلاغ السلطات'),
                '4.14' => array('name' => 'إشعال الحرائق عمدًا داخل حرم المدرسة', 'points' => 30, 'action' => 'فصل نهائي وتحمل التبعات القانونية'),
            )
        );
        return get_option('sm_hierarchical_violations', $default);
    }

    public static function save_hierarchical_violations($data) {
        update_option('sm_hierarchical_violations', $data);
    }

    /**
     * Fetch Regulation Dynamic Info by Code
     */
    public static function get_regulation_by_code($code) {
        if (empty($code)) return false;
        $h_violations = self::get_hierarchical_violations();
        foreach ($h_violations as $level => $items) {
            if (isset($items[$code])) {
                return $items[$code];
            }
        }
        return false;
    }

    public static function get_class_security_codes() {
        $codes = get_option('sm_class_security_codes', array());
        return $codes;
    }

    public static function get_class_security_code($grade, $section) {
        $codes = self::get_class_security_codes();
        $key = $grade . '|' . $section;

        if (!isset($codes[$key])) {
            return self::reset_class_security_code($grade, $section);
        }

        return $codes[$key];
    }

    public static function reset_class_security_code($grade, $section) {
        $codes = self::get_class_security_codes();
        $key = $grade . '|' . $section;
        $new_code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $codes[$key] = $new_code;
        update_option('sm_class_security_codes', $codes);
        return $new_code;
    }

    public static function get_sections_from_db() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT DISTINCT class_name, section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY class_name ASC, section ASC");

        $structure = array();
        foreach ($results as $row) {
            $grade_num = (int)str_replace('الصف ', '', $row->class_name);
            if (!isset($structure[$grade_num])) {
                $structure[$grade_num] = array();
            }
            if (!in_array($row->section, $structure[$grade_num])) {
                $structure[$grade_num][] = $row->section;
            }
        }

        // Sort sections alphabetically for each grade
        foreach ($structure as $grade => $sections) {
            sort($structure[$grade]);
        }

        return $structure;
    }

    public static function get_timetable_settings() {
        $default = array(
            'periods' => 8,
            'days' => array('sun', 'mon', 'tue', 'wed', 'thu')
        );
        return get_option('sm_timetable_settings', $default);
    }

    public static function save_timetable_settings($data) {
        update_option('sm_timetable_settings', $data);
    }

    public static function get_sidebar_visibility() {
        $default = array(
            'sm_system_admin' => array('stats' => true, 'students' => true, 'teachers' => true, 'parents' => true, 'grades' => true, 'teacher-reports' => true, 'confiscated' => true, 'printing' => true, 'surveys' => true, 'timetables' => true, 'attendance' => true, 'lesson-plans' => true, 'assignments' => true, 'clinic' => true, 'messaging' => true, 'documents' => true),
            'sm_principal' => array('stats' => true, 'students' => true, 'teachers' => true, 'parents' => true, 'grades' => true, 'teacher-reports' => true, 'confiscated' => true, 'printing' => true, 'surveys' => true, 'timetables' => true, 'attendance' => true, 'lesson-plans' => true, 'assignments' => true, 'clinic' => true, 'messaging' => true, 'documents' => true),
            'sm_supervisor' => array('stats' => true, 'students' => true, 'teachers' => true, 'parents' => true, 'grades' => true, 'teacher-reports' => true, 'confiscated' => true, 'printing' => true, 'surveys' => true, 'timetables' => true, 'attendance' => true, 'lesson-plans' => true, 'assignments' => true, 'clinic' => true, 'messaging' => true, 'documents' => true),
            'sm_coordinator' => array('stats' => false, 'students' => false, 'teachers' => false, 'parents' => false, 'grades' => true, 'teacher-reports' => false, 'confiscated' => false, 'printing' => false, 'surveys' => false, 'timetables' => false, 'attendance' => false, 'lesson-plans' => true, 'assignments' => false, 'clinic' => false, 'messaging' => true, 'documents' => true),
            'sm_teacher' => array('stats' => true, 'students' => true, 'teachers' => false, 'parents' => false, 'grades' => true, 'teacher-reports' => false, 'confiscated' => false, 'printing' => false, 'surveys' => false, 'timetables' => false, 'attendance' => true, 'lesson-plans' => true, 'assignments' => true, 'clinic' => false, 'messaging' => true, 'documents' => true),
            'sm_student' => array('stats' => true, 'students' => false, 'teachers' => false, 'parents' => false, 'grades' => true, 'teacher-reports' => false, 'confiscated' => false, 'printing' => false, 'surveys' => false, 'timetables' => false, 'attendance' => false, 'lesson-plans' => false, 'assignments' => true, 'clinic' => false, 'messaging' => true, 'documents' => false),
            'sm_parent' => array('stats' => true, 'students' => false, 'teachers' => false, 'parents' => false, 'grades' => true, 'teacher-reports' => false, 'confiscated' => false, 'printing' => false, 'surveys' => false, 'timetables' => false, 'attendance' => false, 'lesson-plans' => false, 'assignments' => false, 'clinic' => false, 'messaging' => true, 'documents' => false),
        );
        $saved = get_option('sm_sidebar_visibility');
        if ($saved === false) return $default;

        // Merge saved settings role by role to ensure persistence
        foreach ($default as $role => $sections) {
            if (isset($saved[$role])) {
                $default[$role] = array_merge($sections, $saved[$role]);
            }
        }
        return $default;
    }

    public static function save_sidebar_visibility($data) {
        update_option('sm_sidebar_visibility', $data);
    }

    /**
     * Enforce UAE Phone Format (+971)
     */
    public static function format_uae_phone($phone) {
        if (empty($phone)) return false;

        // 1. Strip all non-digits
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // 2. Handle 050... -> 97150... (0 + 9 digits)
        if (strlen($digits) == 10 && strpos($digits, '0') === 0) {
            $digits = '971' . substr($digits, 1);
        }
        // 3. Handle 50... -> 97150... (9 digits)
        elseif (strlen($digits) == 9) {
            $digits = '971' . $digits;
        }
        // 4. Handle 97150... -> 97150... (12 digits)
        // Already handled if it was 12 digits.

        // Final Validation: Exactly 12 digits starting with 971
        if (strlen($digits) == 12 && strpos($digits, '971') === 0) {
            return $digits;
        }

        return false;
    }
}
