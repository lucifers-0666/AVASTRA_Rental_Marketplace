<?php

/**
 * AVASTRA — Messages
 *
 * IMPORTANT: this page needs `conversations` and `messages` tables that
 * don't exist in db/schema.sql yet. Run db/messaging_migration.sql first
 * (phpMyAdmin → SQL tab), or this page will show a setup notice instead
 * of crashing.
 */
$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

// Check the migration has been run before touching the new tables.
$tablesExist = $db->query("SHOW TABLES LIKE 'conversations'")->rowCount() > 0
    && $db->query("SHOW TABLES LIKE 'messages'")->rowCount() > 0;

if (!$tablesExist) {
    $unreadNotifCount = 0;
?>
    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <div id="user-content">
            <div class="empty-state">
                <i class="bi bi-tools"></i>
                <h3>Messages isn't set up yet</h3>
                <p>Run <code>db/messaging_migration.sql</code> in phpMyAdmin to add the <code>conversations</code> and <code>messages</code> tables, then refresh this page.</p>
            </div>
        </div><!-- /#user-content -->
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/* -----------------------------------------------------------
   START / FIND A CONVERSATION VIA ?with=<user_id>&space_id=<id>
   (used by a future "Message owner" link on space-details.php)
----------------------------------------------------------- */
if (isset($_GET['with'])) {
    $withUserId = (int) $_GET['with'];
    $withSpaceId = isset($_GET['space_id']) ? (int) $_GET['space_id'] : null;

    if ($withUserId === $userId) {
        $_SESSION['flash_error'] = "You can't message yourself.";
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
        $create = $db->prepare("INSERT INTO conversations (space_id, user_one_id, user_two_id) VALUES (:space_id, :me, :them)");
        $create->execute([':space_id' => $withSpaceId, ':me' => $userId, ':them' => $withUserId]);
        $conversationId = (int) $db->lastInsertId();
    }

    header("Location: " . APP_URL . "/user/messages.php?conversation_id={$conversationId}");
    exit;
}

/* -----------------------------------------------------------
   SEND A MESSAGE
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conversation_id'], $_POST['body'])) {
    $conversationId = (int) $_POST['conversation_id'];
    $body            = trim($_POST['body']);

    // Confirm this conversation actually involves the logged-in user.
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
   CONVERSATION LIST (left column)
----------------------------------------------------------- */
$convStmt = $db->prepare("
    SELECT c.*,
           IF(c.user_one_id = :uid, c.user_two_id, c.user_one_id) AS other_user_id,
           u.full_name AS other_name,
           (SELECT body FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_body,
           (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != :uid2 AND is_read = 0) AS unread_count
    FROM conversations c
    JOIN users u ON u.id = IF(c.user_one_id = :uid3, c.user_two_id, c.user_one_id)
    WHERE c.user_one_id = :uid4 OR c.user_two_id = :uid5
    ORDER BY c.last_message_at DESC
");
$convStmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId, ':uid5' => $userId]);
$conversations = $convStmt->fetchAll();

/* -----------------------------------------------------------
   SELECTED CONVERSATION (right column)
----------------------------------------------------------- */
$activeConversationId = (int) ($_GET['conversation_id'] ?? ($conversations[0]['id'] ?? 0));
$activeConversation   = null;
$threadMessages       = [];
$contextSpace         = null;

if ($activeConversationId) {
    foreach ($conversations as $c) {
        if ((int) $c['id'] === $activeConversationId) {
            $activeConversation = $c;
            break;
        }
    }

    if ($activeConversation) {
        // Mark the other person's messages as read now that we're viewing this thread.
        $db->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = :id AND sender_id != :uid")
            ->execute([':id' => $activeConversationId, ':uid' => $userId]);

        $msgStmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = :id ORDER BY created_at ASC");
        $msgStmt->execute([':id' => $activeConversationId]);
        $threadMessages = $msgStmt->fetchAll();

        if ($activeConversation['space_id']) {
            $spaceStmt = $db->prepare("SELECT id, title, owner_id FROM spaces WHERE id = :id");
            $spaceStmt->execute([':id' => $activeConversation['space_id']]);
            $contextSpace = $spaceStmt->fetch();
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

$unreadNotifCount = 0; // used by topbar.php
    ?>

    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>

        <div id="user-content">
            <div class="greeting-row" style="margin-bottom:20px;">
                <div>
                    <h1>Messages</h1>
                </div>
            </div>

            <div class="msg-layout">

                <!-- Conversation list -->
                <div class="msg-conv-list">
                    <h2>Conversations</h2>
                    <?php if (empty($conversations)): ?>
                        <div style="padding:20px 18px;font-size:13.5px;color:rgba(23,32,27,0.5);">
                            No conversations yet. Message an owner from a space's details page to start one.
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c): ?>
                            <a href="?conversation_id=<?= (int) $c['id']; ?>" class="msg-conv-item <?= $activeConversationId === (int) $c['id'] ? 'active' : ''; ?>">
                                <div class="mc-avatar">
                                    <?= htmlspecialchars(initialsOf($c['other_name'])); ?>
                                    <?php if ((int) $c['unread_count'] > 0): ?><span class="mc-unread-dot"></span><?php endif; ?>
                                </div>
                                <div class="mc-main">
                                    <div class="mc-top">
                                        <span class="mc-name"><?= htmlspecialchars($c['other_name']); ?></span>
                                        <span class="mc-date"><?= date('d M', strtotime($c['last_message_at'])); ?></span>
                                    </div>
                                    <div class="mc-preview"><?= htmlspecialchars($c['last_body'] ?? 'Say hello 👋'); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Thread -->
                <div class="msg-thread">
                    <?php if (!$activeConversation): ?>
                        <div class="msg-empty-thread">
                            <i class="bi bi-chat-dots" style="font-size:34px;"></i>
                            <p>Select a conversation to start chatting.</p>
                        </div>
                    <?php else: ?>
                        <div class="msg-thread-header">
                            <div class="mc-avatar"><?= htmlspecialchars(initialsOf($activeConversation['other_name'])); ?></div>
                            <div>
                                <div class="th-name"><?= htmlspecialchars($activeConversation['other_name']); ?></div>
                                <div class="th-role">
                                    <?php if ($contextSpace && (int) $contextSpace['owner_id'] === (int) $activeConversation['other_user_id']): ?>
                                        Space owner
                                    <?php else: ?>
                                        AVASTRA member
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($contextSpace): ?>
                            <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int) $contextSpace['id']; ?>" class="msg-context-card" style="color:inherit;">
                                <i class="bi bi-building"></i>
                                <div>
                                    <div class="cc-title"><?= htmlspecialchars($contextSpace['title']); ?></div>
                                    <div class="cc-sub">View space</div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <div class="msg-body">
                            <?php foreach ($threadMessages as $m): ?>
                                <?php $isMine = (int) $m['sender_id'] === $userId; ?>
                                <div class="msg-bubble-row <?= $isMine ? 'mine' : 'theirs'; ?>">
                                    <div class="msg-bubble"><?= nl2br(htmlspecialchars($m['body'])); ?></div>
                                    <div class="msg-time"><?= date('d M, g:i A', strtotime($m['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" action="" class="msg-input-row">
                            <input type="hidden" name="conversation_id" value="<?= (int) $activeConversation['id']; ?>">
                            <input type="text" name="body" placeholder="Type a message…" autocomplete="off" required>
                            <button type="submit"><i class="bi bi-send-fill"></i></button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div><!-- /#user-content -->

        <?php require_once __DIR__ . '/includes/footer.php'; ?>