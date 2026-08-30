<?php
/**
 * SpaceShare / AVASTRA — Admin Data & Operations Model (Optimized)
 */

require_once __DIR__ . '/Database.php';

class Admin {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get 6 High-Level KPI Cards Metrics (Optimized Single Database Query)
     */
    public function get6KPICards(): array {
        try {
            $sql = "
                SELECT 
                    (SELECT COUNT(*) FROM users WHERE role_id = 2) AS total_users,
                    (SELECT COUNT(DISTINCT owner_id) FROM spaces WHERE is_active = 1) AS active_owners,
                    (SELECT COUNT(*) FROM spaces WHERE verification_status = 'approved' AND is_active = 1) AS active_spaces,
                    (SELECT COUNT(*) FROM bookings) AS total_bookings,
                    COALESCE((SELECT SUM(amount) FROM payments WHERE status = 'completed'), 0.00) AS total_revenue,
                    (SELECT COUNT(*) FROM spaces WHERE verification_status = 'pending') AS pending_verifications
            ";
            $row = $this->db->query($sql)->fetch();
            if ($row) {
                return [
                    'total_users'           => (int) $row['total_users'],
                    'active_owners'         => (int) $row['active_owners'],
                    'active_spaces'         => (int) $row['active_spaces'],
                    'total_bookings'        => (int) $row['total_bookings'],
                    'total_revenue'         => (float) $row['total_revenue'],
                    'pending_verifications' => (int) $row['pending_verifications'],
                ];
            }
        } catch (Exception $e) {
            // Error handling fallback
        }

        return [
            'total_users'           => 0,
            'active_owners'         => 0,
            'active_spaces'         => 0,
            'total_bookings'        => 0,
            'total_revenue'         => 0.00,
            'pending_verifications' => 0,
        ];
    }

    /**
     * Get Only Pending Verification Count (Ultra-Fast for Sidebar Badge)
     */
    public function getPendingVerificationCount(): int {
        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM spaces WHERE verification_status = 'pending'")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Legacy helper method
     */
    public function getDashboardStats(): array {
        return $this->get6KPICards();
    }

    /**
     * Get Needs Your Attention Dashboard Queue Items
     */
    public function getNeedsAttentionQueue(): array {
        $items = [];

        // 1. Spaces awaiting verification
        $pendingSpacesCount = $this->getPendingVerificationCount();
        if ($pendingSpacesCount > 0) {
            $items[] = [
                'icon'        => 'bi-building-check',
                'title'       => "{$pendingSpacesCount} spaces awaiting verification",
                'module'      => 'Verification',
                'priority'    => 'High',
                'action_label'=> 'Review Queue →',
                'action_link' => 'verify-spaces.php'
            ];
        }

        // 2. Open Disputes / Complaints
        $openComplaintsCount = (int) $this->db->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")->fetchColumn();
        if ($openComplaintsCount > 0) {
            $items[] = [
                'icon'        => 'bi-exclamation-octagon',
                'title'       => "{$openComplaintsCount} open customer complaints/disputes",
                'module'      => 'Operations',
                'priority'    => 'High',
                'action_label'=> 'Resolve Disputes →',
                'action_link' => 'complaints.php'
            ];
        }

        // 3. Pending Booking Requests
        $pendingBookingsCount = (int) $this->db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
        if ($pendingBookingsCount > 0) {
            $items[] = [
                'icon'        => 'bi-calendar-event',
                'title'       => "{$pendingBookingsCount} pending booking reservations",
                'module'      => 'Bookings',
                'priority'    => 'Medium',
                'action_label'=> 'View Bookings →',
                'action_link' => 'bookings.php'
            ];
        }

        return $items;
    }

    /**
     * Get Space Categories Distribution
     */
    public function getCategoryAnalytics(): array {
        $sql = "
            SELECT c.name, COUNT(s.id) AS total_spaces
            FROM categories c
            LEFT JOIN spaces s ON c.id = s.category_id
            GROUP BY c.id, c.name
            ORDER BY total_spaces DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get Owners Directory with Aggregated Stats
     */
    public function getOwnersList(): array {
        $sql = "
            SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
                   COUNT(DISTINCT s.id) AS total_spaces,
                   COUNT(DISTINCT b.id) AS total_bookings,
                   COALESCE(SUM(p.amount), 0) AS total_revenue
            FROM users u
            JOIN spaces s ON u.id = s.owner_id
            LEFT JOIN bookings b ON s.id = b.space_id
            LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
            WHERE u.role_id = 2
            GROUP BY u.id
            ORDER BY total_spaces DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get Reviews Moderation List
     */
    public function getReviewsList(): array {
        $sql = "
            SELECT r.*, u.full_name AS reviewer_name, s.title AS space_title, s.id AS space_id
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            JOIN spaces s ON r.space_id = s.id
            ORDER BY r.created_at DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Toggle Review Visibility / Moderation Status
     */
    public function toggleReviewStatus(int $reviewId, int $isApproved): bool {
        $stmt = $this->db->prepare("UPDATE reviews SET is_approved = :status WHERE id = :id");
        return $stmt->execute([':status' => $isApproved, ':id' => $reviewId]);
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
