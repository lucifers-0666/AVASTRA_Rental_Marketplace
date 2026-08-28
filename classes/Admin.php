<?php
/**
 * SpaceShare — Admin Data & Operations Model
 */

require_once __DIR__ . '/Database.php';

class Admin {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get High-Level Dashboard Overview Statistics
     */
    public function getDashboardStats(): array {
        $stats = [
            'total_users'      => 0,
            'total_spaces'     => 0,
            'pending_spaces'   => 0,
            'total_bookings'   => 0,
            'total_revenue'    => 0.00,
            'open_complaints'  => 0,
        ];

        try {
            $stats['total_users']    = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
            $stats['total_spaces']   = (int) $this->db->query("SELECT COUNT(*) FROM spaces")->fetchColumn();
            $stats['pending_spaces'] = (int) $this->db->query("SELECT COUNT(*) FROM spaces WHERE verification_status = 'pending'")->fetchColumn();
            $stats['total_bookings'] = (int) $this->db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            $stats['total_revenue']  = (float) ($this->db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetchColumn() ?? 0.00);
            $stats['open_complaints']= (int) $this->db->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")->fetchColumn();
        } catch (Exception $e) {
            // Error handling fallback
        }

        return $stats;
    }

    /**
     * Get Pending Verification Spaces Queue
     */
    public function getPendingSpaces(int $limit = 10): array {
        $sql = "
            SELECT s.*, u.full_name AS owner_name, u.email AS owner_email, c.name AS category_name
            FROM spaces s
            JOIN users u ON s.owner_id = u.id
            JOIN categories c ON s.category_id = c.id
            WHERE s.verification_status = 'pending'
            ORDER BY s.created_at DESC
            LIMIT :limit
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Approve Space Listing
     */
    public function approveSpace(int $spaceId, int $adminId): bool {
        $stmt = $this->db->prepare("
            UPDATE spaces 
            SET verification_status = 'approved', rejection_reason = NULL, is_active = 1 
            WHERE id = :id
        ");
        $success = $stmt->execute([':id' => $spaceId]);

        if ($success) {
            $this->logAction($adminId, 'APPROVE_SPACE', 'SPACE', $spaceId, 'Space listing approved by admin.');
        }

        return $success;
    }

    /**
     * Reject Space Listing
     */
    public function rejectSpace(int $spaceId, string $reason, int $adminId): bool {
        $stmt = $this->db->prepare("
            UPDATE spaces 
            SET verification_status = 'rejected', rejection_reason = :reason, is_active = 0 
            WHERE id = :id
        ");
        $success = $stmt->execute([':id' => $spaceId, ':reason' => $reason]);

        if ($success) {
            $this->logAction($adminId, 'REJECT_SPACE', 'SPACE', $spaceId, 'Reason: ' . $reason);
        }

        return $success;
    }

    /**
     * Log Admin Audit Trail Action
     */
    public function logAction(int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
                VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip)
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':details'     => $details,
                ':ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {
            // Silent fail for audit logs
        }
    }
}
