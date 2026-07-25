<?php
session_start();
require_once '../config/db.php';

$error = '';

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND status = 'Active' LIMIT 1");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows > 0){

        $row = $result->fetch_assoc();

        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['role'] = $row['role'];

        header("Location: ../dashboard.php");
        exit();

    } else {

        $error = "Invalid Username or Password!";

    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../assets/css/login.css">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>AutoMaster Pro 2026</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">


</head>

<body>

<div class="light-1"></div>
<div class="light-2"></div>
<div class="light-3"></div>

<div class="line line1"></div>
<div class="line line2"></div>
<div class="line line3"></div>

<div class="bubble b1"></div>
<div class="bubble b2"></div>
<div class="bubble b3"></div>
<div class="bubble b4"></div>

<div class="container">

    <div class="left">

        <div class="logo-box">

            <div class="logo">🚗</div>

            <div>
                <div class="brand">AutoMaster Pro</div>
                <div>Workshop & Billing Management System</div>
            </div>

        </div>

        <p class="subtitle">
            Intelligent Workshop Management Solution for Modern Garages.
        </p>

        <div class="car">

            <img src="../assets/images/car.png" alt="Car">

        </div>

    </div>

    <div class="right">

        <div class="login-card">

            <h2 class="login-title">
                Welcome Back
            </h2>

            <p class="login-sub">
                Sign in to continue
            </p>

            <?php if(!empty($error)){ ?>
                <div class="error">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="POST">

                <div class="form-group">
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Username / Email"
                        required>
                </div>

                <div class="form-group">
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Password"
                        required>
                </div>

                <button
                    type="submit"
                    name="login"
                    class="login-btn">
                    Login
                </button>

            </form>

            <div class="footer-text">
                AutoMaster Pro 2026
            </div>

        </div>

    </div>

</div>

</body>
</html>