<?php
require_once 'config.php';

if (!isLoggedIn() || !checkPermission('view_students')) {
    redirect('index.php');
}

$page = 'kanban';

// Fetch all students grouped by status
$statuses = [
    'submitted' => 'Application Submitted', 
    'documents_uploaded' => 'Documents Uploaded', 
    'under_review' => 'Under Review', 
    'interview_scheduled' => 'Interview Scheduled', 
    'approved' => 'Approved', 
    'rejected' => 'Rejected'
];

$students = [];
// Initialize empty arrays for each status to ensure column exists
foreach ($statuses as $key => $label) {
    $students[$key] = [];
}

$sql = "SELECT id, firstname, lastname, email, student_id, status FROM users WHERE role = 'user' ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ensure status key exists (handle potential unexpected DB values)
        if (array_key_exists($row['status'], $students)) {
            $students[$row['status']][] = $row;
        } else {
            // Fallback for unknown status - maybe put in 'submitted'?
            $students['submitted'][] = $row; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Pipeline - Admin Portal</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body admin-body">
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button id="sidebarToggle" class="theme-toggle" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 style="margin: 0; font-size: 1.5em; color: var(--text-main);">Recruitment Pipeline</h2>
                </div>

                <div class="header-actions" style="display: flex; align-items: center; gap: 20px;">
                    <button id="theme-toggle" class="theme-toggle" aria-label="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <!-- User Profile Dropdown (Simplified for brevity, can duplicate from dashboard) -->
                    <div class="user-profile">
                         <div class="user-avatar" style="width: 35px; height: 35px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white;">
                             <?php echo substr($_SESSION['firstname'], 0, 1); ?>
                        </div>
                    </div>
                </div>
            </header>

            <div class="kanban-board">
                <?php foreach ($statuses as $status_key => $status_label): ?>
                    <div class="kanban-column" data-status="<?php echo $status_key; ?>">
                        <div class="kanban-header">
                            <h3><?php echo $status_label; ?></h3>
                            <span class="count"><?php echo count($students[$status_key]); ?></span>
                        </div>
                        <div class="kanban-items" ondrop="drop(event)" ondragover="allowDrop(event)">
                            <?php foreach ($students[$status_key] as $student): ?>
                                <div class="kanban-card" id="student-<?php echo $student['id']; ?>" draggable="true" ondragstart="drag(event)" data-id="<?php echo $student['id']; ?>">
                                    <div class="card-header">
                                        <h4><?php echo $student['firstname'] . ' ' . $student['lastname']; ?></h4>
                                        <div class="kanban-actions">
                                            <a href="admin_dashboard.php?page=student_detail&id=<?php echo $student['id']; ?>" title="View"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </div>
                                    <p class="student-id">ID: <?php echo $student['student_id']; ?></p>
                                    <p class="student-email"><?php echo $student['email']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <?php include 'bottom_nav.php'; ?>

    <script src="theme.js"></script>
    <script src="admin.js"></script>
    <script>
        // Drag and Drop Logic
        function allowDrop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('drag-over');
        }

        function drag(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
            ev.dataTransfer.setData("studentId", ev.target.getAttribute('data-id'));
            ev.target.classList.add('dragging');
        }

        function drop(ev) {
            ev.preventDefault();
            // Find the closest kanban-items container (in case drop target is a card)
            const column = ev.target.closest('.kanban-column');
            const container = column.querySelector('.kanban-items');
            container.classList.remove('drag-over');
            
            var data = ev.dataTransfer.getData("text");
            var studentId = ev.dataTransfer.getData("studentId");
            var draggedElement = document.getElementById(data);
            
            if (draggedElement) {
                container.appendChild(draggedElement);
                draggedElement.classList.remove('dragging');
                
                // Get new status
                const newStatus = column.getAttribute('data-status');
                
                // Update Backend
                updateStatus(studentId, newStatus);
                
                // Update Counts (Simple UI update)
                updateCounts();
            }
        }

        // Remove drag-over style when leaving
        document.querySelectorAll('.kanban-items').forEach(item => {
            item.addEventListener('dragleave', (e) => {
                e.currentTarget.classList.remove('drag-over');
            });
        });

        function updateStatus(studentId, newStatus) {
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('status', newStatus);

            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optional: Show toast notification
                    console.log('Status updated');
                } else {
                    alert('Failed to update status: ' + data.message);
                    location.reload(); // Revert on failure
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Reverting changes.');
                location.reload();
            });
        }

        function updateCounts() {
            document.querySelectorAll('.kanban-column').forEach(col => {
                const count = col.querySelectorAll('.kanban-card').length;
                col.querySelector('.count').innerText = count;
            });
        }
    </script>
</body>
</html>
