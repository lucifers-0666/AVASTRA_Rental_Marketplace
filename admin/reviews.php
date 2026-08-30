<?php
$pageTitle = 'Reviews';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['status'])) {
    $reviewId = (int) $_POST['review_id'];
    $isApproved = (int) $_POST['status'];

    if ($adminModel->toggleReviewStatus($reviewId, $isApproved)) {
        $message = "Review #{$reviewId} moderation status updated.";
    }
}

$reviews = $adminModel->getReviewsList();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Customer Reviews Moderation</h3>
                <p class="text-muted small mb-0">Monitor customer reviews, moderate inappropriate content, or flag ratings.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="avastra-card">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-star fs-1 text-secondary mb-2 d-block"></i>
                    No customer reviews submitted yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle">
                        <thead>
                            <tr>
                                <th>Review ID</th>
                                <th>Reviewer</th>
                                <th>Space Title</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $rev): ?>
                                <tr>
                                    <td><strong>#REV-<?= $rev['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($rev['reviewer_name']); ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($rev['space_title']); ?></td>
                                    <td>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star-fill<?= ($i <= $rev['rating']) ? '' : ' opacity-25'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-secondary d-block" style="max-width:280px;"><?= htmlspecialchars($rev['comment'] ?? 'No written comment.'); ?></small>
                                    </td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($rev['created_at'])); ?></small></td>
                                    <td>
                                        <?php if ($rev['is_approved']): ?>
                                            <span class="badge-status approved">Visible</span>
                                        <?php else: ?>
                                            <span class="badge-status rejected">Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="review_id" value="<?= $rev['id']; ?>">
                                            <?php if ($rev['is_approved']): ?>
                                                <input type="hidden" name="status" value="0">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hide Review</button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
