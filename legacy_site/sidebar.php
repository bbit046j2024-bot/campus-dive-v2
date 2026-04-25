<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="campus.png" alt="Logo" class="logo-img" style="width: 40px; height: 40px;">
        <h2 class="logo-text" style="font-size: 1.2em; margin: 0;">Admin Portal</h2>
    </div>

    <nav class="sidebar-nav">
        <a href="admin_dashboard.php?page=dashboard" class="sidebar-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home" style="width: 25px;"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if (checkPermission('view_students')): ?>
        <a href="admin_dashboard.php?page=students" class="sidebar-link <?php echo $page == 'students' ? 'active' : ''; ?>">
            <i class="fas fa-users" style="width: 25px;"></i>
            <span>Students</span>
        </a>
        <?php endif; ?>

        <?php if (checkPermission('send_messages')): ?>
        <a href="admin_dashboard.php?page=messages" class="sidebar-link <?php echo $page == 'messages' ? 'active' : ''; ?>">
            <i class="fas fa-envelope" style="width: 25px;"></i>
            <span>Messages</span>
            <?php if (isset($unread_messages) && $unread_messages > 0): ?>
                <span class="badge" style="margin-left: auto; background: var(--danger-color); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75em;"><?php echo $unread_messages; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if (checkPermission('approve_applications')): ?>
        <a href="kanban.php" class="sidebar-link <?php echo $page == 'kanban' ? 'active' : ''; ?>">
            <i class="fas fa-columns" style="width: 25px;"></i>
            <span>Pipeline</span>
        </a>
        <?php endif; ?>

        <a href="admin_dashboard.php?page=documents" class="sidebar-link <?php echo $page == 'documents' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt" style="width: 25px;"></i>
            <span>Documents</span>
        </a>

        <?php if (checkPermission('manage_settings')): ?>
        <a href="settings.php" class="sidebar-link <?php echo $page == 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog" style="width: 25px;"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>

        <?php if (checkPermission('manage_roles')): ?>
        <a href="roles.php" class="sidebar-link <?php echo $page == 'roles' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield" style="width: 25px;"></i>
            <span>Roles & Permissions</span>
        </a>
        <?php endif; ?>

        <?php if (checkPermission('view_analytics')): ?>
        <a href="analytics.php" class="sidebar-link <?php echo $page == 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line" style="width: 25px;"></i>
            <span>Analytics</span>
        </a>
        <a href="campaigns.php" class="sidebar-link <?php echo $page == 'campaigns' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn" style="width: 25px;"></i>
            <span>Campaigns</span>
        </a>
        <?php endif; ?>
        
        <a href="logout.php" class="sidebar-link" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt" style="width: 25px;"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>
