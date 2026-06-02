<?php
/*
 * navbar.php — SkillHub Navigation Bar
 *
 * Renders the top navigation based on login state:
 * - Guest:  public pages + Login
 * - User:   public pages + Feedback (with unread dot) + Profile + Logout
 * - Admin:  public pages + Feedback + Admin + Profile + Logout
 *
 * UNREAD DOT FIX:
 * The red dot on Feedback now reads replies_seen_at from the DATABASE,
 * not from $_SESSION. This means it persists correctly across logout/login.
 * A message is "unread" only if its sent_at > replies_seen_at in the DB.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect base path and current page (set by each page before including navbar)
$basePath    = $basePath    ?? '';
$currentPage = $currentPage ?? '';

$isLoggedIn  = function_exists('is_logged_in') && is_logged_in();
$isAdminUser = function_exists('is_admin')      && is_admin();

// ── UNREAD REPLY DOT ──────────────────────────────────────────
// Show the red dot on the Feedback nav link only if:
//   1. User is logged in (not admin)
//   2. Admin has sent a reply message
//   3. That message was sent AFTER the user's replies_seen_at (from DB)
$hasAdminReply = false;

if ($isLoggedIn && !$isAdminUser && function_exists('current_user_id')) {
    try {
        global $pdo;
        if ($pdo) {

            // ── KEY FIX: Read replies_seen_at from the DATABASE ──
            // Session-based seen_at disappears on logout.
            // DB-based seen_at persists permanently.
            $seenStmt = $pdo->prepare(
                "SELECT replies_seen_at FROM users WHERE user_id = :uid"
            );
            $seenStmt->execute([':uid' => current_user_id()]);
            $seenRow   = $seenStmt->fetch();
            // replies_seen_at is a DATETIME string e.g. "2026-06-02 10:30:00"
            // NULL means the user has never opened My Replies
            $seenAt = $seenRow['replies_seen_at'] ?? null;

            if ($seenAt === null) {
                // User has never seen replies — show dot if ANY messages exist
                $replyCheck = $pdo->prepare(
                    "SELECT COUNT(*) FROM feedback_messages fm
                     JOIN feedback f ON fm.feedback_id = f.feedback_id
                     WHERE f.user_id = :uid"
                );
                $replyCheck->execute([':uid' => current_user_id()]);
            } else {
                // User has seen replies before — show dot only for NEW messages
                // sent AFTER their last seen timestamp (DATETIME comparison)
                $replyCheck = $pdo->prepare(
                    "SELECT COUNT(*) FROM feedback_messages fm
                     JOIN feedback f ON fm.feedback_id = f.feedback_id
                     WHERE f.user_id = :uid
                       AND fm.sent_at > :seen_at"
                );
                $replyCheck->execute([
                    ':uid'     => current_user_id(),
                    ':seen_at' => $seenAt,
                ]);
            }

            $hasAdminReply = $replyCheck->fetchColumn() > 0;
        }
    } catch (Exception $e) {
        // feedback_messages table may not exist yet — fall back to admin_reply field
        try {
            global $pdo;
            if ($pdo) {
                $seenAt = $_SESSION['replies_seen_at'] ?? null;
                if ($seenAt === null) {
                    // Never seen — dot if any admin reply exists
                    $replyCheck = $pdo->prepare(
                        "SELECT COUNT(*) FROM feedback
                         WHERE user_id = :uid
                           AND admin_reply IS NOT NULL
                           AND admin_reply != ''"
                    );
                    $replyCheck->execute([':uid' => current_user_id()]);
                    $hasAdminReply = $replyCheck->fetchColumn() > 0;
                }
                // If seenAt is set, we can't compare without message timestamps — hide dot
            }
        } catch (Exception $e2) {
            $hasAdminReply = false;
        }
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

      <!-- Primary navigation — centered, page-level links only -->
      <nav id="main-nav" aria-label="Main navigation">
        <ul>
          <li>
            <a href="<?= $basePath ?>index.php"
               class="<?= $currentPage === 'home' ? 'active' : '' ?>">
              <i class="fa-solid fa-house"></i> Home
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/services.php"
               class="<?= $currentPage === 'services' ? 'active' : '' ?>">
              <i class="fa-solid fa-swatchbook"></i> Services
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/schedule.php"
               class="<?= $currentPage === 'schedule' ? 'active' : '' ?>">
              <i class="fa-solid fa-calendar-days"></i> Schedule
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/video.php"
               class="<?= $currentPage === 'guide' ? 'active' : '' ?>">
              <i class="fa-solid fa-clapperboard"></i> Guide
            </a>
          </li>

          <li>
            <a href="<?= $basePath ?>pages/about.php"
               class="<?= $currentPage === 'about' ? 'active' : '' ?>">
              <i class="fa-solid fa-circle-info"></i> About
            </a>
          </li>

          <?php if ($isLoggedIn): ?>
            <!-- Feedback link with optional unread dot -->
            <li>
              <a href="<?= $basePath ?>pages/feedback.php"
                 class="<?= $currentPage === 'feedback' ? 'active' : '' ?>"
                 style="position:relative;">
                <i class="fa-solid fa-comments"></i> Feedback
                <?php if ($hasAdminReply): ?>
                  <!-- Red dot: unread admin reply exists -->
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
                <a href="<?= $basePath ?>pages/Admin/admin.php"
                   class="<?= $currentPage === 'admin' ? 'active' : '' ?>">
                  <i class="fa-solid fa-user-shield"></i> Admin
                </a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
      </nav>

      <!-- Account actions (Profile + Logout) — separated from main nav -->
      <div id="account-actions" aria-label="Account actions">
        <?php if ($isLoggedIn): ?>
          <a href="<?= $basePath ?>pages/profile.php"
             class="account-btn profile-btn <?= $currentPage === 'profile' ? 'active' : '' ?>"
             aria-label="Profile">
            <i class="fa-solid fa-user"></i>
            <span class="sr-only">Profile</span>
          </a>

          <a href="<?= $basePath ?>pages/logout.php"
             class="account-btn logout-btn"
             aria-label="Logout">
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

      <!-- Mobile hamburger button -->
      <button id="menu-toggle" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
      </button>

    </div>
  </div>
</header>