<?php
if (!defined('ABSPATH')) exit;
if (in_array('sm_student', (array)wp_get_current_user()->roles)) {
    echo '<p>يرجى التوجه إلى لوحة المعلومات الخاصة بك.</p>';
    return;
}

$absent_today = $stats['absent_today'] ?? 0;
$total_students = $stats['total_students'] ?? 0;
$attendance_pct = 100;
if ($total_students > 0) {
    $attendance_pct = round((($total_students - $absent_today) / $total_students) * 100);
}
?>

<!-- Premium Top Stat Cards -->
<div class="sm-card-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
    <!-- Card 1: إجمالي الطلاب -->
    <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); position: relative; text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 110px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="font-size: 13px; color: var(--sm-text-gray); font-weight: 700;">إجمالي الطلاب</div>
            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sm-accent-color);">
                <span class="dashicons dashicons-welcome-learn-more" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: end; margin-top: auto;">
            <div style="font-size: 32px; font-weight: 800; color: var(--sm-dark-color); line-height: 1;"><?php echo esc_html($total_students); ?></div>
            <span class="sm-badge" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-size: 10px; padding: 2px 8px; font-weight: 800; border-radius: 6px;">نشط 100%</span>
        </div>
    </div>

    <!-- Card 2: الكادر التعليمي -->
    <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); position: relative; text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 110px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="font-size: 13px; color: var(--sm-text-gray); font-weight: 700;">الكادر التعليمي</div>
            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sm-accent-color);">
                <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: end; margin-top: auto;">
            <div style="font-size: 32px; font-weight: 800; color: var(--sm-dark-color); line-height: 1;"><?php echo esc_html($stats['total_teachers'] ?? 0); ?></div>
            <span class="sm-badge" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-size: 10px; padding: 2px 8px; font-weight: 800; border-radius: 6px;">مستكمل</span>
        </div>
    </div>

    <!-- Card 3: مخالفات اليوم -->
    <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); position: relative; text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 110px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="font-size: 13px; color: var(--sm-text-gray); font-weight: 700;">مخالفات اليوم</div>
            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sm-accent-color);">
                <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: end; margin-top: auto;">
            <div style="font-size: 32px; font-weight: 800; color: var(--sm-dark-color); line-height: 1;"><?php echo esc_html($stats['violations_today'] ?? 0); ?></div>
            <span class="sm-badge" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-size: 10px; padding: 2px 8px; font-weight: 800; border-radius: 6px;">انضباط ممتاز</span>
        </div>
    </div>

    <!-- Card 4: الاجراءات المتخذة -->
    <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); position: relative; text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 110px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="font-size: 13px; color: var(--sm-text-gray); font-weight: 700;">الاجراءات المتخذة</div>
            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sm-accent-color);">
                <span class="dashicons dashicons-clipboard" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: end; margin-top: auto;">
            <div style="font-size: 32px; font-weight: 800; color: var(--sm-dark-color); line-height: 1;"><?php echo esc_html($stats['total_actions'] ?? 0); ?></div>
            <span class="sm-badge" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-size: 10px; padding: 2px 8px; font-weight: 800; border-radius: 6px;">منفذة</span>
        </div>
    </div>

    <!-- Card 5: غياب الطلاب (اليوم) -->
    <div class="sm-stat-card" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); position: relative; text-align: right; display: flex; flex-direction: column; justify-content: space-between; height: 110px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="font-size: 13px; color: var(--sm-text-gray); font-weight: 700;">غياب الطلاب (اليوم)</div>
            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sm-accent-color);">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: end; margin-top: auto;">
            <div style="font-size: 32px; font-weight: 800; color: var(--sm-dark-color); line-height: 1;"><?php echo esc_html($absent_today); ?></div>
            <span class="sm-badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 10px; padding: 2px 8px; font-weight: 800; border-radius: 6px;"><?php echo esc_html($attendance_pct); ?>% حضور</span>
        </div>
    </div>
</div>

