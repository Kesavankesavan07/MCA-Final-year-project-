<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';
$session_user_id = $_SESSION['user_id'];
$session_user_role = $_SESSION['role'];

// Restrict to Administrator only
if ($session_user_role !== 'Administrator') {
    die("Access Denied! Only Administrators can manage user accounts.");
}

$message = "";
$message_type = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

/* ==========================================
   ADD SYSTEM USER
========================================== */
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $status = trim($_POST['status']);

    if (empty($username) || empty($password) || empty($full_name)) {
        $message = "Username, Password and Full Name are required.";
        $message_type = "error";
    } else {
        // Check duplicate username
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Username already taken.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, full_name, role, phone, email, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param("sssssss", $username, $password, $full_name, $role, $phone, $email, $status);
                if ($stmt->execute()) {
                    $message = "System user added successfully.";
                    $message_type = "success";
                } else {
                    $message = "Database Error: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
        $check->close();
    }
}

/* ==========================================
   UPDATE SYSTEM USER
========================================== */
if (isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $status = trim($_POST['status']);

    if (!empty($password)) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET username=?, password=?, full_name=?, role=?, phone=?, email=?, status=?
            WHERE user_id=?
        ");
        $stmt->bind_param("sssssssi", $username, $password, $full_name, $role, $phone, $email, $status, $user_id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("
            UPDATE users 
            SET username=?, full_name=?, role=?, phone=?, email=?, status=?
            WHERE user_id=?
        ");
        $stmt->bind_param("ssssssi", $username, $full_name, $role, $phone, $email, $status, $user_id);
    }
    
    if ($stmt) {
        if ($stmt->execute()) {
            $message = "System user updated successfully.";
            $message_type = "success";
        } else {
            $message = "Database Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

/* ==========================================
   DELETE SYSTEM USER
========================================== */
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    if ($user_id === $session_user_id) {
        $message = "You cannot delete your own logged-in account.";
        $message_type = "error";
    } else {
        $delete = $conn->query("DELETE FROM users WHERE user_id = $user_id");
        if ($delete) {
            $message = "User deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to delete user.";
            $message_type = "error";
        }
    }
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
        SELECT COUNT(*) AS total FROM users WHERE username LIKE ? OR full_name LIKE ? OR role LIKE ?
    ");
    $countStmt->bind_param("sss", $keyword, $keyword, $keyword);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT * FROM users WHERE username LIKE ? OR full_name LIKE ? OR role LIKE ?
        ORDER BY user_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("sssii", $keyword, $keyword, $keyword, $offset, $limit);
} else {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM users");
    $totalRows = $countRes->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT * FROM users
        ORDER BY user_id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$users = $stmt->get_result();
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css"> <!-- Common table style layout -->
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="customer-header">
        <div>
            <h1>Users & Accounts</h1>
            <p>Manage system access, roles (Manager/Staff), and passwords.</p>
        </div>
    </div>

    <?php if($message!=""){ ?>
        <div class="alert <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <!-- Toolbar -->
    <div class="customer-toolbar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search by username, full name or role..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
        <button class="add-btn" id="addUserBtn">+ Add User</button>
    </div>

    <!-- Table -->
    <div class="customer-table glass-card">
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Registered Date</th>
                    <th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($users->num_rows > 0){ ?>
                    <?php while($row = $users->fetch_assoc()){ ?>
                        <tr>
                            <td><?php echo $row['user_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><span style="font-family:monospace; font-weight:700; color:#6C63FF;"><?php echo htmlspecialchars($row['username']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['role']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['phone'] ? $row['phone'] : '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ? $row['email'] : '-'); ?></td>
                            <td>
                                <span class="status <?php echo strtolower($row['status']); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="view-btn" title="View"
                                        data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                        data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        data-role="<?php echo htmlspecialchars($row['role']); ?>"
                                        data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                        data-created="<?php echo date("d M Y H:i", strtotime($row['created_at'])); ?>">
                                        👁
                                    </button>
                                    <button class="edit-btn" title="Edit"
                                        data-id="<?php echo $row['user_id']; ?>"
                                        data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                        data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        data-role="<?php echo htmlspecialchars($row['role']); ?>"
                                        data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                        ✏
                                    </button>
                                    <a href="?delete=<?php echo $row['user_id']; ?>" class="delete-btn" onclick="return confirm('Delete this system user account?');" title="Delete">🗑</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-table">
                                <div class="empty-icon">👥</div>
                                <h3>No Users Found</h3>
                                <p>Click "+ Add User" to register a system account.</p>
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
                     ADD USER MODAL
=========================================================== -->
<div class="customer-modal" id="userModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Add System User</h2>
            <span class="close-modal" id="closeModal">&times;</span>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label>Username (System login)</label>
                    <input type="text" name="username" placeholder="e.g. jdoe12" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter Password" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="Staff">Staff / Clerk</option>
                        <option value="Manager">Manager</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="Enter Phone Number">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter Email">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelModal">Cancel</button>
                <button type="submit" name="add_user" class="save-btn">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     EDIT USER MODAL
=========================================================== -->
<div class="customer-modal" id="editUserModal">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>Edit System User</h2>
            <span class="close-modal" id="closeEditModal">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_full_name" required>
                </div>
                <div class="form-group">
                    <label>Username (System login)</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                <div class="form-group">
                    <label>Password <span style="font-weight: normal; font-size:11px; color:#777;">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="Staff">Staff / Clerk</option>
                        <option value="Manager">Manager</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="edit_phone">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="edit_email">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancelEditModal">Cancel</button>
                <button type="submit" name="update_user" class="save-btn">Update Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
                     VIEW USER MODAL
=========================================================== -->
<div class="customer-modal" id="viewUserModal" style="display:none;">
    <div class="customer-modal-content">
        <div class="modal-header">
            <h2>System User Details</h2>
            <span class="close-modal" id="closeViewModal">&times;</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="view_full_name" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>System Username</label>
                <input type="text" id="view_username" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group">
                <label>System Role</label>
                <input type="text" id="view_role" readonly style="background: rgba(255,255,255,0.4);">
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
                <label>Account Status</label>
                <input type="text" id="view_status" readonly style="background: rgba(255,255,255,0.4);">
            </div>
            <div class="form-group full-width">
                <label>Account Created Date</label>
                <input type="text" id="view_created" readonly style="background: rgba(255,255,255,0.4);">
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
    const addBtn = document.getElementById("addUserBtn");
    const modal = document.getElementById("userModal");
    const closeModal = document.getElementById("closeModal");
    const cancelModal = document.getElementById("cancelModal");

    if (addBtn) addBtn.addEventListener("click", () => modal.style.display = "flex");
    if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
    if (cancelModal) cancelModal.addEventListener("click", () => modal.style.display = "none");

    const editModal = document.getElementById("editUserModal");
    const closeEdit = document.getElementById("closeEditModal");
    const cancelEdit = document.getElementById("cancelEditModal");

    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("edit_user_id").value = this.dataset.id;
            document.getElementById("edit_username").value = this.dataset.username;
            document.getElementById("edit_full_name").value = this.dataset.name;
            document.getElementById("edit_role").value = this.dataset.role;
            document.getElementById("edit_phone").value = this.dataset.phone;
            document.getElementById("edit_email").value = this.dataset.email;
            document.getElementById("edit_status").value = this.dataset.status;
            
            editModal.style.display = "flex";
        });
    });

    if (closeEdit) closeEdit.addEventListener("click", () => editModal.style.display = "none");
    if (cancelEdit) cancelEdit.addEventListener("click", () => editModal.style.display = "none");

    // View Modal Logic
    const viewModal = document.getElementById("viewUserModal");
    const closeView = document.getElementById("closeViewModal");
    const closeViewBtn = document.getElementById("closeViewModalBtn");

    document.querySelectorAll(".view-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("view_full_name").value = this.dataset.name;
            document.getElementById("view_username").value = this.dataset.username;
            document.getElementById("view_role").value = this.dataset.role;
            document.getElementById("view_phone").value = this.dataset.phone || '-';
            document.getElementById("view_email").value = this.dataset.email || '-';
            document.getElementById("view_status").value = this.dataset.status;
            document.getElementById("view_created").value = this.dataset.created;
            
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
