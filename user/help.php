<?php

/**
 * AVASTRA — Help & Support
 * Quick paths to the support users need most often.
 */
$pageTitle = 'Help & Support';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$unreadNotifCount = 0; // used by includes/topbar.php
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content" class="help-page">
        <section class="help-hero" aria-labelledby="help-title">
            <h1 id="help-title">How can we help?</h1>
            <p>Find answers to common questions or reach out to our support team.</p>
        </section>

        <section class="help-options" aria-label="Support options">
            <a class="help-option-card" href="<?= APP_URL; ?>/user/my-bookings.php">
                <span class="help-option-icon" aria-hidden="true"><i class="bi bi-calendar-check"></i></span>
                <span>
                    <strong>Booking help</strong>
                    <span class="help-option-copy">How to make, manage, and cancel bookings.</span>
                </span>
                <i class="bi bi-arrow-right help-option-arrow" aria-hidden="true"></i>
            </a>

            <a class="help-option-card" href="<?= APP_URL; ?>/user/list-space.php">
                <span class="help-option-icon" aria-hidden="true"><i class="bi bi-building-add"></i></span>
                <span>
                    <strong>Listing help</strong>
                    <span class="help-option-copy">How to list your space and manage availability.</span>
                </span>
                <i class="bi bi-arrow-right help-option-arrow" aria-hidden="true"></i>
            </a>

            <a class="help-option-card" href="mailto:support@avastra.in">
                <span class="help-option-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                <span>
                    <strong>Contact support</strong>
                    <span class="help-option-copy">Reach our team at support@avastra.in</span>
                </span>
                <i class="bi bi-arrow-right help-option-arrow" aria-hidden="true"></i>
            </a>
        </section>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>