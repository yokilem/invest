    </main>

    <footer>
        <div class="footer-content">
            <p>&copy; 2024 <?php echo __('site_title'); ?>. <?php echo __('all_rights_reserved'); ?></p>
            <div class="footer-links">
                <a href="#"><?php echo __('privacy_policy'); ?></a>
                <a href="#"><?php echo __('terms_of_service'); ?></a>
                <a href="#"><?php echo __('contact'); ?></a>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <?php if (isset($admin_page) && $admin_page): ?>
    <script src="assets/js/admin.js"></script>
    <?php endif; ?>
</body>
</html>