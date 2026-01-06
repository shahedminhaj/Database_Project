<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);

// Get all complaints with filters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "1=1";
if($status != 'all') {
    $where .= " AND c.Status = '" . $conn->real_escape_string($status) . "'";
}
if($search) {
    $where .= " AND (c.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR u.Email LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$complaints_sql = "SELECT c.*, u.Name as User_Name, u.Email 
                   FROM Complaints c 
                   JOIN Users u ON c.User_ID = u.User_ID 
                   WHERE $where 
                   ORDER BY c.Created_At DESC";
$complaints = $conn->query($complaints_sql);

// Get complaint stats
$stats = getComplaintStatistics($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Admin</title>
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
                <a href="admin_complaints.php" class="active"><i class="fas fa-exclamation-circle"></i> All Complaints</a>
                <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="admin_services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="admin_pending.php"><i class="fas fa-tasks"></i> Pending Tasks</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
            <h1>Manage Complaints</h1>
            <p>Review and manage all citizen complaints</p>
        </div>

        <!-- Complaint Stats -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-chart-bar"></i> Complaint Statistics
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #ffeb3b, #ffc107); border-radius: 15px; color: #333;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $stats['Pending']; ?></h3>
                    <p style="font-weight: 600;">Pending</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #4caf50, #2ecc71); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $stats['Approved']; ?></h3>
                    <p style="font-weight: 600;">Approved</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #2196f3, #3498db); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $stats['In Progress']; ?></h3>
                    <p style="font-weight: 600;">In Progress</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #27ae60, #2ecc71); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $stats['Resolved']; ?></h3>
                    <p style="font-weight: 600;">Resolved</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(45deg, #e74c3c, #c0392b); border-radius: 15px; color: white;">
                    <h3 style="font-size: 32px; margin-bottom: 5px;"><?php echo $stats['Rejected']; ?></h3>
                    <p style="font-weight: 600;">Rejected</p>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-filter"></i> Filter Complaints
            </h2>
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="In Progress" <?php echo $status == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Resolved" <?php echo $status == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="Rejected" <?php echo $status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div style="flex: 2; min-width: 300px;">
                    <input type="text" name="search" class="form-control" placeholder="Search by title, name, or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="admin_complaints.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Complaints Table -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-list"></i> All Complaints
            </h2>
            
            <?php if($complaints->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>User</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($complaint = $complaints->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($complaint['Complaint_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($complaint['Title']); ?></td>
                            <td><?php echo htmlspecialchars($complaint['User_Name']); ?><br>
                                <small><?php echo htmlspecialchars($complaint['Email']); ?></small>
                            </td>
                            <td><?php echo $complaint['Category']; ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($complaint['Priority']); ?>">
                                    <?php echo $complaint['Priority']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $complaint['Status'])); ?>">
                                    <?php echo $complaint['Status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($complaint['Created_At'])); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if($complaint['Status'] == 'Pending'): ?>
                                    <button class="btn btn-success btn-sm" onclick="approveComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php elseif($complaint['Status'] == 'Approved'): ?>
                                    <button class="btn btn-warning btn-sm" onclick="markInProgress(<?php echo $complaint['Complaint_ID']; ?>)">
                                        <i class="fas fa-spinner"></i> In Progress
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="resolveComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                        <i class="fas fa-check-double"></i> Resolve
                                    </button>
                                <?php elseif($complaint['Status'] == 'In Progress'): ?>
                                    <button class="btn btn-success btn-sm" onclick="resolveComplaint(<?php echo $complaint['Complaint_ID']; ?>)">
                                        <i class="fas fa-check-double"></i> Resolve
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-inbox" style="font-size: 64px; color: #95a5a6; margin-bottom: 20px;"></i>
                    <h3 style="color: #95a5a6;">No Complaints Found</h3>
                    <p style="color: #666;">No complaints match your search criteria.</p>
                </div>
            <?php endif; ?>
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
                        `;
                        
                        if(complaint.Admin_Comments) {
                            html += `
                                <div style="background: #e3f2fd; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                    <h5 style="color: #1565c0; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-comment-dots"></i> Admin Comments
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
                                <div style="background: #d4edda; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                    <h5 style="color: #155724; margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-check-circle"></i> Complaint Resolved
                                    </h5>
                                    <p style="color: #155724; margin: 0;"><i class="fas fa-calendar-check"></i> Resolved on: ${resolvedDate}</p>
                                </div>
                            `;
                        }
                        
                        // Add action buttons based on status
                        html += `<div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">`;
                        
                        if(complaint.Status === 'Pending') {
                            html += `
                                <button class="btn btn-success" onclick="approveComplaint(${complaint.Complaint_ID})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-danger" onclick="rejectComplaint(${complaint.Complaint_ID})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            `;
                        } else if(complaint.Status === 'Approved') {
                            html += `
                                <button class="btn btn-warning" onclick="markInProgress(${complaint.Complaint_ID})">
                                    <i class="fas fa-spinner"></i> Mark In Progress
                                </button>
                                <button class="btn btn-success" onclick="resolveComplaint(${complaint.Complaint_ID})">
                                    <i class="fas fa-check-double"></i> Resolve
                                </button>
                            `;
                        } else if(complaint.Status === 'In Progress') {
                            html += `
                                <button class="btn btn-success" onclick="resolveComplaint(${complaint.Complaint_ID})">
                                    <i class="fas fa-check-double"></i> Resolve
                                </button>
                            `;
                        }
                        
                        html += `
                            <button class="btn btn-secondary" onclick="closeModal()">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>`;
                        
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
        
        function markInProgress(complaintId) {
            if(confirm('Mark this complaint as In Progress?')) {
                fetch('admin_approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=in_progress&id=' + complaintId
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Complaint marked as In Progress!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function resolveComplaint(complaintId) {
            const comments = prompt('Please enter resolution comments:');
            if(comments !== null) {
                fetch('admin_approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=resolve&id=' + complaintId + '&comments=' + encodeURIComponent(comments)
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Complaint resolved successfully!');
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