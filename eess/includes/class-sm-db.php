<?php

class SM_DB {
    public static function get_students($filters = array()) {
        global $wpdb;

        $user = wp_get_current_user();
        $is_teacher = in_array('sm_teacher', (array)$user->roles);
        $is_supervisor = in_array('sm_supervisor', (array)$user->roles);

        // Optimized: Reduced wildcard usage for student_code if it looks like a code
        $query = "SELECT * FROM {$wpdb->prefix}sm_students WHERE 1=1";

        $is_searching = !empty($filters['search']);

        if ($is_teacher && !current_user_can('manage_options') && !$is_searching) {
            $assigned = get_user_meta($user->ID, 'sm_assigned_sections', true);
            if (is_array($assigned) && !empty($assigned)) {
                $clauses = array();
                foreach($assigned as $pair) {
                    list($g, $s) = explode('|', $pair);
                    $clauses[] = $wpdb->prepare("(class_name = %s AND section = %s)", 'الصف '.$g, $s);
                }
                $query .= " AND (" . implode(" OR ", $clauses) . ")";
            }
        }

        if ($is_supervisor && !current_user_can('manage_options') && !$is_searching) {
            $supervised = get_user_meta($user->ID, 'sm_supervised_classes', true);
            if (is_array($supervised) && !empty($supervised)) {
                $clauses = array();
                foreach($supervised as $pair) {
                    list($g, $s) = explode('|', $pair);
                    $clauses[] = $wpdb->prepare("(class_name = %s AND section = %s)", 'الصف '.$g, $s);
                }
                $query .= " AND (" . implode(" OR ", $clauses) . ")";
            }
        }
        
        if (!empty($filters['search'])) {
            $search_str = trim($filters['search']);
            $normalized_search = self::normalize_arabic($search_str);
            $search_like = '%' . $wpdb->esc_like($normalized_search) . '%';
            $name_sql = self::get_arabic_normalized_column('name');

            if (preg_match('/^ST[0-9]+$/i', $search_str)) {
                $query .= $wpdb->prepare(" AND (student_code = %s OR $name_sql LIKE %s OR national_id = %s)", $search_str, $search_like, $search_str);
            } else {
                $query .= $wpdb->prepare(" AND ($name_sql LIKE %s OR student_code LIKE %s OR national_id LIKE %s OR guardian_phone LIKE %s OR class_name LIKE %s OR section LIKE %s)", $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
            }
        }
        
        if (!empty($filters['class_name'])) {
            $query .= $wpdb->prepare(" AND class_name = %s", $filters['class_name']);
        }

        if (!empty($filters['section'])) {
            $query .= $wpdb->prepare(" AND section = %s", $filters['section']);
        }

        if (!empty($filters['teacher_id']) && !empty($filters['include_reported'])) {
            $tid = intval($filters['teacher_id']);
            $query .= $wpdb->prepare(" AND (teacher_id = %d OR id IN (SELECT DISTINCT student_id FROM {$wpdb->prefix}sm_records WHERE teacher_id = %d))", $tid, $tid);
        } elseif (!empty($filters['teacher_id'])) {
            $query .= $wpdb->prepare(" AND teacher_id = %d", $filters['teacher_id']);
        } elseif (!empty($filters['only_reported_by_teacher'])) {
            $teacher_id = intval($filters['only_reported_by_teacher']);
            $query .= $wpdb->prepare(" AND id IN (SELECT DISTINCT student_id FROM {$wpdb->prefix}sm_records WHERE teacher_id = %d)", $teacher_id);
        }

        $query .= " ORDER BY sort_order ASC, name ASC";
        return $wpdb->get_results($query);
    }

    public static function get_next_sort_order() {
        global $wpdb;
        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}sm_students");
        return intval($max) + 1;
    }

