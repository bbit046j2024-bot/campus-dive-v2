<aside id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="campus.png" alt="Logo" class="logo-img">
            <h2 class="logo-text">Campus Dive</h2>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="?page=dashboard" class="sidebar-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="?page=messages" class="sidebar-link <?php echo $page == 'messages' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
            <?php if (isset($unread_messages) && $unread_messages > 0): ?>
                <span class="badge"><?php echo $unread_messages; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=documents" class="sidebar-link <?php echo $page == 'documents' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Documents</span>
        </a>
        <a href="?page=status" class="sidebar-link <?php echo $page == 'status' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i>
            <span>Status</span>
        </a>
        <div style="margin-top: auto;">
             <!-- Mobile Logout -->
            <a href="logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>
