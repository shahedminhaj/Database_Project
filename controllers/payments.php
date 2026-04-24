<?php
require_once '../config/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);
$user_id = $_SESSION['user_id'];

// Handle payment
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $payment_method = $_POST['payment_method'];
    
    // Get request details
    $request_sql = "SELECT sr.*, s.Service_Name FROM Service_Requests sr 
                   JOIN Services s ON sr.Service_ID = s.Service_ID 
                   WHERE sr.Request_ID = ? AND sr.User_ID = ?";
    $stmt = $conn->prepare($request_sql);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $request_result = $stmt->get_result();
    $request = $request_result->fetch_assoc();
    
    if($request) {
        // Check if user has sufficient balance
        if($user['Balance'] >= $request['Payment_Amount']) {
            // Deduct from balance
            $new_balance = $user['Balance'] - $request['Payment_Amount'];
            $update_sql = "UPDATE Users SET Balance = ? WHERE User_ID = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();
            
            // Generate transaction ID
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            
            // Insert payment record
            $payment_sql = "INSERT INTO Payments (User_ID, Request_ID, Amount, Payment_Method, Transaction_ID, Status) 
                           VALUES (?, ?, ?, ?, ?, 'Success')";
            $stmt = $conn->prepare($payment_sql);
            $stmt->bind_param("iidss", $user_id, $request_id, $request['Payment_Amount'], $payment_method, $transaction_id);
            
            if($stmt->execute()) {
                // Update service request payment status
                $update_request_sql = "UPDATE Service_Requests SET Payment_Status = 'Paid', Payment_Date = NOW() 
                                       WHERE Request_ID = ?";
                $stmt = $conn->prepare($update_request_sql);
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                
                $success = "Payment of ৳" . number_format($request['Payment_Amount'], 2) . 
                          " for " . $request['Service_Name'] . " completed successfully!";
                
                // Refresh user data
                $user = getUserInfo($conn);
            }
        } else {
            $error = "Insufficient balance! Your balance is ৳" . number_format($user['Balance'], 2) . 
                    ", but required amount is ৳" . number_format($request['Payment_Amount'], 2);
        }
    } else {
        $error = "Service request not found!";
    }
}

// Get due payments
$due_payments_sql = "SELECT sr.*, s.Service_Name 
                    FROM Service_Requests sr 
                    JOIN Services s ON sr.Service_ID = s.Service_ID 
                    WHERE sr.User_ID = $user_id AND sr.Payment_Status = 'Due'
                    ORDER BY sr.Submission_Date DESC";
$due_payments = $conn->query($due_payments_sql);

// Get payment history
$payment_history_sql = "SELECT p.*, s.Service_Name 
                       FROM Payments p 
                       JOIN Service_Requests sr ON p.Request_ID = sr.Request_ID 
                       JOIN Services s ON sr.Service_ID = s.Service_ID 
                       WHERE p.User_ID = $user_id 
                       ORDER BY p.Payment_Date DESC LIMIT 10";
$payment_history = $conn->query($payment_history_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - City Corporation Portal</title>
    <link rel="stylesheet" href="../views/style.css">
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
                <a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a>
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
        <div class="welcome-banner" style="background: linear-gradient(45deg, #3498db, #2980b9);">
            <h1>Payment Management</h1>
            <p>Pay for services and view payment history</p>
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

        <!-- Due Payments -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> Due Payments
            </h2>
            
            <?php if($due_payments->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($payment = $due_payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['Service_Name']); ?></td>
                            <td><strong>৳<?php echo number_format($payment['Payment_Amount'], 2); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($payment['Submission_Date'])); ?></td>
                            <td>
                                <span class="status-badge status-due">Due</span>
                            </td>
                            <td>
                                <button class="btn btn-success btn-sm" onclick="showPaymentModal(<?php echo $payment['Request_ID']; ?>, '<?php echo addslashes($payment['Service_Name']); ?>', <?php echo $payment['Payment_Amount']; ?>)">
                                    <i class="fas fa-money-bill-wave"></i> Pay Now
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="color: #27ae60;">No Due Payments</h3>
                    <p style="color: #666;">All your payments are up to date!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment History -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-history"></i> Payment History
            </h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($payment = $payment_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $payment['Transaction_ID']; ?></td>
                            <td><?php echo htmlspecialchars($payment['Service_Name']); ?></td>
                            <td>৳<?php echo number_format($payment['Amount'], 2); ?></td>
                            <td><?php echo ucfirst($payment['Payment_Method']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($payment['Payment_Date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($payment['Status']); ?>">
                                    <?php echo $payment['Status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Make Payment</h3>
                <button class="close-modal" onclick="closePaymentModal()">&times;</button>
            </div>
            <form id="paymentForm" method="POST">
                <input type="hidden" id="paymentRequestId" name="request_id">
                <input type="hidden" id="selectedMethod" name="payment_method" value="wallet" required>
                
                <div class="form-group">
                    <label>Service</label>
                    <div class="form-control" id="modalServiceName" style="background: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Amount to Pay</label>
                    <div class="form-control" id="modalAmount" style="background: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Your Current Balance</label>
                    <div class="form-control" style="background: #f8f9fa; color: #27ae60; font-weight: bold;">
                        ৳<?php echo number_format($user['Balance'], 2); ?>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Payment will be deducted from your wallet balance.
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check-circle"></i> Confirm Payment
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closePaymentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showPaymentModal(requestId, serviceName, amount) {
            document.getElementById('paymentRequestId').value = requestId;
            document.getElementById('modalServiceName').textContent = serviceName;
            document.getElementById('modalAmount').textContent = '৳' + parseFloat(amount).toFixed(2);
            
            // Show modal
            document.getElementById('paymentModal').style.display = 'flex';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if(event.target == modal) {
                closePaymentModal();
            }
        };
    </script>
</body>
</html>