<?php
require_once '../config.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $category_id = (int)$_POST['category_id'];
            $name = clean($_POST['name']);
            $description = clean($_POST['description']);
            $price = (float)$_POST['price'];
            $display_order = (int)$_POST['display_order'];
            
            $image = null;
            
            if ($_POST['action'] == 'add') {
                // Handle image upload for new item
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $filename = $_FILES['image']['name'];
                    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($filetype, $allowed)) {
                        $newname = 'item_' . time() . '_' . uniqid() . '.' . $filetype;
                        $upload_path = '../' . UPLOAD_DIR . $newname;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            $image = $newname;
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO items (category_id, name, description, price, image, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $name, $description, $price, $image, $display_order]);
                header('Location: items.php?success=added');
            } else {
                $id = (int)$_POST['id'];
                
                // Get current item data
                $stmt = $conn->prepare("SELECT image FROM items WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                $image = $current['image'];
                
                // Handle image upload for edit
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $filename = $_FILES['image']['name'];
                    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($filetype, $allowed)) {
                        $newname = 'item_' . time() . '_' . uniqid() . '.' . $filetype;
                        $upload_path = '../' . UPLOAD_DIR . $newname;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            // Delete old image if exists
                            if ($image && file_exists('../' . UPLOAD_DIR . $image)) {
                                unlink('../' . UPLOAD_DIR . $image);
                            }
                            $image = $newname;
                        }
                    }
                }
                
                $stmt = $conn->prepare("UPDATE items SET category_id = ?, name = ?, description = ?, price = ?, image = ?, display_order = ? WHERE id = ?");
                $stmt->execute([$category_id, $name, $description, $price, $image, $display_order, $id]);
                header('Location: items.php?success=updated');
            }
            exit;
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            
            // Get image filename before deleting
            $stmt = $conn->prepare("SELECT image FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            // Delete the item
            $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete image file if exists
            if ($item['image'] && file_exists('../' . UPLOAD_DIR . $item['image'])) {
                unlink('../' . UPLOAD_DIR . $item['image']);
            }
            
            header('Location: items.php?success=deleted');
            exit;
        } elseif ($_POST['action'] == 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE items SET is_available = NOT is_available WHERE id = ?");
            $stmt->execute([$id]);
            exit;
        }
    }
}

// Get all items with category info
$stmt = $conn->query("SELECT i.*, c.name as category_name FROM items i 
                     JOIN categories c ON i.category_id = c.id 
                     ORDER BY c.display_order, i.display_order");
$items = $stmt->fetchAll();

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Menu Items</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Add Item</button>
            </div>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Item <?php echo $_GET['success']; ?> successfully!</div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if($item['image']): ?>
                                        <img src="../<?php echo UPLOAD_DIR . $item['image']; ?>" alt="<?php echo $item['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--light); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">🍽️</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['display_order']; ?></td>
                                <td><strong><?php echo $item['name']; ?></strong></td>
                                <td><span class="badge"><?php echo $item['category_name']; ?></span></td>
                                <td><?php echo substr($item['description'], 0, 50); ?>...</td>
                                <td><strong>$<?php echo number_format($item['price'], 2); ?></strong></td>
                                <td>
                                    <label class="toggle">
                                        <input type="checkbox" <?php echo $item['is_available'] ? 'checked' : ''; ?> 
                                               onchange="toggleStatus(<?php echo $item['id']; ?>)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <button class="btn-icon" onclick='editItem(<?php echo json_encode($item); ?>)'>✏️</button>
                                    <button class="btn-icon" onclick="deleteItem(<?php echo $item['id']; ?>)">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Item</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="itemForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="itemId">
                
                <div class="form-group">
                    <label>Item Image</label>
                    <div class="image-upload-container">
                        <img id="imagePreview" src="" alt="Preview" style="display: none; max-width: 200px; max-height: 200px; border-radius: 10px; margin-bottom: 10px;">
                        <input type="file" name="image" id="itemImage" accept="image/*" onchange="previewImage(this)">
                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">Supported: JPG, PNG, GIF, WEBP</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" id="categoryId" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" name="name" id="itemName" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="itemDescription" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Price *</label>
                    <input type="number" name="price" id="itemPrice" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="displayOrder" value="0">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function openModal() {
            document.getElementById('itemModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add Item';
            document.getElementById('formAction').value = 'add';
            document.getElementById('itemForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
        }
        
        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
        }
        
        function editItem(item) {
            document.getElementById('itemModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Item';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('itemId').value = item.id;
            document.getElementById('categoryId').value = item.category_id;
            document.getElementById('itemName').value = item.name;
            document.getElementById('itemDescription').value = item.description;
            document.getElementById('itemPrice').value = item.price;
            document.getElementById('displayOrder').value = item.display_order;
            
            // Show existing image if available
            const preview = document.getElementById('imagePreview');
            if (item.image) {
                preview.src = '../<?php echo UPLOAD_DIR; ?>' + item.image;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
        
        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleStatus(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>