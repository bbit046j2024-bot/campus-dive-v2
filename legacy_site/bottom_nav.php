<nav class="bottom-nav">
    <a href="admin_dashboard.php?page=dashboard" class="nav-item-mobile <?php echo $page == 'dashboard' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    
    <?php if (checkPermission('view_students')): ?>
    <a href="admin_dashboard.php?page=students" class="nav-item-mobile <?php echo $page == 'students' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        <span>Students</span>
    </a>
    <?php endif; ?>

    <a href="admin_dashboard.php?page=messages" class="nav-item-mobile <?php echo $page == 'messages' ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i>
        <span>Chat</span>
    </a>

    <a href="settings.php" class="nav-item-mobile <?php echo $page == 'settings' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </a>
</nav>
