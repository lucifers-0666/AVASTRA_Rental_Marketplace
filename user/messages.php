<?php

/**
 * AVASTRA — Messages & Communication Center
 */

require_once __DIR__ . '/../classes/Auth.php';

Auth::initSession();
Auth::requireLogin();

$currentUser = Auth::getUser();
$db          = Database::getInstance();
$userId      = (int) $currentUser['id'];

// Check migration tables
$tablesExist = $db->query("SHOW TABLES LIKE 'conversations'")->rowCount() > 0
    && $db->query("SHOW TABLES LIKE 'messages'")->rowCount() > 0;

/* -----------------------------------------------------------
   START / FIND A CONVERSATION VIA ?with=<user_id>&space_id=<id>
----------------------------------------------------------- */
if ($tablesExist && isset($_GET['with'])) {
    $withUserId  = (int) $_GET['with'];
    $withSpaceId = isset($_GET['space_id']) ? (int) $_GET['space_id'] : null;

    if ($withUserId === $userId) {
        $_SESSION['flash_error'] = "You cannot message yourself.";
        header("Location: " . APP_URL . "/user/messages.php");
        exit;
    }

    $find = $db->prepare("
        SELECT id FROM conversations
        WHERE (user_one_id = :me AND user_two_id = :them) OR (user_one_id = :them2 AND user_two_id = :me2)
        LIMIT 1
    ");
    $find->execute([':me' => $userId, ':them' => $withUserId, ':them2' => $withUserId, ':me2' => $userId]);
    $existing = $find->fetch();

    if ($existing) {
        $conversationId = (int) $existing['id'];
    } else {
        $create = $db->prepare("INSERT INTO conversations (space_id, user_one_id, user_two_id, last_message_at) VALUES (:space_id, :me, :them, NOW())");
        $create->execute([':space_id' => $withSpaceId, ':me' => $userId, ':them' => $withUserId]);
        $conversationId = (int) $db->lastInsertId();
    }

    header("Location: " . APP_URL . "/user/messages.php?conversation_id={$conversationId}");
    exit;
}

/* -----------------------------------------------------------
   SEND A MESSAGE
----------------------------------------------------------- */
if ($tablesExist && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conversation_id'], $_POST['body'])) {
    $conversationId = (int) $_POST['conversation_id'];
    $body           = trim($_POST['body']);

    // Confirm this conversation actually involves the logged-in user
    $own = $db->prepare("SELECT id FROM conversations WHERE id = :id AND (user_one_id = :uid OR user_two_id = :uid2)");
    $own->execute([':id' => $conversationId, ':uid' => $userId, ':uid2' => $userId]);

    if ($own->fetch() && $body !== '') {
        $insertMsg = $db->prepare("INSERT INTO messages (conversation_id, sender_id, body) VALUES (:cid, :sid, :body)");
        $insertMsg->execute([':cid' => $conversationId, ':sid' => $userId, ':body' => $body]);

        $db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = :id")->execute([':id' => $conversationId]);
    }

    header("Location: " . APP_URL . "/user/messages.php?conversation_id={$conversationId}");
    exit;
}

/* -----------------------------------------------------------
   FETCH CONVERSATIONS & ACTIVE THREAD
----------------------------------------------------------- */
$conversations        = [];
$activeConversationId = 0;
$activeConversation   = null;
$threadMessages       = [];
$contextSpace         = null;

if ($tablesExist) {
    $convStmt = $db->prepare("
        SELECT c.*,
               IF(c.user_one_id = :uid, c.user_two_id, c.user_one_id) AS other_user_id,
               u.full_name AS other_name,
               u.email AS other_email,
               (SELECT body FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_body,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != :uid2 AND is_read = 0) AS unread_count
        FROM conversations c
        JOIN users u ON u.id = IF(c.user_one_id = :uid3, c.user_two_id, c.user_one_id)
        WHERE c.user_one_id = :uid4 OR c.user_two_id = :uid5
        ORDER BY c.last_message_at DESC
    ");
    $convStmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId, ':uid5' => $userId]);
    $conversations = $convStmt->fetchAll();

    $activeConversationId = (int) ($_GET['conversation_id'] ?? ($conversations[0]['id'] ?? 0));

    if ($activeConversationId) {
        foreach ($conversations as $c) {
            if ((int) $c['id'] === $activeConversationId) {
                $activeConversation = $c;
                break;
            }
        }

        if ($activeConversation) {
            // Mark the other person's messages as read
            $db->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = :id AND sender_id != :uid")
                ->execute([':id' => $activeConversationId, ':uid' => $userId]);

            $msgStmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = :id ORDER BY created_at ASC");
            $msgStmt->execute([':id' => $activeConversationId]);
            $threadMessages = $msgStmt->fetchAll();

            if (!empty($activeConversation['space_id'])) {
                $spaceStmt = $db->prepare("SELECT id, title, owner_id, city, daily_rate FROM spaces WHERE id = :id");
                $spaceStmt->execute([':id' => $activeConversation['space_id']]);
                $contextSpace = $spaceStmt->fetch();
            }
        }
    }
}

function initialsOf(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    return count($parts) > 1
        ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1))
        : strtoupper(substr($name, 0, 2));
}

