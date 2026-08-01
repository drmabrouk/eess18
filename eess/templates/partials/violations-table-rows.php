<?php if (!defined('ABSPATH')) exit; ?>
<?php if (empty($records)): ?>
    <tr>
        <td colspan="8" style="padding: 60px; text-align: center; color: var(--sm-text-gray);">
            <span class="dashicons dashicons-clipboard" style="font-size:48px; width:48px; height:48px; margin-bottom:15px;"></span>
            <p>لا توجد سجلات مطابقة حالياً.</p>
        </td>
    </tr>
<?php else: ?>
    <?php
    $type_labels = SM_Settings::get_violation_types();
    $severity_labels = SM_Settings::get_severities();
    $current_user = wp_get_current_user();
    $sender_name = $current_user->display_name;

    foreach ($records as $row):
        // Dynamic Linking
        $reg = SM_Settings::get_regulation_by_code($row->violation_code);
        $display_type = $reg ? $reg['name'] : $row->type;
        $display_action = $reg ? $reg['action'] : $row->action_taken;

        $msg_text = "*السلام عليكم ورحمة الله وبركاته،*\n\n";
        $msg_text .= "إلى ولي أمر الطالب/ة: *{$row->student_name}*\n";
        $msg_text .= "الصف والشعبة: " . SM_Settings::format_grade_name($row->class_name, $row->section, 'short') . "\n";
        $msg_text .= "نوع الملاحظة: *{$display_type}*\n\n";

        $msg_text .= "نرجو منكم المتابعة مع الإدارة.\n\n";
        $msg_text .= "*وتقبلوا فائق الاحترام والتقدير،*\n";
        $msg_text .= "{$sender_name}";

        $waMsg = rawurlencode($msg_text);
        $raw_phone = $row->guardian_phone ?? '';
        $formatted_phone = SM_Settings::format_uae_phone($raw_phone);
    ?>
        <tr id="record-row-<?php echo $row->id; ?>">
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div>
                        <div style="font-weight: 800;"><?php echo esc_html($row->student_name); ?></div>
                        <div style="font-size: 11px; color: var(--sm-text-gray);"><?php echo SM_Settings::format_grade_name($row->class_name, $row->section, 'short'); ?></div>
                    </div>
                    <?php if (current_user_can('إدارة_الطلاب')): ?>
                        <button onclick='editSmStudentFromStats(<?php echo json_encode(array(
                            "id" => $row->student_id,
                            "name" => $row->student_name,
                            "class_name" => $row->class_name,
                            "section" => $row->section,
                            "parent_email" => $row->parent_email ?? "",
                            "guardian_phone" => $row->guardian_phone ?? "",
                            "student_id" => $row->student_code
                        )); ?>)' class="sm-btn sm-btn-outline" style="width: 24px; height: 24px; padding: 0; min-width: 24px; font-size: 10px;" title="تعديل بيانات الطالب">
                            <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; margin: 0;"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
            <td><?php echo date('Y-m-d', strtotime($row->created_at)); ?></td>
            <td style="text-align:center;"><span style="font-weight:900; color:var(--sm-primary-color);"><?php echo (int)$row->degree; ?></span></td>
            <td>
                <div style="font-weight:600;"><?php echo esc_html($row->violation_code); ?></div>
                <div style="font-size:11px; color:#718096;"><?php echo $display_type; ?></div>
            </td>
            <td style="text-align:center;"><span class="sm-badge" style="background:#edf2f7; color:#4a5568;"><?php echo (int)$row->recurrence_count; ?></span></td>
            <td>
                <span class="sm-badge sm-badge-<?php echo esc_attr($row->severity); ?>">
                    <?php echo $severity_labels[$row->severity] ?? $row->severity; ?>
                </span>
                <?php if (!empty($row->contacted)): ?>
                    <div style="margin-top: 5px; font-size: 10px; color: #38a169; font-weight: 800; display: flex; align-items: center; gap: 3px;">
                        <span class="dashicons dashicons-whatsapp" style="font-size: 14px; width: 14px; height: 14px;"></span> تم التواصل
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=single_violation&record_id=' . $row->id); ?>" target="_blank" class="sm-btn sm-btn-outline" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="طباعة"><span class="dashicons dashicons-printer" style="margin:0;"></span></a>
                    <?php if (current_user_can('إدارة_المخالفات')): ?>
                        <button onclick="editSmRecord(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="sm-btn sm-btn-outline" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="تعديل"><span class="dashicons dashicons-edit" style="margin:0;"></span></button>
                        <button onclick="confirmDeleteRecord(<?php echo $row->id; ?>)" class="sm-btn sm-btn-outline" style="width: 32px; height: 32px; padding: 0; color:#e53e3e; display: flex; align-items: center; justify-content: center;" title="حذف"><span class="dashicons dashicons-trash" style="margin:0;"></span></button>
                    <?php endif; ?>

                    <?php if ($formatted_phone): ?>
                        <a href="https://wa.me/<?php echo $formatted_phone; ?>?text=<?php echo $waMsg; ?>"
                           target="_blank"
                           onclick="markAsContacted(<?php echo $row->id; ?>)"
                           class="sm-btn sm-btn-outline"
                           style="width: 32px; height: 32px; padding: 0; color:#38a169; display: flex; align-items: center; justify-content: center;"
                           title="واتساب">
                           <span class="dashicons dashicons-whatsapp" style="margin:0;"></span>
                        </a>
                    <?php else: ?>
                        <button onclick="alert('<?php echo empty($raw_phone) ? 'رقم هاتف ولي الأمر غير مسجل في سجل الطالب' : 'صيغة رقم الهاتف غير صحيحة (يجب أن يكون رقماً إماراتياً)'; ?>')" class="sm-btn sm-btn-outline" style="width: 32px; height: 32px; padding: 0; color:#cbd5e0; display: flex; align-items: center; justify-content: center;" title="واتساب (رقم مفقود أو غير صالح)"><span class="dashicons dashicons-whatsapp" style="margin:0;"></span></button>
                    <?php endif; ?>
                </div>
                <?php if ($row->status === 'pending' && current_user_can('إدارة_المخالفات')): ?>
                    <div style="margin-top: 8px; display: flex; gap: 5px; justify-content: flex-end;">
                        <button onclick="updateRecordStatus(<?php echo $row->id; ?>, 'accepted')" class="sm-btn" style="background: #38a169; font-size: 10px; padding: 0 10px; height: 28px; width: auto;">اعتماد</button>
                        <button onclick="updateRecordStatus(<?php echo $row->id; ?>, 'rejected')" class="sm-btn" style="background: #e53e3e; font-size: 10px; padding: 0 10px; height: 28px; width: auto;">رفض</button>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
