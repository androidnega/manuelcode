<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$analyst_id = $_SESSION['analyst_id'];

// Check if it's a POST request with JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$submission_id = $input['submission_id'] ?? null;
$submission_ids = $input['submission_ids'] ?? null;

// Handle bulk delete
if ($submission_ids && is_array($submission_ids) && count($submission_ids) > 0) {
    try {
        $deleted_count = 0;
        $failed_count = 0;
        $deleted_details = [];
        
        foreach ($submission_ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            
            try {
                // Get submission details before deletion
                $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ? AND status = 'paid'");
                $stmt->execute([$id]);
                $submission = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$submission) {
                    $failed_count++;
                    continue;
                }
                
                // Delete the physical file if it exists
                $file_path = '../' . $submission['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete related records first (maintain referential integrity)
                $stmt = $pdo->prepare("DELETE FROM submission_notifications WHERE submission_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $pdo->prepare("DELETE FROM submission_analyst_logs WHERE submission_id = ?");
                $stmt->execute([$id]);
                
                // Delete the main submission record
                $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    $deleted_count++;
                    $deleted_details[] = [
                        'submission_id' => $id,
                        'student_name' => $submission['name'],
                        'index_number' => $submission['index_number']
                    ];
                } else {
                    $failed_count++;
                }
            } catch (Exception $e) {
                error_log("Error deleting submission $id: " . $e->getMessage());
                $failed_count++;
            }
        }
        
        // Log the bulk deletion action
        if ($deleted_count > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, 'bulk_delete_submissions', ?, ?, ?, NOW())
            ");
            
            $details = json_encode([
                'deleted_count' => $deleted_count,
                'failed_count' => $failed_count,
                'submissions' => $deleted_details
            ]);
            
            $stmt->execute([
                $analyst_id, 
                $details, 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT']
            ]);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => "Deleted $deleted_count submission(s)" . ($failed_count > 0 ? ", $failed_count failed" : ""),
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count
        ]);
        
    } catch (Exception $e) {
        error_log("Error in bulk delete: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred during bulk delete'
        ]);
    }
    exit;
}

// Handle single delete
if (!$submission_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Submission ID is required']);
    exit;
}

try {
    // Get submission details before deletion
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ? AND status = 'paid'");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
        exit;
    }
    
    // Delete the physical file if it exists
    $file_path = '../' . $submission['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete related records first (maintain referential integrity)
    
    // Delete SMS notifications
    $stmt = $pdo->prepare("DELETE FROM submission_notifications WHERE submission_id = ?");
    $stmt->execute([$submission_id]);
    
    // Delete analyst logs for this submission
    $stmt = $pdo->prepare("DELETE FROM submission_analyst_logs WHERE submission_id = ?");
    $stmt->execute([$submission_id]);
    
    // Delete the main submission record
    $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = ?");
    $stmt->execute([$submission_id]);
    
    if ($stmt->rowCount() > 0) {
        // Log the deletion action
        $stmt = $pdo->prepare("
            INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, 'delete_submission', ?, ?, ?, NOW())
        ");
        
        $details = json_encode([
            'submission_id' => $submission_id,
            'student_name' => $submission['name'],
            'index_number' => $submission['index_number'],
            'file_name' => $submission['file_name'],
            'reference' => $submission['reference']
        ]);
        
        $stmt->execute([
            $analyst_id, 
            $details, 
            $_SERVER['REMOTE_ADDR'], 
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Submission deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete submission'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting submission: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
}
?>
