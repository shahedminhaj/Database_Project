<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);
$user_id = $_SESSION['user_id'];

// Update profile
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $update_sql = "UPDATE Users SET Name = ?, Phone = ?, Address = ? WHERE User_ID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssi", $name, $phone, $address, $user_id);
    
    if($stmt->execute()) {
        $_SESSION['name'] = $name;
        $success = "Profile updated successfully!";
        $user = getUserInfo($conn);
    } else {
        $error = "Error updating profile!";
    }
}

// Change password
if(isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Verify current password
    $hashed_current = hash('sha256', $current_password);
    if($hashed_current !== $user['Password']) {
        $password_error = "Current password is incorrect!";
    } elseif($new_password !== $confirm_password) {
        $password_error = "New passwords don't match!";
    } elseif(strlen($new_password) < 6) {
        $password_error = "Password must be at least 6 characters!";
    } else {
        // Update password
        $hashed_new = hash('sha256', $new_password);
        $update_sql = "UPDATE Users SET Password = ? WHERE User_ID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $hashed_new, $user_id);
        
        if($stmt->execute()) {
            $password_success = "Password changed successfully!";
        } else {
            $password_error = "Error changing password!";
        }
    }
}

// Get user statistics
$total_services = $conn->query("SELECT COUNT(*) as count FROM Service_Requests WHERE User_ID = $user_id")->fetch_assoc()['count'];
$total_complaints = $conn->query("SELECT COUNT(*) as count FROM Complaints WHERE User_ID = $user_id")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT COUNT(*) as count FROM Payments WHERE User_ID = $user_id")->fetch_assoc()['count'];
$total_spent = $conn->query("SELECT SUM(Amount) as total FROM Payments WHERE User_ID = $user_id AND Status = 'Success'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - City Corporation Portal</title>
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
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="complaints.php"><i class="fas fa-exclamation-circle"></i> Complaints</a>
                <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                <div class="balance-display">
                    <i class="fas fa-wallet"></i>
                    <span>Balance: </span>
                    <span class="balance-amount">৳<?php echo number_format($user['Balance'], 2); ?></span>
                </div>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(45deg, #00cec9, #00b894);">
            <h1>My Profile</h1>
            <p>Manage your account information</p>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Profile Information -->
            <div class="payment-section">
                <h2 style="color: var(--dark); margin-bottom: 20px;">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" name="name" class="form-control" required 
                               value="<?php echo htmlspecialchars($user['Name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> NID Number</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($user['NID']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['Email']); ?>" disabled>
                        <small style="color: #666; display: block; margin-top: 5px;">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>" 
                               placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Member Since</label>
                        <input type="text" class="form-control" 
                               value="<?php echo date('F j, Y', strtotime($user['Created_At'])); ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Account Stats & Password -->
            <div>
                <!-- Account Stats -->
                <div class="dashboard-card" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-chart-bar"></i> Account Statistics</h3>
                    <div style="margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                            <span>Service Requests:</span>
                            <strong><?php echo $total_services; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                            <span>Complaints Filed:</span>
                            <strong><?php echo $total_complaints; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                            <span>Total Payments:</span>
                            <strong><?php echo $total_payments; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Total Spent:</span>
                            <strong style="color: #e74c3c;">৳<?php echo number_format($total_spent, 2); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="payment-section">
                    <h4 style="color: var(--dark); margin-bottom: 15px;">
                        <i class="fas fa-key"></i> Change Password
                    </h4>
                    
                    <?php if(isset($password_success)): ?>
                        <div class="alert alert-success" style="padding: 10px; font-size: 14px; margin-bottom: 15px;">
                            <?php echo $password_success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($password_error)): ?>
                        <div class="alert alert-error" style="padding: 10px; font-size: 14px; margin-bottom: 15px;">
                            <?php echo $password_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning btn-block">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>