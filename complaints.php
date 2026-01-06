<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = getUserInfo($conn);
$user_id = $_SESSION['user_id'];

// Handle complaint submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $location = trim($_POST['location'] ?? '');
    $priority = $_POST['priority'];
    
    $sql = "INSERT INTO Complaints (User_ID, Title, Description, Category, Location, Priority, Status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $title, $description, $category, $location, $priority);
    
    if($stmt->execute()) {
        $success = "Complaint submitted successfully! Admin will review it soon.";
    } else {
        $error = "Error submitting complaint!";
    }
}

// Get user complaints
$complaints_sql = "SELECT * FROM Complaints WHERE User_ID = $user_id ORDER BY Created_At DESC";
$complaints = $conn->query($complaints_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints - City Corporation Portal</title>
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
                <a href="complaints.php" class="active"><i class="fas fa-exclamation-circle"></i> Complaints</a>
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
        <div class="welcome-banner" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
            <h1>File a Complaint</h1>
            <p>Report issues to city administration</p>
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

        <!-- New Complaint Form -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-plus-circle"></i> Submit New Complaint
            </h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Complaint Title *</label>
                    <input type="text" name="title" class="form-control" required 
                           placeholder="Brief title of your complaint">
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Sanitation">Sanitation & Waste</option>
                        <option value="Water">Water Supply</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Road">Road & Transportation</option>
                        <option value="Noise">Noise Pollution</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Location (Optional)</label>
                    <input type="text" name="location" class="form-control" 
                           placeholder="Enter location where issue occurred">
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" class="form-control" rows="5" required 
                              placeholder="Describe your complaint in detail..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </form>
        </div>

        <!-- Complaint History -->
        <div class="payment-section">
            <h2 style="color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-history"></i> Your Complaint History
            </h2>
            
            <?php if($complaints->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
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
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #95a5a6; margin-bottom: 15px;"></i>
                    <h3 style="color: #95a5a6;">No Complaints Yet</h3>
                    <p style="color: #666;">Submit your first complaint above</p>
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