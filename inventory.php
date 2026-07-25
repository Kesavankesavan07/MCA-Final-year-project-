<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';
$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

/* ==========================================
   ADD PRODUCT / SPARE PART
========================================== */
if (isset($_POST['add_product'])) {
    $category_id = intval($_POST['category_id']);
    $brand_id = intval($_POST['brand_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $part_code = strtoupper(trim($_POST['part_code']));
    $barcode = trim($_POST['barcode']);
    $part_name = trim($_POST['part_name']);
    $description = trim($_POST['description']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price = floatval($_POST['selling_price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $minimum_stock = intval($_POST['minimum_stock']);
    $unit = trim($_POST['unit']);
    $rack_location = trim($_POST['rack_location']);
    $status = trim($_POST['status']);

    if (empty($category_id) || empty($brand_id) || empty($part_code) || empty($part_name)) {
        $message = "Category, Brand, Part Code and Part Name are required.";
        $message_type = "error";
    } else {
        // Check duplicate code
        $check = $conn->prepare("SELECT product_id FROM products WHERE part_code = ?");
        $check->bind_param("s", $part_code);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Part code already exists.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO products 
                (category_id, brand_id, supplier_id, part_code, barcode, part_name, description, purchase_price, selling_price, stock_quantity, minimum_stock, unit, rack_location, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param("iiissssddiisss", $category_id, $brand_id, $supplier_id, $part_code, $barcode, $part_name, $description, $purchase_price, $selling_price, $stock_quantity, $minimum_stock, $unit, $rack_location, $status);
                if ($stmt->execute()) {
                    $message = "Spare part added to inventory successfully.";
                    $message_type = "success";
                } else {
                    $message = "Database Error: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Prepare Failed: " . $conn->error;
                $message_type = "error";
            }
        }
        $check->close();
    }
}

/* ==========================================
   UPDATE PRODUCT / SPARE PART
========================================== */
if (isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $category_id = intval($_POST['category_id']);
    $brand_id = intval($_POST['brand_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $part_code = strtoupper(trim($_POST['part_code']));
    $barcode = trim($_POST['barcode']);
    $part_name = trim($_POST['part_name']);
    $description = trim($_POST['description']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price = floatval($_POST['selling_price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $minimum_stock = intval($_POST['minimum_stock']);
    $unit = trim($_POST['unit']);
    $rack_location = trim($_POST['rack_location']);
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("
        UPDATE products 
        SET category_id=?, brand_id=?, supplier_id=?, part_code=?, barcode=?, part_name=?, description=?, purchase_price=?, selling_price=?, stock_quantity=?, minimum_stock=?, unit=?, rack_location=?, status=?
        WHERE product_id=?
    ");
    
    if ($stmt) {
        $stmt->bind_param("iiissssddiisssi", $category_id, $brand_id, $supplier_id, $part_code, $barcode, $part_name, $description, $purchase_price, $selling_price, $stock_quantity, $minimum_stock, $unit, $rack_location, $status, $product_id);
        if ($stmt->execute()) {
            $message = "Spare part updated successfully.";
            $message_type = "success";
        } else {
            $message = "Database Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

/* ==========================================
   DELETE PRODUCT
========================================== */
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $delete = $conn->query("DELETE FROM products WHERE product_id = $product_id");
    if ($delete) {
        $message = "Product deleted successfully from inventory.";
        $message_type = "success";
    } else {
        $message = "Unable to delete product (might be associated with an invoice).";
        $message_type = "error";
    }
}

/* ==========================================
   INVENTORY STATISTICS
========================================== */
$totalProducts = 0;
$lowStockAlerts = 0;
$totalValue = 0;
$totalItems = 0;

$count_res = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($count_res) $totalProducts = $count_res->fetch_assoc()['total'];

$low_res = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock_quantity <= minimum_stock");
if ($low_res) $lowStockAlerts = $low_res->fetch_assoc()['total'];

$sum_res = $conn->query("SELECT SUM(stock_quantity * purchase_price) AS total_val, SUM(stock_quantity) AS total_items FROM products");
if ($sum_res && $row = $sum_res->fetch_assoc()) {
    $totalValue = $row['total_val'] ? $row['total_val'] : 0;
    $totalItems = $row['total_items'] ? $row['total_items'] : 0;
}

/* ==========================================
   PAGINATION & LISTING
========================================== */
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

if ($search != "") {
    $keyword = "%".$search."%";
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.part_name LIKE ? OR p.part_code LIKE ? OR c.category_name LIKE ? OR b.brand_name LIKE ?
    ");
    $countStmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT p.*, c.category_name, b.brand_name, s.supplier_name
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        WHERE p.part_name LIKE ? OR p.part_code LIKE ? OR c.category_name LIKE ? OR b.brand_name LIKE ?
        ORDER BY p.product_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ssssii", $keyword, $keyword, $keyword, $keyword, $offset, $limit);
} else {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM products");
    $totalRows = $countRes->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT p.*, c.category_name, b.brand_name, s.supplier_name
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        ORDER BY p.product_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$products = $stmt->get_result();
$totalPages = ceil($totalRows / $limit);

// Fetch categories, brands, suppliers
$categories = [];
$cat_res = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
while ($r = $cat_res->fetch_assoc()) $categories[] = $r;

$brands = [];
$brand_res = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
while ($r = $brand_res->fetch_assoc()) $brands[] = $r;

$suppliers = [];
$sup_res = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name ASC");
while ($r = $sup_res->fetch_assoc()) $suppliers[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parts & Inventory | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css"> <!-- Reuses list-table layout -->
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="customer-header">
        <div>
            <h1>Parts & Inventory</h1>
            <p>Manage spare parts, product pricing, locations, and low stock warnings.</p>
        </div>
    </div>

    <?php if($message!=""){ ?>
        <div class="alert <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <!-- Inventory Stats -->
    <div class="customer-stats">
        <div class="stat-card">
            <div class="stat-icon purple">📦</div>
            <div class="stat-info">
                <small>Total Products</small>
                <h2><?php echo $totalProducts; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⚠</div>
            <div class="stat-info">
                <small>Low Stock Items</small>
                <h2><?php echo $lowStockAlerts; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">₹</div>
            <div class="stat-info">
                <small>Total Stock Value</small>
                <h2>₹<?php echo number_format($totalValue, 2); ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🔢</div>
            <div class="stat-info">
                <small>Total Stock Quantity</small>
                <h2><?php echo number_format($totalItems); ?></h2>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="customer-toolbar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search by code, part name, category or brand..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
        <button class="add-btn" id="addProductBtn">+ Add Spare Part</button>
    </div>

    <!-- Table -->
    <div class="customer-table glass-card">
        <table>
            <thead>
                <tr>
                    <th>Part Code</th>
                    <th>Product Details</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Stock Qty</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($products->num_rows > 0){ ?>
                    <?php while($row = $products->fetch_assoc()){ 
                        $is_low = $row['stock_quantity'] <= $row['minimum_stock'];
                        ?>
                        <tr>
                            <td><span style="font-weight: 700; color: #6C63FF;"><?php echo htmlspecialchars($row['part_code']); ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['part_name']); ?></strong><br>
                                <small style="color:#777;"><?php echo (string)$row['description']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                            <td>₹<?php echo number_format($row['purchase_price'], 2); ?></td>
                            <td><strong>₹<?php echo number_format($row['selling_price'], 2); ?></strong></td>
                            <td>
                                <span class="status-badge" style="padding: 4px 8px; font-weight:700; font-size:12px; border-radius:8px; <?php echo $is_low ? 'color:#EF4444; background:rgba(239,68,68,0.15);' : 'color:#15803D; background:rgba(34,197,94,0.15);'; ?>">
                                    <?php echo $row['stock_quantity']; ?> <?php echo htmlspecialchars($row['unit']); ?>
                                </span>
                            </td>
                            <td><span style="font-family:monospace;"><?php echo htmlspecialchars($row['rack_location'] ? $row['rack_location'] : '-'); ?></span></td>
                            <td>
                                <span class="status <?php echo strtolower($row['status']); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="view-btn" title="View"
                                        data-code="<?php echo htmlspecialchars($row['part_code']); ?>"
                                        data-barcode="<?php echo (int)$row['barcode']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['part_name']); ?>"
                                        data-category="<?php echo htmlspecialchars($row['category_name']); ?>"
                                        data-brand="<?php echo htmlspecialchars($row['brand_name']); ?>"
                                        data-supplier="<?php echo htmlspecialchars($row['supplier_name'] ? $row['supplier_name'] : 'N/A'); ?>"
                                        data-desc="<?php echo (string)$row['description']; ?>"
                                        data-purchase="<?php echo $row['purchase_price']; ?>"
                                        data-selling="<?php echo $row['selling_price']; ?>"
                                        data-stock="<?php echo $row['stock_quantity']; ?>"
                                        data-min="<?php echo $row['minimum_stock']; ?>"
                                        data-unit="<?php echo htmlspecialchars($row['unit']); ?>"
                                        data-rack="<?php echo htmlspecialchars($row['rack_location']); ?>"
                                        data-status="<?php echo $row['status']; ?>">
                                        👁
                                    </button>
                                    <button class="edit-btn" title="Edit"
                                        data-id="<?php echo $row['product_id']; ?>"
                                        data-category="<?php echo $row['category_id']; ?>"
                                        data-brand="<?php echo $row['brand_id']; ?>"
                                        data-supplier="<?php echo $row['supplier_id']; ?>"
                                        data-code="<?php echo htmlspecialchars($row['part_code']); ?>"
                                        data-barcode="<?php echo (int)$row['barcode']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['part_name']); ?>"
                                        data-desc="<?php echo (string)$row['description']; ?>"
                                        data-purchase="<?php echo $row['purchase_price']; ?>"
                                        data-selling="<?php echo $row['selling_price']; ?>"
                                        data-stock="<?php echo $row['stock_quantity']; ?>"
                                        data-min="<?php echo $row['minimum_stock']; ?>"
                                        data-unit="<?php echo htmlspecialchars($row['unit']); ?>"
                                        data-rack="<?php echo htmlspecialchars($row['rack_location']); ?>"
                                        data-status="<?php echo $row['status']; ?>">
                                        ✏
                                    </button>
                                    <a href="?delete=<?php echo $row['product_id']; ?>" class="delete-btn" onclick="return confirm('Delete this spare part?');" title="Delete">🗑</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-table">
                                <div class="empty-icon">📦</div>
                                <h3>No Products Found</h3>
                                <p>Click "+ Add Spare Part" to insert parts into inventory.</p>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if($totalPages > 1){ ?>
            <?php for($i=1; $i<=$totalPages; $i++){ ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo ($page == $i) ? 'active-page' : ''; ?>"><?php echo $i; ?></a>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<!-- ===========================================================
                     ADD PRODUCT MODAL
=========================================================== -->
<div class="customer-modal" id="productModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Add Spare Part</h2>
            <span class="close-modal" id="closeModal">&times;</span>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Category</label>
                    <select name="category_id" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach($categories as $cat){ ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Brand</label>
                    <select name="brand_id" required>
                        <option value="">-- Choose Brand --</option>
                        <?php foreach($brands as $b){ ?>
                            <option value="<?php echo $b['brand_id']; ?>"><?php echo htmlspecialchars($b['brand_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Supplier</label>
                    <select name="supplier_id">
                        <option value="0">None</option>
                        <?php foreach($suppliers as $s){ ?>
                            <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Part Code / SKU</label>
                    <input type="text" name="part_code" placeholder="e.g. ENG001, BRK123" required>
                </div>
                <div class="form-group">
                    <label>Barcode ID</label>
                    <input type="text" name="barcode" placeholder="Enter barcode number">
                </div>
                <div class="form-group">
                    <label>Part Name / Title</label>
                    <input type="text" name="part_name" placeholder="e.g. Engine Oil Filter, Brake Pad" required>
                </div>
                <div class="form-group">
                    <label>Purchase Price (₹)</label>
                    <input type="number" step="0.01" name="purchase_price" value="0.00" required>
                </div>
                <div class="form-group">
                    <label>Selling Price (₹)</label>
                    <input type="number" step="0.01" name="selling_price" value="0.00" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock_quantity" value="0" required>
                </div>
                <div class="form-group">
                    <label>Minimum Stock Level (Alert)</label>
                    <input type="number" name="minimum_stock" value="10" required>
                </div>
                <div class="form-group">
                    <label>Measurement Unit</label>
                    <select name="unit">
                        <option value="Piece">Piece</option>
                        <option value="Litre">Litre</option>
                        <option value="Set">Set</option>
                        <option value="Box">Box</option>
                        <option value="Meter">Meter</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rack Location</label>
                    <input type="text" name="rack_location" placeholder="e.g. A-01, B-03">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Part Description</label>
                    <textarea name="description" rows="2" placeholder="Write specifications here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelModal">Cancel</button>
                <button type="submit" name="add_product" class="save-btn">Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     EDIT PRODUCT MODAL
=========================================================== -->
<div class="customer-modal" id="editProductModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Edit Spare Part</h2>
            <span class="close-modal" id="closeEditModal">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="product_id" id="edit_product_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Category</label>
                    <select name="category_id" id="edit_category_id" required>
                        <?php foreach($categories as $cat){ ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Brand</label>
                    <select name="brand_id" id="edit_brand_id" required>
                        <?php foreach($brands as $b){ ?>
                            <option value="<?php echo $b['brand_id']; ?>"><?php echo htmlspecialchars($b['brand_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Supplier</label>
                    <select name="supplier_id" id="edit_supplier_id">
                        <option value="0">None</option>
                        <?php foreach($suppliers as $s){ ?>
                            <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Part Code / SKU</label>
                    <input type="text" name="part_code" id="edit_part_code" required>
                </div>
                <div class="form-group">
                    <label>Barcode ID</label>
                    <input type="text" name="barcode" id="edit_barcode">
                </div>
                <div class="form-group">
                    <label>Part Name / Title</label>
                    <input type="text" name="part_name" id="edit_part_name" required>
                </div>
                <div class="form-group">
                    <label>Purchase Price (₹)</label>
                    <input type="number" step="0.01" name="purchase_price" id="edit_purchase" required>
                </div>
                <div class="form-group">
                    <label>Selling Price (₹)</label>
                    <input type="number" step="0.01" name="selling_price" id="edit_selling" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock_quantity" id="edit_stock" required>
                </div>
                <div class="form-group">
                    <label>Minimum Stock Level (Alert)</label>
                    <input type="number" name="minimum_stock" id="edit_min" required>
                </div>
                <div class="form-group">
                    <label>Measurement Unit</label>
                    <select name="unit" id="edit_unit">
                        <option value="Piece">Piece</option>
                        <option value="Litre">Litre</option>
                        <option value="Set">Set</option>
                        <option value="Box">Box</option>
                        <option value="Meter">Meter</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rack Location</label>
                    <input type="text" name="rack_location" id="edit_rack">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Part Description</label>
                    <textarea name="description" id="edit_desc" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelEditModal">Cancel</button>
                <button type="submit" name="update_product" class="save-btn">Update Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     VIEW INVENTORY MODAL
=========================================================== -->
<div class="customer-modal" id="viewInventoryModal" style="display:none;">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Product / Spare Part Details</h2>
            <span class="close-modal" id="closeViewModal">&times;</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Spare Part Name</label>
                <input type="text" id="view_part_name" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>SKU Part Code</label>
                <input type="text" id="view_part_code" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Barcode / OEM Serial</label>
                <input type="text" id="view_barcode" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="view_category" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Brand (Make)</label>
                <input type="text" id="view_brand" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Supplier Partner</label>
                <input type="text" id="view_supplier" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Purchase Cost Price (₹)</label>
                <input type="text" id="view_purchase_price" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Retail Selling Price (₹)</label>
                <input type="text" id="view_selling_price" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Available Stock Quantity</label>
                <input type="text" id="view_stock" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Minimum Stock Alert Level</label>
                <input type="text" id="view_min" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Unit of Measure</label>
                <input type="text" id="view_unit" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Rack / Bin Location</label>
                <input type="text" id="view_rack" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Inventory Status</label>
                <input type="text" id="view_status" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group full-width">
                <label>Part Description</label>
                <textarea id="view_description" rows="2" readonly style="background: rgba(255,255,255,0.4); resize:none;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="cancel-btn" id="closeViewModalBtn" style="width: 100%;">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/topbar.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const addBtn = document.getElementById("addProductBtn");
    const modal = document.getElementById("productModal");
    const closeModal = document.getElementById("closeModal");
    const cancelModal = document.getElementById("cancelModal");

    if (addBtn) addBtn.addEventListener("click", () => modal.style.display = "flex");
    if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
    if (cancelModal) cancelModal.addEventListener("click", () => modal.style.display = "none");

    const editModal = document.getElementById("editProductModal");
    const closeEdit = document.getElementById("closeEditModal");
    const cancelEdit = document.getElementById("cancelEditModal");

    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("edit_product_id").value = this.dataset.id;
            document.getElementById("edit_category_id").value = this.dataset.category;
            document.getElementById("edit_brand_id").value = this.dataset.brand;
            document.getElementById("edit_supplier_id").value = this.dataset.supplier;
            document.getElementById("edit_part_code").value = this.dataset.code;
            document.getElementById("edit_barcode").value = this.dataset.barcode;
            document.getElementById("edit_part_name").value = this.dataset.name;
            document.getElementById("edit_desc").value = this.dataset.desc;
            document.getElementById("edit_purchase").value = this.dataset.purchase;
            document.getElementById("edit_selling").value = this.dataset.selling;
            document.getElementById("edit_stock").value = this.dataset.stock;
            document.getElementById("edit_min").value = this.dataset.min;
            document.getElementById("edit_unit").value = this.dataset.unit;
            document.getElementById("edit_rack").value = this.dataset.rack;
            document.getElementById("edit_status").value = this.dataset.status;
            
            editModal.style.display = "flex";
        });
    });

    if (closeEdit) closeEdit.addEventListener("click", () => editModal.style.display = "none");
    if (cancelEdit) cancelEdit.addEventListener("click", () => editModal.style.display = "none");

    // View Modal Logic
    const viewModal = document.getElementById("viewInventoryModal");
    const closeView = document.getElementById("closeViewModal");
    const closeViewBtn = document.getElementById("closeViewModalBtn");

    document.querySelectorAll(".view-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("view_part_name").value = this.dataset.name;
            document.getElementById("view_part_code").value = this.dataset.code;
            document.getElementById("view_barcode").value = this.dataset.barcode || '-';
            document.getElementById("view_category").value = this.dataset.category;
            document.getElementById("view_brand").value = this.dataset.brand;
            document.getElementById("view_supplier").value = this.dataset.supplier;
            document.getElementById("view_purchase_price").value = parseFloat(this.dataset.purchase).toFixed(2);
            document.getElementById("view_selling_price").value = parseFloat(this.dataset.selling).toFixed(2);
            document.getElementById("view_stock").value = this.dataset.stock;
            document.getElementById("view_min").value = this.dataset.min;
            document.getElementById("view_unit").value = this.dataset.unit;
            document.getElementById("view_rack").value = this.dataset.rack || '-';
            document.getElementById("view_status").value = this.dataset.status;
            document.getElementById("view_description").value = this.dataset.desc || '-';
            
            viewModal.style.display = "flex";
        });
    });

    if (closeView) closeView.addEventListener("click", () => viewModal.style.display = "none");
    if (closeViewBtn) closeViewBtn.addEventListener("click", () => viewModal.style.display = "none");

    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
        if (e.target === editModal) editModal.style.display = "none";
        if (e.target === viewModal) viewModal.style.display = "none";
    });
});
</script>
</body>
</html>
