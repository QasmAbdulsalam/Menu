<?php
require_once '../config.php';
requireAdmin();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
$total_categories = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM items WHERE is_available = 1");
$total_items = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM restaurant_tables WHERE is_active = 1");
$total_tables = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetch()['total'];

// Get recent orders
$stmt = $conn->query("SELECT o.*, t.table_number FROM orders o 
                      JOIN restaurant_tables t ON o.table_id = t.id 
                      ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['admin_username']; ?>!</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        📁
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_categories; ?></h3>
                        <p>Categories</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        🍔
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_items; ?></h3>
                        <p>Menu Items</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        📱
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_tables; ?></h3>
                        <p>Active Tables</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        📋
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>
            
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Table</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td>Table <?php echo $order['table_number']; ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>