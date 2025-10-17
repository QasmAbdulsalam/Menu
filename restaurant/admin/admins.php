<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

// Handle admin deletion (only if more than 1 admin exists)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'delete') {
        $admin_id = (int)$_POST['id'];
        
        // Don't allow deleting yourself
        if ($admin_id == $_SESSION['admin_id']) {
            $error = 'You cannot delete your own account!';
        } else {
            // Check if there's more than one admin
            $stmt = $conn->query("SELECT COUNT(*) as total FROM admins");
            $total_admins = $stmt->fetch()['total'];
            
            if ($total_admins <= 1) {
                $error = 'Cannot delete the last admin account!';
            } else {
                $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                if ($stmt->execute([$admin_id])) {
                    $success = 'Admin deleted successfully!';
                } else {
                    $error = 'Failed to delete admin!';
                }
            }
        }
    }
}

// Get all admins
$stmt = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Admin Accounts</h1>
                <a href="../register.php" class="btn btn-primary">+ Add New Admin</a>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($admins as $admin): ?>
                            <tr>
                                <td>#<?php echo $admin['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                    <?php if($admin['id'] == $_SESSION['admin_id']): ?>
                                        <span class="badge" style="background: var(--primary); color: white; margin-left: 5px;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                <td><span class="badge" style="background: #d1fae5; color: #065f46;">Active</span></td>
                                <td>
                                    <?php if($admin['id'] != $_SESSION['admin_id']): ?>
                                        <button class="btn-icon" onclick="deleteAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">🗑️</button>
                                    <?php else: ?>
                                        <a href="profile.php" class="btn-icon" title="Edit Profile">✏️</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card" style="margin-top: 30px;">
                <h2>Admin Management Info</h2>
                <div style="margin-top: 15px; line-height: 1.8;">
                    <p>📝 <strong>Total Admins:</strong> <?php echo count($admins); ?></p>
                    <p>🔒 <strong>Security:</strong> All passwords are encrypted using bcrypt hashing</p>
                    <p>⚠️ <strong>Note:</strong> You cannot delete your own account or the last admin account</p>
                    <p>✅ <strong>Best Practice:</strong> Regularly review admin accounts and remove inactive ones</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function deleteAdmin(id, username) {
            if (confirm(`Are you sure you want to delete admin "${username}"?\n\nThis action cannot be undone!`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>