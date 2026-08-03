<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 20px;">مراجعة بلاغات المعلمين المعلقة</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">التحقق من المخالفات المرصودة من المعلمين واعتمادها أو تعديل الإجراء المتخذ</p>
        </div>
        <span class="sm-badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 16px; font-size: 11px; font-weight: 800; border-radius: 9999px;">
            <?php echo count($records); ?> بلاغ بانتظار الاعتماد
        </span>
    </div>

    <!-- Alert Banner -->
    <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 18px; margin-bottom: 30px; color: #b91c1c; font-size: 12px; font-weight: 700; display: flex; align-items: flex-start; gap: 12px; line-height: 1.6;">
        <span class="dashicons dashicons-warning" style="font-size: 18px; width: 18px; height: 18px; margin-top: 2px; color: #b91c1c;"></span>
        <div>
            <strong style="font-size: 13px; display: block; margin-bottom: 3px; color: #7f1d1d;">تنبيه الأمان والامتثال</strong>
            البلاغات والمسودات المعروضة بالجدول أدناه تم رصدها وإرسالها بواسطة معلمي الفصول وتتطلب مراجعة أو اعتماد فوري من إدارة المدرسة أو وكيل شؤون الطلاب لتقييدها رسمياً في ملف الطالب السلوكي.
        </div>
    </div>

    <div class="sm-table-container" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; box-shadow: var(--sm-shadow); overflow: hidden; margin-bottom: 30px;">
        <table class="sm-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--sm-bg-light); border-bottom: 1px solid var(--sm-border-color);">
                    <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">الطالب المستهدف</th>
                    <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">مقدم البلاغ</th>
                    <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">تاريخ الرصد</th>
                    <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">بند المخالفة</th>
                    <th style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--sm-dark-color); max-width: 250px;">التفاصيل وملاحظات المعلم</th>
                    <th style="padding: 15px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">مستوى الحدة</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--sm-dark-color);">الخيارات المتاحة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="7" style="padding: 70px 20px; text-align: center;">
                            <div style="width: 50px; height: 50px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: #16a34a; border: 1px solid #bbf7d0;">
                                <span class="dashicons dashicons-yes-alt" style="font-size: 24px; width: 24px; height: 24px;"></span>
                            </div>
                            <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: var(--sm-dark-color);">لا توجد بلاغات معلقة حالياً</h4>
                            <p style="margin: 5px 0 0 0; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">سجل البلاغات نظيف بالكامل، شكراً لتعاونكم مع الهيئة التعليمية!</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $type_labels = SM_Settings::get_violation_types();
                    $severity_labels = SM_Settings::get_severities();
                    foreach ($records as $row):
                        $teacher = get_userdata($row->teacher_id);
                        $reg = SM_Settings::get_regulation_by_code($row->violation_code);
                        $display_type = $reg ? $reg['name'] : ($type_labels[$row->type] ?? $row->type);
                    ?>
                        <tr style="border-bottom: 1px solid var(--sm-border-color); transition: background 0.2s;" onmouseover="this.style.background='var(--sm-bg-light)'" onmouseout="this.style.background='#ffffff'">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 800; color: var(--sm-dark-color); font-size: 12px;"><?php echo esc_html($row->student_name); ?></div>
                                <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700; margin-top: 3px;"><?php echo esc_html($row->class_name); ?></div>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 700; color: var(--sm-primary-color); font-size: 12px;">
                                <?php echo $teacher ? esc_html($teacher->display_name) : 'غير معروف'; ?>
                            </td>
                            <td style="padding: 15px 20px; font-size: 11px; color: var(--sm-text-gray); font-weight: 600;">
                                <?php echo date_i18n('Y-m-d H:i', strtotime($row->created_at)); ?>
                            </td>
                            <td style="padding: 15px 20px; font-size: 12px; font-weight: 700; color: var(--sm-dark-color);">
                                <?php echo esc_html($display_type); ?>
                            </td>
                            <td style="padding: 15px 20px; max-width: 250px; font-size: 11px; color: var(--sm-text-gray); font-weight: 600; line-height: 1.5; font-style: italic;">
                                "<?php echo esc_html($row->details ?: 'لا توجد تفاصيل إضافية مكتوبة.'); ?>"
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <?php
                                $badge_style = 'background: #fef3c7; color: #d97706; border: 1px solid #fde68a;'; // low / yellow
                                if ($row->severity === 'medium') {
                                    $badge_style = 'background: #ffedd5; color: #ea580c; border: 1px solid #fed7aa;';
                                } elseif ($row->severity === 'high') {
                                    $badge_style = 'background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5;';
                                }
                                ?>
                                <span class="sm-badge" style="<?php echo $badge_style; ?> padding: 4px 10px; font-size: 10px; font-weight: 800; border-radius: 6px;">
                                    <?php echo esc_html($severity_labels[$row->severity] ?? $row->severity); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button onclick="reviewReportDecision(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="sm-btn" style="height: 30px; font-size: 11px; font-weight: 700; width: auto; padding: 0 12px; background: #16a34a; border-color: #16a34a;">اعتماد</button>
                                    <button onclick="updateRecordStatus(<?php echo $row->id; ?>, 'rejected')" class="sm-btn sm-btn-outline" style="height: 30px; font-size: 11px; font-weight: 700; width: auto; padding: 0 12px; color: #dc2626; border-color: #fca5a5; background: #fff;">رفض البلاغ</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Decision Modal -->
    <div id="decision-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 500px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">اعتماد البلاغ وتثبيت الإجراء</h3>
                <button class="sm-modal-close" onclick="document.getElementById('decision-modal').style.display='none'">&times;</button>
            </div>
            <form method="post" action="">
                <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
                <input type="hidden" name="record_id" id="decision_record_id">
                <input type="hidden" name="status" value="accepted">
                
                <p style="font-size: 12px; color: var(--sm-text-gray); margin: 0 0 20px 0; font-weight: 600; line-height: 1.5;">
                    مراجعة بلاغ المخالفة وتثبيته في ملف الطالب. يمكنك تخصيص الإجراء المتخذ والعقوبة الإضافية لحفظها في سجلات المنصة العامة.
                </p>

                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label class="sm-label">تعديل الإجراء المتخذ (اختياري):</label>
                    <input type="text" name="action_taken" id="decision_action" class="sm-input" placeholder="مثال: استدعاء ولي الأمر، توجيه رسمي..." style="height: 38px; font-size: 12px;">
                </div>

                <div class="sm-form-group" style="margin-bottom: 25px;">
                    <label class="sm-label">المكافأة أو العقوبة الإضافية:</label>
                    <input type="text" name="reward_penalty" id="decision_reward" class="sm-input" placeholder="أدخل أية تفاصيل أخرى للعقوبة أو الملاحظات..." style="height: 38px; font-size: 12px;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" name="sm_update_record" class="sm-btn" style="height: 38px; font-size: 12px; background: #16a34a; border-color: #16a34a; font-weight: 700; width: auto; padding: 0 20px;">✓ اعتماد وحفظ القرار</button>
                    <button type="button" onclick="document.getElementById('decision-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 38px; font-size: 12px; font-weight: 700;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    window.reviewReportDecision = function(record) {
        document.getElementById('decision_record_id').value = record.id;
        document.getElementById('decision_action').value = record.action_taken || '';
        document.getElementById('decision_reward').value = record.reward_penalty || '';
        document.getElementById('decision-modal').style.display = 'flex';
    };

    window.updateRecordStatus = function(recordId, status) {
        if (!confirm('هل أنت متأكد من رغبتك في ' + (status === 'rejected' ? 'رفض' : 'تحديث') + ' هذا البلاغ بشكل نهائي؟')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'record_id';
        inputId.value = recordId;

        const inputStatus = document.createElement('input');
        inputStatus.type = 'hidden';
        inputStatus.name = 'status';
        inputStatus.value = status;

        const inputNonce = document.createElement('input');
        inputNonce.type = 'hidden';
        inputNonce.name = 'sm_nonce';
        inputNonce.value = '<?php echo wp_create_nonce("sm_record_action"); ?>';

        const inputSubmit = document.createElement('input');
        inputSubmit.type = 'hidden';
        inputSubmit.name = 'sm_update_record';
        inputSubmit.value = '1';

        form.appendChild(inputId);
        form.appendChild(inputStatus);
        form.appendChild(inputNonce);
        form.appendChild(inputSubmit);
        document.body.appendChild(form);
        form.submit();
    };
    </script>
</div>
