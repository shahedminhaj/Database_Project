<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "CSE370_project1";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Get user info
function getUserInfo($conn) {
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM Users WHERE User_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

// Get pending complaints count for admin
function getPendingComplaintsCount($conn) {
    $sql = "SELECT COUNT(*) as count FROM Complaints WHERE Status = 'Pending'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get all pending complaints for admin
function getPendingComplaints($conn) {
    $sql = "SELECT c.*, u.Name as User_Name, u.Email 
            FROM Complaints c 
            JOIN Users u ON c.User_ID = u.User_ID 
            WHERE c.Status = 'Pending' 
            ORDER BY c.Created_At DESC";
    return $conn->query($sql);
}

// Get complaint status count for citizen
function getComplaintStats($conn, $user_id) {
    $sql = "SELECT Status, COUNT(*) as count FROM Complaints WHERE User_ID = ? GROUP BY Status";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = ['Pending' => 0, 'Approved' => 0, 'In Progress' => 0, 'Resolved' => 0, 'Rejected' => 0];
    while($row = $result->fetch_assoc()) {
        $stats[$row['Status']] = $row['count'];
    }
    return $stats;
}

// Update complaint status
function updateComplaintStatus($conn, $complaint_id, $status, $comments = null) {
    $sql = "UPDATE Complaints SET Status = ?, Admin_Comments = ?, Updated_At = NOW()";
    if($status == 'Resolved') {
        $sql .= ", Resolved_At = NOW()";
    }
    $sql .= " WHERE Complaint_ID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $comments, $complaint_id);
    return $stmt->execute();
}

// Get all complaints for admin with filters
function getAllComplaints($conn, $status = 'all', $search = '') {
    $where = "1=1";
    if($status != 'all') {
        $where .= " AND c.Status = '" . $conn->real_escape_string($status) . "'";
    }
    if($search) {
        $where .= " AND (c.Title LIKE '%" . $conn->real_escape_string($search) . "%' 
                      OR u.Name LIKE '%" . $conn->real_escape_string($search) . "%' 
                      OR u.Email LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    $sql = "SELECT c.*, u.Name as User_Name, u.Email 
            FROM Complaints c 
            JOIN Users u ON c.User_ID = u.User_ID 
            WHERE $where 
            ORDER BY c.Created_At DESC";
    return $conn->query($sql);
}

// Get complaint statistics
function getComplaintStatistics($conn) {
    $sql = "SELECT Status, COUNT(*) as count FROM Complaints GROUP BY Status";
    $result = $conn->query($sql);
    
    $stats = ['Pending' => 0, 'Approved' => 0, 'In Progress' => 0, 'Resolved' => 0, 'Rejected' => 0];
    while($row = $result->fetch_assoc()) {
        $stats[$row['Status']] = $row['count'];
    }
    return $stats;
}
?>