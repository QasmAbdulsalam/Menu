<?php
require_once '../config.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $order_id = (int)$_POST['order_id'];
        $status = clean($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        exit(json_encode(['success' => true]));
    }
}

// Get all orders
$stmt = $conn->query("SELECT o.*, t.table_number 
                     FROM orders o 
                     JOIN restaurant_tables t ON o.table_id = t.id 
                     ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Orders Management</h1>
            </div>
            
            <div class="orders-filter">
                <button class="filter-btn active" onclick="filterOrders('all')">All</button>
                <button class="filter-btn" onclick="filterOrders('pending')">Pending</button>
                <button class="filter-btn" onclick="filterOrders('preparing')">Preparing</button>
                <button class="filter-btn" onclick="filterOrders('completed')">Completed</button>
            </div>
            
            <div class="orders-container">
                <?php foreach($orders as $order): 
                    // Get order items
                    $stmt = $conn->prepare("SELECT oi.*, i.name 
                                           FROM order_items oi 
                                           JOIN items i ON oi.item_id = i.id 
                                           WHERE oi.order_id = ?");
                    $stmt->execute([$order['id']]);
                    $order_items = $stmt->fetchAll();
                ?>
                <div class="order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo $order['order_number']; ?></h3>
                            <p class="order-meta">Table <?php echo $order['table_number']; ?> • <?php echo date('M d, H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <select class="status-select status-<?php echo $order['status']; ?>" 
                                onchange="updateStatus(<?php echo $order['id']; ?>, this.value)">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="order-items">
                        <?php foreach($order_items as $item): ?>
                        <div class="order-item">
                            <span class="item-qty"><?php echo $item['quantity']; ?>x</span>
                            <span class="item-name"><?php echo $item['name']; ?></span>
                            <span class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($order['notes']): ?>
                    <div class="order-notes">
                        <strong>Notes:</strong> <?php echo $order['notes']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="order-footer">
                        <strong>Total: $<?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        function updateStatus(orderId, status) {
            fetch('orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&order_id=${orderId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function filterOrders(status) {
            const cards = document.querySelectorAll('.order-card');
            const btns = document.querySelectorAll('.filter-btn');
            
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>