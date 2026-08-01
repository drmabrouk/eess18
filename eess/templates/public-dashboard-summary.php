<?php
if (!defined('ABSPATH')) exit;
if (in_array('sm_student', (array)wp_get_current_user()->roles)) {
    echo '<p>يرجى التوجه إلى لوحة المعلومات الخاصة بك.</p>';
    return;
}

// Check for active surveys for current user role
$user_role = !empty(wp_get_current_user()->roles) ? wp_get_current_user()->roles[0] : '';
$active_surveys = SM_DB::get_surveys($user_role);

foreach ($active_surveys as $survey):
    // Check if already responded
    global $wpdb;
    $responded = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_survey_responses WHERE survey_id = %d AND user_id = %d", $survey->id, get_current_user_id()));
    if ($responded) continue;
?>
<div class="sm-survey-card" style="background: #fffdf2; border: 2px solid #fef3c7; border-radius: 12px; padding: 25px; margin-bottom: 30px; position: relative; overflow: hidden;">
    <div style="position: absolute; top: 0; right: 0; background: #fbbf24; color: #78350f; font-size: 10px; font-weight: 800; padding: 4px 15px; border-radius: 0 0 0 12px;">استطلاع رأي هام</div>
    <h3 style="margin: 0 0 10px 0; color: #92400e;"><?php echo esc_html($survey->title); ?></h3>
    <p style="margin: 0 0 20px 0; font-size: 14px; color: #b45309;">يرجى المشاركة في هذا الاستطلاع القصير للمساهمة في تحسين جودة العملية التعليمية.</p>

    <button class="sm-btn" style="background: #d97706; width: auto;" onclick="smOpenSurveyModal(<?php echo $survey->id; ?>)">المشاركة الآن</button>
</div>

<!-- Survey Participation Modal -->
<div id="survey-participation-modal-<?php echo $survey->id; ?>" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 700px;">
        <div class="sm-modal-header">
            <h3><?php echo esc_html($survey->title); ?></h3>
            <button class="sm-modal-close" onclick="this.closest('.sm-modal-overlay').style.display='none'">&times;</button>
        </div>
        <div class="sm-modal-body" style="padding: 30px;">
            <div id="survey-questions-list-<?php echo $survey->id; ?>">
                <?php
                $questions = json_decode($survey->questions, true);
                foreach ($questions as $index => $q):
                ?>
                <div class="survey-question-block" style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <div style="font-weight: 800; margin-bottom: 15px; color: var(--sm-dark-color);"><?php echo ($index+1) . '. ' . esc_html($q); ?></div>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="survey_q_<?php echo $survey->id; ?>_<?php echo $index; ?>" value="ممتاز" required> ممتاز
                        </label>
                        <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="survey_q_<?php echo $survey->id; ?>_<?php echo $index; ?>" value="جيد جداً"> جيد جداً
                        </label>
                        <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="survey_q_<?php echo $survey->id; ?>_<?php echo $index; ?>" value="جيد"> جيد
                        </label>
                        <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="survey_q_<?php echo $survey->id; ?>_<?php echo $index; ?>" value="مقبول"> مقبول
                        </label>
                        <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="survey_q_<?php echo $survey->id; ?>_<?php echo $index; ?>" value="غير راض"> غير راض
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="sm-btn" style="height: 45px; margin-top: 20px;" onclick="smSubmitSurveyResponse(<?php echo $survey->id; ?>, <?php echo count($questions); ?>)">إرسال الردود</button>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function smOpenSurveyModal(id) {
    document.getElementById('survey-participation-modal-' + id).style.display = 'flex';
}

