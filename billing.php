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
   GENERATE NEW INVOICE
========================================== */
if (isset($_POST['create_invoice'])) {
    $customer_id = intval($_POST['customer_id']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : NULL;
    if ($service_id == 0) $service_id = NULL;

    $invoice_number = "INV-2026-" . rand(1000, 9999);
    $invoice_date = date("Y-m-d");
    
    $subtotal = floatval($_POST['subtotal']);
    $gst_percentage = floatval($_POST['gst_percentage']);
    $gst_amount = floatval($_POST['gst_amount']);
    $discount = floatval($_POST['discount']);
    $grand_total = floatval($_POST['grand_total']);
    $payment_status = trim($_POST['payment_status']);
    $remarks = trim($_POST['remarks']);

    if (empty($customer_id) || empty($vehicle_id) || empty($grand_total)) {
        $message = "Please fill in all details and add items to invoice.";
        $message_type = "error";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("
                INSERT INTO invoices 
                (invoice_number, customer_id, vehicle_id, service_id, invoice_date, subtotal, gst_percentage, gst_amount, discount, grand_total, payment_status, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("siiisdddddss", $invoice_number, $customer_id, $vehicle_id, $service_id, $invoice_date, $subtotal, $gst_percentage, $gst_amount, $discount, $grand_total, $payment_status, $remarks);
            $stmt->execute();
            $invoice_id = $conn->insert_id;
            $stmt->close();

            // Insert invoice items (parts / labor)
            if (isset($_POST['item_desc'])) {
                $item_descs = $_POST['item_desc'];
                $item_qtys = $_POST['item_qty'];
                $item_prices = $_POST['item_price'];
                $item_products = $_POST['item_product_id'];

                for ($i = 0; $i < count($item_descs); $i++) {
                    $desc = trim($item_descs[$i]);
                    $qty = intval($item_qtys[$i]);
                    $price = floatval($item_prices[$i]);
                    $prod_id = intval($item_products[$i]);
                    if ($prod_id == 0) $prod_id = NULL;

                    $total = $qty * $price;

                    $item_stmt = $conn->prepare("
                        INSERT INTO invoice_items 
                        (invoice_id, product_id, description, quantity, unit_price, total) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $item_stmt->bind_param("iisiid", $invoice_id, $prod_id, $desc, $qty, $price, $total);
                    $item_stmt->execute();
                    $item_stmt->close();

                    // Decrement stock if product is linked
                    if (!empty($prod_id)) {
                        $conn->query("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - $qty) WHERE product_id = $prod_id");
                    }
                }
            }

            // Create dynamic Payment transaction if payment is Paid
            if ($payment_status == 'Paid') {
                $pay_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Cash';
                $pay_ref = isset($_POST['payment_ref']) ? trim($_POST['payment_ref']) : '';
                $pay_stmt = $conn->prepare("
                    INSERT INTO payments (invoice_id, payment_date, payment_method, amount, reference_number, notes) 
                    VALUES (?, ?, ?, ?, ?, 'Initial invoice settlement')
                ");
                $pay_stmt->bind_param("issds", $invoice_id, $invoice_date, $pay_method, $grand_total, $pay_ref);
                $pay_stmt->execute();
                $pay_stmt->close();
            }

            // Mark service status as delivered / completed if service card is billed
            if (!empty($service_id)) {
                $conn->query("UPDATE services SET service_status = 'Delivered', completed_date = '$invoice_date' WHERE service_id = $service_id");
            }

            $conn->commit();
            $message = "Invoice $invoice_number generated successfully.";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Transaction Failed: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

/* ==========================================
   DELETE INVOICE
========================================== */
if (isset($_GET['delete'])) {
    $invoice_id = intval($_GET['delete']);
    $delete = $conn->query("DELETE FROM invoices WHERE invoice_id = $invoice_id");
    if ($delete) {
        $message = "Invoice deleted successfully.";
        $message_type = "success";
    } else {
        $message = "Unable to delete invoice.";
        $message_type = "error";
    }
}

/* ==========================================
   STATISTICS
========================================== */
$totalRevenue = 0;
$totalInvoicesCount = 0;
$pendingAmount = 0;

$count_res = $conn->query("SELECT COUNT(*) AS total, SUM(grand_total) AS total_rev FROM invoices");
if ($count_res && $row = $count_res->fetch_assoc()) {
    $totalInvoicesCount = $row['total'];
    $totalRevenue = $row['total_rev'] ? $row['total_rev'] : 0;
}

$pending_res = $conn->query("SELECT SUM(grand_total) AS total FROM invoices WHERE payment_status = 'Pending'");
if ($pending_res) $pendingAmount = $pending_res->fetch_assoc()['total'];
$pendingAmount = $pendingAmount ? $pendingAmount : 0;

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
        FROM invoices i
        JOIN customers c ON i.customer_id = c.customer_id
        WHERE i.invoice_number LIKE ? OR c.customer_name LIKE ?
    ");
    $countStmt->bind_param("ss", $keyword, $keyword);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT i.*, c.customer_name, v.vehicle_number 
        FROM invoices i
        JOIN customers c ON i.customer_id = c.customer_id
        JOIN vehicles v ON i.vehicle_id = v.vehicle_id
        WHERE i.invoice_number LIKE ? OR c.customer_name LIKE ?
        ORDER BY i.invoice_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ssii", $keyword, $keyword, $offset, $limit);
} else {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM invoices");
    $totalRows = $countRes->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT i.*, c.customer_name, v.vehicle_number 
        FROM invoices i
        JOIN customers c ON i.customer_id = c.customer_id
        JOIN vehicles v ON i.vehicle_id = v.vehicle_id
        ORDER BY i.invoice_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$invoices = $stmt->get_result();
$totalPages = ceil($totalRows / $limit);

// Fetch Customers, Vehicles, Products, Completed/Pending Services
$customers = [];
$c_res = $conn->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name ASC");
while ($r = $c_res->fetch_assoc()) $customers[] = $r;

$vehicles = [];
$v_res = $conn->query("SELECT vehicle_id, vehicle_number, vehicle_name, customer_id FROM vehicles");
while ($r = $v_res->fetch_assoc()) $vehicles[] = $r;

$products = [];
$p_res = $conn->query("SELECT product_id, part_name, selling_price, stock_quantity FROM products WHERE status='Active'");
while ($r = $p_res->fetch_assoc()) $products[] = $r;

$services = [];
$s_res = $conn->query("
    SELECT s.service_id, s.customer_id, s.vehicle_id, s.labour_charge, st.service_name, v.vehicle_number
    FROM services s
    JOIN service_types st ON s.service_type_id = st.service_type_id
    JOIN vehicles v ON s.vehicle_id = v.vehicle_id
    WHERE s.service_status IN ('Completed', 'Pending', 'In Progress')
");
while ($r = $s_res->fetch_assoc()) $services[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Invoices | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css"> <!-- Layout structure -->
    
    <style>
        .invoice-item-row {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
            align-items: center;
        }
        .invoice-item-row select, .invoice-item-row input {
            padding: 10px 14px;
            border-radius: 12px !important;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .remove-row-btn {
            background: #EF4444;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }
        .billing-summary-box {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.4);
            border-radius: 18px;
        }
        .billing-totals {
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .billing-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 600;
        }
        .billing-total-row.grand {
            font-size: 18px;
            font-weight: 700;
            color: #6C63FF;
            border-top: 1px solid rgba(0,0,0,0.1);
            padding-top: 10px;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="customer-header">
        <div>
            <h1>Billing & Invoicing</h1>
            <p>Generate customer invoices, calculate GST/discounts, and log transactions.</p>
        </div>
    </div>

    <?php if($message!=""){ ?>
        <div class="alert <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <!-- Stats -->
    <div class="customer-stats">
        <div class="stat-card">
            <div class="stat-icon purple">🧾</div>
            <div class="stat-info">
                <small>Total Invoices</small>
                <h2><?php echo $totalInvoicesCount; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">₹</div>
            <div class="stat-info">
                <small>Total Business Revenue</small>
                <h2>₹<?php echo number_format($totalRevenue, 2); ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">💸</div>
            <div class="stat-info">
                <small>Pending Receivables</small>
                <h2>₹<?php echo number_format($pendingAmount, 2); ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">💳</div>
            <div class="stat-info">
                <small>Cleared (Paid) Invoices</small>
                <h2><?php 
                    $p_res = $conn->query("SELECT COUNT(*) AS total FROM invoices WHERE payment_status='Paid'");
                    echo $p_res ? $p_res->fetch_assoc()['total'] : 0;
                ?></h2>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="customer-toolbar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search invoices by invoice number or client name..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
        <button class="add-btn" id="addInvoiceBtn">+ Create Invoice</button>
    </div>

    <!-- Table -->
    <div class="customer-table glass-card">
        <table>
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Customer Name</th>
                    <th>Vehicle Plate</th>
                    <th>Invoice Date</th>
                    <th>Subtotal</th>
                    <th>GST Amount</th>
                    <th>Grand Total</th>
                    <th>Status</th>
                    <th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($invoices->num_rows > 0){ ?>
                    <?php while($row = $invoices->fetch_assoc()){ ?>
                        <tr>
                            <td><span style="font-weight: 700; color: #6C63FF;"><?php echo htmlspecialchars($row['invoice_number']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                            <td><span style="font-family: monospace; font-weight:600;"><?php echo htmlspecialchars($row['vehicle_number']); ?></span></td>
                            <td><?php echo date("d M Y", strtotime($row['invoice_date'])); ?></td>
                            <td>₹<?php echo number_format($row['subtotal'], 2); ?></td>
                            <td>₹<?php echo number_format($row['gst_amount'], 2); ?></td>
                            <td><strong>₹<?php echo number_format($row['grand_total'], 2); ?></strong></td>
                            <td>
                                <span class="status <?php echo strtolower($row['payment_status']); ?>">
                                    <?php echo htmlspecialchars($row['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="print_invoice.php?id=<?php echo $row['invoice_id']; ?>" target="_blank" class="view-btn" title="View & Print Invoice" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none; padding:10px;">👁</a>
                                    <a href="?delete=<?php echo $row['invoice_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this invoice?');" title="Delete">🗑</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-table">
                                <div class="empty-icon">🧾</div>
                                <h3>No Invoices Found</h3>
                                <p>Click "+ Create Invoice" to build a billing sheet.</p>
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
                     ADD INVOICE MODAL
=========================================================== -->
<div class="customer-modal" id="invoiceModal" style="display:none;">
    <div class="customer-modal-content" style="max-width: 850px;">
        <div class="modal-header">
            <h2>Generate Invoice</h2>
            <span class="close-modal" id="closeModal">&times;</span>
        </div>
        <form method="POST" id="invoiceForm">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Customer</label>
                    <select name="customer_id" id="inv_customer_id" required>
                        <option value="">-- Choose Customer --</option>
                        <?php foreach($customers as $c){ ?>
                            <option value="<?php echo $c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Vehicle</label>
                    <select name="vehicle_id" id="inv_vehicle_id" required>
                        <option value="">-- Choose Vehicle --</option>
                        <?php foreach($vehicles as $v){ ?>
                            <option value="<?php echo $v['vehicle_id']; ?>" data-customer="<?php echo $v['customer_id']; ?>"><?php echo htmlspecialchars($v['vehicle_number']); ?> (<?php echo htmlspecialchars($v['vehicle_name']); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Load from Job Card (Optional)</label>
                    <select name="service_id" id="inv_service_id">
                        <option value="0">Not Billed from Job Card</option>
                        <?php foreach($services as $s){ ?>
                            <option value="<?php echo $s['service_id']; ?>" data-customer="<?php echo $s['customer_id']; ?>" data-vehicle="<?php echo $s['vehicle_id']; ?>" data-charge="<?php echo $s['labour_charge']; ?>" data-name="<?php echo htmlspecialchars($s['service_name']); ?>">Job #<?php echo $s['service_id']; ?>: <?php echo htmlspecialchars($s['service_name']); ?> (<?php echo htmlspecialchars($s['vehicle_number']); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status" id="inv_payment_status" required>
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
            </div>

            <!-- Invoice Items Section -->
            <h3 style="margin: 20px 0 10px; font-size:16px;">Billing Items</h3>
            <div id="itemsContainer">
                <!-- Items are added here dynamically -->
            </div>
            <button type="button" class="view-all-btn" id="addItemRowBtn" style="margin-bottom: 20px;">+ Add Part/Item</button>
            <button type="button" class="view-all-btn" id="addLaborRowBtn" style="margin-bottom: 20px; background: rgba(37,99,235,0.1); color:#2563EB;">+ Add Labor Charges</button>

            <!-- Calculations Box -->
            <div class="billing-summary-box">
                <div class="billing-totals">
                    <div class="billing-total-row">
                        <span>Subtotal</span>
                        <span>₹<span id="txt_subtotal">0.00</span></span>
                        <input type="hidden" name="subtotal" id="input_subtotal" value="0">
                    </div>
                    <div class="billing-total-row">
                        <span>GST (%)</span>
                        <input type="number" name="gst_percentage" id="input_gst_percentage" value="18" style="width: 60px; text-align: right; padding: 4px;">
                    </div>
                    <div class="billing-total-row">
                        <span>GST Amount</span>
                        <span>₹<span id="txt_gst_amount">0.00</span></span>
                        <input type="hidden" name="gst_amount" id="input_gst_amount" value="0">
                    </div>
                    <div class="billing-total-row">
                        <span>Discount (₹)</span>
                        <input type="number" name="discount" id="input_discount" value="0" style="width: 100px; text-align: right; padding: 4px;">
                    </div>
                    <div class="billing-total-row grand">
                        <span>Grand Total</span>
                        <span>₹<span id="txt_grand_total">0.00</span></span>
                        <input type="hidden" name="grand_total" id="input_grand_total" value="0">
                    </div>
                    
                    <!-- Dynamic payment options if status is Paid -->
                    <div id="paymentDetails" style="margin-top: 10px; border-top: 1px dashed rgba(0,0,0,0.1); padding-top:10px;">
                        <div class="billing-total-row" style="margin-bottom: 6px;">
                            <span>Payment Method</span>
                            <select name="payment_method" style="padding: 4px;">
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="billing-total-row">
                            <span>Reference No</span>
                            <input type="text" name="payment_ref" placeholder="Txn ID / Ref" style="width: 150px; padding: 4px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group full-width" style="margin-top: 20px;">
                <label>Remarks / Notes</label>
                <textarea name="remarks" rows="2" placeholder="Write payment notes here..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelModal">Cancel</button>
                <button type="submit" name="create_invoice" class="save-btn">Generate & Print</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/topbar.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const addBtn = document.getElementById("addInvoiceBtn");
    const modal = document.getElementById("invoiceModal");
    const closeModal = document.getElementById("closeModal");
    const cancelModal = document.getElementById("cancelModal");

    if (addBtn) addBtn.addEventListener("click", () => modal.style.display = "flex");
    if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
    if (cancelModal) cancelModal.addEventListener("click", () => modal.style.display = "none");

    // Dynamic vehicle filtering
    const customerSelect = document.getElementById("inv_customer_id");
    const vehicleSelect = document.getElementById("inv_vehicle_id");
    customerSelect.addEventListener("change", function() {
        const customerId = this.value;
        Array.from(vehicleSelect.options).forEach(opt => {
            if (opt.value === "") {
                opt.style.display = "block";
            } else if (opt.dataset.customer === customerId) {
                opt.style.display = "block";
            } else {
                opt.style.display = "none";
            }
        });
        vehicleSelect.value = "";
    });

    // Payment details display toggle based on status
    const paymentStatus = document.getElementById("inv_payment_status");
    const paymentDetails = document.getElementById("paymentDetails");
    paymentStatus.addEventListener("change", function() {
        paymentDetails.style.display = (this.value === "Paid") ? "block" : "none";
    });

    // Inventory parts arrays for adding rows
    const inventoryProducts = <?php echo json_encode($products); ?>;
    const itemsContainer = document.getElementById("itemsContainer");

    function addInvoiceItemRow(description = "", price = 0, productId = 0) {
        const row = document.createElement("div");
        row.className = "invoice-item-row";
        
        let selectHtml = `<select name="item_product_id[]" class="item-product-select" style="width: 250px;">`;
        selectHtml += `<option value="0" data-price="0">-- Custom / Labor Charge --</option>`;
        inventoryProducts.forEach(prod => {
            const selected = (prod.product_id == productId) ? "selected" : "";
            selectHtml += `<option value="${prod.product_id}" data-price="${prod.selling_price}" ${selected}>${prod.part_name} (Stock: ${prod.stock_quantity})</option>`;
        });
        selectHtml += `</select>`;

        row.innerHTML = `
            ${selectHtml}
            <input type="text" name="item_desc[]" placeholder="Description" value="${description}" style="flex-grow: 1;" required>
            <input type="number" name="item_qty[]" value="1" min="1" class="item-qty" style="width: 70px;" required>
            <input type="number" step="0.01" name="item_price[]" value="${price}" class="item-price-input" style="width: 100px;" required>
            <button type="button" class="remove-row-btn">&times;</button>
        `;

        itemsContainer.appendChild(row);

        // Bind events for calculation
        const productSelect = row.querySelector(".item-product-select");
        const priceInput = row.querySelector(".item-price-input");
        const qtyInput = row.querySelector(".item-qty");
        const removeBtn = row.querySelector(".remove-row-btn");

        productSelect.addEventListener("change", function() {
            const selectedOpt = this.options[this.selectedIndex];
            const price = parseFloat(selectedOpt.dataset.price) || 0;
            priceInput.value = price.toFixed(2);
            row.querySelector('input[name="item_desc[]"]').value = selectedOpt.text.split(" (Stock:")[0];
            calculateInvoiceTotals();
        });

        priceInput.addEventListener("input", calculateInvoiceTotals);
        qtyInput.addEventListener("input", calculateInvoiceTotals);
        removeBtn.addEventListener("click", function() {
            row.remove();
            calculateInvoiceTotals();
        });

        calculateInvoiceTotals();
    }

    document.getElementById("addItemRowBtn").addEventListener("click", () => addInvoiceItemRow());
    document.getElementById("addLaborRowBtn").addEventListener("click", () => addInvoiceItemRow("Labor Services", 500.00));

    // Calculate billing aggregates
    function calculateInvoiceTotals() {
        let subtotal = 0;
        const prices = document.querySelectorAll(".item-price-input");
        const qtys = document.querySelectorAll(".item-qty");

        prices.forEach((el, index) => {
            const price = parseFloat(el.value) || 0;
            const qty = parseInt(qtys[index].value) || 0;
            subtotal += price * qty;
        });

        const gstPercent = parseFloat(document.getElementById("input_gst_percentage").value) || 0;
        const discount = parseFloat(document.getElementById("input_discount").value) || 0;

        const gstAmount = subtotal * (gstPercent / 100);
        const grandTotal = Math.max(0, (subtotal + gstAmount) - discount);

        document.getElementById("txt_subtotal").textContent = subtotal.toFixed(2);
        document.getElementById("input_subtotal").value = subtotal;

        document.getElementById("txt_gst_amount").textContent = gstAmount.toFixed(2);
        document.getElementById("input_gst_amount").value = gstAmount;

        document.getElementById("txt_grand_total").textContent = grandTotal.toFixed(2);
        document.getElementById("input_grand_total").value = grandTotal;
    }

    document.getElementById("input_gst_percentage").addEventListener("input", calculateInvoiceTotals);
    document.getElementById("input_discount").addEventListener("input", calculateInvoiceTotals);

    // Auto load service details on selection
    const serviceSelect = document.getElementById("inv_service_id");
    serviceSelect.addEventListener("change", function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value !== "0") {
            const customerId = selected.dataset.customer;
            const vehicleId = selected.dataset.vehicle;
            const charge = parseFloat(selected.dataset.charge) || 0;
            const name = selected.dataset.name;

            document.getElementById("inv_customer_id").value = customerId;
            // trigger change event to refresh vehicle select options list
            const event = new Event('change');
            document.getElementById("inv_customer_id").dispatchEvent(event);
            document.getElementById("inv_vehicle_id").value = vehicleId;

            // Clear previous items and add service labor
            itemsContainer.innerHTML = "";
            addInvoiceItemRow(name + " Labor Charge", charge, 0);
        }
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });
});
</script>
</body>
</html>
