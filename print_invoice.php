<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Invoice details
$invoice = null;
$query = "
    SELECT i.*, c.customer_name, c.phone, c.email, c.address, c.city, c.pincode, v.vehicle_number, v.vehicle_name, v.brand, v.model, v.fuel_type
    FROM invoices i
    JOIN customers c ON i.customer_id = c.customer_id
    JOIN vehicles v ON i.vehicle_id = v.vehicle_id
    WHERE i.invoice_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found!");
}

// Fetch Company Profile Settings
$company = [];
$comp_res = $conn->query("SELECT * FROM company_settings LIMIT 1");
if ($comp_res) {
    $company = $comp_res->fetch_assoc();
}

// Fetch Invoice Items
$items = [];
$items_res = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $invoice_id");
if ($items_res) {
    while ($row = $items_res->fetch_assoc()) {
        $items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            background: #fff;
            padding: 20px;
            font-size: 14px;
            line-height: 1.4;
            max-width: 800px;
            margin: auto;
        }
        .invoice-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .invoice-header h1 {
            font-size: 24px;
            margin: 0 0 5px;
            text-transform: uppercase;
        }
        .invoice-header p {
            margin: 3px 0;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 15px;
        }
        .invoice-details div {
            width: 48%;
        }
        .invoice-details h3 {
            margin: 0 0 8px;
            text-transform: uppercase;
            font-size: 14px;
        }
        .invoice-details p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px dashed #000;
        }
        th {
            text-transform: uppercase;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .totals-box {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 300px;
        }
        .totals-table td {
            border: none;
            padding: 5px 8px;
        }
        .totals-table tr.grand-total td {
            font-weight: bold;
            font-size: 16px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 8px;
        }
        .footer-note {
            text-align: center;
            margin-top: 50px;
            border-top: 1px dashed #000;
            padding-top: 15px;
            font-size: 12px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        .btn-print {
            background: #6C63FF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button class="btn-print" onclick="window.print()">Print Receipt</button>
    </div>

    <div class="invoice-header">
        <h1><?php echo htmlspecialchars($company['company_name']); ?></h1>
        <p><?php echo (string)$company['address']; ?>, <?php echo (string)$company['city']; ?> - <?php echo (int)$company['pincode']; ?></p>
        <p>Phone: <?php echo htmlspecialchars($company['phone']); ?> | Email: <?php echo (string)$company['email']; ?></p>
        <p>GSTIN: <strong><?php echo htmlspecialchars($company['gst_number']); ?></strong></p>
    </div>

    <div class="invoice-details">
        <div>
            <h3>Billed To:</h3>
            <p><strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong></p>
            <p>Phone: <?php echo htmlspecialchars($invoice['phone']); ?></p>
            <p>Email: <?php echo htmlspecialchars($invoice['email']); ?></p>
            <p><?php echo htmlspecialchars($invoice['address']); ?>, <?php echo htmlspecialchars($invoice['city']); ?></p>
        </div>
        <div>
            <h3>Invoice Metadata:</h3>
            <p>Invoice No: <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></p>
            <p>Date: <?php echo date("d-M-Y", strtotime($invoice['invoice_date'])); ?></p>
            <p>Vehicle: <strong><?php echo htmlspecialchars($invoice['vehicle_number']); ?></strong></p>
            <p>Brand/Model: <?php echo htmlspecialchars($invoice['brand']); ?> <?php echo htmlspecialchars($invoice['model']); ?> (<?php echo htmlspecialchars($invoice['fuel_type']); ?>)</p>
            <p>Payment Status: <strong><?php echo htmlspecialchars($invoice['payment_status']); ?></strong></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Item Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($items as $item) {
                echo '<tr>';
                echo '  <td>' . $i++ . '</td>';
                echo '  <td>' . htmlspecialchars($item['description']) . '</td>';
                echo '  <td class="text-right">' . $item['quantity'] . '</td>';
                echo '  <td class="text-right">₹' . number_format($item['unit_price'], 2) . '</td>';
                echo '  <td class="text-right">₹' . number_format($item['total'], 2) . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="totals-box">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">₹<?php echo number_format($invoice['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td>GST (<?php echo $invoice['gst_percentage']; ?>%)</td>
                <td class="text-right">₹<?php echo number_format($invoice['gst_amount'], 2); ?></td>
            </tr>
            <?php if ($invoice['discount'] > 0) { ?>
            <tr>
                <td>Discount</td>
                <td class="text-right">- ₹<?php echo number_format($invoice['discount'], 2); ?></td>
            </tr>
            <?php } ?>
            <tr class="grand-total">
                <td>Grand Total</td>
                <td class="text-right">₹<?php echo number_format($invoice['grand_total'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        <p>Thank you for choosing <?php echo htmlspecialchars($company['company_name']); ?>!</p>
        <p>Powered by AutoMaster Pro 2026 - Workshop & Billing Management System</p>
    </div>

    <script>
        window.addEventListener('load', function() {
            // Trigger automatic printing on print click or automatically if desired
        });
    </script>
</body>
</html>