function smSubmitSurveyResponse(surveyId, questionsCount) {
    const responses = [];
    for (let i = 0; i < questionsCount; i++) {
        const selected = document.querySelector(`input[name="survey_q_${surveyId}_${i}"]:checked`);
        if (!selected) {
            smShowNotification('يرجى الإجابة على جميع الأسئلة', true);
            return;
        }
        responses.push(selected.value);
    }

    const formData = new FormData();
    formData.append('action', 'sm_submit_survey_response');
    formData.append('survey_id', surveyId);
    formData.append('responses', JSON.stringify(responses));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم إرسال ردودك بنجاح. شكراً لمشاركتك!');
            location.reload();
        } else {
            smShowNotification('فشل إرسال الردود: ' + res.data, true);
        }
    });
}
</script>

<div class="sm-card-grid" style="margin-bottom: 40px;">
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">إجمالي الطلاب</div>
        <div style="font-size: 2.5em; font-weight: 900; color: var(--sm-primary-color);"><?php echo esc_html($stats['total_students'] ?? 0); ?></div>
    </div>
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">إجمالي المعلمين</div>
        <div style="font-size: 2.5em; font-weight: 900; color: var(--sm-secondary-color);"><?php echo esc_html($stats['total_teachers'] ?? 0); ?></div>
    </div>
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">مخالفات اليوم</div>
        <div style="font-size: 2.5em; font-weight: 900; color: var(--sm-accent-color);"><?php echo esc_html($stats['violations_today'] ?? 0); ?></div>
    </div>
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">الإجراءات المتخذة</div>
        <div style="font-size: 2.5em; font-weight: 900; color: var(--sm-dark-color);"><?php echo esc_html($stats['total_actions'] ?? 0); ?></div>
    </div>
    <?php if ($is_wp_admin || !empty($my_visibility['attendance'])): ?>
    <div class="sm-stat-card" style="border-right: 4px solid #e53e3e;">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">غياب الطلاب (اليوم)</div>
        <div style="font-size: 2.5em; font-weight: 900; color: #e53e3e;"><?php echo esc_html($stats['absent_today'] ?? 0); ?></div>
    </div>
    <?php endif; ?>
</div>

<?php if ($is_wp_admin || !empty($my_visibility['attendance'])): ?>
<div style="background: #fff; padding: 20px 30px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; gap: 40px; align-items: center;">
        <div>
            <div style="font-size: 11px; color: #718096; font-weight: 700;">إجمالي الحضور اليوم</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #38a169;"><?php echo esc_html($stats['present_today'] ?? 0); ?> <span style="font-size: 0.5em; color: #a0aec0; font-weight: 400;">طالب</span></div>
        </div>
        <div style="width: 1px; height: 40px; background: #eee;"></div>
        <div>
            <div style="font-size: 11px; color: #718096; font-weight: 700;">إجمالي الغياب اليوم</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #e53e3e;"><?php echo esc_html($stats['absent_today'] ?? 0); ?> <span style="font-size: 0.5em; color: #a0aec0; font-weight: 400;">طالب</span></div>
        </div>
    </div>
    <a href="<?php echo add_query_arg('sm_tab', 'attendance'); ?>" class="sm-btn sm-btn-outline" style="width: auto; font-size: 12px;">عرض سجل الحضور التفصيلي</a>
</div>
<?php endif; ?>



<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 40px;">
    <!-- Trends and Categories Charts -->
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); position: relative; max-height: 350px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin:0; font-size: 1.1em;">اتجاهات المخالفات (آخر 30 يوم)</h3>
            <button onclick="smDownloadChart('violationTrendsChart', 'اتجاهات_المخالفات')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
        </div>
        <div style="height: 200px;"><canvas id="violationTrendsChart"></canvas></div>
    </div>
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); position: relative; max-height: 350px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin:0; font-size: 1.1em;">توزيع الأنواع</h3>
            <button onclick="smDownloadChart('violationCategoriesChart', 'توزيع_الأنواع')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
        </div>
        <div style="height: 200px;"><canvas id="violationCategoriesChart"></canvas></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); position: relative; max-height: 380px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin:0; font-size: 1.1em;">توزيع المخالفات حسب الحدة</h3>
            <button onclick="smDownloadChart('severityChart', 'توزيع_الحدة')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
        </div>
        <div style="height: 250px;"><canvas id="severityChart"></canvas></div>
    </div>
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); position: relative; max-height: 380px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin:0; font-size: 1.1em;">أكثر الطلاب مخالفة (تكرار)</h3>
            <button onclick="smDownloadChart('topStudentsChart', 'أكثر_الطلاب_مخالفة')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
        </div>
        <div style="height: 250px;"><canvas id="topStudentsChart"></canvas></div>
    </div>
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); position: relative; max-height: 380px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin:0; font-size: 1.1em;">توزيع المخالفات حسب الدرجة</h3>
            <button onclick="smDownloadChart('degreeChart', 'توزيع_الدرجة')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
        </div>
        <div style="height: 250px;"><canvas id="degreeChart"></canvas></div>
    </div>
