<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user = $conn->query($user_sql)->fetch_assoc();

// Profile Score Logic
$filled_fields = 0;
$total_fields = 6; 
if ($user['firstname']) $filled_fields++;
if ($user['lastname']) $filled_fields++;
if ($user['email']) $filled_fields++;
$has_resume = $conn->query("SELECT id FROM documents WHERE user_id = $user_id AND document_name = 'Resume'")->num_rows > 0;
if ($has_resume) $filled_fields++;
// Mock others
$profile_percent = round(($filled_fields / $total_fields) * 100);

// Status Tracker
$stages = ['submitted', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved'];
$current_stage_idx = array_search($user['status'], $stages);
if ($current_stage_idx === false && $user['status'] == 'rejected') $current_stage_idx = -1;

// Document Checklist
$required_docs = ['Resume', 'Transcript', 'ID Proof'];
$uploaded_docs_res = $conn->query("SELECT id, document_name, filename, version FROM documents WHERE user_id = $user_id");
$uploaded_docs = [];
while ($d = $uploaded_docs_res->fetch_assoc()) $uploaded_docs[$d['document_name']] = $d;

// Chat Setup (Defaulting recipient to Admin ID 1)
$admin_id = 1; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal - Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="chat.css"> <!-- Include Chat CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-container" style="grid-template-columns: 1fr;">
        
        <header class="top-header">
            <h2 style="margin: 0;">Student Portal</h2>
            <div class="header-actions">
                <div class="profile-score" style="display: flex; align-items: center; gap: 10px; margin-right: 20px;">
                    <div class="progress-circle" style="--p:<?php echo $profile_percent; ?>; --b:5px; --c:var(--primary-color);">
                        <?php echo $profile_percent; ?>%
                    </div>
                    <span style="font-size: 0.8em; color: var(--text-muted);">Profile<br>Completed</span>
                </div>
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </header>

        <main class="wrapper" style="margin-top: 20px;">
            
            <!-- Status Tracker -->
            <div class="dashboard-card" style="margin-bottom: 30px;">
                <h3>Application Status</h3>
                <div class="tracker-container">
                    <?php foreach ($stages as $idx => $stage): 
                        $active = $idx <= $current_stage_idx ? 'active' : '';
                        $current = $idx === $current_stage_idx ? 'current' : '';
                    ?>
                    <div class="step <?php echo $active . ' ' . $current; ?>">
                        <div class="step-icon"><i class="fas fa-check"></i></div>
                        <div class="step-label"><?php echo ucwords(str_replace('_', ' ', $stage)); ?></div>
                    </div>
                    <?php if ($idx < count($stages) - 1): ?>
                    <div class="step-line <?php echo $active; ?>"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid-2-col" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px;">
                
                <!-- Left Column -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <!-- Document Checklist -->
                    <div class="dashboard-card">
                        <h3>Document Checklist</h3>
                        <div class="checklist">
                            <?php foreach ($required_docs as $doc): 
                                $is_uploaded = isset($uploaded_docs[$doc]);
                                $doc_data = $is_uploaded ? $uploaded_docs[$doc] : null;
                            ?>
                            <div class="checklist-item" style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <strong><?php echo $doc; ?></strong>
                                    <?php if ($is_uploaded): ?>
                                        <div>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Uploaded</span>
                                            <a href="view_document.php?id=<?php echo $doc_data['id']; ?>" target="_blank" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Required</span>
                                    <?php endif; ?>
                                </div>
                                <!-- Embed Upload Component -->
                                <?php 
                                    $title = "Upload " . $doc; 
                                    $doc_type = $doc;
                                    $id = strtolower(str_replace(' ', '_', $doc));
                                    include 'upload_component.php'; 
                                ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Interview Scheduler -->
                    <div class="dashboard-card">
                        <h3>Interview Schedule</h3>
                         <div id="interviewStatus">Loading...</div>
                         <div id="slotList" class="slots-container" style="margin-top: 15px; display: none;"></div>
                    </div>

                </div>

                <!-- Right Column: Chat -->
                <div class="dashboard-card" style="display: flex; flex-direction: column; height: 600px;">
                    <h3 style="margin-bottom: 15px;">Message Recruiter</h3>
                    
                    <!-- Chat Container -->
                    <div id="chatContainer" class="chat-interface" data-user-id="<?php echo $user_id; ?>" data-recipient-id="<?php echo $admin_id; ?>" style="flex: 1; display: flex; flex-direction: column; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                        
                        <div class="chat-header" style="padding: 10px; background: var(--bg-body); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div id="userStatus" class="status-indicator offline"></div>
                                <span>Recruiter (Admin)</span>
                            </div>
                            <!-- Typing Indicator -->
                            <div id="typingIndicator" class="typing-indicator" style="display: none;">
                                <span></span><span></span><span></span>
                            </div>
                        </div>

                        <div id="chatMessages" class="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background: var(--chat-bg);">
                            <!-- Messages load here -->
                        </div>

                        <form id="chatForm" class="chat-input-area" style="padding: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; background: var(--bg-card);">
                            <div class="input-wrapper" style="flex: 1; position: relative;">
                                <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off" style="width: 100%; padding: 10px; border-radius: 20px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main);">
                            </div>
                            <button type="submit" class="btn-primary" style="border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>

                </div>

            </div>

        </main>
    </div>

    <style>
        /* ... Existing styles ... */ 
        /* Chat specific overrides if needed */
        .chat-messages { display: flex; flex-direction: column; gap: 10px; }
        .chat-message { max-width: 80%; padding: 10px 15px; border-radius: 15px; font-size: 0.9em; }
        .chat-message.sent { align-self: flex-end; background: var(--primary-color); color: white; border-bottom-right-radius: 2px; }
        .chat-message.received { align-self: flex-start; background: var(--bg-body); border: 1px solid var(--border-color); border-bottom-left-radius: 2px; }
        .chat-meta { font-size: 0.7em; opacity: 0.7; margin-top: 5px; text-align: right; }
        
        .slots-container { display: flex; flex-wrap: wrap; gap: 10px; }
        .btn-slot { padding: 10px 15px; border: 1px solid var(--primary-color); background: rgba(var(--primary-rgb), 0.1); color: var(--primary-color); border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .btn-slot:hover { background: var(--primary-color); color: white; }
    </style>

    <script src="theme.js"></script>
    <script src="chat.js"></script> <!-- Reusing existing chat client -->
    <script>
        // Interview Logic
        document.addEventListener('DOMContentLoaded', () => {
            fetchSlots();
        });

        function fetchSlots() {
            const container = document.getElementById('slotList');
            const statusDiv = document.getElementById('interviewStatus');
            
            fetch('interview_api.php?action=get_slots')
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        statusDiv.innerHTML = '<p class="text-muted">No interview slots available at the moment.</p>';
                        container.style.display = 'none';
                        return;
                    }

                    // Check if already booked
                    const mySlot = data.find(s => s.is_mine);
                    if (mySlot) {
                        statusDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> Interview Scheduled: <strong>${mySlot.start}</strong></div>`;
                        container.style.display = 'none';
                    } else {
                        statusDiv.innerHTML = '<p>Select a time slot:</p>';
                        container.style.display = 'flex';
                        container.innerHTML = '';
                        
                        data.forEach(slot => {
                            if (slot.status === 'open') {
                                const btn = document.createElement('button');
                                btn.className = 'btn-slot';
                                btn.innerText = slot.start;
                                btn.onclick = () => bookSlot(slot.id);
                                container.appendChild(btn);
                            }
                        });
                    }
                });
        }

        function bookSlot(id) {
            if (!confirm('Confirm booking this slot?')) return;
            
            fetch('interview_api.php?action=book', {
                method: 'POST',
                body: JSON.stringify({ slot_id: id }),
                headers: { 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Interview Scheduled!');
                    fetchSlots(); // Refresh
                    window.location.reload(); // Refresh to update tracker
                } else {
                    alert('Error: ' + res.error);
                }
            });
        }
    </script>
</body>
</html>