<!-- Shortcuts and Quick Actions Panel -->
<div class="sm-shortcuts-bar" style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; direction: rtl;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: #fffbeb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #d97706; border: 1px solid #fef3c7;">
            <span class="dashicons dashicons-performance" style="font-size: 20px; width: 20px; height: 20px;"></span>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 14px; font-weight: 800; color: var(--sm-dark-color);">اختصارات الإدارة المباشرة</h3>
            <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--sm-text-gray);">وصول سريع لكافة الوظائف الإدارية والسلوكية الهامة</p>
        </div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="?page=sm-dashboard&sm_tab=attendance" class="sm-btn sm-btn-outline" style="background: #f8fafc; border-color: var(--sm-border-color); height: 36px; padding: 0 16px; font-size: 12px; font-weight: 700; color: var(--sm-dark-color) !important; border-radius: 8px;">
            <span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px;"></span> رصد الحضور
        </a>
        <button onclick="smOpenViolationModal()" class="sm-btn" style="background: #1e293b; height: 36px; padding: 0 16px; font-size: 12px; font-weight: 700; color: #ffffff !important; border-radius: 8px;">
            <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span> تسجيل مخالفة
        </button>
        <a href="?page=sm-dashboard&sm_tab=lesson-plans" class="sm-btn sm-btn-outline" style="background: #f8fafc; border-color: var(--sm-border-color); height: 36px; padding: 0 16px; font-size: 12px; font-weight: 700; color: var(--sm-dark-color) !important; border-radius: 8px;">
            <span class="dashicons dashicons-welcome-write-blog" style="font-size: 16px; width: 16px; height: 16px;"></span> حصر التحضير
        </a>
        <a href="?page=sm-dashboard&sm_tab=teacher-reports" class="sm-btn sm-btn-outline" style="background: #fffbeb; border-color: #fde68a; height: 36px; padding: 0 16px; font-size: 12px; font-weight: 700; color: #b45309 !important; border-radius: 8px;">
            <span class="dashicons dashicons-testimonial" style="font-size: 16px; width: 16px; height: 16px;"></span> تقارير المعلمين
        </a>
    </div>
</div>

<!-- Detailed Attendance Panel -->
<?php if ($is_wp_admin || !empty($my_visibility['attendance'])): ?>
<div style="background: #fff; padding: 20px 30px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--sm-shadow);">
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

