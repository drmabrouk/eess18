<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; color: var(--sm-dark-color); font-size: 20px;">إدارة أولياء الأمور</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--sm-text-gray);">إدارة بيانات الاتصال بأولياء الأمور، ربط الأبناء، وإرسال طلبات الاستدعاء الرسمية</p>
        </div>
        <?php if (current_user_can('إدارة_أولياء_الأمور')): ?>
            <button onclick="document.getElementById('add-parent-modal').style.display='flex'" class="sm-btn" style="height: 40px; font-size: 12px; font-weight: 700; width: auto; padding: 0 20px;">
                <span class="dashicons dashicons-plus" style="font-size: 16px; width: 16px; height: 16px; margin-left: 5px;"></span> إضافة ولي أمر جديد
            </button>
        <?php endif; ?>
    </div>

    <!-- Filter/Search Panel -->
    <div style="background: #ffffff; padding: 25px; border: 1px solid var(--sm-border-color); border-radius: 12px; margin-bottom: 30px; box-shadow: var(--sm-shadow);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <!-- Preserve other query parameters if any -->
            <?php foreach ($_GET as $key => $val): if ($key !== 'parent_search'): ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($val); ?>">
            <?php endif; endforeach; ?>

            <div style="flex: 1; min-width: 280px;">
                <label class="sm-label" style="margin-bottom: 8px;">البحث الذكي عن ولي أمر:</label>
                <input type="text" name="parent_search" class="sm-input" value="<?php echo esc_attr(isset($_GET['parent_search']) ? $_GET['parent_search'] : ''); ?>" placeholder="البحث بالاسم، البريد الإلكتروني، رقم الهاتف، أو اسم الطالب..." style="height: 38px; font-size: 12px;">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="sm-btn" style="height: 38px; font-size: 12px; font-weight: 700; width: auto; padding: 0 20px;">بحث</button>
                <a href="<?php echo remove_query_arg('parent_search'); ?>" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 0 15px; background: #fff;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <!-- Rows Container -->
    <div class="sm-parents-rows-container" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
        <?php 
        $search = !empty($_GET['parent_search']) ? sanitize_text_field($_GET['parent_search']) : '';
        $args = array('role' => 'sm_parent');

        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'display_name', 'user_email');

            // Advanced Search: Join with students and check meta
            global $wpdb;
            $extra_parent_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT parent_user_id FROM {$wpdb->prefix}sm_students WHERE (name LIKE %s OR parent_email LIKE %s) AND parent_user_id IS NOT NULL",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            ));

            $phone_parent_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'sm_phone' AND meta_value LIKE %s",
                '%' . $wpdb->esc_like($search) . '%'
            ));

            $all_ids = array_unique(array_merge($extra_parent_ids, $phone_parent_ids));

            if (!empty($all_ids)) {
                // Get users by search first
                $search_parents = get_users($args);
                $search_ids = wp_list_pluck($search_parents, 'ID');

                // Combine and fetch all
                $final_ids = array_unique(array_merge($search_ids, $all_ids));
                unset($args['search'], $args['search_columns']);
                $args['include'] = $final_ids;
            }
        }

        $parents = get_users($args);
        if (empty($parents)): ?>
            <div style="padding: 70px 20px; text-align: center; background: #ffffff; border-radius: 12px; border: 2px dashed var(--sm-border-color); box-shadow: var(--sm-shadow);">
                <span class="dashicons dashicons-admin-users" style="font-size: 40px; width:40px; height:40px; color: var(--sm-text-gray); margin-bottom:12px;"></span>
                <p style="color: var(--sm-text-gray); font-weight: 700; font-size: 13px; margin:0;">لا يوجد أولياء أمور مسجلون بالنظام حالياً بهذه المعايير.</p>
            </div>
        <?php else: ?>
            <?php foreach ($parents as $parent): 
                $children = SM_DB::get_students_by_parent($parent->ID);
            ?>
                <div class="sm-parent-row" style="background: #ffffff; border-radius: 12px; border: 1px solid var(--sm-border-color); padding: 20px; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease; gap: 20px; box-shadow: var(--sm-shadow); flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 15px; flex: 2; min-width: 250px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--sm-bg-light); border: 1px solid var(--sm-border-color); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--sm-primary-color); flex-shrink: 0;">
                            👨‍👩‍👦
                        </div>
                        <div>
                            <div style="font-weight: 800; color: var(--sm-dark-color); font-size: 13px;"><?php echo esc_html($parent->display_name); ?></div>
                            <div style="font-size: 11px; color: var(--sm-text-gray); margin-top: 3px; font-weight: 600;"><?php echo esc_html($parent->user_email); ?></div>
                        </div>
                    </div>

                    <div style="flex: 2; background: var(--sm-bg-light); padding: 12px 15px; border-radius: 8px; border: 1px solid var(--sm-border-color); font-size: 11px; min-width: 250px;">
                        <strong style="color: var(--sm-dark-color); font-size: 11px;">الأبناء المرتبطون بالوالد:</strong>
                        <?php if (empty($children)): ?>
                            <span style="color: #dc2626; font-size: 11px; margin-right: 10px; font-weight: 700;">لا يوجد أبناء مرتبطون</span>
                        <?php else: ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                <?php foreach ($children as $c): ?>
                                    <span class="sm-badge" style="background: #ffffff; color: var(--sm-primary-color); border: 1px solid var(--sm-border-color); font-size: 10px; font-weight: 800; border-radius: 6px; padding: 2px 8px;">
                                        <?php echo esc_html($c->name); ?> (<?php echo SM_Settings::format_grade_name($c->class_name, $c->section, 'short'); ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="flex: 1; display: flex; gap: 8px; justify-content: flex-end; min-width: 180px;">
                        <?php
                            $parent_phone = get_user_meta($parent->ID, 'sm_phone', true);
                            $formatted_phone = SM_Settings::format_uae_phone($parent_phone);
                        ?>
                        <button onclick="requestCallIn(<?php echo $parent->ID; ?>, '<?php echo esc_js($parent->display_name); ?>', '<?php echo esc_js($parent->user_email); ?>', '<?php echo esc_js($formatted_phone ?: ''); ?>')" class="sm-btn" style="background: #f8fafc; color: var(--sm-primary-color) !important; border: 1px solid var(--sm-border-color); padding: 0 12px; font-size: 11px; height: 32px; width: auto; font-weight: 800; box-shadow: none;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size:14px; margin-left:5px; line-height:30px;"></span> طلب استدعاء
                        </button>
                        <form method="post" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف حساب ولي الأمر بالكامل من النظام؟')">
                            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                            <input type="hidden" name="delete_user_id" value="<?php echo $parent->ID; ?>">
                            <button type="submit" name="sm_delete_user" class="sm-btn" style="background: #fef2f2; color: #dc2626 !important; border: 1px solid #fca5a5; padding: 0 12px; font-size: 11px; height: 32px; width: auto; font-weight: 800; box-shadow: none;">
                                <span class="dashicons dashicons-trash" style="font-size:14px; margin-left:5px; line-height:30px;"></span> حذف
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Parent Modal -->
    <div id="add-parent-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 550px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">إضافة حساب ولي أمر جديد</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-parent-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-parent-form">
                <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                <input type="hidden" name="user_role" value="sm_parent">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 15px;">
                    <div class="sm-form-group">
                        <label class="sm-label">الاسم الكامل لولي الأمر:</label>
                        <input type="text" name="display_name" class="sm-input" required placeholder="مثال: أحمد عبد الله" style="height: 38px; font-size: 12px;">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">اسم المستخدم (الدخول):</label>
                        <input type="text" name="user_login" class="sm-input" required placeholder="مثال: ahmed_user" style="height: 38px; font-size: 12px;">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">البريد الإلكتروني للوالد:</label>
                        <input type="email" name="user_email" class="sm-input" required placeholder="مثال: ahmed@eess.online" style="height: 38px; font-size: 12px;">
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">كلمة مرور الحساب:</label>
                        <input type="password" name="user_pass" class="sm-input" required placeholder="••••••••" style="height: 38px; font-size: 12px;">
                    </div>
                </div>

                <div style="background: var(--sm-bg-light); border: 1px solid var(--sm-border-color); padding: 12px; border-radius: 8px; font-size: 11px; color: var(--sm-text-gray); font-weight: 600; line-height: 1.5; margin-bottom: 20px;">
                    📌 ملاحظة هامة: لربط ولي الأمر بأبنائه الطلاب في الفصول الدراسية المختلفة، يرجى الانتقال إلى قسم "إدارة شؤون الطلاب" وتحرير بيانات الطالب لربط حسابه مع ولي الأمر هذا.
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="height: 38px; font-size: 12px; font-weight: 700;">إنشاء حساب ولي الأمر</button>
                    <button type="button" onclick="document.getElementById('add-parent-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background: #fff; height: 38px; font-size: 12px; font-weight: 700;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Call In Modal -->
    <div id="call-in-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 500px; padding: 25px;">
            <div class="sm-modal-header">
                <h3 style="font-size: 15px; font-weight: 800;">إرسال طلب استدعاء رسمي</h3>
                <button class="sm-modal-close" onclick="document.getElementById('call-in-modal').style.display='none'">&times;</button>
            </div>
            <div style="text-align: center; padding-top: 10px;">
                <p style="font-size: 12px; margin-bottom: 15px; color: var(--sm-text-gray); font-weight: 600; line-height: 1.5;">
                    سيتم تزويد ولي الأمر بطلب الحضور والمراجعة لزيارة مكتب الإرشاد الطلابي.<br>
                    المستلم: <strong id="call_in_parent_name" style="color: var(--sm-primary-color); font-size: 14px;"></strong>
                </p>

                <div class="sm-form-group" style="text-align: right; margin-bottom: 20px;">
                    <label class="sm-label">نص رسالة الاستدعاء المقترحة:</label>
                    <textarea id="call_in_msg_text" class="sm-textarea" rows="4" style="font-size: 12px; line-height: 1.6;">تحية طيبة، نرجو منكم التكرم بزيارة مكتب الإرشاد الطلابي بالمدرسة في أقرب وقت ممكن لمناقشة أمور هامة تخص ابنكم/ابنتكم. شكراً لتعاونكم المقدر.</textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <button onclick="sendCallViaWhatsApp()" class="sm-btn" style="background: #25d366; border-color: #25d366; height: 38px; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span class="dashicons dashicons-whatsapp" style="font-size: 16px; width:16px; height:16px; margin:0;"></span> الإرسال عبر واتساب
                    </button>
                    <button onclick="sendCallViaEmail()" class="sm-btn" style="background: var(--sm-dark-color); border-color: var(--sm-dark-color); height: 38px; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span class="dashicons dashicons-email" style="font-size: 16px; width:16px; height:16px; margin:0;"></span> الإرسال عبر البريد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const addForm = document.getElementById('add-parent-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_add_parent_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تمت إضافة حساب ولي الأمر بنجاح');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                });
            });
        }
    })();

    let currentParentData = {};

    window.requestCallIn = function(id, name, email, phone) {
        currentParentData = { id, name, email, phone };
        document.getElementById('call_in_parent_name').innerText = name;
        document.getElementById('call-in-modal').style.display = 'flex';
    };

    window.sendCallViaWhatsApp = function() {
        const msg = encodeURIComponent(document.getElementById('call_in_msg_text').value);
        const phone = currentParentData.phone || '';
        if (!phone) {
            alert('رقم الهاتف غير مسجل لهذا الوالد أو صيغته غير صحيحة (يجب أن يكون رقماً إماراتياً).');
            return;
        }
        window.open(`https://wa.me/${phone}?text=${msg}`, '_blank');
    };

    window.sendCallViaEmail = function() {
        const msg = encodeURIComponent(document.getElementById('call_in_msg_text').value);
        const subject = encodeURIComponent('طلب استدعاء رسمي من المدرسة');
        const email = currentParentData.email || '';
        if (!email) {
            alert('البريد الإلكتروني غير مسجل لهذا الوالد.');
            return;
        }
        window.location.href = `mailto:${email}?subject=${subject}&body=${msg}`;
    };
    </script>
</div>
