<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';
$session_user_role = $_SESSION['role'];

// Restrict settings edit to Administrator / Manager
if ($session_user_role !== 'Administrator' && $session_user_role !== 'Manager') {
    die("Access Denied! You do not have permission to view this settings page.");
}

$message = "";
$message_type = "";

// Fetch current company settings
$comp_res = $conn->query("SELECT * FROM company_settings LIMIT 1");
$company = null;
if ($comp_res && $comp_res->num_rows > 0) {
    $company = $comp_res->fetch_assoc();
}

/* ==========================================
   UPDATE COMPANY PROFILE
========================================== */
if (isset($_POST['update_settings'])) {
    $company_name = trim($_POST['company_name']);
    $owner_name = trim($_POST['owner_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $gst_number = trim($_POST['gst_number']);
    $currency_symbol = trim($_POST['currency_symbol']);
    $timezone = trim($_POST['timezone']);

    if (empty($company_name) || empty($phone)) {
        $message = "Company Name and Phone Number are required.";
        $message_type = "error";
    } else {
        if ($company) {
            // Update
            $stmt = $conn->prepare("
                UPDATE company_settings 
                SET company_name=?, owner_name=?, address=?, city=?, state=?, pincode=?, phone=?, email=?, website=?, gst_number=?, currency_symbol=?, timezone=?
                WHERE company_id = ?
            ");
            $stmt->bind_param("ssssssssssssi", $company_name, $owner_name, $address, $city, $state, $pincode, $phone, $email, $website, $gst_number, $currency_symbol, $timezone, $company['company_id']);
        } else {
            // Insert
            $stmt = $conn->prepare("
                INSERT INTO company_settings (company_name, owner_name, address, city, state, pincode, phone, email, website, gst_number, currency_symbol, timezone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssssssssss", $company_name, $owner_name, $address, $city, $state, $pincode, $phone, $email, $website, $gst_number, $currency_symbol, $timezone);
        }

        if ($stmt && $stmt->execute()) {
            $message = "System settings updated successfully.";
            $message_type = "success";
            
            // Re-fetch
            $comp_res = $conn->query("SELECT * FROM company_settings LIMIT 1");
            $company = $comp_res->fetch_assoc();
        } else {
            $message = "Database Error: " . ($stmt ? $stmt->error : $conn->error);
            $message_type = "error";
        }
        if ($stmt) $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage Settings | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css">
    
    <style>
        .settings-container {
            max-width: 750px;
            margin: auto;
            padding: 30px;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="settings-container glass-card">
        <h2 style="margin-bottom: 8px;">Workshop Garage Settings</h2>
        <p style="color: #6B7280; margin-bottom: 25px;">Adjust company details, invoice logos, GST registrations, and default currency parameters.</p>

        <?php if($message!=""){ ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <div class="form-grid">
                
                <div class="form-group">
                    <label>Garage Company Name</label>
                    <input type="text" name="company_name" value="<?php echo htmlspecialchars($company['company_name'] ?? 'AutoMaster Pro'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Owner Full Name</label>
                    <input type="text" name="owner_name" value="<?php echo htmlspecialchars($company['owner_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>GST Number (GSTIN)</label>
                    <input type="text" name="gst_number" value="<?php echo htmlspecialchars($company['gst_number'] ?? ''); ?>" placeholder="e.g. 33ABCDE1234F1Z5">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Website URL</label>
                    <input type="text" name="website" value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>" placeholder="e.g. https://automasterpro.com">
                </div>

                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($company['city'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($company['state'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Pincode / Zip</label>
                    <input type="text" name="pincode" value="<?php echo htmlspecialchars($company['pincode'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($company['currency_symbol'] ?? '₹'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Default Timezone</label>
                    <select name="timezone" required>
                        <option value="Asia/Kolkata" <?php echo ($company['timezone'] ?? '') == 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                        <option value="UTC" <?php echo ($company['timezone'] ?? '') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Company Address</label>
                    <textarea name="address" rows="3" required><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                </div>

            </div>

            <div style="margin-top: 25px; display:flex; justify-content: flex-end;">
                <button type="submit" name="update_settings" class="save-btn" style="width: 100%;">Save Settings Profile</button>
            </div>
        </form>
    </div>

</div>

<script src="assets/js/topbar.js"></script>
</body>
</html>
