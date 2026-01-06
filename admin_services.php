<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);

// Get all services
$services_sql = "SELECT * FROM Services ORDER BY Service_Name";
$services = $conn->query($services_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <i class="fas fa-city"></i>
                <span>City Corporation Portal</span>
            </div>
            
            <div class="nav-links">
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="admin_complaints.php"><i class="fas fa-exclamation-circle"></i> All Complaints</a>
                <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="admin_services.php" class="active"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="admin_pending.php"><i class="fas fa-tasks"></i> Pending Tasks</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(45deg, #1abc9c, #16a085);">
            <h1>Manage Services</h1>
            <p>View and manage all municipal services</p>
        </div>

        <!-- Services Table -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-list"></i> All Services
            </h2>
            
            <?php if($services->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Department</th>
                            <th>Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($service = $services->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['Service_Name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($service['Description'], 0, 100)) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($service['Dept']); ?></td>
                            <td>৳<?php echo number_format($service['Fee'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($service['Status']); ?>">
                                    <?php echo $service['Status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-concierge-bell" style="font-size: 64px; color: #95a5a6; margin-bottom: 20px;"></i>
                    <h3 style="color: #95a5a6;">No Services Found</h3>
                    <p style="color: #666;">No services have been added yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>