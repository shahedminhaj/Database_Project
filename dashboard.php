<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);
$user_id = $_SESSION['user_id'];

if($user['User_Type'] == 'Admin') {
    header("Location: admin_dashboard.php");
    exit();
}

// Get statistics
$total_services = $conn->query("SELECT COUNT(*) as count FROM Service_Requests WHERE User_ID = $user_id")->fetch_assoc()['count'];
$total_complaints = $conn->query("SELECT COUNT(*) as count FROM Complaints WHERE User_ID = $user_id")->fetch_assoc()['count'];
$pending_complaints = $conn->query("SELECT COUNT(*) as count FROM Complaints WHERE User_ID = $user_id AND Status = 'Pending'")->fetch_assoc()['count'];
$due_payments = $conn->query("SELECT SUM(Payment_Amount) as total FROM Service_Requests WHERE User_ID = $user_id AND Payment_Status = 'Due'")->fetch_assoc()['total'] ?? 0;

// Get recent complaints
$recent_complaints = $conn->query("SELECT * FROM Complaints WHERE User_ID = $user_id ORDER BY Created_At DESC LIMIT 5");

// Get recent services
$recent_services = $conn->query("SELECT sr.*, s.Service_Name 
                                 FROM Service_Requests sr 
                                 JOIN Services s ON sr.Service_ID = s.Service_ID 
                                 WHERE sr.User_ID = $user_id 
                                 ORDER BY sr.Submission_Date DESC LIMIT 5");

// Get complaint stats
$complaint_stats = getComplaintStats($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - City Corporation Portal</title>
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
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
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
        <div class="welcome-banner">
            <h1>Welcome, <?php echo $user['Name']; ?>! 👋</h1>
            <p>Citizen Dashboard • <?php echo date('l, F j, Y'); ?></p>
        </div>

        <!-- Dashboard Stats -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <i class="fas fa-tasks"></i>
                <h3>Service Requests</h3>
                <div class="amount positive"><?php echo $total_services; ?></div>
                <p>Total services requested</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Complaints</h3>
                <div class="amount due"><?php echo $total_complaints; ?></div>
                <p>Total complaints filed</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-clock"></i>
                <h3>Pending Complaints</h3>
                <div class="amount warning"><?php echo $pending_complaints; ?></div>
                <p>Awaiting admin review</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-wallet"></i>
                <h3>Account Balance</h3>
                <div class="amount balance">৳<?php echo number_format($user['Balance'], 2); ?></div>
                <p>Available for payments</p>
            </div>
        </div>

        <!-- Complaint Stats -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-chart-pie"></i> Complaint Status
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #ffeb3b, #ffc107); border-radius: 15px; color: #333;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $complaint_stats['Pending']; ?></h3>
                    <p style="font-weight: 600;">Pending</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #4caf50, #2ecc71); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $complaint_stats['Approved']; ?></h3>
                    <p style="font-weight: 600;">Approved</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #2196f3, #3498db); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $complaint_stats['In Progress']; ?></h3>
                    <p style="font-weight: 600;">In Progress</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #27ae60, #2ecc71); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $complaint_stats['Resolved']; ?></h3>
                    <p style="font-weight: 600;">Resolved</p>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 30px; margin-bottom: 40px;">
            <!-- Recent Complaints -->
            <div class="payment-section">
                <h2 style="color: var(--dark); margin-bottom: 20px;">
                    <i class="fas fa-history"></i> Recent Complaints
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($complaint = $recent_complaints->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($complaint['Title']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $complaint['Status'])); ?>">
                                        <?php echo $complaint['Status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d', strtotime($complaint['Created_At'])); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="viewComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Services -->
            <div class="payment-section">
                <h2 style="color: var(--dark); margin-bottom: 20px;">
                    <i class="fas fa-history"></i> Recent Services
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($service = $recent_services->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['Service_Name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($service['Status']); ?>">
                                        <?php echo $service['Status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($service['Payment_Status']); ?>">
                                        <?php echo $service['Payment_Status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d', strtotime($service['Submission_Date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-bolt"></i> Quick Actions
            </h2>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <a href="services.php" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-plus"></i> Request Service
                </a>
                <a href="complaints.php" class="btn btn-warning" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-exclamation-circle"></i> File Complaint
                </a>
                <a href="payments.php" class="btn btn-success" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-credit-card"></i> Make Payment
                </a>
                <a href="profile.php" class="btn btn-info" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-user-edit"></i> Update Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Complaint View Modal -->
    <div id="complaintModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Complaint Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="complaintDetails">
                Loading...
            </div>
        </div>
    </div>

    <script>
        function viewComplaint(complaintId) {
            fetch('get_complaint.php?id=' + complaintId)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const complaint = data.complaint;
                        const date = new Date(complaint.Created_At).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        let html = `
                            <div style="margin-bottom: 25px;">
                                <h4 style="color: #2c3e50; margin-bottom: 10px;">${complaint.Title}</h4>
                                <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                                    <span class="status-badge status-${complaint.Status.toLowerCase().replace(' ', '-')}">
                                        ${complaint.Status}
                                    </span>
                                    <span style="background: #f8f9fa; padding: 5px 15px; border-radius: 20px; font-weight: 500;">
                                        ${complaint.Category}
                                    </span>
                                    <span class="priority-badge priority-${complaint.Priority.toLowerCase()}">
                                        ${complaint.Priority}
                                    </span>
                                </div>
                                <p style="color: #666; margin-bottom: 10px;"><i class="fas fa-calendar"></i> ${date}</p>
                                ${complaint.Location ? `<p style="color: #666; margin-bottom: 10px;"><i class="fas fa-map-marker-alt"></i> ${complaint.Location}</p>` : ''}
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                <h5 style="color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-align-left"></i> Description
                                </h5>
                                <p style="white-space: pre-wrap; line-height: 1.6;">${complaint.Description}</p>
                            </div>
                        `;
                        
                        if(complaint.Admin_Comments) {
                            html += `
                                <div style="background: #e3f2fd; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                    <h5 style="color: #1565c0; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-comment-dots"></i> Admin Response
                                    </h5>
                                    <p style="color: #1565c0; white-space: pre-wrap; line-height: 1.6;">${complaint.Admin_Comments}</p>
                                </div>
                            `;
                        }
                        
                        if(complaint.Resolved_At) {
                            const resolvedDate = new Date(complaint.Resolved_At).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                            html += `
                                <div style="background: #d4edda; padding: 20px; border-radius: 15px;">
                                    <h5 style="color: #155724; margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-check-circle"></i> Complaint Resolved
                                    </h5>
                                    <p style="color: #155724; margin: 0;"><i class="fas fa-calendar-check"></i> Resolved on: ${resolvedDate}</p>
                                </div>
                            `;
                        }
                        
                        document.getElementById('complaintDetails').innerHTML = html;
                        document.getElementById('complaintModal').style.display = 'flex';
                    }
                });
        }
        
        function closeModal() {
            document.getElementById('complaintModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('complaintModal');
            if(event.target == modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>