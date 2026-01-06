<?php
require_once 'config.php';

if(isset($_SESSION['user_id'])) {
    $user = getUserInfo($conn);
    if($user['User_Type'] == 'Admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $hashed_password = hash('sha256', $password);
    
    $sql = "SELECT * FROM Users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if($hashed_password === $user['Password']) {
            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['user_type'] = $user['User_Type'];
            
            if($user['User_Type'] == 'Admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Account not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - City Corporation Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo" style="justify-content: center; margin-bottom: 30px;">
                <i class="fas fa-city"></i>
                <span>City Corporation Portal</span>
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: var(--indigo);">Welcome Back</h2>
                <p style="color: #666;">Login to your account</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 25px;">
                <p style="color: #666;">Don't have an account? 
                    <a href="register.php" style="color: var(--indigo); font-weight: 600;">Register here</a>
                </p>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 12px; font-size: 14px;">
                <p style="color: #666; margin-bottom: 10px; font-weight: 600;">
                    <i class="fas fa-user-shield"></i> Test Accounts:
                </p>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; justify-content: space-between;">

                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Admin:</span>
                        <span style="font-weight: 500;">admin@city.com / admin123</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>