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
   ADD VEHICLE
========================================== */
if (isset($_POST['add_vehicle'])) {
    $customer_id = intval($_POST['customer_id']);
    $vehicle_number = strtoupper(trim($_POST['vehicle_number']));
    $vehicle_name = trim($_POST['vehicle_name']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $fuel_type = trim($_POST['fuel_type']);
    $manufacture_year = intval($_POST['manufacture_year']);
    $odometer = intval($_POST['odometer']);
    $color = trim($_POST['color']);
    $chassis_number = trim($_POST['chassis_number']);
    $engine_number = trim($_POST['engine_number']);

    if (empty($customer_id) || empty($vehicle_number)) {
        $message = "Customer and Vehicle Number are required.";
        $message_type = "error";
    } else {
        // Check duplicate vehicle number
        $check = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_number = ?");
        $check->bind_param("s", $vehicle_number);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Vehicle number already registered.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO vehicles 
                (customer_id, vehicle_number, vehicle_name, brand, model, fuel_type, manufacture_year, odometer, color, chassis_number, engine_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param("isssssiisss", $customer_id, $vehicle_number, $vehicle_name, $brand, $model, $fuel_type, $manufacture_year, $odometer, $color, $chassis_number, $engine_number);
                if ($stmt->execute()) {
                    // Update vehicle count in customers table
                    $conn->query("UPDATE customers SET vehicle_count = vehicle_count + 1 WHERE customer_id = $customer_id");
                    $message = "Vehicle added successfully.";
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
   UPDATE VEHICLE
========================================== */
if (isset($_POST['update_vehicle'])) {
    $vehicle_id = intval($_POST['vehicle_id']);
    $customer_id = intval($_POST['customer_id']);
    $vehicle_number = strtoupper(trim($_POST['vehicle_number']));
    $vehicle_name = trim($_POST['vehicle_name']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $fuel_type = trim($_POST['fuel_type']);
    $manufacture_year = intval($_POST['manufacture_year']);
    $odometer = intval($_POST['odometer']);
    $color = trim($_POST['color']);
    $chassis_number = trim($_POST['chassis_number']);
    $engine_number = trim($_POST['engine_number']);

    // Get old customer_id to manage count
    $old_cust_res = $conn->query("SELECT customer_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    $old_cust_id = $old_cust_res ? $old_cust_res->fetch_assoc()['customer_id'] : 0;

    $stmt = $conn->prepare("
        UPDATE vehicles 
        SET customer_id=?, vehicle_number=?, vehicle_name=?, brand=?, model=?, fuel_type=?, manufacture_year=?, odometer=?, color=?, chassis_number=?, engine_number=?
        WHERE vehicle_id=?
    ");
    
    if ($stmt) {
        $stmt->bind_param("isssssiisssi", $customer_id, $vehicle_number, $vehicle_name, $brand, $model, $fuel_type, $manufacture_year, $odometer, $color, $chassis_number, $engine_number, $vehicle_id);
        if ($stmt->execute()) {
            if ($old_cust_id != $customer_id) {
                // Adjust vehicle counts
                $conn->query("UPDATE customers SET vehicle_count = GREATEST(0, vehicle_count - 1) WHERE customer_id = $old_cust_id");
                $conn->query("UPDATE customers SET vehicle_count = vehicle_count + 1 WHERE customer_id = $customer_id");
            }
            $message = "Vehicle updated successfully.";
            $message_type = "success";
        } else {
            $message = "Database Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

/* ==========================================
   DELETE VEHICLE
========================================== */
if (isset($_GET['delete'])) {
    $vehicle_id = intval($_GET['delete']);
    
    // Find customer_id first to decrement count
    $veh_res = $conn->query("SELECT customer_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($veh_res && $row = $veh_res->fetch_assoc()) {
        $customer_id = $row['customer_id'];
        $delete = $conn->query("DELETE FROM vehicles WHERE vehicle_id = $vehicle_id");
        if ($delete) {
            $conn->query("UPDATE customers SET vehicle_count = GREATEST(0, vehicle_count - 1) WHERE customer_id = $customer_id");
            $message = "Vehicle deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to delete vehicle.";
            $message_type = "error";
        }
    }
}

/* ==========================================
   VEHICLES STATISTICS
========================================== */
$totalVehicles = 0;
$petrolVehicles = 0;
$dieselVehicles = 0;
$cngVehicles = 0;

$count_res = $conn->query("SELECT COUNT(*) AS total FROM vehicles");
if ($count_res) $totalVehicles = $count_res->fetch_assoc()['total'];

$petrol_res = $conn->query("SELECT COUNT(*) AS total FROM vehicles WHERE fuel_type='Petrol'");
if ($petrol_res) $petrolVehicles = $petrol_res->fetch_assoc()['total'];

$diesel_res = $conn->query("SELECT COUNT(*) AS total FROM vehicles WHERE fuel_type='Diesel'");
if ($diesel_res) $dieselVehicles = $diesel_res->fetch_assoc()['total'];

$cng_res = $conn->query("SELECT COUNT(*) AS total FROM vehicles WHERE fuel_type IN ('CNG', 'Electric', 'Hybrid')");
if ($cng_res) $cngVehicles = $cng_res->fetch_assoc()['total'];

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
        FROM vehicles v
        JOIN customers c ON v.customer_id = c.customer_id
        WHERE v.vehicle_number LIKE ? OR v.vehicle_name LIKE ? OR c.customer_name LIKE ? OR v.brand LIKE ?
    ");
    $countStmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT v.*, c.customer_name 
        FROM vehicles v
        JOIN customers c ON v.customer_id = c.customer_id
        WHERE v.vehicle_number LIKE ? OR v.vehicle_name LIKE ? OR c.customer_name LIKE ? OR v.brand LIKE ?
        ORDER BY v.vehicle_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ssssii", $keyword, $keyword, $keyword, $keyword, $offset, $limit);
} else {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM vehicles");
    $totalRows = $countRes->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT v.*, c.customer_name 
        FROM vehicles v
        JOIN customers c ON v.customer_id = c.customer_id
        ORDER BY v.vehicle_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$vehicles = $stmt->get_result();
$totalPages = ceil($totalRows / $limit);

// Fetch Customers for select options
$customers_list = [];
$cust_res = $conn->query("SELECT customer_id, customer_name, phone FROM customers ORDER BY customer_name ASC");
if ($cust_res) {
    while ($r = $cust_res->fetch_assoc()) {
        $customers_list[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css"> <!-- We reuse customer.css styles for consistency -->
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="customer-header">
        <div>
            <h1>Vehicles</h1>
            <p>Manage workshop garage vehicles and link them to owners.</p>
        </div>
    </div>

    <?php if($message!=""){ ?>
        <div class="alert <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <!-- Vehicle Stats -->
    <div class="customer-stats">
        <div class="stat-card">
            <div class="stat-icon purple">🚘</div>
            <div class="stat-info">
                <small>Total Vehicles</small>
                <h2><?php echo $totalVehicles; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">⛽</div>
            <div class="stat-info">
                <small>Petrol Vehicles</small>
                <h2><?php echo $petrolVehicles; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">🛢</div>
            <div class="stat-info">
                <small>Diesel Vehicles</small>
                <h2><?php echo $dieselVehicles; ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">⚡</div>
            <div class="stat-info">
                <small>Eco-Friendly (CNG/EV)</small>
                <h2><?php echo $cngVehicles; ?></h2>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="customer-toolbar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search by plate number, owner, brand or model..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
        <button class="add-btn" id="addVehicleBtn">+ Add Vehicle</button>
    </div>

    <!-- Table -->
    <div class="customer-table glass-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle Plate</th>
                    <th>Name/Brand</th>
                    <th>Model</th>
                    <th>Owner Name</th>
                    <th>Fuel Type</th>
                    <th>Odometer</th>
                    <th>Color</th>
                    <th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($vehicles->num_rows > 0){ ?>
                    <?php while($row = $vehicles->fetch_assoc()){ ?>
                        <tr>
                            <td><?php echo $row['vehicle_id']; ?></td>
                            <td><span style="font-weight: 700; color: #6C63FF;"><?php echo htmlspecialchars($row['vehicle_number']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['vehicle_name']); ?></strong><br><small style="color:#777;"><?php echo htmlspecialchars($row['brand']); ?></small></td>
                            <td><?php echo htmlspecialchars($row['model']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['fuel_type']); ?></td>
                            <td><?php echo number_format($row['odometer']); ?> km</td>
                            <td><?php echo htmlspecialchars($row['color'] ? $row['color'] : '-'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="view-btn" title="View"
                                        data-number="<?php echo htmlspecialchars($row['vehicle_number']); ?>"
                                        data-name="<?php echo htmlspecialchars($row['vehicle_name']); ?>"
                                        data-brand="<?php echo htmlspecialchars($row['brand']); ?>"
                                        data-model="<?php echo htmlspecialchars($row['model']); ?>"
                                        data-fuel="<?php echo htmlspecialchars($row['fuel_type']); ?>"
                                        data-year="<?php echo $row['manufacture_year']; ?>"
                                        data-odometer="<?php echo $row['odometer']; ?>"
                                        data-color="<?php echo htmlspecialchars($row['color']); ?>"
                                        data-chassis="<?php echo htmlspecialchars($row['chassis_number']); ?>"
                                        data-engine="<?php echo htmlspecialchars($row['engine_number']); ?>"
                                        data-owner="<?php echo htmlspecialchars($row['customer_name']); ?>">
                                        👁
                                    </button>
                                    <button class="edit-btn" title="Edit"
                                        data-id="<?php echo $row['vehicle_id']; ?>"
                                        data-customer="<?php echo $row['customer_id']; ?>"
                                        data-number="<?php echo htmlspecialchars($row['vehicle_number']); ?>"
                                        data-name="<?php echo htmlspecialchars($row['vehicle_name']); ?>"
                                        data-brand="<?php echo htmlspecialchars($row['brand']); ?>"
                                        data-model="<?php echo htmlspecialchars($row['model']); ?>"
                                        data-fuel="<?php echo htmlspecialchars($row['fuel_type']); ?>"
                                        data-year="<?php echo $row['manufacture_year']; ?>"
                                        data-odometer="<?php echo $row['odometer']; ?>"
                                        data-color="<?php echo htmlspecialchars($row['color']); ?>"
                                        data-chassis="<?php echo htmlspecialchars($row['chassis_number']); ?>"
                                        data-engine="<?php echo htmlspecialchars($row['engine_number']); ?>">
                                        ✏
                                    </button>
                                    <a href="?delete=<?php echo $row['vehicle_id']; ?>" class="delete-btn" onclick="return confirm('Delete this vehicle?');" title="Delete">🗑</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-table">
                                <div class="empty-icon">🚘</div>
                                <h3>No Vehicles Found</h3>
                                <p>Click "+ Add Vehicle" to register a vehicle.</p>
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
                     ADD VEHICLE MODAL
=========================================================== -->
<div class="customer-modal" id="vehicleModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Add New Vehicle</h2>
            <span class="close-modal" id="closeModal">&times;</span>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Owner (Customer)</label>
                    <select name="customer_id" required>
                        <option value="">-- Choose Owner --</option>
                        <?php foreach($customers_list as $cust){ ?>
                            <option value="<?php echo $cust['customer_id']; ?>"><?php echo htmlspecialchars($cust['customer_name']); ?> (<?php echo htmlspecialchars($cust['phone']); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehicle Plate Number</label>
                    <input type="text" name="vehicle_number" placeholder="e.g. TN-37-BY-1234" required>
                </div>
                <div class="form-group">
                    <label>Vehicle Model Name</label>
                    <input type="text" name="vehicle_name" placeholder="e.g. Swift, BMW 3 Series" required>
                </div>
                <div class="form-group">
                    <label>Brand (Make)</label>
                    <input type="text" name="brand" placeholder="e.g. Maruti Suzuki, Tata, BMW" required>
                </div>
                <div class="form-group">
                    <label>Sub Model / Trim</label>
                    <input type="text" name="model" placeholder="e.g. VXI, SX, 320d">
                </div>
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" required>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="CNG">CNG</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year of Manufacture</label>
                    <input type="number" name="manufacture_year" min="1990" max="2030" placeholder="e.g. 2022">
                </div>
                <div class="form-group">
                    <label>Odometer Reading (KM)</label>
                    <input type="number" name="odometer" value="0">
                </div>
                <div class="form-group">
                    <label>Vehicle Color</label>
                    <input type="text" name="color" placeholder="e.g. White, Black">
                </div>
                <div class="form-group">
                    <label>Chassis Number (VIN)</label>
                    <input type="text" name="chassis_number" placeholder="Enter Chassis Number">
                </div>
                <div class="form-group full-width">
                    <label>Engine Number</label>
                    <input type="text" name="engine_number" placeholder="Enter Engine Number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelModal">Cancel</button>
                <button type="submit" name="add_vehicle" class="save-btn">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     EDIT VEHICLE MODAL
=========================================================== -->
<div class="customer-modal" id="editVehicleModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Edit Vehicle Details</h2>
            <span class="close-modal" id="closeEditModal">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Owner (Customer)</label>
                    <select name="customer_id" id="edit_customer_id" required>
                        <?php foreach($customers_list as $cust){ ?>
                            <option value="<?php echo $cust['customer_id']; ?>"><?php echo htmlspecialchars($cust['customer_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehicle Plate Number</label>
                    <input type="text" name="vehicle_number" id="edit_vehicle_number" required>
                </div>
                <div class="form-group">
                    <label>Vehicle Model Name</label>
                    <input type="text" name="vehicle_name" id="edit_vehicle_name" required>
                </div>
                <div class="form-group">
                    <label>Brand (Make)</label>
                    <input type="text" name="brand" id="edit_brand" required>
                </div>
                <div class="form-group">
                    <label>Sub Model / Trim</label>
                    <input type="text" name="model" id="edit_model">
                </div>
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" id="edit_fuel_type" required>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="CNG">CNG</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year of Manufacture</label>
                    <input type="number" name="manufacture_year" id="edit_manufacture_year" min="1990" max="2030">
                </div>
                <div class="form-group">
                    <label>Odometer Reading (KM)</label>
                    <input type="number" name="odometer" id="edit_odometer">
                </div>
                <div class="form-group">
                    <label>Vehicle Color</label>
                    <input type="text" name="color" id="edit_color">
                </div>
                <div class="form-group">
                    <label>Chassis Number (VIN)</label>
                    <input type="text" name="chassis_number" id="edit_chassis_number">
                </div>
                <div class="form-group full-width">
                    <label>Engine Number</label>
                    <input type="text" name="engine_number" id="edit_engine_number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelEditModal">Cancel</button>
                <button type="submit" name="update_vehicle" class="save-btn">Update Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     VIEW VEHICLE MODAL
=========================================================== -->
<div class="customer-modal" id="viewVehicleModal" style="display:none;">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Vehicle Details</h2>
            <span class="close-modal" id="closeViewModal">&times;</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Owner Name</label>
                <input type="text" id="view_owner" readonly style="background: rgba(255,255,255,0.4);">
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
                <label>Brand (Make)</label>
                <input type="text" id="view_brand" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Sub Model / Trim</label>
                <input type="text" id="view_model" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Fuel Type</label>
                <input type="text" id="view_fuel_type" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Year of Manufacture</label>
                <input type="text" id="view_manufacture_year" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Odometer Reading (KM)</label>
                <input type="text" id="view_odometer" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Vehicle Color</label>
                <input type="text" id="view_color" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Chassis Number (VIN)</label>
                <input type="text" id="view_chassis_number" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group full-width">
                <label>Engine Number</label>
                <input type="text" id="view_engine_number" readonly style="background: rgba(255,255,255,0.4);">
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
    const addBtn = document.getElementById("addVehicleBtn");
    const modal = document.getElementById("vehicleModal");
    const closeModal = document.getElementById("closeModal");
    const cancelModal = document.getElementById("cancelModal");

    if (addBtn) addBtn.addEventListener("click", () => modal.style.display = "flex");
    if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
    if (cancelModal) cancelModal.addEventListener("click", () => modal.style.display = "none");

    const editModal = document.getElementById("editVehicleModal");
    const closeEdit = document.getElementById("closeEditModal");
    const cancelEdit = document.getElementById("cancelEditModal");

    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("edit_vehicle_id").value = this.dataset.id;
            document.getElementById("edit_customer_id").value = this.dataset.customer;
            document.getElementById("edit_vehicle_number").value = this.dataset.number;
            document.getElementById("edit_vehicle_name").value = this.dataset.name;
            document.getElementById("edit_brand").value = this.dataset.brand;
            document.getElementById("edit_model").value = this.dataset.model;
            document.getElementById("edit_fuel_type").value = this.dataset.fuel;
            document.getElementById("edit_manufacture_year").value = this.dataset.year;
            document.getElementById("edit_odometer").value = this.dataset.odometer;
            document.getElementById("edit_color").value = this.dataset.color;
            document.getElementById("edit_chassis_number").value = this.dataset.chassis;
            document.getElementById("edit_engine_number").value = this.dataset.engine;
            
            editModal.style.display = "flex";
        });
    });

    if (closeEdit) closeEdit.addEventListener("click", () => editModal.style.display = "none");
    if (cancelEdit) cancelEdit.addEventListener("click", () => editModal.style.display = "none");

    // View Modal Logic
    const viewModal = document.getElementById("viewVehicleModal");
    const closeView = document.getElementById("closeViewModal");
    const closeViewBtn = document.getElementById("closeViewModalBtn");

    document.querySelectorAll(".view-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("view_owner").value = this.dataset.owner;
            document.getElementById("view_vehicle_number").value = this.dataset.number;
            document.getElementById("view_vehicle_name").value = this.dataset.name;
            document.getElementById("view_brand").value = this.dataset.brand;
            document.getElementById("view_model").value = this.dataset.model || '-';
            document.getElementById("view_fuel_type").value = this.dataset.fuel;
            document.getElementById("view_manufacture_year").value = this.dataset.year || '-';
            document.getElementById("view_odometer").value = this.dataset.odometer + " km";
            document.getElementById("view_color").value = this.dataset.color || '-';
            document.getElementById("view_chassis_number").value = this.dataset.chassis || '-';
            document.getElementById("view_engine_number").value = this.dataset.engine || '-';
            
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
