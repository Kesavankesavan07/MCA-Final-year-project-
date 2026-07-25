<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';
$session_user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";

// Fetch current user details
$user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $session_user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

/* ==========================================
   UPDATE PROFILE
========================================== */
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($full_name)) {
        $message = "Full Name is required.";
        $message_type = "error";
    } else {
        $pass_update_success = true;
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                $message = "Passwords do not match.";
                $message_type = "error";
                $pass_update_success = false;
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, email=?, password=? WHERE user_id=?");
                $stmt->bind_param("ssssi", $full_name, $phone, $email, $password, $session_user_id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, email=? WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $phone, $email, $session_user_id);
        }

        if ($pass_update_success && $stmt) {
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name; // update current session display name
                $message = "Profile updated successfully.";
                $message_type = "success";
                
                // Re-fetch details
                $user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                $user_stmt->bind_param("i", $session_user_id);
                $user_stmt->execute();
                $user = $user_stmt->get_result()->fetch_assoc();
                $user_stmt->close();
            } else {
                $message = "Database Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css"> <!-- Form/Modal layout -->
    
    <style>
        .profile-container {
            max-width: 650px;
            margin: auto;
            padding: 30px;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="profile-container glass-card">
        <h2 style="margin-bottom: 8px;">My Account Profile</h2>
        <p style="color: #6B7280; margin-bottom: 25px;">View or update your personal account info and passwords.</p>

        <?php if($message!=""){ ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <div class="form-grid" style="grid-template-columns: 1fr;">
                
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled style="background: rgba(0,0,0,0.05); font-weight: bold; color: #444;">
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: rgba(0,0,0,0.05); font-family: monospace; color: #444;">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <h3 style="margin-top: 20px; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 15px; font-size:16px;">Update Password</h3>

                <div class="form-group">
                    <label>New Password <span style="font-weight:normal; font-size:11px; color:#777;">(Leave blank to keep unchanged)</span></label>
                    <input type="password" name="password" placeholder="Enter new password">
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat new password">
                </div>

            </div>

            <div style="margin-top: 25px; display:flex; justify-content: flex-end;">
                <button type="submit" name="update_profile" class="save-btn" style="width: 100%;">Save Changes</button>
            </div>
        </form>
    </div>

</div>

<script src="assets/js/topbar.js"></script>
</body>
</html>
