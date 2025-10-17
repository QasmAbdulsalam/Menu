<?php
require_once '../config.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $table_number = (int)$_POST['table_number'];
            $qr_code = 'QR_TABLE_' . str_pad($table_number, 3, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO restaurant_tables (table_number, qr_code) VALUES (?, ?)");
            $stmt->execute([$table_number, $qr_code]);
            header('Location: tables.php?success=added');
            exit;
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM restaurant_tables WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: tables.php?success=deleted');
            exit;
        } elseif ($_POST['action'] == 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE restaurant_tables SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            exit;
        }
    }
}

// Get all tables
$stmt = $conn->query("SELECT * FROM restaurant_tables ORDER BY table_number");
$tables = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Tables - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1>QR Code Tables</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Add Table</button>
            </div>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Table <?php echo $_GET['success']; ?> successfully!</div>
            <?php endif; ?>
            
            <div class="qr-grid">
                <?php foreach($tables as $table): ?>
                <div class="qr-card <?php echo !$table['is_active'] ? 'inactive' : ''; ?>">
                    <div class="qr-header">
                        <h3>Table <?php echo $table['table_number']; ?></h3>
                        <label class="toggle">
                            <input type="checkbox" <?php echo $table['is_active'] ? 'checked' : ''; ?> 
                                   onchange="toggleStatus(<?php echo $table['id']; ?>)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode(BASE_URL . 'menu.php?table=' . $table['qr_code']); ?>" 
                             alt="QR Code for Table <?php echo $table['table_number']; ?>">
                    </div>
                    
                    <div class="qr-info">
                        <p><strong>QR Code:</strong> <?php echo $table['qr_code']; ?></p>
                        <p><strong>URL:</strong> <small><?php echo BASE_URL . 'menu.php?table=' . $table['qr_code']; ?></small></p>
                    </div>
                    
                    <div class="qr-actions">
                        <a href="<?php echo BASE_URL; ?>menu.php?table=<?php echo $table['qr_code']; ?>" 
                           target="_blank" class="btn btn-sm btn-secondary">View Menu</a>
                        <button class="btn btn-sm btn-primary" onclick="printQR(<?php echo $table['table_number']; ?>, '<?php echo $table['qr_code']; ?>')">Print QR</button>
                        <button class="btn-icon" onclick="deleteTable(<?php echo $table['id']; ?>)">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="tableModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Table</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Table Number *</label>
                    <input type="number" name="table_number" required min="1">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Table</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('tableModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('tableModal').style.display = 'none';
        }
        
        function deleteTable(id) {
            if (confirm('Are you sure you want to delete this table?')) {
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
        
        function printQR(tableNum, qrCode) {
            const url = '<?php echo BASE_URL; ?>menu.php?table=' + qrCode;
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + encodeURIComponent(url);
            
            const printWindow = window.open('', '', 'width=600,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Table ${tableNum} QR Code</title>
                    <style>
                        body { text-align: center; font-family: Arial; padding: 40px; }
                        h1 { margin-bottom: 20px; }
                        img { margin: 20px 0; }
                        p { margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <h1>Table ${tableNum}</h1>
                    <p>Scan to view menu</p>
                    <img src="${qrUrl}" alt="QR Code">
                    <p><small>${url}</small></p>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    </script>
</body>
</html>