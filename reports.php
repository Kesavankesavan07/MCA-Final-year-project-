<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

include 'config/db.php';
$user_id = $_SESSION['user_id'];

// Get Total Revenue & Invoice Metrics
$revenue = 0;
$invoicesCount = 0;
$avgInvoice = 0;
$pendingReceivables = 0;

$res1 = $conn->query("SELECT SUM(grand_total) AS rev, COUNT(*) AS count FROM invoices");
if ($res1 && $row = $res1->fetch_assoc()) {
    $revenue = $row['rev'] ? $row['rev'] : 0;
    $invoicesCount = $row['count'] ? $row['count'] : 0;
    $avgInvoice = $invoicesCount > 0 ? ($revenue / $invoicesCount) : 0;
}

$res2 = $conn->query("SELECT SUM(grand_total) AS total FROM invoices WHERE payment_status='Pending'");
if ($res2 && $row = $res2->fetch_assoc()) {
    $pendingReceivables = $row['total'] ? $row['total'] : 0;
}

// Fetch Mechanic performance stats
$mechanics = [];
$mech_res = $conn->query("
    SELECT m.mechanic_name, m.specialization, m.experience, m.status, COUNT(s.service_id) AS total_jobs, IFNULL(SUM(s.labour_charge), 0) AS total_revenue
    FROM mechanics m
    LEFT JOIN services s ON m.mechanic_id = s.mechanic_id
    GROUP BY m.mechanic_id
    ORDER BY total_revenue DESC
");
if ($mech_res) {
    while ($row = $mech_res->fetch_assoc()) {
        $mechanics[] = $row;
    }
}

// Fetch Monthly Income for Line Chart
$months = [];
$sales = [];
$sales_res = $conn->query("
    SELECT DATE_FORMAT(invoice_date, '%M') AS month_name, SUM(grand_total) AS total 
    FROM invoices 
    GROUP BY MONTH(invoice_date) 
    ORDER BY MONTH(invoice_date) ASC
");
if ($sales_res) {
    while ($row = $sales_res->fetch_assoc()) {
        $months[] = $row['month_name'];
        $sales[] = (float)$row['total'];
    }
}
if (empty($months)) {
    $months = ['January', 'February', 'March', 'April', 'May', 'June'];
    $sales = [0, 0, 0, 0, 0, $revenue];
}

// Service type volume for breakdown
$serviceLabels = [];
$serviceCounts = [];
$serv_break = $conn->query("
    SELECT st.service_name, COUNT(s.service_id) AS total_count
    FROM service_types st
    LEFT JOIN services s ON st.service_type_id = s.service_type_id
    GROUP BY st.service_type_id
    ORDER BY total_count DESC
");
if ($serv_break) {
    while ($row = $serv_break->fetch_assoc()) {
        $serviceLabels[] = $row['service_name'];
        $serviceCounts[] = (int)$row['total_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | AutoMaster Pro 2026</title>
    
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/topbar.css">
    <link rel="stylesheet" href="assets/css/customer.css"> <!-- Common container classes -->
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .chart-box {
            min-height: 350px;
            padding: 24px;
        }
        .chart-container {
            height: 280px;
            position: relative;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="customer-content">

    <div class="customer-header">
        <div>
            <h1>Reports & Analytics</h1>
            <p>Track business health, sales margins, mechanic performances, and category metrics.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="customer-stats">
        <div class="stat-card">
            <div class="stat-icon purple">📈</div>
            <div class="stat-info">
                <small>Gross Earnings</small>
                <h2>₹<?php echo number_format($revenue, 2); ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">💸</div>
            <div class="stat-info">
                <small>Total Outstanding</small>
                <h2>₹<?php echo number_format($pendingReceivables, 2); ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📊</div>
            <div class="stat-info">
                <small>Average Ticket Size</small>
                <h2>₹<?php echo number_format($avgInvoice, 2); ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🧾</div>
            <div class="stat-info">
                <small>Bills Generated</small>
                <h2><?php echo $invoicesCount; ?></h2>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="report-grid">
        <div class="glass-card chart-box">
            <h3>Monthly Income Stream</h3>
            <div class="chart-container" style="margin-top: 15px;">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
        
        <div class="glass-card chart-box">
            <h3>Service Type Distribution</h3>
            <div class="chart-container" style="margin-top: 15px;">
                <canvas id="servicesDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Mechanic Performance Table -->
    <h3 style="margin-bottom: 15px; font-size:18px;">Staff Performance (Mechanics Log)</h3>
    <div class="customer-table glass-card" style="margin-bottom: 30px;">
        <table>
            <thead>
                <tr>
                    <th>Mechanic Name</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Status</th>
                    <th>Total Completed Jobs</th>
                    <th>Total Labor Value Generated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mechanics)): ?>
                    <?php foreach ($mechanics as $mech): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($mech['mechanic_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($mech['specialization']); ?></td>
                            <td><?php echo $mech['experience']; ?> Years</td>
                            <td>
                                <span class="status <?php echo strtolower($mech['status']); ?>">
                                    <?php echo htmlspecialchars($mech['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $mech['total_jobs']; ?> Jobs</td>
                            <td><strong>₹<?php echo number_format($mech['total_revenue'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No mechanics registered.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Sales Trend
    const salesCtx = document.getElementById("monthlySalesChart");
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($sales); ?>,
                    backgroundColor: '#6C63FF',
                    borderRadius: 10,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: "rgba(108, 99, 255, 0.05)" } }
                }
            }
        });
    }

    // 2. Services distribution
    const servicesCtx = document.getElementById("servicesDistributionChart");
    if (servicesCtx) {
        new Chart(servicesCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($serviceLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($serviceCounts); ?>,
                    backgroundColor: ['#7C5CFF', '#2563EB', '#16A34A', '#F59E0B', '#EC4899', '#3B82F6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { family: 'Poppins', size: 11 } } }
                }
            }
        });
    }
});
</script>

</body>
</html>
