<?php
// includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar" id="navbar">
    <div class="navbar__inner">
        <a href="index.php" class="navbar__logo">Star<span>Flow</span></a>

        <div class="navbar__links">
            <a href="gallery.php"    class="<?= $current === 'gallery.php'    ? 'active' : '' ?>">Gallery</a>
            <a href="commission.php" class="<?= $current === 'commission.php' ? 'active' : '' ?>">Commission</a>
            <a href="about.php"      class="<?= $current === 'about.php'      ? 'active' : '' ?>">About</a>
            <a href="faq.php"        class="<?= $current === 'faq.php'        ? 'active' : '' ?>">FAQ</a>
            <a href="contact.php"    class="<?= $current === 'contact.php'    ? 'active' : '' ?>">Contact</a>
        </div>

        <div class="navbar__actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="favorites.php" class="navbar__icon" title="Favorites">♡</a>
                <a href="cart.php"      class="navbar__icon" title="Cart">◻</a>
                <a href="dashboard.php" class="btn btn--primary" style="padding:0.5rem 1.25rem;font-size:0.78rem;">Dashboard</a>
                <a href="logout.php"    class="btn btn--ghost"   style="padding:0.5rem 1.25rem;font-size:0.78rem;">Logout</a>
            <?php else: ?>
                <a href="login.php"    class="btn btn--ghost"   style="padding:0.5rem 1.25rem;font-size:0.78rem;">Login</a>
                <a href="register.php" class="btn btn--primary" style="padding:0.5rem 1.25rem;font-size:0.78rem;">Sign Up</a>
            <?php endif; ?>
        </div>

    </div>
</nav>