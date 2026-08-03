<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

// Only authorized supervisors and admins can access this page
$can_evaluate = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_hr;

if (!$can_evaluate) {
    echo '<div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700; font-family:\'Cairo\'; text-align:center;">🚫 عذراً، لا تمتلك الصلاحيات الكافية للوصول لصفحة تقييم الموظفين.</div>';
    return;
}

// Fetch all staff users for selection (exclude students, parents, administrators if desired, but let's exclude student/parent)
$staff_users = get_users(array(
    'role__not_in' => array('sm_student', 'sm_parent'),
    'orderby'      => 'display_name',
    'order'        => 'ASC'
));

// Handle form submission
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eess_submit_evaluation'])) {
    if (!isset($_POST['eess_eval_nonce']) || !wp_verify_nonce($_POST['eess_eval_nonce'], 'eess_submit_evaluation_action')) {
        wp_die('عذراً، انتهت صلاحية الجلسة. يرجى المحاولة مجدداً.');
    }

    $target_emp_id = intval($_POST['employee_id'] ?? 0);
    $period        = sanitize_text_field($_POST['eval_period'] ?? '');
    $m1            = intval($_POST['metric_perf'] ?? 0);
    $m2            = intval($_POST['metric_plan'] ?? 0);
    $m3            = intval($_POST['metric_disc'] ?? 0);
    $m4            = intval($_POST['metric_interact'] ?? 0);
    $m5            = intval($_POST['metric_lead'] ?? 0);
    $comments      = sanitize_textarea_field($_POST['eval_comments'] ?? '');

    $total_score = $m1 + $m2 + $m3 + $m4 + $m5;

    // Determine Grade
    if ($total_score >= 90) {
        $grade = 'ممتاز';
    } elseif ($total_score >= 80) {
        $grade = 'جيد جداً';
    } elseif ($total_score >= 70) {
        $grade = 'جيد';
    } elseif ($total_score >= 60) {
        $grade = 'مقبول';
    } else {
        $grade = 'ضعيف / غير مرضٍ';
    }

    if ($target_emp_id > 0 && !empty($period)) {
        // Save evaluation to employee's metadata
        $employee_evals = get_user_meta($target_emp_id, 'eess_hr_evaluations', true) ?: array();
        if (!is_array($employee_evals)) {
            $employee_evals = json_decode($employee_evals, true) ?: array();
        }

        $new_eval = array(
            'id'         => uniqid(),
            'date'       => current_time('Y-m-d H:i:s'),
            'period'     => $period,
            'scores'     => array($m1, $m2, $m3, $m4, $m5),
            'score'      => $total_score,
            'grade'      => $grade,
            'notes'      => $comments,
            'evaluator'  => $current_user->display_name,
            'eval_id'    => $current_user->ID
        );

        array_unshift($employee_evals, $new_eval);
        update_user_meta($target_emp_id, 'eess_hr_evaluations', $employee_evals);

        // Also add to employee activity timeline for complete profile auditing
        $timeline = get_user_meta($target_emp_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) $timeline = array();
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => 'إضافة تقييم أداء سنوي',
            'actor' => $current_user->display_name,
            'details' => "تم اعتماد تقييم أداء جديد للفترة ($period) بدرجة إجمالية $total_score% بتقدير ($grade)."
        ));
        update_user_meta($target_emp_id, 'eess_hr_activity_timeline', $timeline);

        $success_msg = '✅ تم تسجيل تقييم الأداء بنجاح في السجل المهني والوظيفي للموظف وتحديث ملفه العملي فوراً.';
    }
}
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Page Header -->
    <div style="margin-bottom: 25px;">
        <h1 style="font-weight: 900; font-size: 1.8rem; color: #1e293b; margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-awards" style="font-size: 2rem; width: 32px; height: 32px; line-height: 32px; color: #475569;"></span>
            منظومة تقييم أداء الموظفين (Employee Evaluation)
        </h1>
        <p style="margin: 0; color: #64748b; font-size: 0.9rem;">إجراء وتقييم أداء الكادر الأكاديمي والتعليمي والوظائف المعاونة والاطلاع على السجل التاريخي للتقييمات المعتمدة.</p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div style="background: #dcfce7; color: #15803d; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; font-weight: 700; margin-bottom: 25px;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">

        <!-- Evaluation Form Card -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow);">
            <h3 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📝 استمارة تقييم الأداء السنوي والدوري الموحد</h3>

            <form method="POST" action="" oninput="calculateTotalScore()">
                <?php wp_nonce_field('eess_submit_evaluation_action', 'eess_eval_nonce'); ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <!-- Employee Selector -->
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: bold; color: #475569; margin-bottom: 6px;">الموظف المراد تقييمه *</label>
                        <select name="employee_id" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Cairo'; font-size: 13px; height: 42px;">
                            <option value="">-- اختر الموظف من القائمة --</option>
                            <?php foreach ($staff_users as $staff):
                                $staff_role = !empty($staff->roles) ? $staff->roles[0] : '';
                                $role_lbl = $role_map[$staff_role] ?? $staff_role;
                            ?>
                                <option value="<?php echo $staff->ID; ?>">
                                    <?php echo esc_html($staff->display_name); ?> (<?php echo esc_html($role_lbl); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Evaluation Period -->
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: bold; color: #475569; margin-bottom: 6px;">فترة التقييم المستهدفة *</label>
                        <select name="eval_period" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Cairo'; font-size: 13px; height: 42px;">
                            <option value="">-- اختر فترة التقييم --</option>
                            <option value="التقييم السنوي للعام الدراسي 2024">التقييم السنوي للعام الدراسي 2024</option>
                            <option value="التقييم الفصلي - الفصل الأول 2024-2025">التقييم الفصلي - الفصل الأول 2024-2025</option>
                            <option value="التقييم الفصلي - الفصل الثاني 2024-2025">التقييم الفصلي - الفصل الثاني 2024-2025</option>
                            <option value="التقييم الفصلي - الفصل الثالث 2024-2025">التقييم الفصلي - الفصل الثالث 2024-2025</option>
                        </select>
                    </div>
                </div>

                <!-- Structured Metric Scores -->
                <div style="background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 13px; font-weight: bold; color: #334155; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">📊 عناصر التقييم والدرجات المخصصة لكل بند (المجموع الإجمالي 100 درجة)</h4>

                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <!-- Metric 1 -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px;">
                                <strong style="font-size: 13px; color: #1e293b; display: block;">1. جودة الأداء وإنجاز المهام الوظيفية</strong>
                                <span style="font-size: 11px; color: #64748b;">دقة العمل وسرعة التنفيذ وخلو المخرجات من الأخطاء المهنية.</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="metric_perf" id="metric_perf" min="0" max="20" required style="width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: monospace;" value="18">
                                <span style="font-size: 12px; color: #64748b; font-weight: bold;">/ 20</span>
                            </div>
                        </div>

                        <!-- Metric 2 -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <strong style="font-size: 13px; color: #1e293b; display: block;">2. التخطيط التربوي والتعليمي والابتكار</strong>
                                <span style="font-size: 11px; color: #64748b;">مدى الالتزام بتحضير الدروس والمنهجية المبتكرة وابتكار أساليب شرح جديدة.</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="metric_plan" id="metric_plan" min="0" max="20" required style="width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: monospace;" value="17">
                                <span style="font-size: 12px; color: #64748b; font-weight: bold;">/ 20</span>
                            </div>
                        </div>

                        <!-- Metric 3 -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <strong style="font-size: 13px; color: #1e293b; display: block;">3. الالتزام بالسلوك والانضباط الوظيفي</strong>
                                <span style="font-size: 11px; color: #64748b;">الحضور والانصراف والالتزام بتوجيهات الإدارة والسياسات العامة للمدرسة.</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="metric_disc" id="metric_disc" min="0" max="20" required style="width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: monospace;" value="19">
                                <span style="font-size: 12px; color: #64748b; font-weight: bold;">/ 20</span>
                            </div>
                        </div>

                        <!-- Metric 4 -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <strong style="font-size: 13px; color: #1e293b; display: block;">4. التفاعل والتواصل الإيجابي والمجتمعي</strong>
                                <span style="font-size: 11px; color: #64748b;">التواصل الفعال مع الطلاب، أولياء الأمور، الزملاء والمشرفين.</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="metric_interact" id="metric_interact" min="0" max="20" required style="width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: monospace;" value="18">
                                <span style="font-size: 12px; color: #64748b; font-weight: bold;">/ 20</span>
                            </div>
                        </div>

                        <!-- Metric 5 -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <strong style="font-size: 13px; color: #1e293b; display: block;">5. القيادة والمبادرة وتحمل المسؤولية</strong>
                                <span style="font-size: 11px; color: #64748b;">التطوع والمبادرة باقتراح حلول تربوية ومساعدة الفريق والأعمال الإضافية.</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="metric_lead" id="metric_lead" min="0" max="20" required style="width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: monospace;" value="18">
                                <span style="font-size: 12px; color: #64748b; font-weight: bold;">/ 20</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Live Calculation -->
                    <div style="margin-top: 25px; padding-top: 15px; border-top: 2px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                        <strong style="font-size: 15px; color: #1e293b;">الدرجة الكلية والتقدير المرتقب:</strong>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span id="total-score-badge" style="background: var(--sm-primary-color); color: white; padding: 5px 15px; border-radius: 8px; font-weight: 800; font-size: 1.1rem; font-family: monospace;">90 %</span>
                            <span id="grade-badge" style="background: #16a34a; color: white; padding: 5px 15px; border-radius: 8px; font-weight: 800; font-size: 14px;">ممتاز</span>
                        </div>
                    </div>
                </div>

                <!-- Evaluator Comments -->
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: bold; color: #475569; margin-bottom: 6px;">ملاحظات المقيّم، التوجيهات والتوصيات المباشرة للتحسين *</label>
                    <textarea name="eval_comments" rows="4" required placeholder="أدخل هنا التوصيات والتعليقات بالتفصيل لمساعدة الموظف على التطور المستمر..." style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Cairo'; font-size: 13px; resize: vertical; line-height: 1.6;"></textarea>
                </div>

                <!-- Submit Button -->
                <div style="text-align: left; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    <button type="submit" name="eess_submit_evaluation" class="sm-btn" style="background: #000; border: 1px solid #000; color: #fff; border-radius: 8px; font-weight: 700; padding: 12px 35px; cursor: pointer;">
                        💾 اعتماد وإصدار تقرير الأداء والمزامنة فوراً
                    </button>
                </div>

            </form>
        </div>

    </div>

</div>

<script>
function calculateTotalScore() {
    const p = parseInt(document.getElementById('metric_perf').value) || 0;
    const pl = parseInt(document.getElementById('metric_plan').value) || 0;
    const d = parseInt(document.getElementById('metric_disc').value) || 0;
    const i = parseInt(document.getElementById('metric_interact').value) || 0;
    const l = parseInt(document.getElementById('metric_lead').value) || 0;

    const total = p + pl + d + i + l;

    // Update total badge
    const scoreBadge = document.getElementById('total-score-badge');
    scoreBadge.innerText = total + " %";

    // Update grade badge
    const gradeBadge = document.getElementById('grade-badge');
    let gradeText = "";
    let gradeColor = "";

    if (total >= 90) {
        gradeText = "ممتاز";
        gradeColor = "#16a34a";
    } else if (total >= 80) {
        gradeText = "جيد جداً";
        gradeColor = "#2563eb";
    } else if (total >= 70) {
        gradeText = "جيد";
        gradeColor = "#ca8a04";
    } else if (total >= 60) {
        gradeText = "مقبول";
        gradeColor = "#ea580c";
    } else {
        gradeText = "ضعيف / غير مرضٍ";
        gradeColor = "#dc2626";
    }

    gradeBadge.innerText = gradeText;
    gradeBadge.style.backgroundColor = gradeColor;
}

// Initial Run
calculateTotalScore();
</script>
