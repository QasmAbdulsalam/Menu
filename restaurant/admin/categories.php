<?php
require_once '../config.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = clean($_POST['name']);
            $description = clean($_POST['description']);
            $display_order = (int)$_POST['display_order'];
            
            // Handle image upload
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['image']['name'];
                $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($filetype, $allowed)) {
                    $newname = 'cat_' . time() . '_' . uniqid() . '.' . $filetype;
                    $upload_path = '../' . UPLOAD_DIR . $newname;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image = $newname;
                    }
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO categories (name, description, image, display_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $image, $display_order]);
            header('Location: categories.php?success=added');
            exit;
        } elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id'];
            $name = clean($_POST['name']);
            $description = clean($_POST['description']);
            $display_order = (int)$_POST['display_order'];
            
            // Get current category data
            $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            $image = $current['image'];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['image']['name'];
                $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($filetype, $allowed)) {
                    $newname = 'cat_' . time() . '_' . uniqid() . '.' . $filetype;
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
            
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, image = ?, display_order = ? WHERE id = ?");
            $stmt->execute([$name, $description, $image, $display_order, $id]);
            header('Location: categories.php?success=updated');
            exit;
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            
            // Get image filename before deleting
            $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $cat = $stmt->fetch();
            
            // Delete the category
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete image file if exists
            if ($cat['image'] && file_exists('../' . UPLOAD_DIR . $cat['image'])) {
                unlink('../' . UPLOAD_DIR . $cat['image']);
            }
            
            header('Location: categories.php?success=deleted');
            exit;
        } elseif ($_POST['action'] == 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            exit;
        }
    }
}

// Get all categories
$stmt = $conn->query("SELECT c.*, COUNT(i.id) as item_count FROM categories c 
                     LEFT JOIN items i ON c.id = i.category_id 
                     GROUP BY c.id ORDER BY c.display_order");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Categories</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Add Category</button>
            </div>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Category <?php echo $_GET['success']; ?> successfully!</div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $cat): ?>
                            <tr>
                                <td>
                                    <?php if($cat['image']): ?>
                                        <img src="../<?php echo UPLOAD_DIR . $cat['image']; ?>" alt="<?php echo $cat['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--light); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">📁</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $cat['display_order']; ?></td>
                                <td><strong><?php echo $cat['name']; ?></strong></td>
                                <td><?php echo $cat['description']; ?></td>
                                <td><?php echo $cat['item_count']; ?> items</td>
                                <td>
                                    <label class="toggle">
                                        <input type="checkbox" <?php echo $cat['is_active'] ? 'checked' : ''; ?> 
                                               onchange="toggleStatus(<?php echo $cat['id']; ?>)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <button class="btn-icon" onclick='editCategory(<?php echo json_encode($cat); ?>)'>✏️</button>
                                    <button class="btn-icon" onclick="deleteCategory(<?php echo $cat['id']; ?>)">🗑️</button>
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
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Category</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="categoryForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label>Category Image</label>
                    <div class="image-upload-container">
                        <img id="imagePreview" src="" alt="Preview" style="display: none; max-width: 200px; max-height: 200px; border-radius: 10px; margin-bottom: 10px;">
                        <input type="file" name="image" id="categoryImage" accept="image/*" onchange="previewImage(this)">
                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">Supported: JPG, PNG, GIF, WEBP</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" id="categoryName" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="categoryDescription" rows="3"></textarea>
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
            document.getElementById('categoryModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('formAction').value = 'add';
            document.getElementById('categoryForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
        }
        
        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        function editCategory(cat) {
            document.getElementById('categoryModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryId').value = cat.id;
            document.getElementById('categoryName').value = cat.name;
            document.getElementById('categoryDescription').value = cat.description;
            document.getElementById('displayOrder').value = cat.display_order;
            
            // Show existing image if available
            const preview = document.getElementById('imagePreview');
            if (cat.image) {
                preview.src = '../<?php echo UPLOAD_DIR; ?>' + cat.image;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
        
        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category?')) {
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