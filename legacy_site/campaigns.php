<?php
require_once 'config.php';

if (!isLoggedIn() || !checkPermission('approve_applications')) { // Assuming high level permission needed
    redirect('admin_dashboard.php');
}

$page = 'campaigns';

// Handle Campaign Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
    $name = sanitize($conn, $_POST['name']);
    $subject = sanitize($conn, $_POST['subject']);
    $content = $conn->real_escape_string($_POST['content']); // Allow HTML
    $segment = $_POST['segment']; // 'all', 'status:approved', etc.
    $type = 'email'; // limiting to email for now
    
    // 1. Create Template (or update if reusing logic, here we create new for simplicity)
    $stmt = $conn->prepare("INSERT INTO marketing_templates (name, subject, body_content, type) VALUES (?, ?, ?, ?)");
    $tmpl_name = $name . " Template";
    $stmt->bind_param("ssss", $tmpl_name, $subject, $content, $type);
    $stmt->execute();
    $template_id = $conn->insert_id;
    
    // 2. Create Campaign
    $stmt = $conn->prepare("INSERT INTO marketing_campaigns (name, template_id, type, segment_criteria, status, created_by) VALUES (?, ?, ?, ?, 'draft', ?)");
    $segment_json = json_encode(['filter' => $segment]);
    $stmt->bind_param("sissi", $name, $template_id, $type, $segment_json, $_SESSION['user_id']);
    $stmt->execute();
    
    $campaign_id = $conn->insert_id;
    
    // Redirect to review/scheduling page (omitted for brevity, staying here)
    $success = "Campaign Draft Created! ID: " . $campaign_id;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketing Campaigns - Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: '#emailContent',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
      });
    </script>
</head>
<body class="dashboard-body admin-body">
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
             <header class="top-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button id="sidebarToggle" class="theme-toggle" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 style="margin: 0; font-size: 1.5em; color: var(--text-main);">Marketing Campaigns</h2>
                </div>
            </header>

            <div style="padding: 20px;">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="dashboard-card generic-card">
                    <h3><i class="fas fa-paper-plane"></i> Create New Campaign</h3>
                    <form method="POST">
                        <div class="input-box">
                            <label>Campaign Name</label>
                            <input type="text" name="name" required placeholder="e.g., Summer Internship Announcement">
                        </div>
                        
                        <div class="input-box">
                            <label>Target Audience (Segment)</label>
                            <select name="segment" class="input-field" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main);">
                                <option value="all">All Students</option>
                                <option value="status:submitted">Pending Applications</option>
                                <option value="status:approved">Approved Students</option>
                                <option value="status:rejected">Rejected Students</option>
                            </select>
                        </div>
                        
                        <div class="input-box">
                            <label>Email Subject</label>
                            <input type="text" name="subject" required placeholder="Enter email subject">
                        </div>
                        
                        <div class="input-box">
                            <label>Email Content</label>
                            <p style="font-size: 0.9em; color: var(--text-muted); margin-bottom: 5px;">
                                Available variables: <code>{{firstname}}</code>, <code>{{lastname}}</code>, <code>{{email}}</code>, <code>{{status}}</code>.
                            </p>
                            <textarea id="emailContent" name="content" rows="15"></textarea>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: right;">
                             <button type="submit" name="create_campaign" class="btn-primary">
                                 <i class="fas fa-save"></i> Save & Next
                             </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Campaigns List -->
                <div class="dashboard-card generic-card" style="margin-top: 30px;">
                    <h3>Recent Campaigns</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Audience</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $c_res = $conn->query("SELECT * FROM marketing_campaigns ORDER BY created_at DESC LIMIT 5");
                            while ($row = $c_res->fetch_assoc()):
                                $criteria = json_decode($row['segment_criteria'], true);
                                $segment = $criteria['filter'] ?? 'All';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><span class="status-badge status-<?php echo $row['status'] == 'draft' ? 'pending' : 'approved'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td><?php echo ucfirst($segment); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'draft'): ?>
                                    <form action="launch_campaign.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="campaign_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="launch_campaign" class="btn-primary" style="padding: 5px 10px; font-size: 0.8em;">Launch</button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color: var(--success-color);"><i class="fas fa-check"></i> Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'bottom_nav.php'; ?>
    <script src="theme.js"></script>
    <script src="admin.js"></script>
</body>
</html>
