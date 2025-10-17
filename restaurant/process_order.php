<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$table_qr = clean($data['table_qr']);
$items = $data['items'];
$notes = clean($data['notes']);

// Validate table
$stmt = $conn->prepare("SELECT * FROM restaurant_tables WHERE qr_code = ? AND is_active = 1");
$stmt->execute([$table_qr]);
$table = $stmt->fetch();

if (!$table) {
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Calculate total
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
    
    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (table_id, order_number, total_amount, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$table['id'], $order_number, $total, $notes]);
    $order_id = $conn->lastInsertId();
    
    // Add order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_number' => $order_number
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to place order']);
}
?>