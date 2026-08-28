<?php
$pageTitle = 'Platform Settings';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$adminModel = new Admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $platformFee = (float) ($_POST['platform_fee_percent'] ?? 5.00);
    $depositPercent = (float) ($_POST['deposit_percent'] ?? 10.00);
    $minPayout = (float) ($_POST['min_payout_amount'] ?? 500.00);
    $contactEmail = trim($_POST['contact_email'] ?? 'support@spaceshare.com');

    try {
        $stmt = $db->prepare("
            UPDATE commission_settings 
            SET platform_fee_percent = :fee, deposit_percent = :deposit, min_payout_amount = :payout, contact_email = :email 
            WHERE id = 1
        ");
        $stmt->execute([':fee' => $platformFee, ':deposit' => $depositPercent, ':payout' => $minPayout, ':email' => $contactEmail]);
        $adminModel->logAction($currentUser['id'], 'UPDATE_SETTINGS', 'SYSTEM', 1, "Platform fee set to {$platformFee}%");
        $message = "Platform settings updated successfully!";
    } catch (Exception $e) {
        $error = "Failed to update settings: " . $e->getMessage();
    }
}

$settings = $db->query("SELECT * FROM commission_settings WHERE id = 1")->fetch();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Platform & Commission Settings</h3>
                <p class="text-muted small mb-0">Configure marketplace revenue shares, security deposit policies, and contact information.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="admin-card">
                    <form method="POST" action="">
                        <input type="hidden" name="save_settings" value="1">

                        <h5 class="fw-bold mb-3">Revenue & Fees</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Platform Fee (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="platform_fee_percent" class="form-control" value="<?= htmlspecialchars($settings['platform_fee_percent'] ?? 5.00); ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Fee added to booking price for marketplace maintenance.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Default Security Deposit (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="deposit_percent" class="form-control" value="<?= htmlspecialchars($settings['deposit_percent'] ?? 10.00); ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Refundable deposit percentage held during active rentals.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="fw-bold mb-3">Payouts & Support</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Minimum Owner Payout (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="100" name="min_payout_amount" class="form-control" value="<?= htmlspecialchars($settings['min_payout_amount'] ?? 500.00); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Support Contact Email</label>
                                <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? 'support@spaceshare.com'); ?>" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Save Platform Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
