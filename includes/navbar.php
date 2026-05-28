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
?>

<header>
  <div class="container">
    <div id="header-inner">
      <!-- Site logo -->
      <a href="<?= $basePath ?>index.php" id="site-logo">
        <div id="logo-icon"><i class="fa-solid fa-book-open"></i></div>
        <span id="site-name">Skill<span>Hub</span></span>
      </a>

      <!-- Role-based navigation menu -->
      <nav id="main-nav" aria-label="Main navigation">
        <ul>
          <li>
            <a href="<?= $basePath ?>index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">
              <i class="fa-solid fa-house"></i> Home
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/services.php" class="<?= $currentPage === 'services' ? 'active' : '' ?>">
              <i class="fa-solid fa-box"></i> Services
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
              <a href="<?= $basePath ?>pages/feedback.php" class="<?= $currentPage === 'feedback' ? 'active' : '' ?>">
                <i class="fa-solid fa-comments"></i> Feedback
              </a>
            </li>

            <?php if ($isAdminUser): ?>
              <li>
                <a href="<?= $basePath ?>pages/Admin/admin.php" class="<?= $currentPage === 'admin' ? 'active' : '' ?>">
                  <i class="fa-solid fa-user-shield"></i> Admin
                </a>
              </li>
            <?php endif; ?>

            <li>
              <a href="<?= $basePath ?>pages/profile.php" class="<?= $currentPage === 'profile' ? 'active' : '' ?>">
                <i class="fa-solid fa-user"></i> Profile
              </a>
            </li>

            <li>
              <a href="<?= $basePath ?>pages/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
              </a>
            </li>
          <?php else: ?>
            <li>
              <a href="<?= $basePath ?>pages/login.php" class="nav-auth-link">
                <i class="fa-solid fa-right-to-bracket"></i> Login/Register
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>

      <!-- Mobile menu button -->
      <button id="menu-toggle" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>