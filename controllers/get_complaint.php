<?php
require_once '../config/config.php';

if(!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

if(isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM Complaints WHERE Complaint_ID = ? AND User_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $complaint_id, $user_id);
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