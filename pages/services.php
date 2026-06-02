<!--
 /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath    = '../';
$currentPage = 'services';

require_once __DIR__ . '/../includes/db.php';

// ── LOAD ALL WORKSHOPS ────────────────────────────────────────
// JOIN instructors so all instructor fields are available for the
// View Details modal popup (name, email, specialty, experience).
// Also loads learning_points for the "What you'll learn" section.
$stmt = $pdo->query("
    SELECT workshops.*,
           categories.category_name,
           TRIM(CONCAT(COALESCE(i.title,''), ' ', COALESCE(i.full_name,''))) AS instructor_name,
           i.email      AS instructor_email,
           i.specialty  AS instructor_specialty,
           i.experience AS instructor_experience
    FROM workshops
    JOIN categories ON workshops.category_id = categories.category_id
    LEFT JOIN instructors i ON workshops.instructor_id = i.instructor_id
");
$workshops = $stmt->fetchAll();

// ── LOAD BOOKED WORKSHOP IDs FOR CURRENT USER ─────────────────
// Used to show "Already Booked" button state on cards.
// Only runs for logged-in non-admin users.
$bookedWorkshopIds = [];
if (is_logged_in() && !is_admin()) {
    try {
        $bookedStmt = $pdo->prepare(
            'SELECT DISTINCT workshop_id FROM bookings WHERE email = :email'
        );
        $bookedStmt->execute(['email' => $_SESSION['email'] ?? '']);
        $bookedWorkshopIds = array_column($bookedStmt->fetchAll(), 'workshop_id');
    } catch (PDOException $e) {
        $bookedWorkshopIds = [];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Services – SkillHub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../global/main.css" />
  <link rel="stylesheet" href="../global/print.css" media="print" />
</head>

<body
  data-logged-in="<?= is_logged_in() ? '1' : '0' ?>"
  data-is-admin="<?= is_admin() ? '1' : '0' ?>"
  data-user-name="<?= h($_SESSION['full_name'] ?? '') ?>"
  data-user-email="<?= h($_SESSION['email'] ?? '') ?>"
>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <!-- PAGE HERO -->
  <div class="page-hero services-hero">
    <div class="container">
      <div class="badge"><i class="fa-solid fa-swatchbook"></i> Our Workshops</div>
      <h1>Workshop Categories</h1>
      <p>
        Choose from our selected range of workshops designed specifically for students who want to build practical,
        in-demand skills. Each workshop is a focused, hands-on session that fits into your busy schedule.
      </p>
    </div>
  </div>

  <!-- WORKSHOP CARDS SECTION -->
  <section class="section services-section">
    <div class="container">

      <!-- Search and category filter -->
      <div class="services-search-panel">
        <div class="services-search-copy">
          <h2>Search Workshops</h2>
          <p>Find workshops by title, description, or category.</p>
        </div>
        <div class="services-search-controls">
          <input type="text" id="searchInput" placeholder="Search workshops..." />
          <select id="categoryFilter">
            <option value="">All Categories</option>
            <?php
              $catStmt = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC");
              foreach ($catStmt->fetchAll() as $cat):
            ?>
              <option value="<?= h($cat['category_name']) ?>"><?= h($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Workshop cards grid -->
      <div class="grid-2">
        <?php foreach ($workshops as $workshop):
          $imgPath             = $workshop['image_path']          ?? '';
          $hasImg              = $imgPath !== '' && $imgPath !== null;
          $isBooked            = in_array($workshop['workshop_id'], $bookedWorkshopIds);
          $isFull              = (int)$workshop['available_seats'] <= 0;
          $instructorName      = trim($workshop['instructor_name']      ?? '');
          $instructorEmail     = trim($workshop['instructor_email']     ?? '');
          $instructorSpecialty = trim($workshop['instructor_specialty'] ?? '');
          $instructorExp       = trim($workshop['instructor_experience']?? '');
          // learning_points stored as newline-separated text; passed as-is to JS
          $learningPoints      = trim($workshop['learning_points']      ?? '');
        ?>
        <div class="card">

          <!-- Workshop image or gradient placeholder -->
          <?php if ($hasImg): ?>
          <img
            src="<?= h($imgPath) ?>"
            alt="<?= h($workshop['title']) ?>"
            class="card-img"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
          />
          <?php endif; ?>
          <div class="card-img-placeholder" <?= $hasImg ? 'style="display:none"' : '' ?>>
            <i class="fa-solid fa-book-open"></i>
            <span><?= h($workshop['category_name']) ?></span>
          </div>

          <div class="card-body">
            <div class="card-icon card-icon-web">
              <i class="fa-solid fa-laptop-code"></i>
            </div>

            <h3><?= h($workshop['title']) ?></h3>
            <p><?= h($workshop['description']) ?></p>

            <div class="card-tags" style="margin-top:16px">
              <span class="tag tag-primary"><?= h($workshop['category_name']) ?></span>
              <!-- Seats tag — id allows JS to update it instantly after booking -->
              <span class="tag tag-secondary seats-tag" id="seats-tag-<?= $workshop['workshop_id'] ?>">
                <?= h((string)$workshop['available_seats']) ?> Seats
              </span>
            </div>

            <!-- ASEEL ADDITION: View Details button — outlined style -->
            <button
              type="button"
              class="btn view-details-btn"
              data-title="<?= h($workshop['title']) ?>"
              data-description="<?= h($workshop['description']) ?>"
              data-category="<?= h($workshop['category_name']) ?>"
              data-instructor="<?= h($instructorName ?: '—') ?>"
              data-instructor-email="<?= h($instructorEmail) ?>"
              data-instructor-specialty="<?= h($instructorSpecialty) ?>"
              data-instructor-experience="<?= h($instructorExp) ?>"
              data-learning-points="<?= h($learningPoints) ?>"
              data-date="<?= h($workshop['workshop_date']) ?>"
              data-time="<?= h(date('g:i A', strtotime($workshop['start_time'])) . ' – ' . date('g:i A', strtotime($workshop['end_time']))) ?>"
              data-seats="<?= h((string)$workshop['available_seats']) ?>"
              data-workshop-id="<?= h((string)$workshop['workshop_id']) ?>"
            >
              <i class="fa-solid fa-eye"></i>
              View Details
            </button>

            <!-- ASEEL ADDITION: Full / Already Booked / Book Workshop -->
            <?php if ($isFull): ?>
              <!-- Workshop is fully booked — no seats left -->
              <button type="button" class="btn btn-secondary workshop-book-btn" disabled style="margin-top:8px;">
                <i class="fa-solid fa-circle-xmark"></i> Full
              </button>
            <?php elseif ($isBooked): ?>
              <!-- User already has a booking for this workshop -->
              <button type="button" class="btn btn-booked-already" disabled>
                <i class="fa-solid fa-circle-check"></i> Already Booked
              </button>
            <?php else: ?>
              <!-- Default: user can book this workshop -->
              <button
                type="button"
                class="btn btn-primary book-btn workshop-book-btn"
                data-workshop-id="<?= h((string)$workshop['workshop_id']) ?>"
                data-workshop-title="<?= h($workshop['title']) ?>"
                data-workshop-date="<?= h($workshop['workshop_date']) ?>"
                data-workshop-time="<?= h($workshop['start_time'] . ' - ' . $workshop['end_time']) ?>"
                data-workshop-link="#"
              >
                <i class="fa-solid fa-calendar-days"></i> Book Workshop
              </button>
            <?php endif; ?>

          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <blockquote>
        "SkillHub workshops gave me the practical skills I needed to land my
        first internship. The hands-on approach made all the difference."
        <cite>— Sarah K., Computer Science Student</cite>
      </blockquote>
    </div>
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

  <!-- ===== BOOKING MODAL ===== -->
  <div id="booking-overlay" hidden>
    <div id="booking-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div id="modal-header">
        <div>
          <p id="modal-badge"><i class="fa-solid fa-calendar-days"></i> Workshop Booking</p>
          <h2 id="modal-title">Confirm Booking</h2>
        </div>
        <button type="button" id="modal-close" aria-label="Close booking window">&times;</button>
      </div>

      <!-- Confirm state -->
      <div id="booking-state-confirm" class="booking-state">
        <div id="modal-workshop-info">
          <p id="info-name"></p>
          <div id="info-details">
            <span id="info-date"></span>
            <span id="info-time"></span>
          </div>
          <p id="info-email-note">
            <i class="fa-solid fa-envelope"></i>
            A booking confirmation email will be sent with the workshop details.
          </p>
        </div>
        <div class="booking-confirm-actions">
          <button type="button" class="btn btn-outline" id="booking-cancel-btn">Back</button>
          <button type="button" class="btn btn-primary" id="booking-confirm-btn">
            <i class="fa-solid fa-circle-check"></i> Confirm Booking
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div id="booking-state-loading" class="booking-state" hidden>
        <div class="booking-feedback-card">
          <div class="booking-loader" aria-hidden="true"></div>
          <h3>Booking your workshop...</h3>
          <p>Please wait while we reserve your seat.</p>
        </div>
      </div>

      <!-- Success state -->
      <div id="booking-state-success" class="booking-state" hidden>
        <div class="booking-feedback-card booking-success-card">
          <div class="booking-success-icon"><i class="fa-solid fa-check"></i></div>
          <h3>Workshop booked successfully</h3>
          <p id="booking-success-message">
            Your seat has been reserved successfully.
            A confirmation email was sent with your booking details.
          </p>
          <div class="booking-zoom-note">
            <i class="fa-solid fa-video"></i>
            <span>The Zoom meeting link for this workshop will be sent to your email before it starts.</span>
          </div>
          <div class="booking-success-actions">
            <a href="profile.php" class="btn btn-primary">
              <i class="fa-solid fa-user"></i> View My Bookings
            </a>
            <button type="button" class="btn btn-outline" id="booking-back-btn">Back to Workshops</button>
          </div>
        </div>
      </div>

      <!-- Error state -->
      <div id="booking-state-error" class="booking-state" hidden>
        <div class="booking-feedback-card booking-error-card">
          <div class="booking-error-icon"><i class="fa-solid fa-xmark"></i></div>
          <h3>Booking failed</h3>
          <p id="booking-error-message">Something went wrong while booking this workshop.</p>
          <button type="button" class="btn btn-outline" id="booking-error-back-btn">Back</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== WORKSHOP DETAILS MODAL ===== -->
  <!-- ASEEL ADDITION: Shows full workshop info with instructor hover popup and What you'll learn -->
  <div id="details-overlay" hidden>
    <div id="details-modal">

      <button type="button" class="details-close-btn" aria-label="Close details modal">&times;</button>

      <!-- Header: category badge, seats badge, title, description -->
      <div class="details-header">
        <div class="details-badges">
          <span class="details-badge-category" id="details-category">Workshop</span>
          <span class="details-badge-seats" id="details-seats-badge">Seats Available</span>
        </div>
        <h2 id="details-title"></h2>
        <p id="details-description"></p>
      </div>

      <!-- Info grid: Instructor (with hover popup), Date, Time -->
      <!-- NO Seats card — seats shown in badge at top -->
      <div class="details-grid">

        <!-- Instructor with hover popup showing specialty + experience -->
        <div class="details-item">
          <span class="details-label">Instructor</span>
          <p id="details-instructor" class="instructor-hover">
            <span id="details-instructor-name"></span>
            <i class="fa-solid fa-circle-info"></i>
            <!-- Popup shown on hover — populated by JS -->
            <span class="instructor-popup">
              <strong id="details-instructor-popup-name"></strong>
              <small id="details-instructor-specialty" style="display:block;margin-top:4px;color:#374151;"></small>
              <small id="details-instructor-experience" style="display:block;margin-top:2px;color:#6b7280;"></small>
              <span id="details-instructor-email" style="font-size:0.82rem;color:#6b7280;margin-top:4px;display:block;"></span>
            </span>
          </p>
        </div>

        <div class="details-item">
          <span class="details-label">Date</span>
          <p id="details-date"></p>
        </div>

        <!-- Time spans full width -->
        <div class="details-item" style="grid-column: 1 / -1;">
          <span class="details-label">Time</span>
          <p id="details-time"></p>
        </div>

      </div>

      <!-- What you'll learn — populated from learning_points field by JS -->
      <!-- Shows actual admin-written bullet points, NOT the description -->
      <div id="details-learn-section" style="margin-top:4px;">
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:16px; padding:18px 20px;">
          <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#059669; margin-bottom:12px;">
            <i class="fa-solid fa-graduation-cap" style="margin-right:6px;"></i>
            What you'll learn
          </p>
          <ul id="details-learn" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;"></ul>
        </div>
      </div>

    </div>
  </div>

  <!-- Pass booked IDs to JS for correct button state in search results -->
  <script>
    window.bookedWorkshopIds = <?= json_encode(array_map('intval', $bookedWorkshopIds)) ?>;
  </script>

  <!-- Live search + category filter AJAX -->
  <!-- Fetches workshops from API on every keystroke and category change — no page reload -->
  <script>
  const searchInput    = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const grid           = document.querySelector('.grid-2');

  function formatTimeStr(t) {
  if (!t) return '';
  const [h, m] = t.split(':').map(Number);
  const ampm = h >= 12 ? 'PM' : 'AM';
  const hour = h % 12 || 12;
  return hour + ':' + String(m).padStart(2, '0') + ' ' + ampm;
}
  async function loadWorkshops() {
    const searchValue   = searchInput.value;
    const categoryValue = categoryFilter.value;

    const response = await fetch(
      `../api/search_workshops.php?search=${encodeURIComponent(searchValue)}&category=${encodeURIComponent(categoryValue)}`
    );
    const workshops = await response.json();
    grid.innerHTML  = '';

    const bookedIds = window.bookedWorkshopIds || [];
    const isAdmin   = document.body.dataset.isAdmin === '1';

    workshops.forEach(workshop => {
      const hasImg = workshop.image_path && workshop.image_path.trim() !== '';

      // Image + placeholder matching PHP card rendering
      const imgHtml = hasImg
        ? `<img src="${workshop.image_path}" alt="${workshop.title}" class="card-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />`
        : '';
      const placeholderStyle = hasImg ? 'style="display:none"' : '';
      const placeholderHtml  = `<div class="card-img-placeholder" ${placeholderStyle}>
        <i class="fa-solid fa-book-open"></i>
        <span>${workshop.category_name}</span>
      </div>`;

      // Instructor fields for popup
      const instructorName      = (workshop.instructor_name      || '').trim() || '—';
      const instructorEmail     = (workshop.instructor_email     || '').trim();
      const instructorSpecialty = (workshop.instructor_specialty || '').trim();
      const instructorExp       = (workshop.instructor_experience|| '').trim();
      // learning_points for What you'll learn section
      const learningPoints      = (workshop.learning_points      || '').trim();

      // Action button: Full / Already Booked / Book Workshop
      let btnHtml = '';
      if (!isAdmin) {
        if (parseInt(workshop.available_seats) <= 0) {
          btnHtml = `<button type="button" class="btn btn-secondary workshop-book-btn" disabled style="margin-top:8px;">
            <i class="fa-solid fa-circle-xmark"></i> Full
          </button>`;
        } else if (bookedIds.includes(parseInt(workshop.workshop_id))) {
          btnHtml = `<button type="button" class="btn btn-booked-already" disabled>
            <i class="fa-solid fa-circle-check"></i> Already Booked
          </button>`;
        } else {
          btnHtml = `<button type="button"
            class="btn btn-primary book-btn workshop-book-btn"
            data-workshop-id="${workshop.workshop_id}"
            data-workshop-title="${workshop.title}"
            data-workshop-date="${workshop.workshop_date}"
            data-workshop-time="${workshop.start_time} - ${workshop.end_time}"
            data-workshop-link="#">
            <i class="fa-solid fa-calendar-days"></i> Book Workshop
          </button>`;
        }
      }

      // View Details button — passes all instructor fields + learning_points
      const viewDetailsBtn = `<button
        type="button"
        class="btn view-details-btn"
        data-title="${workshop.title}"
        data-description="${workshop.description}"
        data-category="${workshop.category_name}"
        data-instructor="${instructorName}"
        data-instructor-email="${instructorEmail}"
        data-instructor-specialty="${instructorSpecialty}"
        data-instructor-experience="${instructorExp}"
        data-learning-points="${learningPoints}"
        data-date="${workshop.workshop_date}"
        data-time="${formatTimeStr(workshop.start_time)} – ${formatTimeStr(workshop.end_time)}"
        data-seats="${workshop.available_seats}"
        data-workshop-id="${workshop.workshop_id}">
        <i class="fa-solid fa-eye"></i> View Details
      </button>`;

      grid.innerHTML += `
        <div class="card">
          ${imgHtml}
          ${placeholderHtml}
          <div class="card-body">
            <div class="card-icon card-icon-web">
              <i class="fa-solid fa-laptop-code"></i>
            </div>
            <h3>${workshop.title}</h3>
            <p>${workshop.description}</p>
            <div class="card-tags" style="margin-top:16px">
              <span class="tag tag-primary">${workshop.category_name}</span>
              <span class="tag tag-secondary seats-tag" id="seats-tag-${workshop.workshop_id}">${workshop.available_seats} Seats</span>
            </div>
            ${viewDetailsBtn}
            ${btnHtml}
          </div>
        </div>
      `;
    });
  }

  searchInput.addEventListener('keyup',    loadWorkshops);
  categoryFilter.addEventListener('change', loadWorkshops);
  </script>

  <script src="../scripts/main.js"></script>
</body>
</html>