<?php
// User Messages Partial — included from user_dashboard.php
// Allows students to initiate conversations with any user, send messages,
// and delete conversations.
if ($page !== 'messages') return;

$current_user_id = $_SESSION['user_id'];
$active_chat_user = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : null;

// ── Handle POST: Send Message ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message'])) {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $msg_text    = trim($conn->real_escape_string($_POST['chat_message']));

    if ($receiver_id && $msg_text) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, type, created_at) VALUES (?, ?, ?, 'text', NOW())");
        $stmt->bind_param("iis", $current_user_id, $receiver_id, $msg_text);
        $stmt->execute();
    }
    header("Location: ?page=messages&chat_with=$receiver_id");
    exit;
}

// ── Handle GET: Delete Conversation ─────────────────────────────────────────
if (isset($_GET['delete_conv']) && intval($_GET['delete_conv']) > 0) {
    $del_uid = intval($_GET['delete_conv']);
    $del_stmt = $conn->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $del_stmt->bind_param("iiii", $current_user_id, $del_uid, $del_uid, $current_user_id);
    $del_stmt->execute();
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Conversation deleted.'];
    header("Location: ?page=messages");
    exit;
}

// ── Fetch Conversations ───────────────────────────────────────────────────────
$conv_sql = "SELECT DISTINCT
    u.id, u.firstname, u.lastname, u.role,
    (SELECT message FROM messages
     WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
     ORDER BY created_at DESC LIMIT 1) as last_msg,
    (SELECT created_at FROM messages
     WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
     ORDER BY created_at DESC LIMIT 1) as last_time,
    (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread
FROM users u
JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
WHERE (m.receiver_id = ? OR m.sender_id = ?) AND u.id != ?
GROUP BY u.id
ORDER BY last_time DESC";

$conv_stmt = $conn->prepare($conv_sql);
$conv_stmt->bind_param("iiiiiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$conv_stmt->execute();
$conversations = $conv_stmt->get_result();

// ── Fetch All Other Users (for New Message modal) ─────────────────────────────
$all_users_stmt = $conn->prepare("SELECT id, firstname, lastname, email, role FROM users WHERE id != ? AND status != 'banned' ORDER BY firstname ASC");
$all_users_stmt->bind_param("i", $current_user_id);
$all_users_stmt->execute();
$all_users = $all_users_stmt->get_result();
?>

<!-- MESSAGES PAGE -->
<div class="dashboard-content">
    <div class="dashboard-card" style="display: grid; grid-template-columns: 300px 1fr; height: calc(100vh - 140px); padding: 0; overflow: hidden;">

        <!-- Left Sidebar: Conversations -->
        <div class="message-sidebar" style="border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background: var(--bg-card);">
            <div class="sidebar-header" style="padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                <h3 style="margin: 0; font-size: 1.1em;">Inbox</h3>
                <button onclick="document.getElementById('newMsgModal').style.display='flex'"
                        style="background: var(--primary-color); color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2em; flex-shrink: 0;"
                        title="New Message">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="conversation-list" style="flex: 1; overflow-y: auto;">
                <?php if ($conversations->num_rows === 0): ?>
                    <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-comments" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                        <p style="font-size: 0.9em;">No conversations yet.</p>
                        <button onclick="document.getElementById('newMsgModal').style.display='flex'"
                                style="margin-top: 10px; background: none; border: none; color: var(--primary-color); cursor: pointer; font-weight: 600; font-size: 0.85em;">
                            + Start a new chat
                        </button>
                    </div>
                <?php else: ?>
                    <?php while ($conv = $conversations->fetch_assoc()):
                        $is_active = ($active_chat_user == $conv['id']) ? 'background: var(--input-bg);' : '';
                        $role_label = ucfirst($conv['role']);
                    ?>
                    <a href="?page=messages&chat_with=<?php echo $conv['id']; ?>"
                       style="display: flex; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--border-color); transition: background 0.2s; position: relative; <?php echo $is_active; ?>">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                            <?php echo strtoupper(substr($conv['firstname'], 0, 1) . substr($conv['lastname'], 0, 1)); ?>
                        </div>
                        <div style="flex: 1; overflow: hidden;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <strong style="font-size: 0.9em;"><?php echo htmlspecialchars($conv['firstname'] . ' ' . $conv['lastname']); ?></strong>
                                <span style="font-size: 0.72em; color: var(--text-muted);"><?php echo $conv['last_time'] ? date('M d', strtotime($conv['last_time'])) : ''; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.82em; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                                    <?php echo htmlspecialchars(substr($conv['last_msg'] ?? '', 0, 35)) . (strlen($conv['last_msg'] ?? '') > 35 ? '…' : ''); ?>
                                </span>
                                <?php if ($conv['unread'] > 0): ?>
                                    <span style="background: var(--danger-color); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; font-weight: bold;">
                                        <?php echo $conv['unread']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Chat Area -->
        <div class="chat-area" style="display: flex; flex-direction: column; background: var(--bg-body);">
            <?php if ($active_chat_user):
                // Fetch Details
                $cu_res = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $cu_res->bind_param("i", $active_chat_user);
                $cu_res->execute();
                $chat_user = $cu_res->get_result()->fetch_assoc();

                // Mark as Read
                $stmt_read = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
                $stmt_read->bind_param("ii", $active_chat_user, $current_user_id);
                $stmt_read->execute();
            ?>
            <!-- Chat Header -->
            <div style="padding: 12px 15px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                <a href="?page=messages" style="color: var(--text-muted); text-decoration: none; margin-right: 4px;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink:0;">
                    <?php echo strtoupper(substr($chat_user['firstname'], 0, 1) . substr($chat_user['lastname'], 0, 1)); ?>
                </div>
                <div style="flex:1;">
                    <strong style="font-size: 0.95em;"><?php echo htmlspecialchars($chat_user['firstname'] . ' ' . $chat_user['lastname']); ?></strong>
                    <div style="font-size: 0.75em; color: var(--text-muted);"><?php echo ucfirst($chat_user['role']); ?></div>
                </div>
                <!-- Delete Conversation -->
                <a href="?page=messages&delete_conv=<?php echo $active_chat_user; ?>"
                   onclick="return confirm('Delete this entire conversation? This cannot be undone.')"
                   title="Delete conversation"
                   style="color: var(--text-muted); text-decoration: none; padding: 6px 8px; border-radius: 8px; transition: color 0.2s;"
                   onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>

            <!-- Message History -->
            <div id="chatMessages" style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
                <?php
                $msgs_stmt = $conn->prepare("SELECT m.*, u.firstname as sender_name FROM messages m JOIN users u ON u.id = m.sender_id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC");
                $msgs_stmt->bind_param("iiii", $current_user_id, $active_chat_user, $active_chat_user, $current_user_id);
                $msgs_stmt->execute();
                $msgs = $msgs_stmt->get_result();

                if ($msgs->num_rows === 0):
                ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; color: var(--text-muted);">
                        <i class="fas fa-comments" style="font-size: 2em; margin-bottom: 10px;"></i>
                        <p>No messages yet — say hello!</p>
                    </div>
                <?php else: while ($m = $msgs->fetch_assoc()):
                    $is_me = ($m['sender_id'] == $current_user_id);
                ?>
                    <div style="display: flex; justify-content: <?php echo $is_me ? 'flex-end' : 'flex-start'; ?>">
                        <div style="<?php echo $is_me ? 'background: var(--primary-color); color: white;' : 'background: var(--bg-card); border: 1px solid var(--border-color);'; ?> padding: 10px 15px; border-radius: <?php echo $is_me ? '15px 15px 4px 15px' : '15px 15px 15px 4px'; ?>; max-width: 70%;">
                            <div style="font-size: 0.9em;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                            <?php if (!empty($m['attachment_path'])): ?>
                                <a href="<?php echo htmlspecialchars($m['attachment_path']); ?>" target="_blank"
                                   style="display: flex; align-items: center; gap: 4px; margin-top: 6px; font-size: 0.75em; opacity: 0.8; <?php echo $is_me ? 'color: white;' : 'color: var(--primary-color);'; ?> text-decoration: underline;">
                                    <i class="fas fa-paperclip"></i> Attachment
                                </a>
                            <?php endif; ?>
                            <div style="font-size: 0.7em; opacity: 0.65; text-align: right; margin-top: 4px;">
                                <?php echo date('H:i', strtotime($m['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
            </div>

            <!-- Message Input Form -->
            <form method="POST" action="?page=messages&chat_with=<?php echo $active_chat_user; ?>"
                  style="padding: 12px 15px; background: var(--bg-card); border-top: 1px solid var(--border-color); display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="receiver_id" value="<?php echo $active_chat_user; ?>">
                <input type="text" name="chat_message" placeholder="Type a message…" required autocomplete="off"
                       style="flex: 1; padding: 10px 15px; border-radius: 20px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-size: 0.9em;">
                <button type="submit" style="background: var(--primary-color); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>

            <?php else: ?>
            <!-- No active thread selected -->
            <div style="display: flex; align-items: center; justify-content: center; flex: 1; color: var(--text-muted); flex-direction: column; gap: 15px;">
                <i class="fas fa-comments" style="font-size: 4em;"></i>
                <p>Select a conversation or start a new one</p>
                <button onclick="document.getElementById('newMsgModal').style.display='flex'"
                        style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div id="newMsgModal" style="display: none; position: fixed; inset: 0; z-index: 1000; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
    <div style="background: var(--bg-card); border-radius: 16px; width: 100%; max-width: 440px; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
            <strong><i class="fas fa-user-plus" style="color: var(--primary-color); margin-right: 8px;"></i>Start New Conversation</strong>
            <button onclick="document.getElementById('newMsgModal').style.display='none'"
                    style="background: none; border: none; cursor: pointer; font-size: 1.2em; color: var(--text-muted);">&times;</button>
        </div>
        <div style="padding: 12px 15px; border-bottom: 1px solid var(--border-color); background: var(--input-bg);">
            <input type="text" id="userSearchInput" placeholder="Search by name or email…" oninput="filterUsers()"
                   style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); font-size: 0.9em; box-sizing: border-box;">
        </div>
        <div id="userList" style="flex: 1; overflow-y: auto;">
            <?php
            $all_users->data_seek(0);
            while ($u = $all_users->fetch_assoc()):
                $role_color = match($u['role']) {
                    'admin'   => '#ef4444',
                    'manager' => '#3b82f6',
                    default   => '#6b7280'
                };
            ?>
            <a href="?page=messages&chat_with=<?php echo $u['id']; ?>"
               class="user-item"
               data-name="<?php echo strtolower($u['firstname'] . ' ' . $u['lastname']); ?>"
               data-email="<?php echo strtolower($u['email']); ?>"
               style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--border-color); transition: background 0.15s;"
               onmouseover="this.style.background='var(--input-bg)'" onmouseout="this.style.background='none'">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; font-size: 0.9em;">
                    <?php echo strtoupper(substr($u['firstname'], 0, 1) . substr($u['lastname'], 0, 1)); ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: 0.9em;"><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></div>
                    <div style="font-size: 0.78em; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($u['email']); ?></div>
                </div>
                <span style="font-size: 0.72em; padding: 3px 8px; border-radius: 20px; background: <?php echo $role_color; ?>20; color: <?php echo $role_color; ?>; font-weight: 600; text-transform: uppercase; flex-shrink: 0;">
                    <?php echo htmlspecialchars($u['role']); ?>
                </span>
            </a>
            <?php endwhile; ?>
            <div id="noUsersMsg" style="display: none; padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.9em;">No users found.</div>
        </div>
    </div>
</div>

<script>
// Auto-scroll chat to bottom
const chatMsgs = document.getElementById('chatMessages');
if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;

// Filter users in modal
function filterUsers() {
    const q = document.getElementById('userSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('#userList .user-item');
    let visible = 0;
    items.forEach(item => {
        const match = item.dataset.name.includes(q) || item.dataset.email.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('noUsersMsg').style.display = visible === 0 ? 'block' : 'none';
}

// Close modal on backdrop click
document.getElementById('newMsgModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
