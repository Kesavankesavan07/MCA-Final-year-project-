<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';

$user_id = $_SESSION['user_id'];

/* ==========================================
   DEFAULT VARIABLES
========================================== */

$message = "";
$message_type = "";

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

/* ==========================================
   ADD CUSTOMER
========================================== */

/* ==========================================
   ADD CUSTOMER
========================================== */

if (isset($_POST['add_customer'])) {

    $customer_name = trim($_POST['customer_name']);
    $phone         = trim($_POST['phone']);
    $email         = trim($_POST['email']);
    $address       = trim($_POST['address']);
    $city          = trim($_POST['city']);
    $state         = trim($_POST['state']);
    $pincode       = trim($_POST['pincode']);

    /* Validation */

    if (empty($customer_name) || empty($phone)) {

        $message = "Customer Name and Phone Number are required.";
        $message_type = "error";

    } else {

        /* Check duplicate phone */

        $check = $conn->prepare("
            SELECT customer_id
            FROM customers
            WHERE phone = ?
        ");

        $check->bind_param("s", $phone);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $message = "Phone number already exists.";
            $message_type = "error";

        } else {

            $status = "Active";
            $vehicle_count = 0;

            $stmt = $conn->prepare("
                INSERT INTO customers
                (
                    customer_name,
                    phone,
                    email,
                    address,
                    city,
                    state,
                    pincode,
                    status,
                    vehicle_count,
                    created_by,
                    updated_by
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "ssssssssiii",
                    $customer_name,
                    $phone,
                    $email,
                    $address,
                    $city,
                    $state,
                    $pincode,
                    $status,
                    $vehicle_count,
                    $user_id,
                    $user_id
                );

                if ($stmt->execute()) {

                    $message = "Customer added successfully.";
                    $message_type = "success";

                    header("Location: customer.php?success=1");
                    exit();

                } else {

                    $message = "Database Error : " . $stmt->error;
                    $message_type = "error";

                }

                $stmt->close();

            } else {

                $message = "Prepare Failed : " . $conn->error;
                $message_type = "error";

            }

        }

        $check->close();

    }

}

/* ==========================================
   UPDATE CUSTOMER
========================================== */

