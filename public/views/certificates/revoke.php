<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $certificateId = (int)($input['certificate_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Revoked by administrator');
    
    if (!$certificateId) {
        throw new Exception('Invalid certificate ID');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Update certificate status
    $stmt = $db->prepare("
        UPDATE certificates 
        SET status = 'revoked', 
            revoked_at = NOW(),
            revoked_by = ?,
            revocation_reason = ?
        WHERE certificate_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $reason, $certificateId]);
    
    if ($stmt->rowCount() > 0) {
        // Update establishment compliance status
        $stmt = $db->prepare("
            UPDATE establishments e
            JOIN certificates c ON e.establishment_id = c.establishment_id
            SET e.compliance_status = 'non_compliant'
            WHERE c.certificate_id = ?
        ");
        $stmt->execute([$certificateId]);
        
        echo json_encode(['success' => true, 'message' => 'Certificate revoked successfully']);
    } else {
        throw new Exception('Certificate not found or already revoked');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
