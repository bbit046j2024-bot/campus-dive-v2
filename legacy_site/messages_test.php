<?php
require_once 'config.php';

// Simple messaging that works
$page_title = "Simple Messaging Test";

// Get all users for dropdown
$users_result = $conn->query("SELECT id, firstname, lastname, email, role FROM users ORDER BY role, firstname");
$users = [];
while ($u = $users_result->fetch_assoc()) {
    $users[] = $u;
}

// Handle message sending
$message_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_msg'])) {
    $sender_id = intval($_POST['sender_id']);
    $receiver_id = intval($_POST['receiver_id']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $message = $conn->real_escape_string($_POST['message']);

    if ($sender_id > 0 && $receiver_id > 0 && !empty($subject) && !empty($message)) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) 
                VALUES ($sender_id, $receiver_id, '$subject', '$message', 0)";

        if ($conn->query($sql)) {
            $message_status = "<p style='color: green; background: #d4edda; padding: 10px; border-radius: 5px;'>✅ Message sent successfully!</p>";
        } else {
            $message_status = "<p style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>❌ Error: " . $conn->error . "</p>";
        }
    } else {
        $message_status = "<p style='color: orange; background: #fff3cd; padding: 10px; border-radius: 5px;'>⚠️ Please fill all fields</p>";
    }
}

// Get messages
$messages_query = "SELECT m.*, 
                          s.firstname as sender_fname, s.lastname as sender_lname, s.role as sender_role,
                          r.firstname as receiver_fname, r.lastname as receiver_lname, r.role as receiver_role
                   FROM messages m
                   JOIN users s ON m.sender_id = s.id
                   JOIN users r ON m.receiver_id = r.id
                   ORDER BY m.created_at DESC LIMIT 20";
$messages_result = $conn->query($messages_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title; ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #1e5aa8; color: white; }
        tr:hover { background: #f5f5f5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        textarea { height: 100px; resize: vertical; }
        button { padding: 12px 30px; background: #1e5aa8; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #154785; }
        .message-row { padding: 15px; margin-bottom: 10px; border-radius: 8px; }
        .message-row.sent { background: #e3f2fd; margin-left: 20%; }
        .message-row.received { background: #e8f5e9; margin-right: 20%; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-user { background: #27ae60; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>💬 Simple Messaging Test</h1>
    <?php echo $message_status; ?>
</div>

<div class="container">
    <h2>Send Message</h2>
    <form method="POST">
        <div class="form-group">
            <label>From:</label>
            <select name="sender_id" required>
                <option value="">Select Sender</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['id']; ?>">
                    <?php echo $u['firstname'] . ' ' . $u['lastname'] . ' (' . $u['email'] . ') - ' . ucfirst($u['role']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>To:</label>
            <select name="receiver_id" required>
                <option value="">Select Receiver</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['id']; ?>">
                    <?php echo $u['firstname'] . ' ' . $u['lastname'] . ' (' . $u['email'] . ') - ' . ucfirst($u['role']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Subject:</label>
            <input type="text" name="subject" placeholder="Enter subject" required>
        </div>

        <div class="form-group">
            <label>Message:</label>
            <textarea name="message" placeholder="Type your message here..." required></textarea>
        </div>

        <button type="submit" name="send_msg">Send Message</button>
    </form>
</div>

<div class="container">
    <h2>All Messages (<?php echo $messages_result ? $messages_result->num_rows : 0; ?>)</h2>

    <?php if ($messages_result && $messages_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>From</th>
                <th>To</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php while ($msg = $messages_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $msg['id']; ?></td>
                <td>
                    <?php echo $msg['sender_fname'] . ' ' . $msg['sender_lname']; ?>
                    <span class="badge badge-<?php echo $msg['sender_role']; ?>"><?php echo $msg['sender_role']; ?></span>
                </td>
                <td>
                    <?php echo $msg['receiver_fname'] . ' ' . $msg['receiver_lname']; ?>
                    <span class="badge badge-<?php echo $msg['receiver_role']; ?>"><?php echo $msg['receiver_role']; ?></span>
                </td>
                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                <td><?php echo htmlspecialchars(substr($msg['message'], 0, 50)) . '...'; ?></td>
                <td><?php echo $msg['is_read'] ? '✅ Read' : '❌ Unread'; ?></td>
                <td><?php echo $msg['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No messages yet.</p>
    <?php endif; ?>
</div>

<div class="container">
    <p><a href="index.php" style="padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;">Back to Login</a></p>
</div>

</body>
</html>