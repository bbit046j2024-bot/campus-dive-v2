                    <!-- MESSAGES PAGE -->
        <?php if ($page == 'messages'): 
            $active_chat_user = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : null;
        ?>
        <div class="dashboard-content">
            <div class="dashboard-card" style="display: grid; grid-template-columns: 300px 1fr; height: calc(100vh - 140px); padding: 0; overflow: hidden;">
                
                <!-- Left Sidebar: Inbox -->
                <div class="message-sidebar" style="border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background: var(--bg-card);">
                    <div class="sidebar-header" style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0; font-size: 1.1em;">Inbox</h3>
                    </div>
                    <div class="conversation-list" style="flex: 1; overflow-y: auto;">
                        <?php 
                        // Get unique users who have chatted with admin
                        $sql = "SELECT DISTINCT 
                                    u.id, u.firstname, u.lastname, u.role,
                                    (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_msg,
                                    (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_time,
                                    (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread
                                FROM users u
                                JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
                                WHERE (m.receiver_id = ? OR m.sender_id = ?) AND u.id != ?
                                GROUP BY u.id
                                ORDER BY last_time DESC";
                        
                        $stmt = $conn->prepare($sql);
                        $admin_id = $_SESSION['user_id'];
                        $stmt->bind_param("iiiiiiii", $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id);
                        $stmt->execute();
                        $conversations = $stmt->get_result();

                        while ($conv = $conversations->fetch_assoc()):
                            $is_active = ($active_chat_user == $conv['id']) ? 'background: var(--input-bg);' : '';
                        ?>
                        <a href="?page=messages&chat_with=<?php echo $conv['id']; ?>" class="conversation-item" style="display: flex; gap: 10px; padding: 15px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--border-color); transition: background 0.2s; <?php echo $is_active; ?>">
                            <div class="avatar" style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                                <?php echo substr($conv['firstname'], 0, 1) . substr($conv['lastname'], 0, 1); ?>
                            </div>
                            <div class="conv-info" style="flex: 1; overflow: hidden;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <strong style="font-size: 0.9em;"><?php echo $conv['firstname'] . ' ' . $conv['lastname']; ?></strong>
                                    <span style="font-size: 0.75em; color: var(--text-muted);"><?php echo date('M d', strtotime($conv['last_time'])); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="font-size: 0.85em; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                                        <?php echo htmlspecialchars(substr($conv['last_msg'], 0, 30)) . '...'; ?>
                                    </span>
                                    <?php if ($conv['unread'] > 0): ?>
                                    <span class="badge" style="background: var(--danger-color); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em;"><?php echo $conv['unread']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Right Side: Chat Area -->
                <div class="chat-area" style="display: flex; flex-direction: column; background: var(--bg-body);">
                    <?php if ($active_chat_user): 
                        // Fetch User Details
                        $user_res = $conn->query("SELECT * FROM users WHERE id = $active_chat_user");
                        $chat_user = $user_res->fetch_assoc();
                        
                        // Mark as Read
                        $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $active_chat_user AND receiver_id = $admin_id");
                    ?>
                    <div class="chat-header" style="padding: 15px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                        <h3 style="margin: 0; font-size: 1.1em;">Chat with <?php echo $chat_user['firstname'] . ' ' . $chat_user['lastname']; ?></h3>
                        <div id="userStatus" class="status-indicator offline" title="Offline"></div>
                    </div>

                    <div id="chatMessages" class="chat-messages" style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px;">
                        <?php 
                        // Fetch History
                        $msgs = $conn->query("SELECT * FROM messages 
                                            WHERE (sender_id = $admin_id AND receiver_id = $active_chat_user) 
                                               OR (sender_id = $active_chat_user AND receiver_id = $admin_id)
                                            ORDER BY created_at ASC");
                        while ($m = $msgs->fetch_assoc()):
                            $is_me = ($m['sender_id'] == $admin_id);
                        ?>
                        <div class="chat-message <?php echo $is_me ? 'sent' : 'received'; ?>" style="<?php echo $is_me ? 'align-self: flex-end; background: var(--primary-color); color: white;' : 'align-self: flex-start; background: var(--bg-card); border: 1px solid var(--border-color);'; ?> padding: 10px 15px; border-radius: 15px; max-width: 70%;">
                            <div class="msg-content"><?php echo htmlspecialchars($m['message']); ?></div>
                            <div class="msg-meta" style="font-size: 0.7em; opacity: 0.7; text-align: right; margin-top: 5px;">
                                <?php echo date('H:i', strtotime($m['created_at'])); ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <form id="chatForm" style="padding: 15px; background: var(--bg-card); border-top: 1px solid var(--border-color); display: flex; gap: 10px;">
                        <input type="text" id="chatInput" placeholder="Type a message..." style="flex: 1; padding: 10px; border-radius: 20px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main);">
                        <button type="submit" class="btn-primary" style="border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-paper-plane"></i></button>
                    </form>

                    <!-- Init Chat for Admin -->
                    <div id="chatContainer" data-user-id="<?php echo $admin_id; ?>" data-recipient-id="<?php echo $active_chat_user; ?>" style="display: none;"></div>
                    <script src="chat.js"></script>

                    <?php else: ?>
                    <div class="empty-state" style="display: flex; align-items: center; justify-content: center; flex: 1; color: var(--text-muted); flex-direction: column;">
                        <i class="fas fa-comments" style="font-size: 4em; margin-bottom: 20px;"></i>
                        <p>Select a conversation to start chatting</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
