<?php
require_once 'config.php';

if (!isLoggedIn() || !checkPermission('view_analytics')) {
    redirect('admin_dashboard.php');
}

if (isset($_POST['export_csv'])) {
    $filename = "applications_report_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    
    // Header Row
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Student ID', 'Status', 'Applied Date']);
    
    // Data Rows
    $sql = "SELECT id, firstname, lastname, email, student_id, status, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['firstname'],
            $row['lastname'],
            $row['email'],
            $row['student_id'],
            ucfirst($row['status']),
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reports - Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h2 style="margin: 0; font-size: 1.5em; color: var(--text-main);">Reports Center</h2>
                </div>
            </header>

            <div class="reports-container" style="max-width: 800px; margin: 40px auto;">
                <div class="dashboard-card">
                    <h3><i class="fas fa-file-csv"></i> Export Student Data</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">
                        Download a complete list of all student applications, including their current status and contact details, in CSV format compatible with Excel.
                    </p>
                    
                    <form method="POST">
                        <button type="submit" name="export_csv" class="btn-primary" style="width: 100%; padding: 15px; font-size: 1.1em;">
                            <i class="fas fa-download"></i> Download CSV Report
                        </button>
                    </form>
                </div>

                <!-- Placeholder for PDF Export (Requires TCPDF or similar lib) -->
                <div class="dashboard-card" style="margin-top: 20px; opacity: 0.7;">
                    <h3><i class="fas fa-file-pdf"></i> PDF Summary Report</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">
                        Generate a printable PDF summary of recruitment statistics and charts. (Coming Soon)
                    </p>
                    <button disabled class="btn-secondary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-lock"></i> Feature Unavailable
                    </button>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'bottom_nav.php'; ?>
    <script src="theme.js"></script>
    <script src="admin.js"></script>
</body>
</html>
