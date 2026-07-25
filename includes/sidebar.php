<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar glass-card">

    <!-- Decorative Sidebar Bubbles -->
    <div class="sidebar-glow-bubble"></div>

    <!-- Logo Section -->
    <div class="sidebar-logo-card">

        <div class="sidebar-logo">

            <div class="logo-icon">
                🚗
            </div>

            <div class="logo-text">
                <h2>AutoMaster Pro</h2>
                <p>Workshop & Billing System</p>
            </div>

        </div>

    </div>

    <!-- Menu Section -->
    <div class="sidebar-menu-card">

        <ul class="sidebar-menu">

            <li class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <span>🏠</span>
                    Dashboard
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'customer.php') ? 'active' : ''; ?>">
                <a href="customer.php">
                    <span>👥</span>
                    Customers
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'vehicles.php') ? 'active' : ''; ?>">
                <a href="vehicles.php">
                    <span>🚘</span>
                    Vehicles
                </a>
            </li>
            
            <li class="<?php echo ($currentPage == 'services.php') ? 'active' : ''; ?>">
                <a href="services.php">
                    <span>🛠</span>
                    Services
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'inventory.php') ? 'active' : ''; ?>">
                <a href="inventory.php">
                    <span>📦</span>
                    Inventory
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'billing.php') ? 'active' : ''; ?>">
                <a href="billing.php">
                    <span>🧾</span>
                    Billing / Invoice
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php">
                    <span>📊</span>
                    Reports
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                <a href="users.php">
                    <span>👤</span>
                    Users
                </a>
            </li>

            <li class="<?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">
                <a href="settings.php">
                    <span>⚙️</span>
                    Settings
                </a>
            </li>

        </ul>

    </div>

    <!-- Car Showcase Section with Bubble -->
    <div class="sidebar-car-card">

        <div class="sidebar-car">
            <div class="car-bubble-bg"></div>
            <img src="assets/images/BMW.png" alt="AutoMaster Car" class="sidebar-car-img">

        </div>

    </div>

    <!-- Footer Section -->
    <div class="sidebar-footer-card">

        <div class="sidebar-footer">

            <div class="footer-settings">
                ⚙️
            </div>

            <h4>AutoMaster Pro</h4>

            <p>
                Intelligent Workshop &
                Billing System
            </p>

        </div>

    </div>

</div>