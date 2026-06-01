<!--
 /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$basePath    = '../';
$currentPage = 'schedule';

// Load workshops ordered by date then time
// LEFT JOIN instructors safely — if table doesn't exist yet, falls back gracefully
try {
    $workshops = $pdo->query("
        SELECT
            w.workshop_id,
            w.title,
            w.workshop_date,
            w.start_time,
            w.end_time,
            w.available_seats,
            c.category_name,
            TRIM(CONCAT(COALESCE(i.title, ''), ' ', COALESCE(i.full_name, ''))) AS instructor_name
        FROM workshops w
        JOIN categories c ON w.category_id = c.category_id
        LEFT JOIN instructors i ON w.instructor_id = i.instructor_id
        ORDER BY w.workshop_date ASC, w.start_time ASC
    ")->fetchAll();
} catch (PDOException $e) {
    // instructors table doesn't exist yet — load without instructor
    $workshops = $pdo->query("
        SELECT
            w.workshop_id, w.title, w.workshop_date,
            w.start_time, w.end_time, w.available_seats,
            c.category_name,
            '' AS instructor_name
        FROM workshops w
        JOIN categories c ON w.category_id = c.category_id
        ORDER BY w.workshop_date ASC, w.start_time ASC
    ")->fetchAll();
}

// Group by date for rowspan display
$grouped = [];
foreach ($workshops as $w) {
    $dateKey = date('l, M j Y', strtotime($w['workshop_date']));
    $grouped[$dateKey][] = $w;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Schedule – SkillHub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../global/main.css" />
  <link rel="stylesheet" href="../global/print.css" media="print" />
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="page-hero">
    <div class="container">
      <div class="badge"><i class="fa-solid fa-calendar-days"></i> Workshop Timetable</div>
      <h1>Workshop Schedule</h1>
      <p>All upcoming workshops sorted by date and time. Updated in real time.</p>
      <button class="btn btn-outline" onclick="window.print()" style="margin-top:20px">
        <i class="fa-solid fa-print"></i> Print Schedule
      </button>
    </div>
  </div>

  <section class="section">
    <div class="container">

      <?php if (empty($workshops)): ?>
        <div style="text-align:center; padding:60px; color:#94a3b8;">
          <i class="fa-solid fa-calendar-xmark" style="font-size:3rem; margin-bottom:16px; display:block;"></i>
          <h3>No workshops scheduled yet</h3>
          <p>Check back soon — new sessions will be added shortly.</p>
        </div>

      <?php else: ?>
      <div class="table-wrapper">
        <table id="schedule-table">
          <caption>
            Upcoming Workshop Schedule
            <p>Pulled live from the database — sorted by date and time</p>
          </caption>
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Time</th>
              <th scope="col">Workshop</th>
              <th scope="col">Category</th>
              <th scope="col">Seats</th>
              <th scope="col">Instructor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grouped as $dateLabel => $sessions): ?>
              <?php foreach ($sessions as $idx => $w): ?>
              <tr>
                <?php if ($idx === 0): ?>
                  <td rowspan="<?= count($sessions) ?>" class="day-cell">
                    <?= h($dateLabel) ?>
                  </td>
                <?php endif; ?>
                <td style="white-space:nowrap; font-size:0.88rem;">
                  <?= date('g:i A', strtotime($w['start_time'])) ?> – <?= date('g:i A', strtotime($w['end_time'])) ?>
                </td>
                <td>
                  <span class="workshop-name"><?= h($w['title']) ?></span>
                </td>
                <td>
                  <span class="tag tag-primary" style="white-space:nowrap;"><?= h($w['category_name']) ?></span>
                </td>
                <td>
                  <?php if ($w['available_seats'] > 0): ?>
                    <span class="tag tag-secondary"><?= (int)$w['available_seats'] ?> seats</span>
                  <?php else: ?>
                    <span style="color:#dc2626; font-weight:600; font-size:0.82rem;">Full</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:0.85rem; color:#475569;">
                  <?= h(trim($w['instructor_name']) ?: '—') ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="schedule-note">
        <strong>Note:</strong> Schedule is subject to change. Check back regularly for updates.
      </p>
      <?php endif; ?>

    </div>
  </section>

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
            <?php if (is_logged_in()): ?>
              <li><a href="<?= $basePath ?>pages/feedback.php">Feedback</a></li>
            <?php endif; ?>
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
</body>
</html>