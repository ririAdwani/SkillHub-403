<?php
/*
 * schedule.php — Weekly Workshop Schedule
 * Shows workshops grouped by day for the current or selected week.
 * Week runs Sunday–Saturday; Friday and Saturday are marked as off days.
 *
 * Navigation rules:
 * - "This Week" always returns to the current week
 * - "Previous Week" is disabled/hidden if it would go before the current week
 * - "Next Week" is always available — no limit going forward
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$basePath    = '../';
$currentPage = 'schedule';

// ── WEEK CALCULATION ──────────────────────────────────────────
// Snap $today to Sunday of the CURRENT week (used as the minimum allowed week)
$today        = new DateTime();
$todayDow     = (int)$today->format('w'); // 0=Sun … 6=Sat
$currentWeekStart = clone $today;
$currentWeekStart->modify('-' . $todayDow . ' days');
$currentWeekStart->setTime(0, 0, 0);

// Determine the requested week start from ?week=YYYY-MM-DD
if (!empty($_GET['week'])) {
    $requestedDate = DateTime::createFromFormat('Y-m-d', $_GET['week']);
    $weekStart     = $requestedDate ?: clone $currentWeekStart;
} else {
    $weekStart = clone $currentWeekStart;
}

// Always snap to Sunday of that week
$dow = (int)$weekStart->format('w');
$weekStart->modify('-' . $dow . ' days');
$weekStart->setTime(0, 0, 0);

// ── ENFORCE MINIMUM: never go before the current week ─────────
// If the requested week is before this week, clamp it to this week
if ($weekStart < $currentWeekStart) {
    $weekStart = clone $currentWeekStart;
}

// Week ends Saturday
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');
$weekEnd->setTime(23, 59, 59);

// Previous and next week dates for nav links
$prevWeek = clone $weekStart;
$prevWeek->modify('-7 days');
$nextWeek = clone $weekStart;
$nextWeek->modify('+7 days');

// Determine if "Previous Week" should be shown
// It is hidden when we are already on the current week
$isCurrentWeek   = ($weekStart->format('Y-m-d') === $currentWeekStart->format('Y-m-d'));
$showPrevBtn     = !$isCurrentWeek; // Only show Previous when not on this week

// ── LOAD WORKSHOPS FOR THIS WEEK FROM DATABASE ────────────────
try {
    // Try with instructors table joined
    $stmt = $pdo->prepare("
        SELECT
            w.workshop_id,
            w.title,
            w.description,
            w.workshop_date,
            w.start_time,
            w.end_time,
            w.available_seats,
            c.category_name,
            TRIM(CONCAT(COALESCE(i.title, ''), ' ', COALESCE(i.full_name, ''))) AS instructor_name
        FROM workshops w
        JOIN categories c ON w.category_id = c.category_id
        LEFT JOIN instructors i ON w.instructor_id = i.instructor_id
        WHERE w.workshop_date BETWEEN :start AND :end
        ORDER BY w.workshop_date ASC, w.start_time ASC
    ");
    $stmt->execute([
        ':start' => $weekStart->format('Y-m-d'),
        ':end'   => $weekEnd->format('Y-m-d'),
    ]);
    $workshops = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback without instructors table
    $stmt = $pdo->prepare("
        SELECT
            w.workshop_id, w.title, w.description,
            w.workshop_date, w.start_time, w.end_time, w.available_seats,
            c.category_name, '' AS instructor_name
        FROM workshops w
        JOIN categories c ON w.category_id = c.category_id
        WHERE w.workshop_date BETWEEN :start AND :end
        ORDER BY w.workshop_date ASC, w.start_time ASC
    ");
    $stmt->execute([
        ':start' => $weekStart->format('Y-m-d'),
        ':end'   => $weekEnd->format('Y-m-d'),
    ]);
    $workshops = $stmt->fetchAll();
}

// ── GROUP WORKSHOPS BY DATE ───────────────────────────────────
$byDate = [];
foreach ($workshops as $w) {
    $byDate[$w['workshop_date']][] = $w;
}

// Friday=5 and Saturday=6 are off/weekend days
$WEEKEND_DAYS = [5, 6];
?>
<!doctype html>
<!--
  /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
  /* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
  /* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Schedule – SkillHub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../global/main.css" />
  <link rel="stylesheet" href="../global/print.css" media="print" />
  <style>
    /* ── WEEK NAVIGATOR BAR ──────────────────────────────────── */
    .week-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 28px;
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 16px 24px;
      box-shadow: var(--shadow-sm);
    }

    /* Left side: Previous Week (or empty spacer) */
    .week-nav-left {
      min-width: 160px; /* Reserve space so label stays centered even when btn hidden */
    }

    /* Center label */
    .week-nav-label {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text);
      text-align: center;
      flex: 1;
    }

    .week-nav-label span {
      display: block;
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--text-light);
      margin-top: 2px;
    }

    /* Right side: This Week + Next Week */
    .week-nav-right {
      display: flex;
      gap: 8px;
      align-items: center;
      min-width: 160px;
      justify-content: flex-end;
    }

    /* Shared nav button style */
    .week-nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      border-radius: 10px;
      border: 1.5px solid var(--border);
      background: white;
      color: var(--text);
      font-size: 0.88rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
      white-space: nowrap;
    }

    .week-nav-btn:hover {
      border-color: var(--primary);
      background: var(--primary-light);
      color: var(--primary);
    }

    /* "This Week" button — filled blue */
    .week-nav-btn.today-btn {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
      box-shadow: 0 4px 12px rgba(44,123,229,0.28);
    }

    .week-nav-btn.today-btn:hover {
      background: #1a5cbf;
      color: white;
    }

    /* ── TABLE OVERRIDES ─────────────────────────────────────── */
    #schedule-table th,
    #schedule-table td {
      vertical-align: top;
    }

    /* ── DAY COLUMN ──────────────────────────────────────────── */
    .day-name {
      font-weight: 700;
      color: var(--primary);
      display: block;
      font-size: 0.95rem;
    }

    .day-date {
      display: block;
      font-size: 0.78rem;
      color: var(--text-light);
      font-weight: 500;
      margin-top: 2px;
    }

    /* ── TODAY ROW ───────────────────────────────────────────── */
    /* Left border accent on today — no green background */
    .row-today td:first-child {
      border-left: 3px solid var(--primary);
    }

    .row-today .day-name {
      font-style: italic;
    }

    /* ── WEEKEND ROW ─────────────────────────────────────────── */
    .row-weekend td {
      background: #f8fafc;
      opacity: 0.75;
    }

    .row-weekend .day-name {
      color: var(--text-light);
    }

    /* Weekend pill badge */
    .weekend-badge {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 600;
      color: #94a3b8;
      background: #f1f5f9;
      border-radius: 6px;
      padding: 2px 8px;
      margin-top: 4px;
    }

    /* ── EMPTY WORKING DAY ───────────────────────────────────── */
    /* "No workshops scheduled" — orange/amber tint so it's clearly
       different from green seats badges and not misleading */
    .no-workshop-cell {
      font-size: 0.85rem;
      font-style: italic;
      /* Amber/orange color — clearly not success, just informational */
      color: #92400e;
    }

    /* Subtle amber background on the full row for empty working days */
    .row-empty td {
      background: #fffbeb; /* Very light amber — clearly distinct from white rows */
    }

    /* ── WORKSHOP ENTRY CARD ─────────────────────────────────── */
    .schedule-workshop-entry {
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: white;
      margin-bottom: 10px;
      box-shadow: var(--shadow-sm);
      transition: box-shadow 0.2s;
    }

    .schedule-workshop-entry:last-child {
      margin-bottom: 0;
    }

    .schedule-workshop-entry:hover {
      box-shadow: var(--shadow-md);
    }

    .schedule-workshop-title {
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--text);
      margin-bottom: 5px;
      display: block;
    }

    /* Category badge shown below title */
    .schedule-category-badge {
      display: inline-block;
      font-size: 0.7rem;
      font-weight: 600;
      padding: 3px 10px;
      border-radius: 99px;
      background: var(--primary-light);
      color: var(--primary);
      margin-bottom: 6px;
    }

    /* Time text */
    .schedule-time {
      font-size: 0.8rem;
      color: var(--text-light);
      display: block;
      white-space: nowrap;
    }

    /* Seats badge */
    .schedule-seats {
      font-size: 0.78rem;
      font-weight: 600;
      display: inline-block;
      padding: 3px 10px;
      border-radius: 99px;
      white-space: nowrap;
    }

    .schedule-seats.has-seats {
      background: var(--secondary-light);
      color: var(--secondary);
    }

    .schedule-seats.no-seats {
      color: #dc2626;
    }

    /* Instructor name */
    .schedule-instructor {
      font-size: 0.85rem;
      color: var(--text);
    }

    /* Transparent entry wrapper in Time/Seats/Instructor columns
       keeps vertical alignment with the workshop card */
    .schedule-cell-entry {
      padding: 10px 0;
      margin-bottom: 10px;
      min-height: 72px; /* Matches approx height of .schedule-workshop-entry */
      display: flex;
      align-items: center;
    }

    .schedule-cell-entry:last-child {
      margin-bottom: 0;
    }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <!-- ── PAGE HERO ── -->
  <div class="page-hero">
    <div class="container">
      <div class="badge"><i class="fa-solid fa-calendar-week"></i> Weekly Timetable</div>
      <h1>Workshop Schedule</h1>
      <p>Browse workshops by week. Friday and Saturday are off days.</p>
      <button class="btn btn-outline" onclick="window.print()" style="margin-top:20px">
        <i class="fa-solid fa-print"></i> Print Schedule
      </button>
    </div>
  </div>

  <section class="section">
    <div class="container">

      <!-- ── WEEK NAVIGATOR ── -->
      <!-- Anchored so navigation buttons scroll back to the table, not the top of the page -->
      <div class="week-nav" id="schedule-nav">

        <!-- LEFT: Previous Week — hidden when already on current week -->
        <div class="week-nav-left">
          <?php if ($showPrevBtn): ?>
            <!-- Previous Week shown only when viewing a future week -->
            <a href="schedule.php?week=<?= $prevWeek->format('Y-m-d') ?>#schedule-nav"
               class="week-nav-btn">
              <i class="fa-solid fa-chevron-left"></i> Previous Week
            </a>
          <?php endif; ?>
          <!-- No button here when on current week — space kept for centering -->
        </div>

        <!-- CENTER: Week range label -->
        <div class="week-nav-label">
          <?= $weekStart->format('M j') ?> – <?= $weekEnd->format('M j, Y') ?>
          <span>Week of <?= $weekStart->format('F j, Y') ?></span>
        </div>

        <!-- RIGHT: This Week + Next Week -->
        <div class="week-nav-right">
          <?php if (!$isCurrentWeek): ?>
            <!-- "This Week" only shown when not already on it -->
            <a href="schedule.php#schedule-nav" class="week-nav-btn today-btn">
              <i class="fa-solid fa-calendar-day"></i> This Week
            </a>
          <?php endif; ?>
          <!-- Next Week always available — no upper limit -->
          <a href="schedule.php?week=<?= $nextWeek->format('Y-m-d') ?>#schedule-nav"
             class="week-nav-btn">
            Next Week <i class="fa-solid fa-chevron-right"></i>
          </a>
        </div>

      </div><!-- /.week-nav -->

      <!-- ── WEEKLY TABLE ── -->
      <div class="table-wrapper">
        <table id="schedule-table">
          <caption>
            Weekly Workshop Schedule
            <p>Delivering Workshops Sunday through Thursday· Friday &amp; Saturday are off days</p>
          </caption>
          <thead>
            <tr>
              <th scope="col" style="width:150px;">Day</th>
              <th scope="col">Workshop</th>
              <th scope="col" style="width:170px;">Time</th>
              <th scope="col" style="width:110px;">Seats</th>
              <th scope="col" style="width:180px;">Instructor</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Loop Sun (0) through Sat (6)
            for ($d = 0; $d < 7; $d++):
              $currentDay   = clone $weekStart;
              $currentDay->modify('+' . $d . ' days');
              $dayKey       = $currentDay->format('Y-m-d');
              $dayNum       = (int)$currentDay->format('w'); // 0=Sun … 6=Sat
              $isWeekend    = in_array($dayNum, $WEEKEND_DAYS);
              $isToday      = ($dayKey === $today->format('Y-m-d'));
              $dayWorkshops = $byDate[$dayKey] ?? [];
              $isEmpty      = !$isWeekend && empty($dayWorkshops);

              // Build row class: weekend, today, empty working day, or default
              $rowClasses = [];
              if ($isWeekend) $rowClasses[] = 'row-weekend';
              if ($isToday)   $rowClasses[] = 'row-today';
              if ($isEmpty)   $rowClasses[] = 'row-empty'; // Amber bg for empty working days
              $rowClass = implode(' ', $rowClasses);
            ?>
            <tr class="<?= $rowClass ?>">

              <!-- DAY CELL -->
              <td>
                <span class="day-name">
                  <?php if ($isToday): ?>
                    <!-- Dot icon marks today — no emoji -->
                    <i class="fa-solid fa-circle-dot"
                       style="font-size:0.55rem; margin-right:4px; vertical-align:middle;"></i>
                  <?php endif; ?>
                  <?= $currentDay->format('l') ?>
                </span>
                <span class="day-date"><?= $currentDay->format('M j') ?></span>
                <?php if ($isWeekend): ?>
                  <!-- Weekend badge — explains why no workshops appear -->
                  <span class="weekend-badge">
                    <i class="fa-solid fa-moon" style="margin-right:3px; font-size:0.65rem;"></i>
                    Weekend · Off Day
                  </span>
                <?php endif; ?>
              </td>

              <?php if ($isWeekend): ?>
                <!-- Weekend row: single centered message across all columns -->
                <td colspan="4" class="no-workshop-cell"
                    style="text-align:center; padding:18px;">
                  <i class="fa-solid fa-ban" style="margin-right:6px; opacity:0.35;"></i>
                  No workshops on weekends
                </td>

              <?php elseif ($isEmpty): ?>
                <!-- Empty working day: amber-tinted message -->
                <!-- colspan=4 fills Time, Seats, Instructor columns too -->
                <td colspan="4" class="no-workshop-cell" style="padding:16px;">
                  <i class="fa-regular fa-calendar-xmark"
                     style="margin-right:6px; opacity:0.6;"></i>
                  No workshops scheduled
                </td>

              <?php else: ?>
                <!-- WORKSHOP COLUMN: card with title + category badge -->
                <td>
                  <?php foreach ($dayWorkshops as $w): ?>
                  <div class="schedule-workshop-entry">
                    <span class="schedule-workshop-title"><?= h($w['title']) ?></span>
                    <span class="schedule-category-badge"><?= h($w['category_name']) ?></span>
                  </div>
                  <?php endforeach; ?>
                </td>

                <!-- TIME COLUMN -->
                <td>
                  <?php foreach ($dayWorkshops as $w): ?>
                  <div class="schedule-cell-entry">
                    <span class="schedule-time">
                      <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                      <?= date('g:i A', strtotime($w['start_time'])) ?>
                      – <?= date('g:i A', strtotime($w['end_time'])) ?>
                    </span>
                  </div>
                  <?php endforeach; ?>
                </td>

                <!-- SEATS COLUMN -->
                <td>
                  <?php foreach ($dayWorkshops as $w): ?>
                  <div class="schedule-cell-entry">
                    <?php if ($w['available_seats'] > 0): ?>
                      <span class="schedule-seats has-seats">
                        <?= (int)$w['available_seats'] ?> seats
                      </span>
                    <?php else: ?>
                      <span class="schedule-seats no-seats">Full</span>
                    <?php endif; ?>
                  </div>
                  <?php endforeach; ?>
                </td>

                <!-- INSTRUCTOR COLUMN -->
                <td>
                  <?php foreach ($dayWorkshops as $w): ?>
                  <div class="schedule-cell-entry">
                    <span class="schedule-instructor">
                      <?= h(trim($w['instructor_name']) ?: '—') ?>
                    </span>
                  </div>
                  <?php endforeach; ?>
                </td>

              <?php endif; ?>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <p class="schedule-note">
        <strong>Note:</strong> Schedule is subject to change.
        Friday and Saturday are off days with no workshops.
      </p>

    </div>
  </section>

  <!-- ── FOOTER ── -->
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