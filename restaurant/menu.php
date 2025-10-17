<?php
require_once 'config.php';

// Get table info from QR code
$table_qr = isset($_GET['table']) ? clean($_GET['table']) : null;
$table = null;

if ($table_qr) {
    $stmt = $conn->prepare("SELECT * FROM restaurant_tables WHERE qr_code = ? AND is_active = 1");
    $stmt->execute([$table_qr]);
    $table = $stmt->fetch();
}

// Get categories and items
$stmt = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order");
$categories = $stmt->fetchAll();

$items_by_category = [];
foreach ($categories as $cat) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE category_id = ? AND is_available = 1 ORDER BY display_order");
    $stmt->execute([$cat['id']]);
    $items_by_category[$cat['id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu</title>
    <link rel="stylesheet" href="css/menu.css">
</head>
<body>
    <div class="menu-header">
        <h1>🍽️ Our Menu</h1>
        <?php if($table): ?>
        <div class="table-indicator">
            <span class="table-badge">Table <?php echo $table['table_number']; ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="categories-nav">
        <?php foreach($categories as $cat): ?>
        <a href="#cat-<?php echo $cat['id']; ?>" class="category-link"><?php echo $cat['name']; ?></a>
        <?php endforeach; ?>
    </div>
    
    <div class="menu-container">
        <?php foreach($categories as $cat): ?>
        <div class="category-section" id="cat-<?php echo $cat['id']; ?>">
            <div class="category-header">
                <h2><?php echo $cat['name']; ?></h2>
                <?php if($cat['description']): ?>
                <p><?php echo $cat['description']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="items-grid">
                <?php foreach($items_by_category[$cat['id']] as $item): ?>
                <div class="menu-item">
                    <?php if($item['image']): ?>
                        <div class="item-image">
                            <img src="<?php echo UPLOAD_DIR . $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        </div>
                    <?php endif; ?>
                    <div class="item-info">
                        <h3><?php echo $item['name']; ?></h3>
                        <p><?php echo $item['description']; ?></p>
                    </div>
                    <div class="item-price-add">
                        <span class="item-price">$<?php echo number_format($item['price'], 2); ?></span>
                        <button class="btn-add" onclick="addToCart(<?php echo htmlspecialchars(json_encode($item)); ?>)">+</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Cart Button -->
    <button class="cart-btn" onclick="toggleCart()">
        🛒 Cart (<span id="cartCount">0</span>)
    </button>
    
    <!-- Cart Modal -->
    <div id="cartModal" class="cart-modal">
        <div class="cart-content">
            <div class="cart-header">
                <h2>Your Order</h2>
                <button class="close-btn" onclick="toggleCart()">×</button>
            </div>
            
            <div id="cartItems" class="cart-items"></div>
            
            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cartTotal">$0.00</span>
                </div>
                
                <div class="cart-notes">
                    <textarea id="orderNotes" placeholder="Special requests..."></textarea>
                </div>
                
                <button class="btn btn-primary btn-block" onclick="placeOrder()">Place Order</button>
            </div>
        </div>
    </div>
    
    <script src="js/menu.js"></script>
</body>
</html>