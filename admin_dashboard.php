<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM Users WHERE User_Type = 'Citizen'")->fetch_assoc()['count'];
$total_complaints = $conn->query("SELECT COUNT(*) as count FROM Complaints")->fetch_assoc()['count'];
$pending_complaints_count = getPendingComplaintsCount($conn);
$total_services = $conn->query("SELECT COUNT(*) as count FROM Service_Requests")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(Amount) as total FROM Payments WHERE Status = 'Success'")->fetch_assoc()['total'] ?? 0;

// Get pending complaints
$pending_complaints_list = getPendingComplaints($conn);

// Get recent users
$recent_users = $conn->query("SELECT * FROM Users WHERE User_Type = 'Citizen' ORDER BY Created_At DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - City Corporation Portal</title>
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
                <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="admin_complaints.php"><i class="fas fa-exclamation-circle"></i> All Complaints</a>
                <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="admin_services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                
                <!-- Pending Tasks Badge -->
<a href="admin_pending.php" style="position: relative;">
    <i class="fas fa-tasks"></i> Pending Tasks
    <?php if($pending_complaints_count > 0): ?>
        <span class="pending-count <?php echo $pending_complaints_count > 99 ? 'small' : ''; ?>">
            <?php echo $pending_complaints_count > 99 ? '99+' : $pending_complaints_count; ?>
        </span>
    <?php endif; ?>
</a>
                
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
            <h1>Welcome, Admin <?php echo $user['Name']; ?>! 👑</h1>
            <p>Administrator Dashboard • <?php echo date('l, F j, Y'); ?></p>
            <p style="margin-top: 10px; font-size: 16px;">
                <i class="fas fa-shield-alt"></i> Manage complaints, users, and services
            </p>
        </div>

        <!-- Dashboard Stats -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <i class="fas fa-users"></i>
                <h3>Total Users</h3>
                <div class="amount positive"><?php echo $total_users; ?></div>
                <p>Registered citizens</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Total Complaints</h3>
                <div class="amount due"><?php echo $total_complaints; ?></div>
                <p>All complaints received</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-clock"></i>
                <h3>Pending Complaints</h3>
                <div class="amount warning"><?php echo $pending_complaints_count; ?></div>
                <p>Awaiting review</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Total Revenue</h3>
                <div class="amount balance">৳<?php echo number_format($total_revenue, 2); ?></div>
                <p>From all payments</p>
            </div>
        </div>

        <!-- Pending Complaints -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-clock"></i> Pending Complaints for Review
            </h2>
            
            <?php if($pending_complaints_list->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>User</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($complaint = $pending_complaints_list->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($complaint['Complaint_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($complaint['Title']); ?></td>
                            <td><?php echo htmlspecialchars($complaint['User_Name']); ?></td>
                            <td><?php echo $complaint['Category']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($complaint['Created_At'])); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($complaint['Priority']); ?>">
                                    <?php echo $complaint['Priority']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn btn-success btn-sm" onclick="approveComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="rejectComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                    <i class="fas fa-times"></i> Reject
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
                    <h3 style="color: #27ae60;">No Pending Complaints</h3>
                    <p style="color: #666;">All complaints have been reviewed!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Users -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-user-plus"></i> Recently Registered Users
            </h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Balance</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user_row = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user_row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($user_row['Email']); ?></td>
                            <td><?php echo $user_row['Phone'] ? htmlspecialchars($user_row['Phone']) : 'N/A'; ?></td>
                            <td>৳<?php echo number_format($user_row['Balance'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user_row['Created_At'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
            fetch('get_complaint_admin.php?id=' + complaintId)
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
                                <p style="color: #666; margin-bottom: 10px;"><i class="fas fa-user"></i> Submitted by: ${complaint.User_Name}</p>
                                <p style="color: #666; margin-bottom: 10px;"><i class="fas fa-envelope"></i> Email: ${complaint.Email}</p>
                                <p style="color: #666; margin-bottom: 10px;"><i class="fas fa-calendar"></i> ${date}</p>
                                ${complaint.Location ? `<p style="color: #666; margin-bottom: 10px;"><i class="fas fa-map-marker-alt"></i> ${complaint.Location}</p>` : ''}
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                <h5 style="color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-align-left"></i> Description
                                </h5>
                                <p style="white-space: pre-wrap; line-height: 1.6;">${complaint.Description}</p>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button class="btn btn-success" onclick="approveComplaint(${complaint.Complaint_ID})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-danger" onclick="rejectComplaint(${complaint.Complaint_ID})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                <button class="btn btn-secondary" onclick="closeModal()">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        `;
                        
                        document.getElementById('complaintDetails').innerHTML = html;
                        document.getElementById('complaintModal').style.display = 'flex';
                    }
                });
        }
        
        function closeModal() {
            document.getElementById('complaintModal').style.display = 'none';
        }
        
        function approveComplaint(complaintId) {
            if(confirm('Are you sure you want to approve this complaint?')) {
                fetch('admin_approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=approve&id=' + complaintId
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Complaint approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function rejectComplaint(complaintId) {
            const comments = prompt('Please enter reason for rejection:');
            if(comments !== null) {
                fetch('admin_approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=reject&id=' + complaintId + '&comments=' + encodeURIComponent(comments)
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Complaint rejected successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
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