<?php
require_once 'config.php';

if (!isLoggedIn() || !checkPermission('view_analytics')) {
    redirect('admin_dashboard.php');
}

$page = 'analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h2 style="margin: 0; font-size: 1.5em; color: var(--text-main);">Analytics Dashboard</h2>
                </div>
                <div class="header-actions">
                     <a href="reports.php" class="btn-primary" style="margin-right: 20px;"><i class="fas fa-download"></i> Export Reports</a>
                     <div class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <span id="bellBadge" style="display:none;">0</span>
                    </div>
                </div>
            </header>

            <div class="stats-row" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <h3>Total Applications</h3>
                    <p class="stat-number" id="kpi-total">Loading...</p>
                    <i class="fas fa-users stat-icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Avg. Time to Hire</h3>
                    <p class="stat-number" id="kpi-time">Loading...</p>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Conversion Rate</h3>
                    <p class="stat-number" id="kpi-conversion">Loading...</p>
                    <i class="fas fa-chart-line stat-icon"></i>
                </div>
            </div>

            <div class="charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <div class="dashboard-card">
                    <h3>Recruitment Funnel</h3>
                    <canvas id="funnelChart"></canvas>
                </div>
                <div class="dashboard-card">
                    <h3>Application Trends (30 Days)</h3>
                    <canvas id="trendChart"></canvas>
                </div>
                <div class="dashboard-card">
                    <h3>Document Submission Status</h3>
                    <div style="height: 300px; display: flex; justify-content: center;">
                        <canvas id="docChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'bottom_nav.php'; ?>
    <script src="theme.js"></script>
    <script src="admin.js"></script>
    <script src="notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('analytics_data.php')
                .then(response => response.json())
                .then(data => {
                    // Update KPIs
                    document.getElementById('kpi-total').innerText = data.kpis.total_applications;
                    document.getElementById('kpi-time').innerText = data.kpis.avg_time_to_hire + ' Days';
                    document.getElementById('kpi-conversion').innerText = data.kpis.conversion_rate + '%';

                    // Funnel Chart (Bar)
                    new Chart(document.getElementById('funnelChart'), {
                        type: 'bar',
                        data: {
                            labels: data.funnel.labels,
                            datasets: [{
                                label: 'Candidates',
                                data: data.funnel.data,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });

                    // Trend Chart (Line)
                    new Chart(document.getElementById('trendChart'), {
                        type: 'line',
                        data: {
                            labels: data.trend.labels,
                            datasets: [{
                                label: 'New Applications',
                                data: data.trend.data,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                tension: 0.3,
                                fill: true,
                                backgroundColor: 'rgba(75, 192, 192, 0.1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                        }
                    });

                    // Document Chart (Doughnut)
                    new Chart(document.getElementById('docChart'), {
                        type: 'doughnut',
                        data: {
                            labels: data.docs.labels,
                            datasets: [{
                                data: data.docs.data,
                                backgroundColor: ['#2ecc71', '#e74c3c']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                })
                .catch(err => console.error('Error loading analytics:', err));
        });
    </script>
</body>
</html>