if (isset($_POST['update_customer'])) {

    $customer_id   = intval($_POST['customer_id']);
    $customer_name = trim($_POST['customer_name']);
    $phone         = trim($_POST['phone']);
    $email         = trim($_POST['email']);
    $address       = trim($_POST['address']);
    $city          = trim($_POST['city']);
    $state         = trim($_POST['state']);
    $pincode       = trim($_POST['pincode']);
    $status        = trim($_POST['status']);

    $update = $conn->prepare("
        UPDATE customers

        SET

        customer_name=?,
        phone=?,
        email=?,
        address=?,
        city=?,
        state=?,
        pincode=?,
        status=?,
        updated_by=?

        WHERE customer_id=?
    ");

    $update->bind_param(

        "ssssssssii",

        $customer_name,
        $phone,
        $email,
        $address,
        $city,
        $state,
        $pincode,
        $status,
        $user_id,
        $customer_id

    );

    if ($update->execute()) {

        $message = "Customer updated successfully.";
        $message_type = "success";

    } else {

        $message = "Unable to update customer.";
        $message_type = "error";

    }

}
/* ==========================================
   DELETE CUSTOMER
========================================== */

if (isset($_GET['delete'])) {

    $customer_id = intval($_GET['delete']);

    $delete = $conn->prepare("
        DELETE FROM customers
        WHERE customer_id = ?
    ");

    $delete->bind_param("i", $customer_id);

    if ($delete->execute()) {

        $message = "Customer deleted successfully.";
        $message_type = "success";

    } else {

        $message = "Unable to delete customer.";
        $message_type = "error";

    }
}

/* ==========================================
   CUSTOMER STATISTICS
========================================== */

/* Total Customers */

$totalCustomers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM customers
");

if ($result && $row = $result->fetch_assoc()) {

    $totalCustomers = $row['total'];

}

/* Active Customers */

$activeCustomers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM customers
    WHERE status='Active'
");

if ($result && $row = $result->fetch_assoc()) {

    $activeCustomers = $row['total'];

}

/* Inactive Customers */

$inactiveCustomers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM customers
    WHERE status='Inactive'
");

if ($result && $row = $result->fetch_assoc()) {

    $inactiveCustomers = $row['total'];

}

/* Customers Added Today */

$todayCustomers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM customers
    WHERE DATE(created_at)=CURDATE()
");

if ($result && $row = $result->fetch_assoc()) {

    $todayCustomers = $row['total'];

}

/* ==========================================
   PAGINATION
========================================== */

$limit = 10;

$page = isset($_GET['page'])
    ? max(1, intval($_GET['page']))
    : 1;

$offset = ($page - 1) * $limit;

/* ==========================================
   SEARCH + CUSTOMER LIST
========================================== */

if ($search != "") {

    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM customers

        WHERE

        customer_name LIKE ?
        OR phone LIKE ?
        OR email LIKE ?
        OR city LIKE ?
    ");

    $keyword = "%".$search."%";

    $countStmt->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $countStmt->execute();

    $countResult = $countStmt->get_result();

    $totalRows = $countResult->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT *

        FROM customers

        WHERE

        customer_name LIKE ?
        OR phone LIKE ?
        OR email LIKE ?
        OR city LIKE ?

        ORDER BY customer_id DESC

        LIMIT ?,?
    ");

    $stmt->bind_param(

        "ssssii",

        $keyword,
        $keyword,
        $keyword,
        $keyword,

        $offset,
        $limit

    );

} else {

    $countResult = $conn->query("
        SELECT COUNT(*) AS total
        FROM customers
    ");

    $totalRows = $countResult->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT *

        FROM customers

        ORDER BY customer_id DESC

        LIMIT ?,?
    ");

    $stmt->bind_param(

        "ii",

        $offset,
        $limit

    );

}

$stmt->execute();

$customers = $stmt->get_result();

/* ==========================================
   PAGINATION CALCULATION
========================================== */

$totalPages = ceil($totalRows / $limit);

/* ==========================================
   HTML STARTS HERE
========================================== */

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Customers | AutoMaster Pro 2026</title>

    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css">

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <!-- ==========================
         PAGE HEADER
    =========================== -->

    <div class="customer-header">

        <div>

            <h1>Customers</h1>

            <p>

                Manage your garage customers professionally.

            </p>

        </div>

    </div>

    <!-- ==========================
         SUCCESS MESSAGE
    =========================== -->

    <?php if($message!=""){ ?>

        <div class="alert <?php echo $message_type; ?>">

            <?php echo $message; ?>

        </div>

    <?php } ?>

    <!-- ==========================
         CUSTOMER STATISTICS
    =========================== -->

    <div class="customer-stats">

        <div class="stat-card">

            <div class="stat-icon purple">

                👥

            </div>

            <div class="stat-info">

                <small>Total Customers</small>

                <h2>

                    <?php echo $totalCustomers; ?>

                </h2>

            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon green">

                ✔

            </div>

            <div class="stat-info">

                <small>Active Customers</small>

                <h2>

                    <?php echo $activeCustomers; ?>

                </h2>

            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon red">

                ✖

            </div>

            <div class="stat-info">

                <small>Inactive</small>

                <h2>

                    <?php echo $inactiveCustomers; ?>

                </h2>

            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon blue">

                ★

            </div>

            <div class="stat-info">

                <small>Added Today</small>

                <h2>

                    <?php echo $todayCustomers; ?>

                </h2>

            </div>

        </div>

    </div>

    <!-- ==========================
         TOOLBAR
    =========================== -->

    <div class="customer-toolbar">

        <form method="GET">

            <input

                type="text"

                name="search"

                placeholder="Search customer by Name, Phone, Email or City..."

                value="<?php echo htmlspecialchars($search); ?>">

            <button

                type="submit"

                class="search-btn">

                Search

            </button>

        </form>

        <button

            class="add-btn"

            id="addCustomerBtn">

            + Add Customer

        </button>

    </div>

    <!-- ==========================
         TABLE START
    =========================== -->

    <div class="customer-table glass-card">

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Customer</th>

                    <th>Phone</th>

                    <th>Email</th>

                    <th>City</th>

                    <th>Vehicles</th>

                    <th>Status</th>

                    <th>Registered</th>

                    <th width="180">

                        Action

                    </th>

                </tr>

            </thead>

            <tbody>
                <?php

if($customers->num_rows > 0){

while($row = $customers->fetch_assoc()){

?>

<tr>

    <td>

        <?php echo $row['customer_id']; ?>

    </td>

    <td>

        <div class="customer-name">

            <div class="customer-avatar">

                <?php

                echo strtoupper(substr($row['customer_name'],0,1));

                ?>

            </div>

            <div>

                <strong>

                    <?php echo htmlspecialchars($row['customer_name']); ?>

                </strong>

            </div>

        </div>

    </td>

    <td>

        <?php echo htmlspecialchars($row['phone']); ?>

    </td>

    <td>

        <?php echo htmlspecialchars($row['email']); ?>

    </td>

    <td>

        <?php echo htmlspecialchars($row['city']); ?>

    </td>

    <td>

        <span class="vehicle-badge">

            <?php echo $row['vehicle_count']; ?>

        </span>

    </td>

    <td>

        <?php

        if($row['status']=="Active"){

        ?>

        <span class="status active">

            Active

        </span>

        <?php

        }else{

        ?>

        <span class="status inactive">

            Inactive

        </span>

        <?php

        }

        ?>

    </td>

    <td>

        <?php

        echo date(

            "d M Y",

            strtotime($row['created_at'])

        );

        ?>

    </td>

    <td>

        <div class="action-buttons">

            <button

                class="view-btn"

                title="View"

                data-name="<?php echo htmlspecialchars($row['customer_name']); ?>"

                data-phone="<?php echo htmlspecialchars($row['phone']); ?>"

                data-email="<?php echo htmlspecialchars($row['email']); ?>"

                data-address="<?php echo htmlspecialchars($row['address']); ?>"

                data-city="<?php echo htmlspecialchars($row['city']); ?>"

                data-state="<?php echo htmlspecialchars($row['state']); ?>"

                data-pincode="<?php echo htmlspecialchars($row['pincode']); ?>"

                data-status="<?php echo htmlspecialchars($row['status']); ?>">

                👁

            </button>

            <button

                class="edit-btn"

                title="Edit"

                data-id="<?php echo $row['customer_id']; ?>"

                data-name="<?php echo htmlspecialchars($row['customer_name']); ?>"

                data-phone="<?php echo htmlspecialchars($row['phone']); ?>"

                data-email="<?php echo htmlspecialchars($row['email']); ?>"

                data-address="<?php echo htmlspecialchars($row['address']); ?>"

                data-city="<?php echo htmlspecialchars($row['city']); ?>"

                data-state="<?php echo htmlspecialchars($row['state']); ?>"

                data-pincode="<?php echo htmlspecialchars($row['pincode']); ?>"

                data-status="<?php echo htmlspecialchars($row['status']); ?>">

                ✏

            </button>

            <a

                href="?delete=<?php echo $row['customer_id']; ?>"

                class="delete-btn"

                onclick="return confirm('Delete this customer?');"

                title="Delete">

                🗑

            </a>

        </div>

    </td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="9">

<div class="empty-table">

    <div class="empty-icon">

        👥

    </div>

    <h3>

        No Customers Found

    </h3>

    <p>

        Click "Add Customer" to create your first customer.

    </p>

</div>

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

<!-- ===========================
     PAGINATION
=========================== -->

<div class="pagination">

<?php

if($totalPages>1){

for($i=1;$i<=$totalPages;$i++){

?>

<a

href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"

class="<?php

if($page==$i){

echo "active-page";

}

?>">

<?php echo $i; ?>

</a>

<?php

}

}

?>

</div>
<!-- ===========================================================
                    ADD CUSTOMER MODAL
=========================================================== -->

<div class="customer-modal" id="customerModal">

    <div class="customer-modal-content">

        <div class="modal-header">

            <h2>Add New Customer</h2>

            <span class="close-modal" id="closeModal">&times;</span>

        </div>

        <form method="POST" id="customerForm">

            <div class="form-grid">

                <div class="form-group">

                    <label>Customer Name</label>

                    <input
                        type="text"
                        name="customer_name"
                        required>

                </div>

                <div class="form-group">

                    <label>Phone Number</label>

                    <input
                        type="text"
                        name="phone"
                        maxlength="10"
                        required>

                </div>

                <div class="form-group">

                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email">

                </div>

                <div class="form-group">

                    <label>City</label>

                    <input
                        type="text"
                        name="city">

                </div>

                <div class="form-group">

                    <label>State</label>

                    <input
                        type="text"
                        name="state">

                </div>

                <div class="form-group">

                    <label>Pincode</label>

                    <input
                        type="text"
                        name="pincode">

                </div>

            </div>

            <div class="form-group full-width">

                <label>Address</label>

                <textarea
                    name="address"
                    rows="4"></textarea>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="cancel-btn"
                    id="cancelModal">

                    Cancel

                </button>

                <button
                    type="submit"
                    name="add_customer"
                    class="save-btn">
                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Customer

                </button>

            </div>

        </form>

    </div>

</div>
<!-- ===========================================================
                    EDIT CUSTOMER MODAL
=========================================================== -->

<div class="customer-modal" id="editCustomerModal">

    <div class="customer-modal-content">

        <div class="modal-header">

            <h2>Edit Customer</h2>

            <span class="close-modal" id="closeEditModal">&times;</span>

        </div>

        <form method="POST" id="editCustomerForm">

            <input
                type="hidden"
                name="customer_id"
                id="edit_customer_id">

            <div class="form-grid">

                <div class="form-group">

                    <label>Customer Name</label>

                    <input
                        type="text"
                        name="customer_name"
                        id="edit_customer_name"
                        required>

                </div>

                <div class="form-group">

                    <label>Phone Number</label>

                    <input
                        type="text"
                        name="phone"
                        id="edit_phone"
                        maxlength="10"
                        required>

                </div>

                <div class="form-group">

                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        id="edit_email">

                </div>

                <div class="form-group">

                    <label>City</label>

                    <input
                        type="text"
                        name="city"
                        id="edit_city">

                </div>

                <div class="form-group">

                    <label>State</label>

                    <input
                        type="text"
                        name="state"
                        id="edit_state">

                </div>

                <div class="form-group">

                    <label>Pincode</label>

                    <input
                        type="text"
                        name="pincode"
                        id="edit_pincode">

                </div>

            </div>

            <div class="form-group full-width">

                <label>Address</label>

                <textarea
                    name="address"
                    id="edit_address"
                    rows="4"></textarea>

            </div>

            <div class="form-group">

                <label>Status</label>

                <select
                    name="status"
                    id="edit_status">

                    <option value="Active">
                        Active
                    </option>

                    <option value="Inactive">
                        Inactive
                    </option>

                </select>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="cancel-btn"
                    id="cancelEditModal">

                    Cancel

                </button>

                <button
                    type="submit"
                    name="update_customer"
                    class="save-btn">

                    Update Customer

                </button>

            </div>

        </form>

    </div>

</div>
<!-- ===========================================================
                    CUSTOMER MODULE END
=========================================================== -->

</div>

<!-- ===========================================================
                    VIEW CUSTOMER MODAL
=========================================================== -->
<div class="customer-modal" id="viewCustomerModal" style="display:none;">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Customer Details</h2>
            <span class="close-modal" id="closeViewModal">&times;</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" id="view_customer_name" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" id="view_phone" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="text" id="view_email" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" id="view_city" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>State</label>
                <input type="text" id="view_state" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>Pincode</label>
                <input type="text" id="view_pincode" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group full-width">
                <label>Address</label>
                <textarea id="view_address" rows="3" readonly style="background: rgba(255,255,255,0.4); resize:none;"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <input type="text" id="view_status" readonly style="background: rgba(255,255,255,0.4);">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="cancel-btn" id="closeViewModalBtn" style="width: 100%;">Close</button>
        </div>
    </div>
</div>

<!-- ==========================
     JAVASCRIPT
========================== -->
<script src="assets/js/topbar.js"></script>

<script src="assets/js/customer.js"></script>

</body>
</html>