$pageTitle = 'Messages';
$unreadNotifCount = 0;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content" style="padding:24px 28px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 style="font-family:'DM Serif Display',serif; font-size:26px; color:var(--avastra-dark); margin:0;">Messages</h1>
                <p class="text-secondary mb-0" style="font-size:13.5px;">Direct communication with space owners and verified renters.</p>
            </div>
        </div>

        <?php if (!$tablesExist): ?>
            <div class="empty-state p-5 bg-white border rounded-3 shadow-sm text-center">
                <i class="bi bi-chat-left-dots" style="font-size:40px; color:var(--avastra-primary);"></i>
                <h3 class="mt-3">Messages setup required</h3>
                <p class="text-muted">The messaging tables are not yet initialized. Please run <code>db/messaging_migration.sql</code>.</p>
            </div>
        <?php else: ?>
            <div class="msg-layout">

                <!-- Left Column: Conversations List -->
                <div class="msg-conv-list">
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <strong style="font-size:13px; text-transform:uppercase; letter-spacing:0.06em; color:var(--avastra-primary);">All Conversations (<?= count($conversations); ?>)</strong>
                    </div>

                    <?php if (empty($conversations)): ?>
                        <div class="p-4 text-center text-muted" style="font-size:13.5px;">
                            <i class="bi bi-chat-square mb-2 d-block" style="font-size:24px; color:rgba(7,59,54,0.4);"></i>
                            No conversations yet.<br>Message an owner directly from any space listing.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($conversations as $c): ?>
                                <?php
                                $isActive = $activeConversationId === (int) $c['id'];
                                $cInitials = initialsOf($c['other_name']);
                                $unread = (int) $c['unread_count'];
                                ?>
                                <a href="?conversation_id=<?= (int) $c['id']; ?>" class="msg-conv-item <?= $isActive ? 'active' : ''; ?>">
                                    <div class="mc-avatar">
                                        <?= htmlspecialchars($cInitials); ?>
                                        <?php if ($unread > 0): ?>
                                            <span class="mc-unread-dot" title="<?= $unread; ?> new message(s)"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mc-main">
                                        <div class="mc-top">
                                            <span class="mc-name"><?= htmlspecialchars($c['other_name']); ?></span>
                                            <span class="mc-date"><?= date('d M', strtotime($c['last_message_at'])); ?></span>
                                        </div>
                                        <div class="mc-preview <?= $unread > 0 ? 'fw-bold text-dark' : ''; ?>">
                                            <?= htmlspecialchars($c['last_body'] ?? 'Say hello 👋'); ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Active Thread -->
                <div class="msg-thread">
                    <?php if (!$activeConversation): ?>
                        <div class="msg-empty-thread d-flex flex-column align-items-center justify-content-center h-100 text-center p-5 text-muted">
                            <i class="bi bi-chat-dots" style="font-size:48px; color:var(--avastra-primary); opacity:0.6;"></i>
                            <h4 class="mt-3 mb-1" style="color:var(--avastra-dark); font-family:'DM Serif Display',serif;">Select a Conversation</h4>
                            <p class="small text-muted mb-0">Choose a chat from the left sidebar to view messages or reply.</p>
                        </div>
                    <?php else: ?>
                        <!-- Thread Header -->
                        <div class="msg-thread-header">
                            <div class="mc-avatar"><?= htmlspecialchars(initialsOf($activeConversation['other_name'])); ?></div>
                            <div style="flex:1;">
                                <div class="th-name"><?= htmlspecialchars($activeConversation['other_name']); ?></div>
                                <div class="th-role">
                                    <i class="bi bi-person-check text-success"></i> Verified AVASTRA Member
                                </div>
                            </div>
                            <?php if ($contextSpace): ?>
                                <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int) $contextSpace['id']; ?>" class="btn btn-ghost-avastra btn-sm" target="_blank">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($contextSpace['title']); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Space Context Notice Bar -->
                        <?php if ($contextSpace): ?>
                            <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="background:#F0F7F4; font-size:12.5px;">
                                <span class="text-secondary">
                                    <i class="bi bi-geo-alt text-success"></i> Discussing listing: <strong><?= htmlspecialchars($contextSpace['title']); ?></strong> (<?= htmlspecialchars($contextSpace['city']); ?>) · ₹<?= number_format((float)$contextSpace['daily_rate'], 0); ?>/day
                                </span>
                                <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int) $contextSpace['id']; ?>" class="text-success fw-bold text-decoration-none">
                                    View Details &rarr;
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Messages Scroll Body -->
                        <div class="msg-body" id="msgBody">
                            <?php if (empty($threadMessages)): ?>
                                <div class="text-center text-muted my-auto py-5">
                                    <i class="bi bi-send mb-2 d-block" style="font-size:28px;"></i>
                                    Send your first message to start the conversation.
                                </div>
                            <?php else: ?>
                                <?php foreach ($threadMessages as $m): ?>
                                    <?php $isMine = (int) $m['sender_id'] === $userId; ?>
                                    <div class="msg-bubble-row <?= $isMine ? 'mine' : 'theirs'; ?>">
                                        <div class="msg-bubble"><?= nl2br(htmlspecialchars($m['body'])); ?></div>
                                        <div class="msg-time"><?= date('j M, g:i A', strtotime($m['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Send Message Form -->
                        <form method="POST" action="" class="msg-input-row" id="msgForm">
                            <input type="hidden" name="conversation_id" value="<?= (int) $activeConversation['id']; ?>">
                            <input type="text" name="body" placeholder="Write a message to <?= htmlspecialchars($activeConversation['other_name']); ?>…" autocomplete="off" required autofocus>
                            <button type="submit" title="Send message"><i class="bi bi-send-fill"></i></button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const msgBody = document.getElementById('msgBody');
        if (msgBody) {
            msgBody.scrollTop = msgBody.scrollHeight;
        }
    });
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>