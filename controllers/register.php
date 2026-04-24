<?php
require_once '../config/config.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nid = trim($_POST['nid']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($name) || empty($email) || empty($password) || empty($nid)) {
        $error = "All required fields must be filled!";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        $check_sql = "SELECT User_ID FROM Users WHERE Email = ? OR NID = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $email, $nid);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "Email or NID already registered!";
        } else {
            $hashed_password = hash('sha256', $password);
            
            $insert_sql = "INSERT INTO Users (NID, Name, Email, Phone, Password, Address, Balance) 
                           VALUES (?, ?, ?, ?, ?, ?, 100.00)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $nid, $name, $email, $phone, $hashed_password, $address);
            
            if($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed! Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - City Corporation Portal</title>
    <link rel="stylesheet" href="../views/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo" style="justify-content: center; margin-bottom: 30px;">
                <i class="fas fa-city"></i>
                <span>City Corporation Portal</span>
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: var(--indigo);">Create Account</h2>
                <p style="color: #666;">Join as a citizen</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <p style="margin-top: 10px;">
                        <a href="login.php" style="color: #155724; font-weight: 600;">
                            <i class="fas fa-sign-in-alt"></i> Click here to login
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="name" class="form-control" required 
                           placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> NID Number *</label>
                    <input type="text" name="nid" class="form-control" required 
                           placeholder="Enter your NID number" value="<?php echo isset($_POST['nid']) ? htmlspecialchars($_POST['nid']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           placeholder="Enter your phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Address</label>
                    <textarea name="address" class="form-control" rows="3" 
                              placeholder="Enter your address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Create a password (min 6 characters)">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required 
                           placeholder="Confirm your password">
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 25px;">
                <p style="color: #666;">Already have an account? 
                    <a href="login.php" style="color: var(--indigo); font-weight: 600;">Login here</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>