</div>




<script>
function smDownloadChart(chartId, fileName) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = fileName + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

(function() {
    window.smCharts = window.smCharts || {};

    const initSummaryCharts = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(initSummaryCharts, 200);
            return;
        }

        const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
        const severityLabels = <?php echo json_encode(SM_Settings::get_severities()); ?>;

        const createOrUpdateChart = (id, config) => {
            if (window.smCharts[id]) {
                window.smCharts[id].destroy();
            }
            const el = document.getElementById(id);
            if (el) {
                window.smCharts[id] = new Chart(el.getContext('2d'), config);
            }
        };

        // Trends Chart
        createOrUpdateChart('violationTrendsChart', {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($t){ return date('m/d', strtotime($t->date)); }, $stats['trends'] ?? [])); ?>,
                datasets: [{
                    label: 'المخالفات',
                    data: <?php echo json_encode(array_map(function($t){ return $t->count; }, $stats['trends'] ?? [])); ?>,
                    borderColor: '#F63049',
                    backgroundColor: 'rgba(246, 48, 73, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // Categories Chart
        const typeLabels = <?php echo json_encode(SM_Settings::get_violation_types()); ?>;
        createOrUpdateChart('violationCategoriesChart', {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($t) use ($typeLabels){ return $typeLabels[$t->type] ?? $t->type; }, $stats['by_type'] ?? [])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(function($t){ return $t->count; }, $stats['by_type'] ?? [])); ?>,
                    backgroundColor: ['#F63049', '#D02752', '#8A244B', '#111F35', '#718096']
                }]
            },
            options: chartOptions
        });

        // Severity Chart
        createOrUpdateChart('severityChart', {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($s) use ($severityLabels){ return $severityLabels[$s->severity] ?? $s->severity; }, $stats['by_severity'] ?? [])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(function($s){ return $s->count; }, $stats['by_severity'] ?? [])); ?>,
                    backgroundColor: ['#111F35', '#D02752', '#F63049']
                }]
            },
            options: chartOptions
        });

        // Top Students Chart
        createOrUpdateChart('topStudentsChart', {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($s){ return $s->name; }, $stats['top_students'] ?? [])); ?>,
                datasets: [{
                    label: 'عدد المخالفات',
                    data: <?php echo json_encode(array_map(function($s){ return $s->count; }, $stats['top_students'] ?? [])); ?>,
                    backgroundColor: '#F63049'
                }]
            },
            options: { ...chartOptions, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        // Degree Chart
        createOrUpdateChart('degreeChart', {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($s){ return 'الدرجة ' . $s->degree; }, $stats['by_degree'] ?? [])); ?>,
                datasets: [{
                    label: 'عدد الحالات',
                    data: <?php echo json_encode(array_map(function($s){ return $s->count; }, $stats['by_degree'] ?? [])); ?>,
                    backgroundColor: '#111F35'
                }]
            },
            options: { ...chartOptions, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    };

    if (document.readyState === 'complete') initSummaryCharts();
    else window.addEventListener('load', initSummaryCharts);
})();
</script>