<!-- 3-Column Modern Grid Layout (Activities, Trends, Types) -->
<div class="sm-dashboard-widgets-grid" style="display: grid; grid-template-columns: 320px 1fr 340px; gap: 24px; margin-bottom: 24px; direction: rtl;">
    <!-- Column 1: السجل المباشر للأنشطة المدرسية -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); display: flex; flex-direction: column; justify-content: space-between; min-height: 400px; box-sizing: border-box;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <span class="dashicons dashicons-media-text" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
                <h3 style="margin:0; font-size: 14px; font-weight: 800; color: var(--sm-dark-color);">السجل المباشر للأنشطة المدرسية</h3>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; overflow-y: auto; max-height: 320px; padding-left: 4px;">
                <?php
                $logs = SM_Logger::get_logs(4, 0);
                if (empty($logs)):
                ?>
                    <p style="text-align: center; color: var(--sm-text-gray); font-size: 12px; padding: 40px 0;">لا يوجد أنشطة مسجلة حالياً.</p>
                <?php else: ?>
                    <?php foreach ($logs as $log):
                        // Assigning custom status labels and themes based on log action
                        $badge_text = 'إجراء نظام';
                        $badge_style = 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;';

                        $act_lower = mb_strtolower($log->action);
                        if (strpos($act_lower, 'مخالفة') !== false) {
                            $badge_text = 'مخالفة سلوكية';
                            $badge_style = 'background: #fff5f5; color: #c53030; border: 1px solid #feb2b2;';
                        } elseif (strpos($act_lower, 'حضور') !== false || strpos($act_lower, 'غياب') !== false) {
                            $badge_text = 'تنبيه تأخير';
                            $badge_style = 'background: #fffbeb; color: #b45309; border: 1px solid #fde68a;';
                        } elseif (strpos($act_lower, 'تحديث') !== false || strpos($act_lower, 'اعتماد') !== false) {
                            $badge_text = 'معتمد';
                            $badge_style = 'background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;';
                        } elseif (strpos($act_lower, 'إرسال') !== false) {
                            $badge_text = 'إشعار تلقائي';
                            $badge_style = 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;';
                        }

                        // Time calculation
                        $log_time = strtotime($log->created_at);
                        $formatted_time = date('H:i', $log_time) . ' - اليوم';
                    ?>
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 6px; text-align: right;">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 4px;">
                                <span style="font-size: 11px; color: var(--sm-text-gray); font-weight: 600;"><?php echo esc_html($formatted_time); ?></span>
                                <span class="sm-badge" style="<?php echo $badge_style; ?> font-size: 9px; padding: 1px 6px; font-weight: 800; border-radius: 4px;"><?php echo esc_html($badge_text); ?></span>
                            </div>
                            <div style="font-size: 12px; font-weight: 800; color: var(--sm-dark-color); line-height: 1.4;"><?php echo esc_html($log->action); ?></div>
                            <div style="font-size: 11px; color: var(--sm-text-gray); line-height: 1.3;"><?php echo esc_html($log->details); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--sm-border-color); padding-top: 12px; margin-top: 12px; font-size: 10px; color: var(--sm-text-gray); font-weight: 600;">
            <span>v2.4 Pro (EESS)</span>
            <span>حالة المزامنة: متزامن مع الخادم</span>
        </div>
    </div>

    <!-- Column 2: اتجاهات المخالفات (آخر 30 يوم) -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); display: flex; flex-direction: column; justify-content: space-between; min-height: 400px; box-sizing: border-box;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-chart-line" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
                    <h3 style="margin:0; font-size: 14px; font-weight: 800; color: var(--sm-dark-color);">اتجاهات المخالفات (آخر 30 يوم)</h3>
                </div>
                <button onclick="smDownloadChart('violationTrendsChart', 'اتجاهات_المخالفات')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
            </div>
            <div style="height: 240px; position: relative;"><canvas id="violationTrendsChart"></canvas></div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
            <span class="sm-badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 11px; padding: 4px 12px; font-weight: 800; border-radius: 6px;">مؤشر استقرار سلوكي مرتفع</span>
            <span style="font-size: 10px; color: var(--sm-text-gray); font-weight: 600;">نطاق التحليل: من 30 يوماً مضت إلى اليوم</span>
        </div>
    </div>

    <!-- Column 3: توزيع الأنواع -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); display: flex; flex-direction: column; justify-content: space-between; min-height: 400px; box-sizing: border-box;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-chart-pie" style="color: var(--sm-primary-color); font-size: 18px; width: 18px; height: 18px;"></span>
                    <h3 style="margin:0; font-size: 14px; font-weight: 800; color: var(--sm-dark-color);">توزيع الأنواع</h3>
                </div>
                <button onclick="smDownloadChart('violationCategoriesChart', 'توزيع_الأنواع')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
            </div>
            <div style="height: 240px; position: relative;"><canvas id="violationCategoriesChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Bottom Charts Row (Severity, Top Students, Degree) -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; direction: rtl;">
    <!-- Card 1: توزيع المخالفات حسب الحدة -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); min-height: 320px; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="margin:0; font-size: 13px; font-weight: 800; color: var(--sm-dark-color);">توزيع المخالفات حسب الحدة</h3>
                <button onclick="smDownloadChart('severityChart', 'توزيع_الحدة')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
            </div>
            <div style="height: 200px; position: relative;"><canvas id="severityChart"></canvas></div>
        </div>
    </div>

    <!-- Card 2: أكثر الطلاب مخالفة (تكرار) -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); min-height: 320px; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="margin:0; font-size: 13px; font-weight: 800; color: var(--sm-dark-color);">أكثر الطلاب مخالفة (تكرار)</h3>
                <button onclick="smDownloadChart('topStudentsChart', 'أكثر_الطلاب_مخالفة')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
            </div>
            <div style="height: 200px; position: relative;"><canvas id="topStudentsChart"></canvas></div>
        </div>
    </div>

    <!-- Card 3: توزيع المخالفات حسب الدرجة -->
    <div style="background: #ffffff; border: 1px solid var(--sm-border-color); border-radius: 12px; padding: 20px; box-shadow: var(--sm-shadow); min-height: 320px; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--sm-border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="margin:0; font-size: 13px; font-weight: 800; color: var(--sm-dark-color);">توزيع المخالفات حسب الدرجة</h3>
                <button onclick="smDownloadChart('degreeChart', 'توزيع_الدرجة')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
            </div>
            <div style="height: 200px; position: relative;"><canvas id="degreeChart"></canvas></div>
        </div>
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
                    borderColor: '#334155',
                    backgroundColor: 'rgba(51, 65, 85, 0.08)',
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
                    backgroundColor: ['#334155', '#475569', '#64748B', '#1E293B', '#94A3B8']
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
                    backgroundColor: ['#1E293B', '#475569', '#334155']
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
                    backgroundColor: '#334155'
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
                    backgroundColor: '#1E293B'
                }]
            },
            options: { ...chartOptions, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    };

    if (document.readyState === 'complete') initSummaryCharts();
    else window.addEventListener('load', initSummaryCharts);
})();
</script>
