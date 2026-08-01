<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-messaging-center" style="display: grid; grid-template-columns: 300px 1fr; gap: 0; background: #fff; border: 1px solid var(--sm-border-color); border-radius: 12px; overflow: hidden; min-height: 600px;">
    <!-- Sidebar: Conversations -->
    <div style="border-left: 1px solid var(--sm-border-color); background: #f8fafc; display: flex; flex-direction: column;">
        <div style="padding: 20px; border-bottom: 1px solid var(--sm-border-color); background: #fff;">
            <h3 style="margin:0; font-size: 1.1em;">المحادثات</h3>
            <button onclick="document.getElementById('new-msg-modal').style.display='flex'" class="sm-btn" style="margin-top:10px; font-size: 12px; padding: 8px;">+ رسالة جديدة</button>
        </div>
        <div id="conversations-list" style="flex: 1; overflow-y: auto;">
            <?php 
            $my_id = get_current_user_id();
            $conversations = SM_DB::get_conversations($my_id);
            if (empty($conversations)): ?>
                <p style="text-align: center; color: #999; margin-top: 30px; font-size: 0.9em;">لا يوجد محادثات نشطة.</p>
            <?php else: ?>
                <?php foreach ($conversations as $conv): 
                    $other_user = $conv['user'];
                    $last_msg = $conv['last_message'];
                ?>
                    <div class="conv-item" onclick="loadConversation(<?php echo $other_user->ID; ?>, '<?php echo esc_js($other_user->display_name); ?>')" style="padding: 15px 20px; border-bottom: 1px solid #edf2f7; cursor: pointer; transition: background 0.2s;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php echo get_avatar($other_user->ID, 40, '', '', array('style' => 'border-radius: 50%;')); ?>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 0.95em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($other_user->display_name); ?></div>
                                <div style="font-size: 0.8em; color: #718096; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo esc_html($last_msg->message); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main View: Chat -->
    <div id="chat-window" style="display: flex; flex-direction: column; background: #fff; position: relative;">
        <div id="chat-header" style="padding: 15px 30px; border-bottom: 1px solid var(--sm-border-color); display: flex; align-items: center; gap: 15px; visibility: hidden;">
            <div id="active-user-avatar"></div>
            <h3 id="active-user-name" style="margin:0; font-size: 1.1em;"></h3>
        </div>
        
        <div id="chat-messages" style="flex: 1; padding: 30px; overflow-y: auto; background: #f0f4f8; display: flex; flex-direction: column; gap: 15px;">
            <div style="text-align: center; color: #a0aec0; margin-top: 100px;">
                <span class="dashicons dashicons-format-chat" style="font-size: 50px; width: 50px; height: 50px;"></span>
                <p>اختر محادثة للبدء في المراسلة</p>
            </div>
        </div>

        <div id="chat-input-area" style="padding: 20px 30px; border-top: 1px solid var(--sm-border-color); display: none;">
            <form id="chat-form" style="display: flex; gap: 15px;">
                <input type="hidden" name="receiver_id" id="chat_receiver_id">
                <textarea name="message" class="sm-textarea" style="flex: 1; resize: none;" rows="1" placeholder="اكتب رسالتك هنا..." required></textarea>
                <button type="submit" class="sm-btn" style="width: auto; padding: 0 25px;">إرسال</button>
            </form>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div id="new-msg-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 500px;">
        <div class="sm-modal-header">
            <h3>بدء محادثة جديدة</h3>
            <button class="sm-modal-close" onclick="document.getElementById('new-msg-modal').style.display='none'">&times;</button>
        </div>
        <form id="new-msg-form">
            <div class="sm-form-group">
                <label class="sm-label">اختر المستلم:</label>
                <select name="receiver_id" class="sm-select" required>
                    <option value="">بحث عن مستخدم...</option>
                    <?php 
                    $users = get_users(array('exclude' => array($my_id)));
                    foreach ($users as $u) {
                        $role = !empty($u->roles) ? $u->roles[0] : '';
                        echo '<option value="'.$u->ID.'">'.$u->display_name.' ('.$role.')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="sm-form-group">
                <label class="sm-label">الرسالة الأولى:</label>
                <textarea name="message" class="sm-textarea" rows="4" required></textarea>
            </div>
            <button type="submit" class="sm-btn">بدء المحادثة</button>
        </form>
    </div>
</div>

<style>
.conv-item:hover { background: #edf2f7; }
.conv-item.sm-active { background: #e2e8f0; border-right: 4px solid var(--sm-primary-color); }
.msg-bubble { max-width: 70%; padding: 12px 18px; border-radius: 18px; font-size: 0.95em; position: relative; }
.msg-sent { align-self: flex-start; background: var(--sm-primary-color); color: #fff; border-bottom-right-radius: 4px; }
.msg-received { align-self: flex-end; background: #fff; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; }
.msg-time { font-size: 0.7em; margin-top: 5px; opacity: 0.8; display: block; text-align: left; }
</style>

<script>
window.loadConversation = function(otherId, otherName) {
    // UI Updates
    const header = document.getElementById('chat-header');
    if (header) header.style.visibility = 'visible';
    
    const nameEl = document.getElementById('active-user-name');
    if (nameEl) nameEl.innerText = otherName;
    
    const recId = document.getElementById('chat_receiver_id');
    if (recId) recId.value = otherId;
    
    const inputArea = document.getElementById('chat-input-area');
    if (inputArea) inputArea.style.display = 'block';
    
    // Set active item
    const items = document.querySelectorAll('.conv-item');
    items.forEach(i => i.classList.remove('sm-active'));
    
    // Find clicked item (usually via event but we can search)
    const activeItem = Array.from(items).find(i => i.getAttribute('onclick') && i.getAttribute('onclick').includes(otherId));
    if (activeItem) activeItem.classList.add('sm-active');
    
    // Fetch Messages via AJAX
    window.fetchMessages(otherId);
};

window.fetchMessages = function(otherId) {
    const container = document.getElementById('chat-messages');
    container.innerHTML = '<div style="text-align:center; margin-top:50px;">جاري تحميل الرسائل...</div>';

    const formData = new FormData();
    formData.append('action', 'sm_get_conversation_ajax');
    formData.append('other_user_id', otherId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            container.innerHTML = '';
            res.data.forEach(m => {
                const isSent = m.sender_id == <?php echo $my_id; ?>;
                const div = document.createElement('div');
                div.className = 'msg-bubble ' + (isSent ? 'msg-sent' : 'msg-received');
                div.innerHTML = `
                    <div style="font-weight:700; font-size:0.75em; margin-bottom:4px;">${m.sender_name}</div>
                    <div>${m.message}</div>
                    <span class="msg-time">${m.created_at}</span>
                `;
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
            
            // Mark as read
            const markForm = new FormData();
            markForm.append('action', 'sm_mark_read');
            markForm.append('other_user_id', otherId);
            markForm.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: markForm });
        }
    });
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'sm_send_message_ajax');
    formData.append('sm_message_nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            this.querySelector('textarea').value = '';
            fetchMessages(formData.get('receiver_id'));
        }
    });
});

document.getElementById('new-msg-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'sm_send_message_ajax');
    formData.append('sm_message_nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        }
    });
});
</script>
