<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);

// Get all users
$users_sql = "SELECT * FROM Users WHERE User_Type = 'Citizen' ORDER BY Created_At DESC";
$users = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                <a href="admin_users.php" class="active"><i class="fas fa-users"></i> Users</a>
                <a href="admin_services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="admin_pending.php"><i class="fas fa-tasks"></i> Pending Tasks</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(45deg, #f39c12, #d35400);">
            <h1>Manage Users</h1>
            <p>View and manage all registered citizens</p>
        </div>

        <!-- Users Table -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-users"></i> All Registered Users
            </h2>
            
            <?php if($users->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>NID</th>
                            <th>Balance</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user_row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user_row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($user_row['Email']); ?></td>
                            <td><?php echo $user_row['Phone'] ? htmlspecialchars($user_row['Phone']) : 'N/A'; ?></td>
                            <td><?php echo $user_row['NID']; ?></td>
                            <td>৳<?php echo number_format($user_row['Balance'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user_row['Created_At'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-users-slash" style="font-size: 64px; color: #95a5a6; margin-bottom: 20px;"></i>
                    <h3 style="color: #95a5a6;">No Users Found</h3>
                    <p style="color: #666;">No citizens have registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>