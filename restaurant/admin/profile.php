<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

// Get current admin info
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        
        // Update Profile
        if ($_POST['action'] == 'update_profile') {
            $username = clean($_POST['username']);
            $email = clean($_POST['email']);
            
            if (empty($username) || empty($email)) {
                $error = 'All fields are required!';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format!';
            } else {
                // Check if username or email exists for other users
                $stmt = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $_SESSION['admin_id']]);
                
                if ($stmt->fetch()) {
                    $error = 'Username or email already exists!';
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
                    if ($stmt->execute([$username, $email, $_SESSION['admin_id']])) {
                        $_SESSION['admin_username'] = $username;
                        $success = 'Profile updated successfully!';
                        // Refresh admin data
                        $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
                        $stmt->execute([$_SESSION['admin_id']]);
                        $admin = $stmt->fetch();
                    } else {
                        $error = 'Failed to update profile!';
                    }
                }
            }
        }
        
        // Change Password
        elseif ($_POST['action'] == 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required!';
            } elseif (!password_verify($current_password, $admin['password'])) {
                $error = 'Current password is incorrect!';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters!';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match!';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $_SESSION['admin_id']])) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Admin Profile</h1>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <!-- Profile Information -->
                <div class="card">
                    <h2>Profile Information</h2>
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required minlength="3">
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Member Since</label>
                            <input type="text" value="<?php echo date('F d, Y', strtotime($admin['created_at'])); ?>" readonly style="background: var(--light);">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="card" style="margin-top: 30px;">
                    <h2>Change Password</h2>
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters">
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
                
                <!-- Account Stats -->
                <div class="card" style="margin-top: 30px;">
                    <h2>Account Statistics</h2>
                    <div class="stats-list" style="margin-top: 20px;">
                        <?php
                        // Get admin stats
                        $stmt = $conn->query("SELECT COUNT(*) as total FROM categories");
                        $total_categories = $stmt->fetch()['total'];
                        
                        $stmt = $conn->query("SELECT COUNT(*) as total FROM items");
                        $total_items = $stmt->fetch()['total'];
                        
                        $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
                        $total_orders = $stmt->fetch()['total'];
                        ?>
                        
                        <div class="stat-row">
                            <span>Total Categories:</span>
                            <strong><?php echo $total_categories; ?></strong>
                        </div>
                        <div class="stat-row">
                            <span>Total Menu Items:</span>
                            <strong><?php echo $total_items; ?></strong>
                        </div>
                        <div class="stat-row">
                            <span>Total Orders:</span>
                            <strong><?php echo $total_orders; ?></strong>
                        </div>
                        <div class="stat-row">
                            <span>Account ID:</span>
                            <strong>#<?php echo $admin['id']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .profile-container {
            max-width: 800px;
        }
        
        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: var(--light);
            border-radius: 10px;
        }
        
        .stat-row span {
            color: var(--text-light);
        }
        
        .stat-row strong {
            color: var(--dark);
            font-size: 18px;
        }
    </style>
</body>
</html>