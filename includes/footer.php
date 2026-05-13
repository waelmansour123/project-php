<?php
$is_admin_dir = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$root_prefix = $is_admin_dir ? '../' : '';
?>
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-info">
                <h3>SkillHub</h3>
                <p>Your premium local services reservation partner. Booking verified professionals made easy and reliable.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo $root_prefix; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $root_prefix; ?>services.php">Browse Services</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo $root_prefix; ?>reservations.php">Reservations</a></li>
                        <li><a href="<?php echo $root_prefix; ?>profile.php">Profile</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $root_prefix; ?>login.php">Login</a></li>
                        <li><a href="<?php echo $root_prefix; ?>register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p>📧 support@skillhub.com</p>
                <p>📞 +1 (555) 019-2834</p>
                <p>📍 123 Service Lane, Tech City</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SkillHub. All rights reserved.</p>
        </div>
    </footer>
    <script src="<?php echo $root_prefix; ?>assets/js/script.js"></script>
</body>
</html>
