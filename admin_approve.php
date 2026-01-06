<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $complaint_id = $_POST['id'];
    $comments = $_POST['comments'] ?? '';
    
    // Get complaint details
    $sql = "SELECT c.*, u.Name as User_Name, u.Email 
            FROM Complaints c 
            JOIN Users u ON c.User_ID = u.User_ID 
            WHERE c.Complaint_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        exit();
    }
    
    $complaint = $result->fetch_assoc();
    
    if($action == 'approve') {
        $update_sql = "UPDATE Complaints SET Status = 'Approved', Admin_Comments = ?, Updated_At = NOW() 
                       WHERE Complaint_ID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $comments, $complaint_id);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Complaint approved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } 
    elseif($action == 'reject') {
        $update_sql = "UPDATE Complaints SET Status = 'Rejected', Admin_Comments = ?, Updated_At = NOW() 
                       WHERE Complaint_ID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $comments, $complaint_id);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Complaint rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
    elseif($action == 'in_progress') {
        $update_sql = "UPDATE Complaints SET Status = 'In Progress', Updated_At = NOW() 
                       WHERE Complaint_ID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $complaint_id);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Complaint marked as In Progress']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
    elseif($action == 'resolve') {
        $update_sql = "UPDATE Complaints SET Status = 'Resolved', Admin_Comments = ?, Resolved_At = NOW(), Updated_At = NOW() 
                       WHERE Complaint_ID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $comments, $complaint_id);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Complaint resolved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}
?>