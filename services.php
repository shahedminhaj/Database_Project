<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);
$user_id = $_SESSION['user_id'];

// Handle service request
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['service_id'])) {
    $service_id = $_POST['service_id'];
    $details = $_POST['details'] ?? '';
    
    // Get service fee
    $service_sql = "SELECT * FROM Services WHERE Service_ID = ? AND Status = 'Active'";
    $stmt = $conn->prepare($service_sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service_result = $stmt->get_result();
    $service = $service_result->fetch_assoc();
    
    if($service) {
        // Insert service request
        $insert_sql = "INSERT INTO Service_Requests (User_ID, Service_ID, Request_Details, Payment_Amount, Payment_Status) 
                       VALUES (?, ?, ?, ?, 'Due')";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iisd", $user_id, $service_id, $details, $service['Fee']);
        
        if($stmt->execute()) {
            $success = "Service requested successfully! Amount due: ৳" . number_format($service['Fee'], 2);
        } else {
            $error = "Error requesting service!";
        }
    } else {
        $error = "Service not found or inactive!";
    }
}

// Get all active services
$services_sql = "SELECT * FROM Services WHERE Status = 'Active' ORDER BY Service_Name";
$services = $conn->query($services_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - City Corporation Portal</title>
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
                <a href="services.php" class="active"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="complaints.php"><i class="fas fa-exclamation-circle"></i> Complaints</a>
                <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <div class="balance-display">
                    <i class="fas fa-wallet"></i>
                    <span>Balance: </span>
                    <span class="balance-amount">৳<?php echo number_format($user['Balance'], 2); ?></span>
                </div>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(45deg, #27ae60, #2ecc71);">
            <h1>Municipal Services</h1>
            <p>Request services and make payments online</p>
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

        <!-- Services Grid -->
        <div class="services-grid">
            <?php while($service = $services->fetch_assoc()): ?>
            <div class="service-card">
                <div class="service-header" style="background: <?php echo $service['Color']; ?>;">
                    <i class="fas <?php echo $service['Icon']; ?>"></i>
                    <?php echo $service['Service_Name']; ?>
                </div>
                <div class="service-content">
                    <p class="service-description">
                        <?php echo $service['Description']; ?>
                    </p>
                    <p><strong>Department:</strong> <?php echo $service['Dept']; ?></p>
                </div>
                <div class="service-footer">
                    <div>
                        <div class="fee-label">Service Fee</div>
                        <div class="fee-amount">৳<?php echo number_format($service['Fee'], 2); ?></div>
                    </div>
                    <button type="button" class="btn btn-primary" 
                            onclick="showServiceModal(<?php echo $service['Service_ID']; ?>, '<?php echo addslashes($service['Service_Name']); ?>', <?php echo $service['Fee']; ?>)">
                        <i class="fas fa-hand-pointer"></i> Request
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Request Service</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="serviceForm" method="POST">
                <input type="hidden" id="modalServiceId" name="service_id">
                
                <div class="form-group">
                    <label>Service Name</label>
                    <div class="form-control" id="modalServiceName" style="background: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Service Fee</label>
                    <div class="form-control" id="modalServiceFee" style="background: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="details">Additional Details (Optional)</label>
                    <textarea id="details" name="details" class="form-control" rows="4" 
                              placeholder="Provide any additional information about your request"></textarea>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    The service fee will be added to your due payments. You can pay immediately or later from Payments page.
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check-circle"></i> Confirm Request
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showServiceModal(serviceId, serviceName, serviceFee) {
            document.getElementById('modalServiceId').value = serviceId;
            document.getElementById('modalServiceName').textContent = serviceName;
            document.getElementById('modalServiceFee').textContent = '৳' + parseFloat(serviceFee).toFixed(2);
            
            // Show modal
            document.getElementById('serviceModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('serviceModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('serviceModal');
            if(event.target == modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>