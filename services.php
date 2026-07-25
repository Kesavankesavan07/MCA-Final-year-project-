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
   ADD SERVICE CARD
========================================== */
if (isset($_POST['add_service'])) {
    $customer_id = intval($_POST['customer_id']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $mechanic_id = intval($_POST['mechanic_id']);
    $service_type_id = intval($_POST['service_type_id']);
    $complaint = trim($_POST['complaint']);
    $diagnosis = trim($_POST['diagnosis']);
    $labour_charge = floatval($_POST['labour_charge']);
    $service_status = trim($_POST['service_status']);
    $service_date = trim($_POST['service_date']);
    $expected_delivery = trim($_POST['expected_delivery']);
    $remarks = trim($_POST['remarks']);

    if (empty($customer_id) || empty($vehicle_id) || empty($service_type_id)) {
        $message = "Customer, Vehicle and Service Type are required.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO services 
            (customer_id, vehicle_id, mechanic_id, service_type_id, complaint, diagnosis, labour_charge, service_status, service_date, expected_delivery, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("iiiissdssss", $customer_id, $vehicle_id, $mechanic_id, $service_type_id, $complaint, $diagnosis, $labour_charge, $service_status, $service_date, $expected_delivery, $remarks);
            if ($stmt->execute()) {
                $message = "Job Card / Service added successfully.";
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
}

/* ==========================================
   UPDATE SERVICE CARD
========================================== */
if (isset($_POST['update_service'])) {
    $service_id = intval($_POST['service_id']);
    $customer_id = intval($_POST['customer_id']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $mechanic_id = intval($_POST['mechanic_id']);
    $service_type_id = intval($_POST['service_type_id']);
    $complaint = trim($_POST['complaint']);
    $diagnosis = trim($_POST['diagnosis']);
    $labour_charge = floatval($_POST['labour_charge']);
    $service_status = trim($_POST['service_status']);
    $service_date = trim($_POST['service_date']);
    $expected_delivery = trim($_POST['expected_delivery']);
    $completed_date = ($service_status == 'Completed' || $service_status == 'Delivered') ? date('Y-m-d') : NULL;
    $remarks = trim($_POST['remarks']);

    $stmt = $conn->prepare("
        UPDATE services 
        SET customer_id=?, vehicle_id=?, mechanic_id=?, service_type_id=?, complaint=?, diagnosis=?, labour_charge=?, service_status=?, service_date=?, expected_delivery=?, completed_date=?, remarks=?
        WHERE service_id=?
    ");
    
    if ($stmt) {
        $stmt->bind_param("iiiissdsssssi", $customer_id, $vehicle_id, $mechanic_id, $service_type_id, $complaint, $diagnosis, $labour_charge, $service_status, $service_date, $expected_delivery, $completed_date, $remarks, $service_id);
        if ($stmt->execute()) {
            $message = "Job Card / Service updated successfully.";
            $message_type = "success";
        } else {
            $message = "Database Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

/* ==========================================
   DELETE SERVICE
========================================== */
if (isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    $delete = $conn->query("DELETE FROM services WHERE service_id = $service_id");
    if ($delete) {
        $message = "Service record deleted successfully.";
        $message_type = "success";
    } else {
        $message = "Unable to delete service record.";
        $message_type = "error";
    }
}

/* ==========================================
   SERVICE STATISTICS
========================================== */
$totalServices = 0;
$pendingServices = 0;
$inProgressServices = 0;
$completedServices = 0;

$count_res = $conn->query("SELECT COUNT(*) AS total FROM services");
if ($count_res) $totalServices = $count_res->fetch_assoc()['total'];

$pending_res = $conn->query("SELECT COUNT(*) AS total FROM services WHERE service_status='Pending'");
if ($pending_res) $pendingServices = $pending_res->fetch_assoc()['total'];

$ip_res = $conn->query("SELECT COUNT(*) AS total FROM services WHERE service_status='In Progress'");
if ($ip_res) $inProgressServices = $ip_res->fetch_assoc()['total'];

$comp_res = $conn->query("SELECT COUNT(*) AS total FROM services WHERE service_status IN ('Completed', 'Delivered')");
if ($comp_res) $completedServices = $comp_res->fetch_assoc()['total'];

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
        FROM services s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN vehicles v ON s.vehicle_id = v.vehicle_id
        JOIN service_types st ON s.service_type_id = st.service_type_id
        WHERE c.customer_name LIKE ? OR v.vehicle_number LIKE ? OR st.service_name LIKE ?
    ");
    $countStmt->bind_param("sss", $keyword, $keyword, $keyword);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT s.*, c.customer_name, v.vehicle_number, v.vehicle_name, m.mechanic_name, st.service_name
        FROM services s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN vehicles v ON s.vehicle_id = v.vehicle_id
        JOIN service_types st ON s.service_type_id = st.service_type_id
        LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
        WHERE c.customer_name LIKE ? OR v.vehicle_number LIKE ? OR st.service_name LIKE ?
        ORDER BY s.service_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("sssii", $keyword, $keyword, $keyword, $offset, $limit);
} else {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM services");
    $totalRows = $countRes->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT s.*, c.customer_name, v.vehicle_number, v.vehicle_name, m.mechanic_name, st.service_name
        FROM services s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN vehicles v ON s.vehicle_id = v.vehicle_id
        JOIN service_types st ON s.service_type_id = st.service_type_id
        LEFT JOIN mechanics m ON s.mechanic_id = m.mechanic_id
        ORDER BY s.service_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$services = $stmt->get_result();
$totalPages = ceil($totalRows / $limit);

// Fetch Customers, Vehicles, Mechanics, Service Types for options
$customers_list = [];
$c_res = $conn->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name ASC");
while ($r = $c_res->fetch_assoc()) $customers_list[] = $r;

$vehicles_list = [];
$v_res = $conn->query("SELECT vehicle_id, vehicle_number, vehicle_name, customer_id FROM vehicles");
while ($r = $v_res->fetch_assoc()) $vehicles_list[] = $r;

$mechanics_list = [];
$m_res = $conn->query("SELECT mechanic_id, mechanic_name FROM mechanics WHERE status='Available' OR status='Busy'");
while ($r = $m_res->fetch_assoc()) $mechanics_list[] = $r;

$service_types_list = [];
$st_res = $conn->query("SELECT service_type_id, service_name, estimated_cost FROM service_types");
while ($r = $st_res->fetch_assoc()) $service_types_list[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services & Job Cards | AutoMaster Pro 2026</title>
    
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
            <h1>Services & Job Cards</h1>
            <p>Manage repair logs, assign mechanics, and track job statuses.</p>
        </div>
    </div>

    <?php if($message!=""){ ?>
        <div class="alert <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <!-- Service Stats -->
    <div class="customer-stats">
        <div class="stat-card">
            <div class="stat-icon purple">🛠</div>
            <div class="stat-info">
                <small>Total Job Cards</small>
                <h2><?php echo $totalServices; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⏳</div>
            <div class="stat-info">
                <small>Pending Jobs</small>
                <h2><?php echo $pendingServices; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">⚙️</div>
            <div class="stat-info">
                <small>In Progress</small>
                <h2><?php echo $inProgressServices; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✔</div>
            <div class="stat-info">
                <small>Completed Jobs</small>
                <h2><?php echo $completedServices; ?></h2>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="customer-toolbar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search by customer name, plate number or service type..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
        <button class="add-btn" id="addServiceBtn">+ Add Job Card</button>
    </div>

    <!-- Table -->
    <div class="customer-table glass-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer & Vehicle</th>
                    <th>Service Type</th>
                    <th>Assigned Mechanic</th>
                    <th>Labour Charge</th>
                    <th>Status</th>
                    <th>Service Date</th>
                    <th>Delivery Expected</th>
                    <th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($services->num_rows > 0){ ?>
                    <?php while($row = $services->fetch_assoc()){ ?>
                        <tr>
                            <td><?php echo $row['service_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong><br>
                                <span style="font-weight:600; font-size:12px; color:#6C63FF;"><?php echo htmlspecialchars($row['vehicle_number']); ?></span> (<?php echo htmlspecialchars($row['vehicle_name']); ?>)
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['service_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['mechanic_name'] ? $row['mechanic_name'] : 'Not Assigned'); ?></td>
                            <td>₹<?php echo number_format($row['labour_charge'], 2); ?></td>
                            <td>
                                <span class="status <?php echo strtolower(str_replace(' ', '-', $row['service_status'])); ?>">
                                    <?php echo htmlspecialchars($row['service_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date("d M Y", strtotime($row['service_date'])); ?></td>
                            <td><?php echo $row['expected_delivery'] ? date("d M Y", strtotime($row['expected_delivery'])) : '-'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="view-btn" title="View"
                                        data-customer-name="<?php echo htmlspecialchars($row['customer_name']); ?>"
                                        data-vehicle-number="<?php echo htmlspecialchars($row['vehicle_number']); ?>"
                                        data-vehicle-name="<?php echo htmlspecialchars($row['vehicle_name']); ?>"
                                        data-service-name="<?php echo htmlspecialchars($row['service_name']); ?>"
                                        data-mechanic="<?php echo htmlspecialchars($row['mechanic_name'] ? $row['mechanic_name'] : 'Not Assigned'); ?>"
                                        data-charge="<?php echo $row['labour_charge']; ?>"
                                        data-status="<?php echo $row['service_status']; ?>"
                                        data-date="<?php echo $row['service_date']; ?>"
                                        data-expected="<?php echo $row['expected_delivery']; ?>"
                                        data-complaint="<?php echo htmlspecialchars($row['complaint']); ?>"
                                        data-diagnosis="<?php echo htmlspecialchars($row['diagnosis']); ?>"
                                        data-remarks="<?php echo htmlspecialchars($row['remarks']); ?>">
                                        👁
                                    </button>
                                    <button class="edit-btn" title="Edit"
                                        data-id="<?php echo $row['service_id']; ?>"
                                        data-customer="<?php echo $row['customer_id']; ?>"
                                        data-vehicle="<?php echo $row['vehicle_id']; ?>"
                                        data-mechanic="<?php echo $row['mechanic_id']; ?>"
                                        data-type="<?php echo $row['service_type_id']; ?>"
                                        data-complaint="<?php echo htmlspecialchars($row['complaint']); ?>"
                                        data-diagnosis="<?php echo htmlspecialchars($row['diagnosis']); ?>"
                                        data-charge="<?php echo $row['labour_charge']; ?>"
                                        data-status="<?php echo $row['service_status']; ?>"
                                        data-date="<?php echo $row['service_date']; ?>"
                                        data-expected="<?php echo $row['expected_delivery']; ?>"
                                        data-remarks="<?php echo htmlspecialchars($row['remarks']); ?>">
                                        ✏
                                    </button>
                                    <a href="?delete=<?php echo $row['service_id']; ?>" class="delete-btn" onclick="return confirm('Delete this job card record?');" title="Delete">🗑</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-table">
                                <div class="empty-icon">🛠</div>
                                <h3>No Service Cards Found</h3>
                                <p>Click "+ Add Job Card" to create your first repair session.</p>
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
                     ADD SERVICE MODAL
=========================================================== -->
<div class="customer-modal" id="serviceModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Add Job Card</h2>
            <span class="close-modal" id="closeModal">&times;</span>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Customer</label>
                    <select name="customer_id" id="modal_customer_id" required>
                        <option value="">-- Choose Customer --</option>
                        <?php foreach($customers_list as $cust){ ?>
                            <option value="<?php echo $cust['customer_id']; ?>"><?php echo htmlspecialchars($cust['customer_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Vehicle</label>
                    <select name="vehicle_id" id="modal_vehicle_id" required>
                        <option value="">-- Choose Vehicle --</option>
                        <?php foreach($vehicles_list as $veh){ ?>
                            <option value="<?php echo $veh['vehicle_id']; ?>" data-customer="<?php echo $veh['customer_id']; ?>"><?php echo htmlspecialchars($veh['vehicle_number']); ?> (<?php echo htmlspecialchars($veh['vehicle_name']); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assigned Mechanic</label>
                    <select name="mechanic_id">
                        <option value="0">Not Assigned</option>
                        <?php foreach($mechanics_list as $mech){ ?>
                            <option value="<?php echo $mech['mechanic_id']; ?>"><?php echo htmlspecialchars($mech['mechanic_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service Type Required</label>
                    <select name="service_type_id" id="modal_service_type_id" required>
                        <option value="">-- Choose Service --</option>
                        <?php foreach($service_types_list as $st){ ?>
                            <option value="<?php echo $st['service_type_id']; ?>" data-cost="<?php echo $st['estimated_cost']; ?>"><?php echo htmlspecialchars($st['service_name']); ?> (₹<?php echo number_format($st['estimated_cost']); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Labour Charge (₹)</label>
                    <input type="number" step="0.01" name="labour_charge" id="modal_labour_charge" value="0.00">
                </div>
                <div class="form-group">
                    <label>Service Status</label>
                    <select name="service_status" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Waiting Parts">Waiting Parts</option>
                        <option value="Completed">Completed</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Job Opened Date</label>
                    <input type="date" name="service_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Expected Delivery Date</label>
                    <input type="date" name="expected_delivery" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group full-width">
                    <label>Customer Complaints</label>
                    <textarea name="complaint" rows="3" placeholder="Describe client complaints here..."></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Internal Diagnosis Details</label>
                    <textarea name="diagnosis" rows="3" placeholder="Describe mechanics findings here..."></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="2" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelModal">Cancel</button>
                <button type="submit" name="add_service" class="save-btn">Save Job Card</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     EDIT SERVICE MODAL
=========================================================== -->
<div class="customer-modal" id="editServiceModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Modify Job Card</h2>
            <span class="close-modal" id="closeEditModal">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="service_id" id="edit_service_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Customer</label>
                    <select name="customer_id" id="edit_customer_id" required>
                        <?php foreach($customers_list as $cust){ ?>
                            <option value="<?php echo $cust['customer_id']; ?>"><?php echo htmlspecialchars($cust['customer_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Vehicle</label>
                    <select name="vehicle_id" id="edit_vehicle_id" required>
                        <?php foreach($vehicles_list as $veh){ ?>
                            <option value="<?php echo $veh['vehicle_id']; ?>" data-customer="<?php echo $veh['customer_id']; ?>"><?php echo htmlspecialchars($veh['vehicle_number']); ?> (<?php echo htmlspecialchars($veh['vehicle_name']); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assigned Mechanic</label>
                    <select name="mechanic_id" id="edit_mechanic_id">
                        <option value="0">Not Assigned</option>
                        <?php foreach($mechanics_list as $mech){ ?>
                            <option value="<?php echo $mech['mechanic_id']; ?>"><?php echo htmlspecialchars($mech['mechanic_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service Type Required</label>
                    <select name="service_type_id" id="edit_service_type_id" required>
                        <?php foreach($service_types_list as $st){ ?>
                            <option value="<?php echo $st['service_type_id']; ?>" data-cost="<?php echo $st['estimated_cost']; ?>"><?php echo htmlspecialchars($st['service_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Labour Charge (₹)</label>
                    <input type="number" step="0.01" name="labour_charge" id="edit_labour_charge">
                </div>
                <div class="form-group">
                    <label>Service Status</label>
                    <select name="service_status" id="edit_status" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Waiting Parts">Waiting Parts</option>
                        <option value="Completed">Completed</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Job Opened Date</label>
                    <input type="date" name="service_date" id="edit_date" required>
                </div>
                <div class="form-group">
                    <label>Expected Delivery Date</label>
                    <input type="date" name="expected_delivery" id="edit_expected">
                </div>
                <div class="form-group full-width">
                    <label>Customer Complaints</label>
                    <textarea name="complaint" id="edit_complaint" rows="3"></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Internal Diagnosis Details</label>
                    <textarea name="diagnosis" id="edit_diagnosis" rows="3"></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Remarks</label>
                    <textarea name="remarks" id="edit_remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelEditModal">Cancel</button>
                <button type="submit" name="update_service" class="save-btn">Update Job Card</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     VIEW SERVICE MODAL
=========================================================== -->
<div class="customer-modal" id="viewServiceModal" style="display:none;">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Job Card Details</h2>
            <span class="close-modal" id="closeViewModal">&times;</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" id="view_customer" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Vehicle Plate Number</label>
                <input type="text" id="view_vehicle_number" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Vehicle Model Name</label>
                <input type="text" id="view_vehicle_name" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Service Category</label>
                <input type="text" id="view_service_name" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Assigned Mechanic</label>
                <input type="text" id="view_mechanic" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Labour Charge (₹)</label>
                <input type="text" id="view_labour_charge" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Job Opened Date</label>
                <input type="text" id="view_date" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Expected Delivery Date</label>
                <input type="text" id="view_expected" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Job Status</label>
                <input type="text" id="view_status" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group full-width">
                <label>Customer Complaints</label>
                <textarea id="view_complaint" rows="3" readonly style="background: rgba(255,255,255,0.4); resize:none;"></textarea>
            </div>
            <div class="form-group full-width">
                <label>Internal Diagnosis Details</label>
                <textarea id="view_diagnosis" rows="3" readonly style="background: rgba(255,255,255,0.4); resize:none;"></textarea>
            </div>
            <div class="form-group full-width">
                <label>Remarks</label>
                <textarea id="view_remarks" rows="2" readonly style="background: rgba(255,255,255,0.4); resize:none;"></textarea>
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
    const addBtn = document.getElementById("addServiceBtn");
    const modal = document.getElementById("serviceModal");
    const closeModal = document.getElementById("closeModal");
    const cancelModal = document.getElementById("cancelModal");

    if (addBtn) addBtn.addEventListener("click", () => modal.style.display = "flex");
    if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
    if (cancelModal) cancelModal.addEventListener("click", () => modal.style.display = "none");

    const editModal = document.getElementById("editServiceModal");
    const closeEdit = document.getElementById("closeEditModal");
    const cancelEdit = document.getElementById("cancelEditModal");

    // Dynamic vehicle filtering based on customer selection in modals
    function filterVehicles(custSelectId, vehSelectId) {
        const custSelect = document.getElementById(custSelectId);
        const vehSelect = document.getElementById(vehSelectId);
        
        if (custSelect && vehSelect) {
            custSelect.addEventListener("change", function() {
                const customerId = this.value;
                const options = vehSelect.querySelectorAll("option");
                options.forEach(opt => {
                    if (opt.value === "" || opt.dataset.customer === customerId) {
                        opt.style.display = "block";
                    } else {
                        opt.style.display = "none";
                    }
                });
                vehSelect.value = "";
            });
        }
    }

    filterVehicles("modal_customer_id", "modal_vehicle_id");
    filterVehicles("edit_customer_id", "edit_vehicle_id");

    // Auto-charge mapping based on service type in modals
    const modalServType = document.getElementById("modal_service_type_id");
    if (modalServType) {
        modalServType.addEventListener("change", function() {
            const selectedOpt = this.options[this.selectedIndex];
            const cost = selectedOpt.dataset.cost || 0;
            document.getElementById("modal_labour_charge").value = parseFloat(cost).toFixed(2);
        });
    }

    const editServType = document.getElementById("edit_service_type_id");
    if (editServType) {
        editServType.addEventListener("change", function() {
            const selectedOpt = this.options[this.selectedIndex];
            const cost = selectedOpt.dataset.cost || 0;
            document.getElementById("edit_labour_charge").value = parseFloat(cost).toFixed(2);
        });
    }

    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("edit_service_id").value = this.dataset.id;
            document.getElementById("edit_customer_id").value = this.dataset.customer;
            document.getElementById("edit_vehicle_id").value = this.dataset.vehicle;
            document.getElementById("edit_mechanic_id").value = this.dataset.mechanic;
            document.getElementById("edit_service_type_id").value = this.dataset.type;
            document.getElementById("edit_complaint").value = this.dataset.complaint;
            document.getElementById("edit_diagnosis").value = this.dataset.diagnosis;
            document.getElementById("edit_labour_charge").value = parseFloat(this.dataset.charge).toFixed(2);
            document.getElementById("edit_status").value = this.dataset.status;
            document.getElementById("edit_date").value = this.dataset.date;
            document.getElementById("edit_expected").value = this.dataset.expected;
            document.getElementById("edit_remarks").value = this.dataset.remarks;
            
            editModal.style.display = "flex";
        });
    });

    if (closeEdit) closeEdit.addEventListener("click", () => editModal.style.display = "none");
    if (cancelEdit) cancelEdit.addEventListener("click", () => editModal.style.display = "none");

    // View Modal Logic
    const viewModal = document.getElementById("viewServiceModal");
    const closeView = document.getElementById("closeViewModal");
    const closeViewBtn = document.getElementById("closeViewModalBtn");

    document.querySelectorAll(".view-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("view_customer").value = this.dataset.customerName;
            document.getElementById("view_vehicle_number").value = this.dataset.vehicleNumber;
            document.getElementById("view_vehicle_name").value = this.dataset.vehicleName;
            document.getElementById("view_service_name").value = this.dataset.serviceName;
            document.getElementById("view_mechanic").value = this.dataset.mechanic;
            document.getElementById("view_labour_charge").value = parseFloat(this.dataset.charge).toFixed(2);
            document.getElementById("view_date").value = this.dataset.date;
            document.getElementById("view_expected").value = this.dataset.expected || '-';
            document.getElementById("view_status").value = this.dataset.status;
            document.getElementById("view_complaint").value = this.dataset.complaint || '-';
            document.getElementById("view_diagnosis").value = this.dataset.diagnosis || '-';
            document.getElementById("view_remarks").value = this.dataset.remarks || '-';
            
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
