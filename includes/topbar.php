<?php
$user_fullname = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Administrator';
$user_avatar = strtoupper(substr($user_fullname, 0, 1));
?>
<div class="topbar">

    <!-- Decorative Topbar Bubble -->
    <div class="topbar-glow-bubble"></div>

    <!-- LEFT -->

    <div class="topbar-left">

        <div class="search-box">

            <span class="search-icon">🔍</span>

            <input
                type="text"
                placeholder="Search customers, vehicles, invoices...">

            <span class="shortcut">
                Ctrl + /
            </span>

        </div>

        <div class="welcome-section">

            <h1>
                Good Afternoon, <?php echo htmlspecialchars($user_fullname); ?>! 👋
            </h1>

            <p>
                Welcome to AutoMaster Pro - 2026
            </p>

        </div>

    </div>

    <!-- RIGHT -->

    <div class="topbar-right">

        <div class="top-icons">

            <div class="top-icon" id="themeToggle">
                🌙
            </div>

            <div class="top-icon">
                🔔
            </div>

            <div class="profile-card">

                <div class="profile-avatar">
                    <?php echo htmlspecialchars($user_avatar); ?>
                </div>

                <div class="profile-text">

                    <h4><?php echo htmlspecialchars($user_fullname); ?></h4>

                    <small><?php echo htmlspecialchars($user_role); ?></small>

                </div>

                <div class="profile-arrow">

                    ▼

                    <div class="profile-dropdown">

                        <a href="profile.php">👤 My Profile</a>

                        <a href="settings.php">⚙ Settings</a>

                        <hr>

                        <a href="logout.php">
                            🚪 Logout
                        </a>

                    </div>

                </div>

            </div>

        </div>

        <div class="datetime-card">

            <h4 id="currentDate"></h4>

            <p id="currentTime"></p>

        </div>

    </div>

</div>