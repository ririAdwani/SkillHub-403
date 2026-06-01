<?php
// navbar.php renders the SkillHub navigation bar based on login state.
// Guest users see public pages + Login/Register.
// Regular users see public pages + Feedback/Profile/Logout.
// Admin users see public pages + Feedback/Admin/Profile/Logout.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect whether this page is inside the /pages folder.
// Pages inside /pages need ../ before root files.
$basePath = $basePath ?? '';
$currentPage = $currentPage ?? '';

$isLoggedIn = function_exists('is_logged_in') && is_logged_in();
$isAdminUser = function_exists('is_admin') && is_admin();

// Check if admin has replied to this user's feedback (to show notification dot)
$hasAdminReply = false;
if ($isLoggedIn && !$isAdminUser && function_exists('current_user_id')) {
    try {
        global $pdo;
        if ($pdo) {
            $replyCheck = $pdo->prepare(
                "SELECT COUNT(*) FROM feedback WHERE user_id = :uid AND admin_reply IS NOT NULL AND admin_reply != ''"
            );
            $replyCheck->execute([':uid' => current_user_id()]);
            $hasAdminReply = $replyCheck->fetchColumn() > 0;
        }
    } catch (Exception $e) {
        $hasAdminReply = false;
    }
}
?>

<header>
    <div class="container header-container">
      <div id="header-inner">
      <!-- Site logo -->
      <a href="<?= $basePath ?>index.php" id="site-logo">
        <div id="logo-icon"><i class="fa-solid fa-book-open"></i></div>
        <span id="site-name">Skill<span>Hub</span></span>
      </a>

      <!-- Primary navigation stays centered and contains page-level links only. -->
      <nav id="main-nav" aria-label="Main navigation">
        <ul>
          <li>
            <a href="<?= $basePath ?>index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">
              <i class="fa-solid fa-house"></i> Home
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/services.php" class="<?= $currentPage === 'services' ? 'active' : '' ?>">
              <i class="fa-solid fa-swatchbook"></i> Services
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/schedule.php" class="<?= $currentPage === 'schedule' ? 'active' : '' ?>">
              <i class="fa-solid fa-calendar-days"></i> Schedule
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/video.php" class="<?= $currentPage === 'guide' ? 'active' : '' ?>">
              <i class="fa-solid fa-clapperboard"></i> Guide
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/about.php" class="<?= $currentPage === 'about' ? 'active' : '' ?>">
              <i class="fa-solid fa-circle-info"></i> About
            </a>
          </li>

          <?php if ($isLoggedIn): ?>
            <li>
              <a href="<?= $basePath ?>pages/feedback.php" class="<?= $currentPage === 'feedback' ? 'active' : '' ?>" style="position:relative;">
                <i class="fa-solid fa-comments"></i> Feedback
                <?php if ($hasAdminReply): ?>
                  <!-- Red dot: admin has replied to user's feedback -->
                  <span style="
                    position:absolute; top:-4px; right:-8px;
                    width:8px; height:8px; border-radius:50%;
                    background:#ef4444; display:inline-block;
                    box-shadow:0 0 0 2px #fff;
                  "></span>
                <?php endif; ?>
              </a>
            </li>

            <?php if ($isAdminUser): ?>
              <li>
                <a href="<?= $basePath ?>pages/Admin/admin.php" class="<?= $currentPage === 'admin' ? 'active' : '' ?>">
                  <i class="fa-solid fa-user-shield"></i> Admin
                </a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
      </nav>

        <!-- Account actions stay separated from the main navigation. -->
        <div id="account-actions" aria-label="Account actions">
        <?php if ($isLoggedIn): ?>
            <a
            href="<?= $basePath ?>pages/profile.php"
            class="account-btn profile-btn <?= $currentPage === 'profile' ? 'active' : '' ?>"
            aria-label="Profile"
            >
            <i class="fa-solid fa-user"></i>
            <span class="sr-only">Profile</span>
            </a>

            <a
            href="<?= $basePath ?>pages/logout.php"
            class="account-btn logout-btn"
            aria-label="Logout"
            >
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="sr-only">Logout</span>
            </a>
        <?php else: ?>
            <a href="<?= $basePath ?>pages/login.php" class="auth-header-link">
            <i class="fa-solid fa-right-to-bracket"></i>
            <span>Login</span>
            </a>
        <?php endif; ?>
        </div>

      <!-- Mobile menu button -->
      <button id="menu-toggle" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>