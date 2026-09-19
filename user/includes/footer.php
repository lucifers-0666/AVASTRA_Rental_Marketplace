<?php

/**
 * AVASTRA User App — Footer Include Component
 */
?>

<footer class="user-footer">
    <div class="user-footer-inner">
        <img
            src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg"
            alt="AVASTRA Logo">

        <span>
            &copy; <?= date('Y'); ?>
            <strong>AVASTRA</strong>.
            All rights reserved.
        </span>
    </div>
</footer>

</div><!-- /#user-main -->
</div><!-- /#user-app -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('user-sidebar');

        if (!sidebar) return;

        let scrollTimer;

        sidebar.addEventListener('scroll', function() {
            // Show scrollbar
            sidebar.classList.add('is-scrolling');

            // Hide scrollbar after scrolling stops
            clearTimeout(scrollTimer);

            scrollTimer = setTimeout(function() {
                sidebar.classList.remove('is-scrolling');
            }, 700);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {

        const messageBody = document.querySelector('.msg-body');

        if (!messageBody) return;

        let scrollTimer;

        messageBody.addEventListener('scroll', function() {

            // Show scrollbar while scrolling
            messageBody.classList.add('is-scrolling');

            // Reset timer
            clearTimeout(scrollTimer);

            // Hide scrollbar after scrolling stops
            scrollTimer = setTimeout(function() {
                messageBody.classList.remove('is-scrolling');
            }, 700);

        });

    });
</script>
</body>

</html>