<!--
/* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

$basePath    = '../';
$currentPage = 'feedback';

// Handle user resolving (hiding) a feedback thread from their view
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_resolve_feedback_id'])) {
    $fid = (int)$_POST['user_resolve_feedback_id'];
    // Store resolved thread IDs in session so they're hidden for this user
    $_SESSION['user_resolved_feedback'] = $_SESSION['user_resolved_feedback'] ?? [];
    if (!in_array($fid, $_SESSION['user_resolved_feedback'])) {
        $_SESSION['user_resolved_feedback'][] = $fid;
    }
    header('Location: feedback.php#replies-panel');
    exit;
}

// Load this user's feedback submissions + full message history
$myFeedback   = [];
$feedbackMsgs = [];
$hasUnreadReply = false;
$userResolved = $_SESSION['user_resolved_feedback'] ?? [];

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
            // Show notification dot if any non-resolved thread has messages
            if (!empty($msgs) && !in_array($fb['feedback_id'], $userResolved)) {
                $hasUnreadReply = true;
            }
        } catch (PDOException $e2) {
            if (!empty($fb['admin_reply']) && !in_array($fb['feedback_id'], $userResolved)) {
                $hasUnreadReply = true;
            }
        }
    }
} catch (PDOException $e) {
    $myFeedback = [];
}

// Count threads that have replies and are not resolved by user
$activeReplies = 0;
foreach ($myFeedback as $fb) {
    $msgs = $feedbackMsgs[$fb['feedback_id']] ?? [];
    $hasMsg = !empty($msgs) || !empty($fb['admin_reply']);
    if ($hasMsg && !in_array($fb['feedback_id'], $userResolved)) {
        $activeReplies++;
    }
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
  <style>
    /* ── FEEDBACK PAGE LAYOUT ── */
    .feedback-page-wrapper {
      display: flex;
      align-items: flex-start;
      gap: 0;
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 20px 60px;
      position: relative;
    }

    /* Main form area */
    .feedback-main {
      flex: 1;
      min-width: 0;
      transition: margin-right 0.3s ease;
    }

    /* ── TAB SWITCHER ── */
    .feedback-tabs {
      display: flex;
      gap: 0;
      border-bottom: 2px solid #e2e8f0;
      margin-bottom: 28px;
    }

    .feedback-tab {
      padding: 12px 24px;
      font-size: 0.92rem;
      font-weight: 600;
      color: #64748b;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
      transition: all 0.15s;
      display: flex;
      align-items: center;
      gap: 8px;
      background: none;
      border-top: none;
      border-left: none;
      border-right: none;
      font-family: inherit;
      position: relative;
    }

    .feedback-tab:hover { color: #2563eb; }

    .feedback-tab.active {
      color: #2563eb;
      border-bottom-color: #2563eb;
    }

    .feedback-tab-badge {
      background: #ef4444;
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 20px;
      min-width: 18px;
      text-align: center;
    }

    /* ── TAB PANELS ── */
    .feedback-panel { display: none; }
    .feedback-panel.active { display: block; }

    /* ── REPLY THREAD CARD ── */
    .reply-thread-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-left: 4px solid #2563eb;
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 16px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }

    .reply-thread-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
      gap: 12px;
    }

    .reply-thread-date {
      font-size: 0.76rem;
      color: #94a3b8;
    }

    .reply-thread-comment {
      background: #f8fafc;
      border-left: 3px solid #cbd5e1;
      border-radius: 0 6px 6px 0;
      padding: 10px 14px;
      font-size: 0.86rem;
      color: #64748b;
      margin-bottom: 14px;
      line-height: 1.6;
    }

    .reply-thread-comment i { color: #cbd5e1; margin-right: 6px; }

    .reply-msg {
      background: #eff6ff;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 8px;
    }

    .reply-msg-label {
      font-size: 0.7rem;
      font-weight: 700;
      color: #2563eb;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 5px;
    }

    .reply-msg p {
      font-size: 0.9rem;
      color: #1e40af;
      margin: 0;
      line-height: 1.6;
    }

    .reply-thread-footer {
      margin-top: 14px;
      display: flex;
      justify-content: flex-end;
    }

    .btn-user-resolve {
      background: none;
      border: 1px solid #e2e8f0;
      color: #94a3b8;
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 0.76rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s;
      font-family: inherit;
    }

    .btn-user-resolve:hover {
      background: #fef2f2;
      border-color: #fca5a5;
      color: #dc2626;
    }

    .replies-empty {
      text-align: center;
      padding: 48px 20px;
      color: #94a3b8;
    }

    .replies-empty i {
      font-size: 2.5rem;
      margin-bottom: 12px;
      display: block;
      opacity: 0.4;
    }

    /* ── RATING CARDS (horizontal pill buttons) ── */
    .rating-group {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .rating-card {
      cursor: pointer;
      flex: 1;
      min-width: 100px;
    }

    .rating-card input[type="radio"] {
      display: none;
    }

    .rating-card span {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 16px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.9rem;
      font-weight: 600;
      color: #64748b;
      background: #f8fafc;
      transition: border-color 0.15s, background 0.15s, color 0.15s;
      cursor: pointer;
    }

    /* Good hover = green tint */
    .rating-card:nth-child(1):hover span {
      border-color: #16a34a; color: #16a34a; background: #f0fdf4;
    }
    /* Average hover = orange tint */
    .rating-card:nth-child(2):hover span {
      border-color: #d97706; color: #d97706; background: #fffbeb;
    }
    /* Poor hover = red tint */
    .rating-card:nth-child(3):hover span {
      border-color: #dc2626; color: #dc2626; background: #fef2f2;
    }

    /* Selected states */
    .rating-card:nth-child(1) input[type="radio"]:checked + span {
      border-color: #16a34a; background: #f0fdf4; color: #16a34a;
    }
    .rating-card:nth-child(2) input[type="radio"]:checked + span {
      border-color: #d97706; background: #fffbeb; color: #d97706;
    }
    .rating-card:nth-child(3) input[type="radio"]:checked + span {
      border-color: #dc2626; background: #fef2f2; color: #dc2626;
    }

    /* ── CHECKBOX GRID ── */
    .checkbox-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .checkbox-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      cursor: pointer;
      font-size: 0.88rem;
      color: #334155;
      font-weight: 500;
      background: #f8fafc;
      transition: all 0.15s;
    }

    .checkbox-option:hover {
      border-color: #2563eb;
      background: #eff6ff;
      color: #2563eb;
    }

    .checkbox-option input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: #2563eb;
      flex-shrink: 0;
    }

    .checkbox-option input:checked ~ * {
      color: #2563eb;
    }

    /* ── FORM INPUTS ── */
    #feedback-form .form-group {
      margin-bottom: 24px;
    }

    #feedback-form label {
      display: block;
      font-weight: 600;
      font-size: 0.9rem;
      color: #0f172a;
      margin-bottom: 8px;
    }

    #feedback-form input[type="text"],
    #feedback-form input[type="email"],
    #feedback-form select,
    #feedback-form textarea {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.9rem;
      font-family: inherit;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box;
    }

    #feedback-form input:focus,
    #feedback-form select:focus,
    #feedback-form textarea:focus {
      border-color: #2563eb;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
    }

    #feedback-form .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    @media (max-width: 600px) {
      #feedback-form .form-row { grid-template-columns: 1fr; }
      .checkbox-grid { grid-template-columns: 1fr; }
      .rating-group { flex-direction: column; }
    }
  </style>
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

        <!-- TABS -->
        <div class="feedback-tabs">
          <button class="feedback-tab active" id="tab-form" onclick="switchTab('form', this)">
            <i class="fa-solid fa-pen-to-square"></i> Submit Feedback
          </button>
          <button class="feedback-tab" id="tab-replies" onclick="switchTab('replies', this)">
            <i class="fa-solid fa-inbox"></i> My Replies
            <?php if ($activeReplies > 0): ?>
              <span class="feedback-tab-badge"><?= $activeReplies ?></span>
            <?php endif; ?>
          </button>
        </div>

        <!-- PANEL: Submit Feedback Form -->
        <div class="feedback-panel active" id="panel-form">
          <?php if (is_admin()): ?>
          <!-- Admin message shown ABOVE the form -->
          <div style="text-align:center; padding:32px 24px 28px; background:#eff6ff; border-radius:16px; margin-bottom:24px;">
            <i class="fa-solid fa-shield-halved" style="font-size:2.5rem; color:#2563eb; margin-bottom:12px; display:block;"></i>
            <h3 style="color:#0f172a; margin:0 0 8px; font-size:1.25rem;">Viewing as Admin</h3>
            <p style="color:#64748b; margin:0; line-height:1.7; font-size:0.93rem;">
              You are browsing the live site as an administrator.<br>
              <strong>Feedback submission is for students only.</strong>
            </p>
          </div>
          <!-- Form shown below, greyed out so admin can see the questions -->
          <div style="opacity:0.55; pointer-events:none; user-select:none;">
          <?php endif; ?>
          <div>

          <div id="feedback-form-wrap">
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
              <div class="form-row">
                <div class="form-group">
                  <label for="feedback-name">Full Name <span style="color:#ef4444">*</span></label>
                  <input type="text" id="name" name="name" placeholder="Enter your full name"
                    value="<?= h($_SESSION['full_name'] ?? '') ?>" />
                  <span id="name-error" class="field-error"></span>
                </div>
                <div class="form-group">
                  <label for="feedback-email">Email Address <span style="color:#ef4444">*</span></label>
                  <input type="email" id="email" name="email" placeholder="your.email@example.com"
                    value="<?= h($_SESSION['email'] ?? '') ?>" />
                  <span id="email-error" class="field-error"></span>
                </div>
              </div>

              <div class="form-group">
                <label>Overall Rating <span style="color:#ef4444">*</span></label>
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

              <div class="form-group">
                <label>Interested Workshops</label>
                <p style="font-size:0.82rem; color:#94a3b8; margin-bottom:10px;">Select all that apply</p>
                <div class="checkbox-grid">
                  <?php
                  try {
                      $wsStmt = $pdo->query("SELECT workshop_id, title, category_id FROM workshops ORDER BY title ASC");
                      $allWorkshops = $wsStmt->fetchAll();
                      $icons = [1=>'fa-laptop-code',2=>'fa-palette',3=>'fa-robot',4=>'fa-briefcase',5=>'fa-chart-bar',6=>'fa-shield-halved'];
                      foreach ($allWorkshops as $ws):
                  ?>
                  <label class="checkbox-option">
                    <input type="checkbox" name="workshops[]" value="<?= h($ws['title']) ?>" />
                    <span>
                      <i class="fa-solid <?= $icons[$ws['category_id']] ?? 'fa-book-open' ?>"></i>
                      <?= h($ws['title']) ?>
                    </span>
                  </label>
                  <?php endforeach; } catch(PDOException $e) {} ?>
                </div>
              </div>

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
              </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.feedback-form-wrap -->
            </div><!-- close greyed preview -->
          </div><!-- close relative wrapper -->
          
          <?php if (is_admin()): ?></div><?php endif; ?>
          </div>

        <!-- PANEL: My Replies -->
        <div class="feedback-panel" id="panel-replies">
          <?php
            $shownAny = false;
            foreach ($myFeedback as $fb):
              $msgs = $feedbackMsgs[$fb['feedback_id']] ?? [];
              $hasMsg = !empty($msgs) || !empty($fb['admin_reply']);
              $isResolved = in_array($fb['feedback_id'], $userResolved);
              if (!$hasMsg || $isResolved) continue;
              $shownAny = true;
          ?>
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

            <?php if ($fb['comments']): ?>
            <div class="reply-thread-comment">
              <i class="fa-solid fa-quote-left"></i> <?= h($fb['comments']) ?>
            </div>
            <?php endif; ?>

            <!-- All admin messages stacked -->
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
              <div class="reply-msg">
                <div class="reply-msg-label"><i class="fa-solid fa-reply"></i> Admin Reply</div>
                <p><?= h($fb['admin_reply']) ?></p>
              </div>
            <?php endif; ?>

            <!-- User can hide this thread -->
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

          <?php if (is_admin() || !$shownAny): ?>
          <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:60px 20px; width:100%;">
            <i class="fa-solid fa-<?= is_admin() ? 'chalkboard-user' : 'inbox' ?>" style="font-size:2.5rem; margin-bottom:16px; display:block; opacity:0.4; color:#94a3b8;"></i>
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
        </div>

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
  // ── TAB SWITCHING ──────────────────────────────────────────
  function switchTab(tabName, btn) {
    document.querySelectorAll('.feedback-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.feedback-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + tabName).classList.add('active');
    btn.classList.add('active');

    // When user opens replies tab, clear the badge and navbar dot
    if (tabName === 'replies') {
      // Remove the red badge from the tab
      const badge = btn.querySelector('.feedback-tab-badge');
      if (badge) badge.remove();
      // Remove the navbar notification dot
      fetch('../api/mark_replies_seen.php', { method: 'POST' });
    }
  }

  // Auto-switch to replies tab if URL has #replies-panel
  if (window.location.hash === '#replies-panel') {
    const repliesBtn = document.getElementById('tab-replies');
    if (repliesBtn) switchTab('replies', repliesBtn);
  }

  // If user has unread replies, auto-open replies tab on load
  <?php if ($activeReplies > 0 && empty($_GET['submitted'])): ?>
  // Only auto-open if there are active replies waiting
  // Comment this out if you don't want auto-open behaviour
  // switchTab('replies', document.getElementById('tab-replies'));
  <?php endif; ?>
  </script>
</body>
</html>