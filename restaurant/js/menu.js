// Cart management
let cart = [];

// Get table QR from URL
const urlParams = new URLSearchParams(window.location.search);
const tableQR = urlParams.get('table');

// Add item to cart
function addToCart(item) {
    const existingItem = cart.find(i => i.id === item.id);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            ...item,
            quantity: 1
        });
    }
    
    updateCart();
    showAddedAnimation();
}

// Update cart display
function updateCart() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    // Update count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    // Update items display
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Your cart is empty</p>
            </div>
        `;
        cartTotal.textContent = '$0.00';
        return;
    }
    
    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)} each</div>
            </div>
            <div class="cart-item-controls">
                <div class="qty-control">
                    <button class="qty-btn" onclick="decreaseQuantity(${item.id})">-</button>
                    <span class="qty-display">${item.quantity}</span>
                    <button class="qty-btn" onclick="increaseQuantity(${item.id})">+</button>
                </div>
                <button class="remove-btn" onclick="removeFromCart(${item.id})">🗑️</button>
            </div>
        </div>
    `).join('');
    
    // Update total
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    cartTotal.textContent = '$' + total.toFixed(2);
}

// Increase quantity
function increaseQuantity(itemId) {
    const item = cart.find(i => i.id === itemId);
    if (item) {
        item.quantity++;
        updateCart();
    }
}

// Decrease quantity
function decreaseQuantity(itemId) {
    const item = cart.find(i => i.id === itemId);
    if (item) {
        if (item.quantity > 1) {
            item.quantity--;
        } else {
            removeFromCart(itemId);
        }
        updateCart();
    }
}

// Remove from cart
function removeFromCart(itemId) {
    cart = cart.filter(i => i.id !== itemId);
    updateCart();
}

// Toggle cart modal
function toggleCart() {
    const modal = document.getElementById('cartModal');
    modal.classList.toggle('active');
}

// Close cart when clicking outside
document.getElementById('cartModal').addEventListener('click', function(e) {
    if (e.target === this) {
        toggleCart();
    }
});

// Show added animation
function showAddedAnimation() {
    const btn = event.target;
    btn.textContent = '✓';
    btn.style.background = '#10b981';
    
    setTimeout(() => {
        btn.textContent = '+';
        btn.style.background = '';
    }, 500);
}

// Place order
async function placeOrder() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }
    
    if (!tableQR) {
        alert('No table selected. Please scan a QR code.');
        return;
    }
    
    const notes = document.getElementById('orderNotes').value;
    
    try {
        const response = await fetch('process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                table_qr: tableQR,
                items: cart,
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessMessage(result.order_number);
            cart = [];
            updateCart();
            toggleCart();
            document.getElementById('orderNotes').value = '';
        } else {
            alert('Failed to place order: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to place order. Please try again.');
    }
}

// Show success message
function showSuccessMessage(orderNumber) {
    const message = document.createElement('div');
    message.className = 'success-message';
    message.innerHTML = `
        <div class="success-icon">✅</div>
        <h2>Order Placed!</h2>
        <p>Order #${orderNumber}</p>
        <p>Your order has been sent to the kitchen.</p>
    `;
    
    document.body.appendChild(message);
    
    setTimeout(() => {
        message.remove();
    }, 3000);
}

// Smooth scroll for category links
document.querySelectorAll('.category-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const target = document.querySelector(targetId);
        
        if (target) {
            const offset = 200; // Account for sticky headers
            const targetPosition = target.offsetTop - offset;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// Initialize
updateCart();