    public static function student_exists($name, $class, $section, $national_id = null) {
        global $wpdb;

        // 1. Prioritize check by National ID
        if (!empty($national_id)) {
            $id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s",
                $national_id
            ));
            if ($id) return $id;
        }

        // 2. Fallback to Name + Class + Section
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_students WHERE name = %s AND class_name = %s AND section = %s",
            $name, $class, $section
        ));
        return $id ? $id : false;
    }

    public static function generate_student_code() {
        global $wpdb;
        // Search for the highest numeric student_code
        $last_code = $wpdb->get_var("SELECT student_code FROM {$wpdb->prefix}sm_students WHERE student_code REGEXP '^[0-9]+$' ORDER BY CAST(student_code AS UNSIGNED) DESC LIMIT 1");

        if (!$last_code) {
            return '00001';
        }

        $next_number = intval($last_code) + 1;
        return str_pad($next_number, 5, '0', STR_PAD_LEFT);
    }

    public static function add_student($name, $class, $email, $code = '', $parent_user_id = null, $teacher_id = null, $section = '', $extra = array()) {
        global $wpdb;

        if (empty($code)) {
            $code = self::generate_student_code();
        }

        // AUTO-GENERATE UNIFIED WP USER (Student)
        if (!$parent_user_id) {
            $username = $code;
            if (!username_exists($username)) {
                // 10-digit numeric password
                $password = '';
                for($i=0; $i<10; $i++) $password .= rand(0,9);

                $email_addr = $code . '@school-system.local'; // Automated email generation

                $user_id = wp_create_user($username, $password, $email_addr);
                if (!is_wp_error($user_id)) {
                    $wp_user = new WP_User($user_id);
                    $wp_user->set_role('sm_student');
                    $parent_user_id = $user_id;

                    update_user_meta($user_id, 'sm_temp_pass', $password);
                    wp_update_user(array('ID' => $user_id, 'display_name' => "Student $name"));
                }
            } else {
                $u = get_user_by('login', $username);
                if ($u) $parent_user_id = $u->ID;
            }
        }

        $sort_order = isset($extra['sort_order']) ? intval($extra['sort_order']) : self::get_next_sort_order();

        SM_Logger::log('إضافة طالب', "الاسم: $name, الصف: $class, الشعبة: $section");
        $success = $wpdb->insert(
            "{$wpdb->prefix}sm_students",
            array(
                'name' => $name,
                'class_name' => $class,
                'section' => $section,
                'parent_email' => $email,
                'guardian_phone' => sanitize_text_field($extra['guardian_phone'] ?? ''),
                'nationality' => sanitize_text_field($extra['nationality'] ?? ''),
                'national_id' => sanitize_text_field($extra['national_id'] ?? ''),
                'registration_date' => !empty($extra['registration_date']) ? sanitize_text_field($extra['registration_date']) : current_time('mysql', 1),
                'student_code' => $code,
                'parent_user_id' => $parent_user_id,
                'teacher_id' => $teacher_id,
                'sort_order' => $sort_order
            )
        );
        return $success ? $wpdb->insert_id : false;
    }

    public static function update_student($id, $data) {
        global $wpdb;
        SM_Logger::log('تعديل بيانات طالب', "معرف الطالب: $id");

        $update_data = array();
        if (isset($data['name'])) $update_data['name'] = sanitize_text_field($data['name']);
        if (isset($data['class_name'])) $update_data['class_name'] = sanitize_text_field($data['class_name']);
        if (isset($data['section'])) $update_data['section'] = sanitize_text_field($data['section']);
        if (isset($data['parent_email'])) $update_data['parent_email'] = sanitize_email($data['parent_email']);
        if (isset($data['guardian_phone'])) $update_data['guardian_phone'] = sanitize_text_field($data['guardian_phone']);
        if (isset($data['nationality'])) $update_data['nationality'] = sanitize_text_field($data['nationality']);

        // Prevent modification of national_id after creation
        $existing = self::get_student_by_id($id);
        if (isset($data['national_id']) && (empty($existing->national_id) || $existing->national_id === $data['national_id'])) {
            $update_data['national_id'] = sanitize_text_field($data['national_id']);
        }

        if (isset($data['registration_date'])) $update_data['registration_date'] = sanitize_text_field($data['registration_date']);
        if (isset($data['student_code'])) $update_data['student_code'] = sanitize_text_field($data['student_code']);

        if (isset($data['parent_user_id'])) $update_data['parent_user_id'] = !empty($data['parent_user_id']) ? intval($data['parent_user_id']) : null;
        if (isset($data['teacher_id'])) $update_data['teacher_id'] = !empty($data['teacher_id']) ? intval($data['teacher_id']) : null;

        if (empty($update_data)) return false;

        return $wpdb->update(
            "{$wpdb->prefix}sm_students",
            $update_data,
            array('id' => $id)
        );
    }

    public static function update_record($id, $data) {
        global $wpdb;
        SM_Logger::log('تعديل مخالفة', "معرف السجل: $id");
        return $wpdb->update(
            "{$wpdb->prefix}sm_records",
            array(
                'type' => sanitize_text_field($data['type']),
                'severity' => sanitize_text_field($data['severity']),
                'degree' => intval($data['degree'] ?? 1),
                'violation_code' => sanitize_text_field($data['violation_code'] ?? ''),
                'classification' => sanitize_text_field($data['classification'] ?? 'general'),
                'points' => intval($data['points'] ?? 0),
                'details' => sanitize_textarea_field($data['details']),
                'action_taken' => sanitize_text_field($data['action_taken'])
            ),
            array('id' => $id)
        );
    }

    public static function add_record($data, $skip_log = false) {
        global $wpdb;
        $user = wp_get_current_user();
        $status = 'accepted';
        if (in_array('sm_teacher', (array) $user->roles) && !current_user_can('إدارة_المستخدمين')) {
            $status = 'pending';
        }

        $student_id = intval($data['student_id']);
        $violation_code = sanitize_text_field($data['violation_code'] ?? '');
        $degree = intval($data['degree'] ?? 1);
        $points = intval($data['points'] ?? 0);
        $created_at = !empty($data['custom_date']) ? sanitize_text_field($data['custom_date']) . ' ' . current_time('H:i:s') : current_time('mysql');

        // Recurrence Tracking
        $recurrence = 1;
        if (!empty($violation_code)) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sm_records WHERE student_id = %d AND violation_code = %s",
                $student_id, $violation_code
            ));
            $recurrence = intval($count) + 1;
        }

        // Automatic Escalation (e.g. double points on 3rd recurrence)
        if ($recurrence >= 3) {
            $points = floor($points * 1.5);
            $data['action_taken'] .= ' (تكرار للمرة الثالثة - تصعيد تلقائي)';
        }

        if (!$skip_log) {
            SM_Logger::log('تسجيل مخالفة', "معرف الطالب: $student_id، النوع: {$data['type']}، الدرجة: $degree");
        }

        $inserted = $wpdb->insert(
            "{$wpdb->prefix}sm_records",
            array(
                'student_id' => $student_id,
                'teacher_id' => get_current_user_id(),
                'type' => sanitize_text_field($data['type']),
                'classification' => sanitize_text_field($data['classification'] ?? 'general'),
                'severity' => sanitize_text_field($data['severity']),
                'degree' => $degree,
                'violation_code' => $violation_code,
                'points' => $points,
                'recurrence_count' => $recurrence,
                'details' => sanitize_textarea_field($data['details']),
                'action_taken' => sanitize_text_field($data['action_taken']),
                'status' => $status,
                'created_at' => $created_at
            )
        );

        if ($inserted && $status === 'accepted') {
            // Update student points and case file
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}sm_students SET behavior_points = behavior_points + %d WHERE id = %d",
                $points, $student_id
            ));

            $total_points = $wpdb->get_var($wpdb->prepare("SELECT behavior_points FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));

            // Thresholds for Student Case File
            if ($total_points >= 20 || ($degree >= 3 && $recurrence >= 1)) {
                $wpdb->update("{$wpdb->prefix}sm_students", array('case_file_active' => 1), array('id' => $student_id));
                SM_Logger::log('فتح ملف حالة طالب', "معرف الطالب: $student_id بسبب وصول النقاط إلى $total_points");
            }
        }

        return $inserted ? $wpdb->insert_id : false;
    }

    public static function get_record_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.name as student_name, s.class_name, s.section, s.student_code FROM {$wpdb->prefix}sm_records r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE r.id = %d",
            $id
        ));
    }

    public static function get_records($filters = array()) {
        global $wpdb;
        $query = "SELECT r.*, s.name as student_name, s.class_name, s.section, s.guardian_phone, s.parent_email, s.student_code FROM {$wpdb->prefix}sm_records r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE 1=1";
        
        if (!empty($filters['student_id'])) {
            $query .= $wpdb->prepare(" AND r.student_id = %d", $filters['student_id']);
        }

        if (!empty($filters['search'])) {
            $search_str = trim($filters['search']);
            $normalized_search = self::normalize_arabic($search_str);
            $search_like = '%' . $wpdb->esc_like($normalized_search) . '%';
            $name_sql = self::get_arabic_normalized_column('s.name');

            $query .= $wpdb->prepare(" AND ($name_sql LIKE %s OR s.student_code LIKE %s)", $search_like, $search_like);
        }

        if (!empty($filters['class_name'])) {
            $query .= $wpdb->prepare(" AND s.class_name = %s", $filters['class_name']);
        }

        if (!empty($filters['section'])) {
            $query .= $wpdb->prepare(" AND s.section = %s", $filters['section']);
        }

        if (!empty($filters['teacher_id'])) {
            $query .= $wpdb->prepare(" AND r.teacher_id = %d", $filters['teacher_id']);
        }

        if (!empty($filters['type'])) {
            $query .= $wpdb->prepare(" AND r.type = %s", $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query .= $wpdb->prepare(" AND r.status = %s", $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query .= $wpdb->prepare(" AND r.created_at >= %s", $filters['start_date'] . ' 00:00:00');
        }

        if (!empty($filters['end_date'])) {
            $query .= $wpdb->prepare(" AND r.created_at <= %s", $filters['end_date'] . ' 23:59:59');
        }
        
        $query .= " ORDER BY r.created_at DESC";

        if (!empty($filters['limit'])) {
            $query .= $wpdb->prepare(" LIMIT %d", $filters['limit']);
        }

        return $wpdb->get_results($query);
    }

    public static function mark_record_contacted($record_id) {
        global $wpdb;
        return $wpdb->update(
            "{$wpdb->prefix}sm_records",
            array('contacted' => 1),
            array('id' => intval($record_id))
        );
    }

    public static function update_record_status($record_id, $status) {
        global $wpdb;
        SM_Logger::log('تحديث حالة المخالفة', "المعرف: $record_id، الحالة الجديدة: $status");
        return $wpdb->update(
            "{$wpdb->prefix}sm_records",
            array('status' => sanitize_text_field($status)),
            array('id' => intval($record_id))
        );
    }

    public static function delete_record($id) {
        global $wpdb;
        $record = self::get_record_by_id($id);
        if ($record) {
            SM_Logger::log('حذف مخالفة', 'ROLLBACK_DATA:' . json_encode(array('table' => 'records', 'data' => $record)));

            $deleted = $wpdb->delete("{$wpdb->prefix}sm_records", array('id' => $id));
            if ($deleted && $record->status === 'accepted') {
                // Synchronize points: deduct the points of the deleted violation
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}sm_students SET behavior_points = GREATEST(0, behavior_points - %d) WHERE id = %d",
                    $record->points, $record->student_id
                ));
            }
            return $deleted;
        }
        return false;
    }

    public static function delete_student($id) {
        global $wpdb;
        $student = self::get_student_by_id($id);
        if ($student) {
            SM_Logger::log('حذف طالب', 'ROLLBACK_DATA:' . json_encode(array('table' => 'students', 'data' => $student)));

            // Delete WP user
            if ($student->parent_user_id) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user($student->parent_user_id);
            }

            $wpdb->delete("{$wpdb->prefix}sm_records", array('student_id' => $id));
            $wpdb->delete("{$wpdb->prefix}sm_attendance", array('student_id' => $id));
            $wpdb->delete("{$wpdb->prefix}sm_clinic", array('student_id' => $id));
            $wpdb->delete("{$wpdb->prefix}sm_grades", array('student_id' => $id));
            $wpdb->delete("{$wpdb->prefix}sm_assignments", array('student_id' => $id));

            return $wpdb->delete("{$wpdb->prefix}sm_students", array('id' => $id));
        }
        return false;
    }

    public static function delete_all_data() {
        global $wpdb;
        SM_Logger::log('حذف كافة البيانات');
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
    }

    public static function get_student_by_code($code) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $code));
    }

    public static function get_backup_data() {
        global $wpdb;
        $data = array(
            'students' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_students", ARRAY_A),
            'records' => $wpdb->get_results("SELECT r.*, s.student_code FROM {$wpdb->prefix}sm_records r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id", ARRAY_A),
            'attendance' => $wpdb->get_results("SELECT a.*, s.student_code FROM {$wpdb->prefix}sm_attendance a JOIN {$wpdb->prefix}sm_students s ON a.student_id = s.id", ARRAY_A),
            'confiscated_items' => $wpdb->get_results("SELECT c.*, s.student_code FROM {$wpdb->prefix}sm_confiscated_items c JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id", ARRAY_A)
        );
        return json_encode($data);
    }

    public static function restore_backup($json) {
        global $wpdb;
        $data = json_decode($json, true);
        if (!$data) return false;

        // Cache for student code -> local ID
        $student_map = array();

        // 1. Process Students First
        if (isset($data['students'])) {
            foreach ($data['students'] as $student) {
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $student['student_code']));
                if ($exists) {
                    unset($student['id']);
                    $wpdb->update("{$wpdb->prefix}sm_students", $student, array('id' => $exists));
                    $student_map[$student['student_code']] = $exists;
                } else {
                    unset($student['id']);
                    $wpdb->insert("{$wpdb->prefix}sm_students", $student);
                    $student_map[$student['student_code']] = $wpdb->insert_id;
                }
            }
        }

        // Helper to get student ID by code (local)
        $get_sid = function($code) use (&$student_map, $wpdb) {
            if (isset($student_map[$code])) return $student_map[$code];
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $code));
            if ($id) $student_map[$code] = $id;
            return $id;
        };

        // 2. Process Records
        if (isset($data['records'])) {
            foreach ($data['records'] as $record) {
                $local_sid = $get_sid($record['student_code'] ?? '');
                if (!$local_sid) continue;

                $old_id = $record['id'];
                unset($record['id'], $record['student_code']);
                $record['student_id'] = $local_sid;

                // Check if this specific record exists (by time and student and type)
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}sm_records WHERE student_id = %d AND created_at = %s AND type = %s",
                    $local_sid, $record['created_at'], $record['type']
                ));

                if ($exists) {
                    $wpdb->update("{$wpdb->prefix}sm_records", $record, array('id' => $exists));
                } else {
                    $wpdb->insert("{$wpdb->prefix}sm_records", $record);
                }
            }
        }
        // 3. Process Attendance
        if (isset($data['attendance'])) {
            foreach ($data['attendance'] as $att) {
                $local_sid = $get_sid($att['student_code'] ?? '');
                if (!$local_sid) continue;

                unset($att['id'], $att['student_code']);
                $att['student_id'] = $local_sid;

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_attendance WHERE student_id = %d AND date = %s", $local_sid, $att['date']));
                if ($exists) {
                    $wpdb->update("{$wpdb->prefix}sm_attendance", $att, array('id' => $exists));
                } else {
                    $wpdb->insert("{$wpdb->prefix}sm_attendance", $att);
                }
            }
        }

        // 4. Process Confiscated Items
        if (isset($data['confiscated_items'])) {
            foreach ($data['confiscated_items'] as $item) {
                $local_sid = $get_sid($item['student_code'] ?? '');
                if (!$local_sid) continue;

                unset($item['id'], $item['student_code']);
                $item['student_id'] = $local_sid;

                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}sm_confiscated_items WHERE student_id = %d AND created_at = %s AND item_name = %s",
                    $local_sid, $item['created_at'], $item['item_name']
                ));

                if ($exists) {
                    $wpdb->update("{$wpdb->prefix}sm_confiscated_items", $item, array('id' => $exists));
                } else {
                    $wpdb->insert("{$wpdb->prefix}sm_confiscated_items", $item);
                }
            }
        }
        return true;
    }

    public static function get_statistics($filters = array()) {
        global $wpdb;
        $stats = array();
        
        $where = " WHERE 1=1";
        if (!empty($filters['teacher_id'])) {
            $where .= $wpdb->prepare(" AND r.teacher_id = %d", $filters['teacher_id']);
        }
        if (!empty($filters['student_id'])) {
            $where .= $wpdb->prepare(" AND r.student_id = %d", $filters['student_id']);
        }

        $stats['by_type'] = $wpdb->get_results("SELECT type, COUNT(*) as count FROM {$wpdb->prefix}sm_records r $where GROUP BY type");
        $stats['by_severity'] = $wpdb->get_results("SELECT severity, COUNT(*) as count FROM {$wpdb->prefix}sm_records r $where GROUP BY severity");
        $stats['by_degree'] = $wpdb->get_results("SELECT degree, COUNT(*) as count FROM {$wpdb->prefix}sm_records r $where GROUP BY degree ORDER BY degree ASC");
        $stats['by_class'] = $wpdb->get_results("SELECT s.class_name, COUNT(r.id) as count FROM {$wpdb->prefix}sm_records r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id $where GROUP BY s.class_name");
        
        if (!empty($filters['teacher_id'])) {
            $stats['total_students'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_students WHERE teacher_id = %d", $filters['teacher_id']));
        } else {
            $stats['total_students'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_students");
        }

        $stats['total_teachers'] = count(get_users(array('role' => 'sm_teacher')));
        
        // Optimized: Combined counts in a single query
        $summary_counts = $wpdb->get_row("
            SELECT
                COUNT(CASE WHEN DATE(r.created_at) = CURDATE() THEN 1 END) as violations_today,
                COUNT(CASE WHEN r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as violations_week,
                COUNT(CASE WHEN r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as violations_month,
                COUNT(CASE WHEN r.action_taken != '' THEN 1 END) as total_actions
            FROM {$wpdb->prefix}sm_records r $where
        ");

        $stats['violations_today'] = $summary_counts->violations_today;
        $stats['violations_week'] = $summary_counts->violations_week;
        $stats['violations_month'] = $summary_counts->violations_month;
        $stats['total_actions'] = $summary_counts->total_actions;

        // Today's Attendance Stats
        $today = current_time('Y-m-d');
        $attendance_counts = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_today,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_today
            FROM {$wpdb->prefix}sm_attendance WHERE date = %s
        ", $today));

        $stats['present_today'] = (int)$attendance_counts->present_today;
        $stats['absent_today']  = (int)$attendance_counts->absent_today;

        $stats['top_students'] = $wpdb->get_results("
            SELECT s.name, COUNT(r.id) as count 
            FROM {$wpdb->prefix}sm_records r 
            JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id 
            $where
            GROUP BY r.student_id 
            ORDER BY count DESC 
            LIMIT 5
        ");

        $stats['trends'] = $wpdb->get_results("
            SELECT DATE(r.created_at) as date, COUNT(*) as count
            FROM {$wpdb->prefix}sm_records r
            $where AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(r.created_at)
            ORDER BY date ASC
        ");

        return $stats;
    }

    public static function get_student_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE id = %d", $id));
    }

    public static function get_student_by_parent($parent_user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE parent_user_id = %d", $parent_user_id));
    }

    public static function get_students_by_parent($parent_user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE parent_user_id = %d", $parent_user_id));
    }

    public static function update_student_photo($id, $url) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}sm_students", array('photo_url' => $url), array('id' => $id));
    }

    public static function add_assignment($data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_assignments", array(
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'student_id' => $data['student_id'] ?? null,
            'title' => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description']),
            'file_url' => esc_url_raw($data['file_url'] ?? ''),
            'type' => sanitize_text_field($data['type'] ?? 'assignment')
        ));
    }

    public static function get_assignments($user_id, $type = 'all') {
        global $wpdb;
        $q = "SELECT a.*, u.display_name as sender_name FROM {$wpdb->prefix}sm_assignments a
              JOIN {$wpdb->prefix}users u ON a.sender_id = u.ID
              WHERE a.receiver_id = %d";
        if ($type !== 'all') {
            $q .= $wpdb->prepare(" AND a.type = %s", $type);
        }
        $q .= " ORDER BY a.created_at DESC";
        $results = $wpdb->get_results($wpdb->prepare($q, $user_id));

        foreach ($results as $res) {
            $res->specialization = get_user_meta($res->sender_id, 'sm_specialization', true);
        }

        return $results;
    }

    public static function get_staff_by_section($grade, $section) {
        $staff = get_users(array('role__in' => array('sm_teacher', 'sm_coordinator', 'sm_supervisor')));
        $filtered = array();
        foreach ($staff as $u) {
            $assigned = get_user_meta($u->ID, 'sm_assigned_sections', true) ?: (get_user_meta($u->ID, 'sm_supervised_classes', true) ?: array());
            if (is_array($assigned) && in_array("$grade|$section", $assigned)) {
                $filtered[] = $u;
            }
        }
        return $filtered;
    }

    public static function get_sent_assignments($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as receiver_name FROM {$wpdb->prefix}sm_assignments a
             JOIN {$wpdb->prefix}users u ON a.receiver_id = u.ID
             WHERE a.sender_id = %d ORDER BY a.created_at DESC",
            $user_id
        ));
    }

    public static function send_message($sender_id, $receiver_id, $message, $student_id = null) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_messages", array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'student_id' => $student_id,
            'message' => sanitize_textarea_field($message)
        ));
    }

    public static function get_messages($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name FROM {$wpdb->prefix}sm_messages m 
             JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID 
             WHERE receiver_id = %d ORDER BY created_at DESC", 
            $user_id
        ));
    }

    public static function get_conversations($user_id) {
        global $wpdb;
        // Get unique users who have messaged the current user or been messaged by them
        $query = $wpdb->prepare(
            "SELECT DISTINCT IF(sender_id = %d, receiver_id, sender_id) as other_user_id 
             FROM {$wpdb->prefix}sm_messages 
             WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        );
        $user_ids = $wpdb->get_col($query);
        
        $conversations = array();
        foreach ($user_ids as $other_id) {
            $other_user = get_userdata($other_id);
            if (!$other_user) continue;

            $last_message = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_messages 
                 WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d) 
                 ORDER BY created_at DESC LIMIT 1",
                $user_id, $other_id, $other_id, $user_id
            ));

            $conversations[] = array(
                'user' => $other_user,
                'last_message' => $last_message
            );
        }

        // Sort conversations by last message date
        usort($conversations, function($a, $b) {
            return strtotime($b['last_message']->created_at) - strtotime($a['last_message']->created_at);
        });

        return $conversations;
    }

    public static function get_conversation_messages($user1, $user2) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name FROM {$wpdb->prefix}sm_messages m 
             JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID 
             WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d) 
             ORDER BY created_at ASC",
            $user1, $user2, $user2, $user1
        ));
    }

    public static function cleanup_old_messages() {
        if (get_transient('sm_daily_cleanup_run')) {
            return;
        }

        global $wpdb;
        $settings = SM_Settings::get_retention_settings();
        $days = intval($settings['message_retention_days']);
        
        if ($days > 0) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}sm_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ));
        }

        set_transient('sm_daily_cleanup_run', true, DAY_IN_SECONDS);
    }

    public static function get_sent_messages($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as receiver_name FROM {$wpdb->prefix}sm_messages m 
             JOIN {$wpdb->prefix}users u ON m.receiver_id = u.ID 
             WHERE sender_id = %d ORDER BY created_at DESC", 
            $user_id
        ));
    }

    public static function normalize_arabic($str) {
        $search = array(
            'أ', 'إ', 'آ', 'ة', 'ى',
            'َ', 'ً', 'ُ', 'ٌ', 'ِ', 'ٍ', 'ْ', 'ّ'
        );
        $replace = array(
            'ا', 'ا', 'ا', 'ه', 'ي',
            '', '', '', '', '', '', '', ''
        );
        return str_replace($search, $replace, $str);
    }

    public static function get_arabic_normalized_column($column) {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ة', 'ه'), 'ى', 'ي'), 'َ', ''), 'ً', ''), 'ُ', ''), 'ٌ', ''), 'ِ', ''), 'ٍ', ''), 'ْ', ''), 'ّ', '')";
    }

    public static function get_student_stats($student_id) {
        global $wpdb;
        $stats = array();
        $stats['total'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_records WHERE student_id = %d", $student_id));
        $stats['points'] = $wpdb->get_var($wpdb->prepare("SELECT behavior_points FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
        $stats['case_file'] = $wpdb->get_var($wpdb->prepare("SELECT case_file_active FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
        $stats['by_type'] = $wpdb->get_results($wpdb->prepare("SELECT type, COUNT(*) as count FROM {$wpdb->prefix}sm_records WHERE student_id = %d GROUP BY type", $student_id));
        $stats['by_severity'] = $wpdb->get_results($wpdb->prepare("SELECT severity, COUNT(*) as count FROM {$wpdb->prefix}sm_records WHERE student_id = %d GROUP BY severity", $student_id));
        $stats['by_degree'] = $wpdb->get_results($wpdb->prepare("SELECT degree, COUNT(*) as count FROM {$wpdb->prefix}sm_records WHERE student_id = %d GROUP BY degree", $student_id));
        
        // Intelligence: Last action and frequent type
        $stats['last_action'] = $wpdb->get_var($wpdb->prepare("SELECT action_taken FROM {$wpdb->prefix}sm_records WHERE student_id = %d AND action_taken != '' ORDER BY created_at DESC LIMIT 1", $student_id));
        $stats['frequent_type'] = $wpdb->get_var($wpdb->prepare("SELECT type FROM {$wpdb->prefix}sm_records WHERE student_id = %d GROUP BY type ORDER BY COUNT(*) DESC LIMIT 1", $student_id));
        $stats['high_severity_count'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_records WHERE student_id = %d AND severity = 'high'", $student_id));
        
        return $stats;
    }

    // User & Teacher Management
    public static function get_teacher_data($user_id) {
        return array(
            'full_name'   => get_user_meta($user_id, 'sm_full_name', true),
            'phone'       => get_user_meta($user_id, 'sm_phone', true),
            'employee_id' => get_user_meta($user_id, 'sm_employee_id', true),
            'job_title'   => get_user_meta($user_id, 'sm_job_title', true),
        );
    }

    public static function update_teacher_data($user_id, $data) {
        if (isset($data['full_name'])) update_user_meta($user_id, 'sm_full_name', sanitize_text_field($data['full_name']));
        if (isset($data['phone'])) update_user_meta($user_id, 'sm_phone', sanitize_text_field($data['phone']));
        if (isset($data['employee_id'])) update_user_meta($user_id, 'sm_employee_id', sanitize_text_field($data['employee_id']));
        if (isset($data['teacher_id'])) update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($data['teacher_id']));
        if (isset($data['job_title'])) update_user_meta($user_id, 'sm_job_title', sanitize_text_field($data['job_title']));
        
        if (isset($data['supervised_classes'])) {
            update_user_meta($user_id, 'sm_supervised_classes', $data['supervised_classes']);
        }
        if (isset($data['assigned_sections'])) {
            update_user_meta($user_id, 'sm_assigned_sections', $data['assigned_sections']);
        }

        if (!empty($data['display_name'])) {
            wp_update_user(array('ID' => $user_id, 'display_name' => $data['display_name']));
        }
    }

    public static function add_system_user($data) {
        $pass = $data['user_pass'];
        if (empty($pass)) {
            $pass = '';
            for($i=0; $i<10; $i++) $pass .= rand(0,9);
        }

        $user_id = wp_create_user($data['user_login'], $pass, $data['user_email']);
        if (is_wp_error($user_id)) return $user_id;

        update_user_meta($user_id, 'sm_temp_pass', $pass);

        $user = new WP_User($user_id);
        $user->set_role($data['role']);

        // Update meta for any staff role
        self::update_teacher_data($user_id, $data);

        return $user_id;
    }

    public static function delete_system_user($user_id) {
        if (get_current_user_id() == $user_id) return false;
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        return wp_delete_user($user_id);
    }

    public static function get_confiscated_items($filters = array()) {
        global $wpdb;
        $query = "SELECT c.*, s.name as student_name, s.class_name, s.section FROM {$wpdb->prefix}sm_confiscated_items c
                  JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id WHERE 1=1";
        if (!empty($filters['student_id'])) {
            $query .= $wpdb->prepare(" AND c.student_id = %d", $filters['student_id']);
        }
        if (!empty($filters['status'])) {
            $query .= $wpdb->prepare(" AND c.status = %s", $filters['status']);
        }
        $query .= " ORDER BY c.created_at DESC";
        return $wpdb->get_results($query);
    }

    public static function add_confiscated_item($data) {
        global $wpdb;
        SM_Logger::log('تسجيل مادة مصادرة', "الطالب: {$data['student_id']}، المادة: {$data['item_name']}");
        return $wpdb->insert(
            "{$wpdb->prefix}sm_confiscated_items",
            array(
                'student_id' => intval($data['student_id']),
                'item_name' => sanitize_text_field($data['item_name']),
                'holding_period' => intval($data['holding_period']),
                'is_returnable' => isset($data['is_returnable']) ? 1 : 0,
                'status' => 'held'
            )
        );
    }

    public static function update_confiscated_item_status($id, $status) {
        global $wpdb;
        SM_Logger::log('تحديث حالة مادة مصادرة', "المعرف: $id، الحالة: $status");
        return $wpdb->update(
            "{$wpdb->prefix}sm_confiscated_items",
            array('status' => $status),
            array('id' => $id)
        );
    }

    public static function delete_confiscated_item($id) {
        global $wpdb;
        SM_Logger::log('حذف مادة مصادرة', "المعرف: $id");
        return $wpdb->delete("{$wpdb->prefix}sm_confiscated_items", array('id' => $id));
    }

    public static function get_pending_reports_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_records WHERE status = 'pending'");
    }

    public static function get_expired_items_count() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT created_at, holding_period FROM {$wpdb->prefix}sm_confiscated_items WHERE status = 'held'");
        $count = 0;
        foreach ($items as $item) {
            $expires = strtotime($item->created_at) + ($item->holding_period * 24 * 60 * 60);
            if ($expires <= time()) $count++;
        }
        return $count;
    }

    // Subject Management
    public static function get_subjects($grade_id = null) {
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}sm_subjects";
        if ($grade_id) {
            $query .= $wpdb->prepare(" WHERE grade_id = %d", $grade_id);
        }
        return $wpdb->get_results($query);
    }

    public static function add_subject($name, $grade_ids) {
        global $wpdb;
        if (!is_array($grade_ids)) $grade_ids = array($grade_ids);

        $success = 0;
        foreach ($grade_ids as $gid) {
            $res = $wpdb->insert("{$wpdb->prefix}sm_subjects", array('name' => sanitize_text_field($name), 'grade_id' => intval($gid)));
            if ($res) $success++;
        }
        return $success > 0;
    }

    public static function delete_subject($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}sm_subjects", array('id' => $id));
    }

    // Survey Management
    public static function add_survey($title, $questions, $target_roles, $user_id) {
        global $wpdb;
        $success = $wpdb->insert("{$wpdb->prefix}sm_surveys", array(
            'title' => sanitize_text_field($title),
            'target_roles' => sanitize_text_field($target_roles),
            'questions' => is_array($questions) ? json_encode($questions) : $questions,
            'created_by' => $user_id,
            'status' => 'active'
        ));
        return $success ? $wpdb->insert_id : false;
    }

    public static function get_surveys($role = null) {
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}sm_surveys WHERE status = 'active'";
        if ($role) {
            if ($role === 'all') {
                $query .= " AND target_roles = 'all'";
            } else {
                $query .= $wpdb->prepare(" AND (target_roles = 'all' OR target_roles LIKE %s)", '%' . $wpdb->esc_like($role) . '%');
            }
        }
        $query .= " ORDER BY created_at DESC";
        return $wpdb->get_results($query);
    }

    public static function get_survey($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_surveys WHERE id = %d", $id));
    }

    public static function save_survey_response($survey_id, $user_id, $responses) {
        global $wpdb;
        // Check if already responded
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_survey_responses WHERE survey_id = %d AND user_id = %d", $survey_id, $user_id));
        if ($exists) return false;

        return $wpdb->insert("{$wpdb->prefix}sm_survey_responses", array(
            'survey_id' => $survey_id,
            'user_id' => $user_id,
            'responses' => is_array($responses) ? json_encode($responses) : $responses
        ));
    }

    public static function get_survey_responses($survey_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT r.*, u.display_name FROM {$wpdb->prefix}sm_survey_responses r JOIN {$wpdb->prefix}users u ON r.user_id = u.ID WHERE r.survey_id = %d", $survey_id));
    }

    public static function get_survey_results($survey_id) {
        $responses = self::get_survey_responses($survey_id);
        $survey = self::get_survey($survey_id);
        if (!$survey) return array();

        $questions = json_decode($survey->questions, true);
        $results = array();
        foreach($questions as $index => $q) {
            $results[$index] = array('question' => $q, 'answers' => array());
        }

        foreach ($responses as $r) {
            $ans_data = json_decode($r->responses, true);
            foreach ($ans_data as $index => $val) {
                if (isset($results[$index])) {
                    if (!isset($results[$index]['answers'][$val])) $results[$index]['answers'][$val] = 0;
                    $results[$index]['answers'][$val]++;
                }
            }
        }
        return $results;
    }

    // Timetable Management
    public static function update_timetable($class, $section, $day, $period, $subject_id, $teacher_id) {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_timetables WHERE class_name = %s AND section = %s AND day = %s AND period = %d", $class, $section, $day, $period));
        if ($exists) {
            return $wpdb->update("{$wpdb->prefix}sm_timetables", array('subject_id' => $subject_id, 'teacher_id' => $teacher_id), array('id' => $exists));
        } else {
            return $wpdb->insert("{$wpdb->prefix}sm_timetables", array(
                'class_name' => $class,
                'section' => $section,
                'day' => $day,
                'period' => $period,
                'subject_id' => $subject_id,
                'teacher_id' => $teacher_id
            ));
        }
    }

    public static function get_timetable($class, $section) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT t.*, s.name as subject_name, u.display_name as teacher_name FROM {$wpdb->prefix}sm_timetables t JOIN {$wpdb->prefix}sm_subjects s ON t.subject_id = s.id JOIN {$wpdb->prefix}users u ON t.teacher_id = u.ID WHERE t.class_name = %s AND t.section = %s ORDER BY t.day, t.period", $class, $section));
    }

    // Attendance Management
    public static function get_attendance_summary($date) {
        global $wpdb;
        $db_structure = SM_Settings::get_sections_from_db();
        $summary = array();

        if (!is_array($db_structure) || empty($db_structure)) {
            return $summary;
        }

        $user = wp_get_current_user();
        $is_teacher = in_array('sm_teacher', (array)$user->roles);
        $is_supervisor = in_array('sm_supervisor', (array)$user->roles);
        $assigned = ($is_teacher) ? get_user_meta($user->ID, 'sm_assigned_sections', true) : array();
        $supervised = ($is_supervisor) ? get_user_meta($user->ID, 'sm_supervised_classes', true) : array();

        ksort($db_structure, SORT_NUMERIC);

        foreach ($db_structure as $grade_num => $sections) {
            $class_name = 'الصف ' . $grade_num;

            foreach ($sections as $section) {
                // Filter for Teacher/Supervisor
                if ($is_teacher && !current_user_can('manage_options')) {
                    if (!is_array($assigned) || !in_array("$grade_num|$section", $assigned)) continue;
                }
                if ($is_supervisor && !current_user_can('manage_options')) {
                    if (!is_array($supervised) || !in_array("$grade_num|$section", $supervised)) continue;
                }

                // Count students
                $student_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}sm_students WHERE class_name = %s AND section = %s",
                    $class_name, $section
                ));

                if ($student_count == 0) continue;

                // Get attendance stats for this date
                $attendance_stats = $wpdb->get_results($wpdb->prepare(
                    "SELECT a.status, COUNT(*) as count
                     FROM {$wpdb->prefix}sm_attendance a
                     JOIN {$wpdb->prefix}sm_students s ON a.student_id = s.id
                     WHERE s.class_name = %s AND s.section = %s AND a.date = %s
                     GROUP BY a.status",
                    $class_name, $section, $date
                ));

                $stats = array('present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total_marked' => 0);
                foreach ($attendance_stats as $as) {
                    $stats[$as->status] = (int)$as->count;
                    $stats['total_marked'] += (int)$as->count;
                }

                $summary[] = array(
                    'grade' => $grade_num,
                    'class_name' => $class_name,
                    'section' => $section,
                    'student_count' => $student_count,
                    'stats' => $stats,
                    'is_complete' => ($stats['total_marked'] >= $student_count),
                    'has_absences' => ($stats['absent'] > 0 || $stats['late'] > 0 || $stats['excused'] > 0)
                );
            }
        }

        return $summary;
    }

    public static function get_students_attendance($class_name, $section, $date) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT s.id, s.name, s.student_code, s.photo_url, a.status, a.id as attendance_id
             FROM {$wpdb->prefix}sm_students s
             LEFT JOIN {$wpdb->prefix}sm_attendance a ON s.id = a.student_id AND a.date = %s
             WHERE s.class_name = %s AND s.section = %s
             ORDER BY s.name ASC",
            $date, $class_name, $section
        );
        return $wpdb->get_results($query);
    }

    public static function save_attendance($student_id, $status, $date, $teacher_id) {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_attendance WHERE student_id = %d AND date = %s",
            $student_id, $date
        ));

        if ($exists) {
            $updated = $wpdb->update(
                "{$wpdb->prefix}sm_attendance",
                array('status' => $status, 'teacher_id' => $teacher_id),
                array('id' => $exists)
            );
        } else {
            $updated = $wpdb->insert(
                "{$wpdb->prefix}sm_attendance",
                array(
                    'student_id' => $student_id,
                    'status' => $status,
                    'date' => $date,
                    'teacher_id' => $teacher_id
                )
            );
        }

        if ($updated !== false) {
            self::sync_attendance_to_violations($student_id, $status, $date);
            return true;
        }

        return false;
    }

    /**
     * Centralized System: Sync Attendance status to Violation Records
     */
    private static function sync_attendance_to_violations($student_id, $status, $date) {
        global $wpdb;
        $violation_type = 'absence';
        $details = "غائب في $date";

        if ($status === 'absent') {
            // Check if violation already exists for this absence
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_records WHERE student_id = %d AND type = %s AND details = %s",
                $student_id, $violation_type, $details
            ));

            if (!$existing) {
                self::add_record(array(
                    'student_id'   => $student_id,
                    'type'         => $violation_type,
                    'severity'     => 'low',
                    'degree'       => 1,
                    'details'      => $details,
                    'action_taken' => 'تسجيل تلقائي للغياب',
                    'custom_date'  => $date
                ), true);
            }
        } else {
            // If status is NOT absent, remove any auto-generated absence violation for this date
            // Use delete_record to ensure points are synchronized
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_records WHERE student_id = %d AND type = %s AND details = %s",
                $student_id, $violation_type, $details
            ));

            if ($existing_id) {
                self::delete_record($existing_id);
            }
        }
    }

    // Student Metadata
    public static function update_student_meta($student_id, $key, $value) {
        global $wpdb;
        $table = "{$wpdb->prefix}sm_student_meta";
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE student_id = %d AND meta_key = %s", $student_id, $key));

        if ($exists) {
            return $wpdb->update($table, array('meta_value' => $value), array('id' => $exists));
        } else {
            return $wpdb->insert($table, array('student_id' => $student_id, 'meta_key' => $key, 'meta_value' => $value));
        }
    }

    public static function get_student_meta($student_id, $key, $single = true) {
        global $wpdb;
        $table = "{$wpdb->prefix}sm_student_meta";
        if ($single) {
            return $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $table WHERE student_id = %d AND meta_key = %s", $student_id, $key));
        }
        return $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $table WHERE student_id = %d AND meta_key = %s", $student_id, $key));
    }

    // Filtered Logs
    public static function get_filtered_logs($filters = array()) {
        global $wpdb;
        $query = "SELECT l.*, u.display_name FROM {$wpdb->prefix}sm_logs l LEFT JOIN {$wpdb->base_prefix}users u ON l.user_id = u.ID WHERE 1=1";
        
        if (!empty($filters['user_id'])) {
            $query .= $wpdb->prepare(" AND l.user_id = %d", $filters['user_id']);
        }
        if (!empty($filters['start_date'])) {
            $query .= $wpdb->prepare(" AND l.created_at >= %s", $filters['start_date'] . ' 00:00:00');
        }
        if (!empty($filters['end_date'])) {
            $query .= $wpdb->prepare(" AND l.created_at <= %s", $filters['end_date'] . ' 23:59:59');
        }
        if (!empty($filters['action'])) {
            $action = '%' . $wpdb->esc_like($filters['action']) . '%';
            $query .= $wpdb->prepare(" AND l.action LIKE %s", $action);
        }

        $query .= " ORDER BY l.created_at DESC LIMIT 500";
        return $wpdb->get_results($query);
    }
}
