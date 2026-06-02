<?php
/*
 * feedback.php — Student Feedback Page
 *
 * FIX: replies_seen_at is now loaded from the DATABASE (users table),
 * not from $_SESSION. This means "read" state persists across logout/login.
 * Only messages sent AFTER the user's replies_seen_at are counted as unread.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

$basePath    = '../';
$currentPage = 'feedback';

// ── LOAD CATEGORIES FOR CHECKBOX LIST ────────────────────────
// Categories come from DB so they match what admin manages
$feedbackCategories = [];
try {
    $categoryStmt = $pdo->query(
        'SELECT category_id, category_name FROM categories ORDER BY category_name ASC'
    );
    $feedbackCategories = $categoryStmt->fetchAll();
} catch (PDOException $e) {
    $feedbackCategories = [];
}

// ── HANDLE: User hiding a reply thread ───────────────────────
// Stores hidden thread IDs in session — does NOT delete from DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_resolve_feedback_id'])) {
    $fid = (int)$_POST['user_resolve_feedback_id'];
    $_SESSION['user_resolved_feedback'] = $_SESSION['user_resolved_feedback'] ?? [];
    if (!in_array($fid, $_SESSION['user_resolved_feedback'])) {
        $_SESSION['user_resolved_feedback'][] = $fid;
    }
    header('Location: feedback.php#replies-panel');
    exit;
}

// ── LOAD replies_seen_at FROM DATABASE ────────────────────────
// KEY FIX: Read from the DB, not $_SESSION.
// Session disappears on logout; DB value persists permanently.
// This is how we know which replies the user has already seen.
$repliesSeenAt = null;
try {
    $seenStmt = $pdo->prepare(
        "SELECT replies_seen_at FROM users WHERE user_id = :uid"
    );
    $seenStmt->execute([':uid' => current_user_id()]);
    $seenRow = $seenStmt->fetch();
    if ($seenRow && !empty($seenRow['replies_seen_at'])) {
        $repliesSeenAt = $seenRow['replies_seen_at'];
        // Also sync session so navbar dot works on this request
        $_SESSION['replies_seen_at'] = $repliesSeenAt;
    }
} catch (PDOException $e) {
    // replies_seen_at column may not exist yet (auto-created on first mark-seen)
    // Fall back to null = treat all replies as unread
    $repliesSeenAt = null;
}

// ── LOAD THIS USER'S FEEDBACK + MESSAGE HISTORY ───────────────
$myFeedback     = [];
$feedbackMsgs   = [];
$hasUnreadReply = false;
$userResolved   = $_SESSION['user_resolved_feedback'] ?? [];

try {
    $fbStmt = $pdo->prepare(
        "SELECT * FROM feedback WHERE user_id = :uid ORDER BY submitted_at DESC"
    );
    $fbStmt->execute([':uid' => current_user_id()]);
    $myFeedback = $fbStmt->fetchAll();

    foreach ($myFeedback as $fb) {
        try {
            $msgStmt = $pdo->prepare(
                "SELECT * FROM feedback_messages WHERE feedback_id = :id ORDER BY sent_at ASC"
            );
            $msgStmt->execute([':id' => $fb['feedback_id']]);
            $msgs = $msgStmt->fetchAll();
            $feedbackMsgs[$fb['feedback_id']] = $msgs;

            // Unread = has messages AND thread not hidden AND
            // latest message is newer than replies_seen_at
            if (!empty($msgs) && !in_array($fb['feedback_id'], $userResolved)) {
                if ($repliesSeenAt === null) {
                    // Never seen anything — all are unread
                    $hasUnreadReply = true;
                } else {
                    $latestMsg = end($msgs);
                    if ($latestMsg['sent_at'] > $repliesSeenAt) {
                        $hasUnreadReply = true;
                    }
                }
            }
        } catch (PDOException $e2) {
            // feedback_messages table may not exist — fall back
            if (!empty($fb['admin_reply']) && !in_array($fb['feedback_id'], $userResolved)) {
                $hasUnreadReply = true;
            }
        }
    }
} catch (PDOException $e) {
    $myFeedback = [];
}

// ── COUNT UNREAD REPLY THREADS FOR TAB BADGE ─────────────────
// Only threads with messages newer than replies_seen_at are counted
$activeReplies = 0;
foreach ($myFeedback as $fb) {
    $msgs       = $feedbackMsgs[$fb['feedback_id']] ?? [];
    $isResolved = in_array($fb['feedback_id'], $userResolved);
    if ($isResolved) continue;

    if (!empty($msgs)) {
        if ($repliesSeenAt === null) {
            // Never opened replies — count everything
            $activeReplies++;
        } else {
            // Only count if latest message is newer than last seen
            $latestMsg = end($msgs);
            if ($latestMsg['sent_at'] > $repliesSeenAt) {
                $activeReplies++;
            }
        }
    }
    // NOTE: No fallback for admin_reply field — that field has no timestamp
    // so we cannot know if it's been seen. Only feedback_messages are counted.
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Feedback – SkillHub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../global/main.css" />
  <link rel="stylesheet" href="../global/print.css" media="print" />
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <!-- PAGE HERO -->
  <div class="page-hero">
    <div class="container">
      <div class="badge"><i class="fa-solid fa-comments"></i> We Value Your Input</div>
      <h1>Share Your Feedback</h1>
      <p>Help us improve SkillHub by sharing your experience, ratings, and suggestions.</p>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <section class="section" style="padding-top: 40px;">
    <div class="feedback-page-wrapper">
      <div class="feedback-main">

        <!-- TAB SWITCHER -->
        <div class="feedback-tabs">
          <button class="feedback-tab active" id="tab-form" onclick="switchTab('form', this)">
            <i class="fa-solid fa-pen-to-square"></i> Submit Feedback
          </button>
          <button class="feedback-tab" id="tab-replies" onclick="switchTab('replies', this)">
            <i class="fa-solid fa-inbox"></i> My Replies
            <?php if ($activeReplies > 0): ?>
              <!-- Badge shows count of unread reply threads -->
              <span class="feedback-tab-badge"><?= $activeReplies ?></span>
            <?php endif; ?>
          </button>
        </div>

        <!-- PANEL: Submit Feedback Form -->
        <div class="feedback-panel active" id="panel-form">

          <?php if (is_admin()): ?>
          <!-- Admin sees this notice — form is visible but greyed out -->
          <div style="text-align:center; padding:32px 24px 28px; background:#eff6ff; border-radius:16px; margin-bottom:24px;">
            <i class="fa-solid fa-shield-halved" style="font-size:2.5rem; color:#2563eb; margin-bottom:12px; display:block;"></i>
            <h3 style="color:#0f172a; margin:0 0 8px; font-size:1.25rem;">Viewing as Admin</h3>
            <p style="color:#64748b; margin:0; line-height:1.7; font-size:0.93rem;">
              You are browsing the live site as an administrator.<br>
              <strong>Feedback submission is for students only.</strong>
            </p>
          </div>
          <!-- Greyed-out non-interactive form for admin preview -->
          <div style="opacity:0.55; pointer-events:none; user-select:none;">
          <?php endif; ?>

          <div>
            <div id="feedback-form-wrap">

              <!-- Success banner shown after successful submission (hidden by default) -->
              <div id="success-message" style="display:none; border:2px solid #86efac; background:#f0fdf4; border-radius:16px; text-align:center; padding:48px 24px; margin-bottom:24px;">
                <div style="width:56px;height:56px;background:#0f172a;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                  <i class="fa-solid fa-check" style="color:#fff;font-size:1.4rem;"></i>
                </div>
                <h3 style="color:#16a34a; margin-bottom:8px;">Thank You!</h3>
                <p style="color:#475569;">Your feedback has been submitted successfully. We appreciate your input!</p>
              </div>

              <div class="card" style="padding: 0;">
                <div class="card-body" style="padding: 40px;">
                  <form id="feedback-form" novalidate>

                    <!-- Form intro -->
                    <div class="feedback-form-intro">
                      <h3>Help us plan better workshops</h3>
                      <p>
                        Share how useful your SkillHub experience was, which workshop topics
                        you want to see more often, and what session times work best for students.
                      </p>
                    </div>

                    <!-- Name and email auto-filled from session, read-only -->
                    <div class="form-row">
                      <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name"
                          value="<?= h($_SESSION['full_name'] ?? '') ?>" readonly />
                        <span id="name-error" class="field-error"></span>
                      </div>
                      <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email"
                          value="<?= h($_SESSION['email'] ?? '') ?>" readonly />
                        <span id="email-error" class="field-error"></span>
                      </div>
                    </div>

                    <p class="feedback-account-note">
                      <i class="fa-solid fa-lock"></i>
                      Your name and email are taken from your account.
                    </p>

                    <!-- Overall experience rating -->
                    <div class="form-group">
                      <label>How useful was your SkillHub experience? <span class="required">*</span></label>
                      <div class="rating-group">
                        <label class="rating-card">
                          <input type="radio" name="rating" value="Good" />
                          <span><i class="fa-solid fa-face-smile"></i> Good</span>
                        </label>
                        <label class="rating-card">
                          <input type="radio" name="rating" value="Average" />
                          <span><i class="fa-solid fa-face-meh"></i> Average</span>
                        </label>
                        <label class="rating-card">
                          <input type="radio" name="rating" value="Poor" />
                          <span><i class="fa-solid fa-face-frown"></i> Poor</span>
                        </label>
                      </div>
                      <span id="rating-error" class="field-error"></span>
                    </div>

                    <!-- Workshop topic checkboxes from DB -->
                    <div class="form-group">
                      <label>Workshop Topics You Want More Of</label>
                      <p class="feedback-field-help">Select all topics you would like SkillHub to offer more often.</p>
                      <div class="checkbox-grid">
                        <?php if (empty($feedbackCategories)): ?>
                          <p class="feedback-field-help">No workshop categories available right now.</p>
                        <?php else: ?>
                          <?php
                            $icons = [
                                1 => 'fa-laptop-code', 2 => 'fa-palette',
                                3 => 'fa-chart-line',  4 => 'fa-shield-halved',
                                5 => 'fa-robot',       6 => 'fa-briefcase',
                            ];
                            foreach ($feedbackCategories as $category):
                          ?>
                            <label class="checkbox-option">
                              <input type="checkbox" name="workshops[]"
                                value="<?= h($category['category_name']) ?>" />
                              <span>
                                <i class="fa-solid <?= h($icons[$category['category_id']] ?? 'fa-book-open') ?>"></i>
                                <?= h($category['category_name']) ?>
                              </span>
                            </label>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Preferred time dropdown -->
                    <div class="form-group">
                      <label for="feedback-preference">Preferred Session Time</label>
                      <select id="preference" name="preference">
                        <option value="">Select preferred time</option>
                        <option value="morning">Morning Sessions (9 AM – 12 PM)</option>
                        <option value="afternoon">Afternoon Sessions (1 PM – 5 PM)</option>
                        <option value="evening">Evening Sessions (6 PM – 9 PM)</option>
                        <option value="no-pref">No Preference</option>
                      </select>
                    </div>

                    <!-- Free text comments -->
                    <div class="form-group">
                      <label for="feedback-comments">Additional Comments</label>
                      <textarea id="comments" name="comments" rows="4"
                        placeholder="Share any additional thoughts or suggestions..."></textarea>
                    </div>

                    <div style="text-align:center; margin-top:8px;">
                      <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                      </button>
                    </div>

                  </form>
                </div>
              </div>
            </div>
          </div>

          <?php if (is_admin()): ?>
          </div><!-- close admin greyed wrapper -->
          <?php endif; ?>

        </div><!-- /#panel-form -->

        <!-- PANEL: My Replies -->
        <!-- padding-top keeps cards from sitting behind the tab bar -->
        <div class="feedback-panel" id="panel-replies" style="padding-top: 24px;">

          <?php
            $shownAny = false;
            foreach ($myFeedback as $fb):
              $msgs       = $feedbackMsgs[$fb['feedback_id']] ?? [];
              $hasMsg     = !empty($msgs) || !empty($fb['admin_reply']);
              $isResolved = in_array($fb['feedback_id'], $userResolved);
              if (!$hasMsg || $isResolved) continue;
              $shownAny = true;
          ?>

          <!-- Reply thread card for one feedback submission -->
          <div class="reply-thread-card" id="user-thread-<?= $fb['feedback_id'] ?>">

            <div class="reply-thread-header">
              <div>
                <strong style="font-size:0.9rem; color:#0f172a;">
                  Feedback from <?= date('M j, Y', strtotime($fb['submitted_at'])) ?>
                </strong>
                <div class="reply-thread-date">
                  Rating: <?= h($fb['rating']) ?>
                  <?php if ($fb['preferred_time']): ?>
                    · Prefers <?= h($fb['preferred_time']) ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- User's original comment -->
            <?php if ($fb['comments']): ?>
            <div class="reply-thread-comment">
              <i class="fa-solid fa-quote-left"></i> <?= h($fb['comments']) ?>
            </div>
            <?php endif; ?>

            <!-- Admin messages stacked in chronological order -->
            <?php if (!empty($msgs)): ?>
              <?php foreach ($msgs as $msg): ?>
              <div class="reply-msg">
                <div class="reply-msg-label">
                  <i class="fa-solid fa-reply"></i>
                  Admin · <?= date('M j, g:i A', strtotime($msg['sent_at'])) ?>
                </div>
                <p><?= h($msg['message']) ?></p>
              </div>
              <?php endforeach; ?>
            <?php elseif (!empty($fb['admin_reply'])): ?>
              <!-- Fallback for threads without message history -->
              <div class="reply-msg">
                <div class="reply-msg-label"><i class="fa-solid fa-reply"></i> Admin Reply</div>
                <p><?= h($fb['admin_reply']) ?></p>
              </div>
            <?php endif; ?>

            <!-- User can hide this thread from their view -->
            <div class="reply-thread-footer">
              <form method="post" style="display:inline;">
                <input type="hidden" name="user_resolve_feedback_id" value="<?= $fb['feedback_id'] ?>" />
                <button type="submit" class="btn-user-resolve">
                  <i class="fa-solid fa-eye-slash"></i> Hide this thread
                </button>
              </form>
            </div>
          </div>

          <?php endforeach; ?>

          <!-- Empty state: shown for admin or when no reply threads exist -->
          <?php if (is_admin() || !$shownAny): ?>
          <div class="replies-empty" style="width:100%; box-sizing:border-box; padding-top:60px; text-align:center;">
            <i class="fa-solid fa-<?= is_admin() ? 'chalkboard-user' : 'inbox' ?>"
               style="font-size:2.5rem; margin-bottom:16px; display:block; opacity:0.4; color:#94a3b8;"></i>
            <?php if (is_admin()): ?>
              <h4 style="color:#0f172a; margin-bottom:10px;">Admin Panel</h4>
              <p style="color:#64748b; line-height:1.7; margin:0 auto;">
                Student reply threads appear here for students after you reply from the Admin Panel.<br><br>
                <a href="Admin/admin.php#feedback" style="color:#2563eb; font-weight:600;">
                  <i class="fa-solid fa-arrow-right"></i> Go to Admin Panel
                </a>
              </p>
            <?php else: ?>
              <h4 style="margin-bottom:10px;">No replies yet</h4>
              <p style="color:#64748b;">When SkillHub replies to your feedback, you'll see it here.</p>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div><!-- /#panel-replies -->

      </div><!-- /.feedback-main -->
    </div><!-- /.feedback-page-wrapper -->
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div id="footer-grid">
        <div id="footer-brand">
          <h3><i class="fa-solid fa-book-open"></i> SkillHub</h3>
          <p>Empowering students to discover and develop new skills through short, focused workshops.</p>
        </div>
        <div id="footer-links">
          <p class="footer-heading">Quick Links</p>
          <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="schedule.php">Schedule</a></li>
            <li><a href="video.php">Guide</a></li>
            <li><a href="feedback.php">Feedback</a></li>
            <li><a href="about.php">About</a></li>
          </ul>
        </div>
        <div id="footer-contact">
          <p class="footer-heading">Contact</p>
          <address>Email: info@skillhub.edu</address>
        </div>
      </div>
    </div>
    <div id="footer-copyright">
      &copy; 2026 SkillHub – Student Workshops Platform. All rights reserved.
    </div>
  </footer>

  <script src="../scripts/main.js"></script>
  <script>
  // ── TAB SWITCHING ─────────────────────────────────────────
  function switchTab(tabName, btn) {
  document.querySelectorAll('.feedback-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.feedback-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + tabName).classList.add('active');
  btn.classList.add('active');

  if (tabName === 'replies') {
    // Remove badge from tab immediately
    const badge = btn.querySelector('.feedback-tab-badge');
    if (badge) badge.remove();

    // Remove navbar dot immediately without waiting for fetch
    const navDot = document.querySelector('nav #main-nav a[href*="feedback"] span[style*="border-radius:50%"]');
    if (navDot) navDot.remove();

    // Also remove any inline dot spans inside the feedback nav link
    document.querySelectorAll('#main-nav a').forEach(a => {
      if (a.href && a.href.includes('feedback')) {
        a.querySelectorAll('span').forEach(s => {
          if (s.style && s.style.background && s.style.background.includes('ef4444')) {
            s.remove();
          }
        });
      }
    });

    // Save seen timestamp to DB — persists across logout/login
    fetch('../api/mark_replies_seen.php', { method: 'POST' });
  }
}

  // Auto-switch to replies tab if URL hash is #replies-panel
  if (window.location.hash === '#replies-panel') {
    const repliesBtn = document.getElementById('tab-replies');
    if (repliesBtn) switchTab('replies', repliesBtn);
  }
  </script>
</body>
</html>