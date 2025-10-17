<header class="top-header">
    <div class="header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
    
    <div class="header-right">
        <div class="admin-profile">
            <span class="admin-name"><?php echo $_SESSION['admin_username']; ?></span>
            <div class="admin-avatar">👤</div>
        </div>
    </div>
</header>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>