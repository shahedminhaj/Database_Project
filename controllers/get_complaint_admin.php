<?php
require_once '../config/config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

if(isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);
    
    $sql = "SELECT c.*, u.Name as User_Name, u.Email 
            FROM Complaints c 
            JOIN Users u ON c.User_ID = u.User_ID 
            WHERE c.Complaint_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $complaint = $result->fetch_assoc();
        echo json_encode(['success' => true, 'complaint' => $complaint]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No complaint ID provided']);
}
?>