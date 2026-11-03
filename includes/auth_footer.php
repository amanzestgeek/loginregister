<?php
/** Shared bottom half of the sign-in / sign-up pages.
 *  Optional: $authAlt = [text, link label, href] */
if (!defined('APP_NAME')) { http_response_code(403); exit; }
?>
    </main>

    <?php if (!empty($authAlt)): ?>
        <p class="auth-alt"><?= e($authAlt[0]) ?> <a href="<?= e($authAlt[2]) ?>"><?= e($authAlt[1]) ?></a></p>
    <?php endif; ?>

    <footer class="auth-footer">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></footer>
</div>
<script src="assets/js/app.js?v=3"></script>
</body>
</html>
