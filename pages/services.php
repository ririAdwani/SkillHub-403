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

// Load all workshops with instructor fields + new hook_message + good_fit_for
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

$bookedWorkshopIds = [];
if (is_logged_in() && !is_admin()) {
    try {
        $bookedStmt = $pdo->prepare('SELECT DISTINCT workshop_id FROM bookings WHERE email = :email');
        $bookedStmt->execute(['email' => $_SESSION['email'] ?? '']);
        $bookedWorkshopIds = array_column($bookedStmt->fetchAll(), 'workshop_id');
    } catch (PDOException $e) { $bookedWorkshopIds = []; }
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

  <div class="page-hero services-hero">
    <div class="container">
      <div class="badge"><i class="fa-solid fa-swatchbook"></i> Our Workshops</div>
      <h1>Workshop Categories</h1>
      <p>Choose from our selected range of workshops designed specifically for students who want to build practical, in-demand skills.</p>
    </div>
  </div>

  <section class="section services-section">
    <div class="container">
      <div class="services-search-panel">
        <div class="services-search-copy">
          <h2>Search Workshops</h2>
          <p>Find workshops by title, description, or category.</p>
        </div>
        <div class="services-search-controls">
          <input type="text" id="searchInput" placeholder="Search workshops..." />
          <select id="categoryFilter">
            <option value="">All Categories</option>
            <?php $catStmt = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC"); foreach ($catStmt->fetchAll() as $cat): ?>
              <option value="<?= h($cat['category_name']) ?>"><?= h($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

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
          $learningPoints      = trim($workshop['learning_points']      ?? '');
          // New fields
          $hookMessage         = trim($workshop['hook_message']          ?? '');
          $goodFitFor          = trim($workshop['good_fit_for']          ?? '');
        ?>
        <div class="card">
          <?php if ($hasImg): ?>
          <img src="<?= h($imgPath) ?>" alt="<?= h($workshop['title']) ?>" class="card-img"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
          <?php endif; ?>
          <div class="card-img-placeholder" <?= $hasImg ? 'style="display:none"' : '' ?>>
            <i class="fa-solid fa-book-open"></i>
            <span><?= h($workshop['category_name']) ?></span>
          </div>

          <div class="card-body">
            <div class="card-icon card-icon-web"><i class="fa-solid fa-laptop-code"></i></div>
            <h3><?= h($workshop['title']) ?></h3>
            <p><?= h($workshop['description']) ?></p>

            <!-- ASEEL ADDITION: Hook message — short punchy line shown on card only -->
            <?php if ($hookMessage): ?>
            <p class="card-hook-message">
              <i class="fa-solid fa-bolt"></i> <?= h($hookMessage) ?>
            </p>
            <?php endif; ?>

            <div class="card-tags" style="margin-top:16px">
              <span class="tag tag-primary"><?= h($workshop['category_name']) ?></span>
              <span class="tag tag-secondary seats-tag" id="seats-tag-<?= $workshop['workshop_id'] ?>">
                <?= h((string)$workshop['available_seats']) ?> Seats
              </span>
            </div>

            <!-- ASEEL ADDITION: View Details button — passes all fields including new ones -->
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
              data-good-fit-for="<?= h($goodFitFor) ?>"
              data-date="<?= h(date('M j, Y', strtotime($workshop['workshop_date']))) ?>"
              data-time="<?= h(date('g:i A', strtotime($workshop['start_time'])) . ' – ' . date('g:i A', strtotime($workshop['end_time']))) ?>"
              data-seats="<?= h((string)$workshop['available_seats']) ?>"
              data-workshop-id="<?= h((string)$workshop['workshop_id']) ?>"
            >
              <i class="fa-solid fa-eye"></i> View Details
            </button>

            <?php if ($isFull): ?>
              <button type="button" class="btn btn-secondary workshop-book-btn" disabled style="margin-top:8px;">
                <i class="fa-solid fa-circle-xmark"></i> Full
              </button>
            <?php elseif ($isBooked): ?>
              <button type="button" class="btn btn-booked-already" disabled>
                <i class="fa-solid fa-circle-check"></i> Already Booked
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-primary book-btn workshop-book-btn"
                data-workshop-id="<?= h((string)$workshop['workshop_id']) ?>"
                data-workshop-title="<?= h($workshop['title']) ?>"
                data-workshop-date="<?= h($workshop['workshop_date']) ?>"
                data-workshop-time="<?= h($workshop['start_time'] . ' - ' . $workshop['end_time']) ?>"
                data-workshop-link="#">
                <i class="fa-solid fa-calendar-days"></i> Book Workshop
              </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <blockquote>
        "SkillHub workshops gave me the practical skills I needed to land my first internship."
        <cite>— Sarah K., Computer Science Student</cite>
      </blockquote>
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
            <?php if (is_logged_in()): ?><li><a href="<?= $basePath ?>pages/feedback.php">Feedback</a></li><?php endif; ?>
            <li><a href="about.php">About</a></li>
          </ul>
        </div>
        <div id="footer-contact">
          <p class="footer-heading">Contact</p>
          <address>Email: info@skillhub.edu</address>
        </div>
      </div>
    </div>
    <div id="footer-copyright">&copy; 2026 SkillHub – Student Workshops Platform. All rights reserved.</div>
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
      <div id="booking-state-confirm" class="booking-state">
        <div id="modal-workshop-info">
          <p id="info-name"></p>
          <div id="info-details"><span id="info-date"></span><span id="info-time"></span></div>
          <p id="info-email-note"><i class="fa-solid fa-envelope"></i> A booking confirmation email will be sent with the workshop details.</p>
        </div>
        <div class="booking-confirm-actions">
          <button type="button" class="btn btn-outline" id="booking-cancel-btn">Back</button>
          <button type="button" class="btn btn-primary" id="booking-confirm-btn"><i class="fa-solid fa-circle-check"></i> Confirm Booking</button>
        </div>
      </div>
      <div id="booking-state-loading" class="booking-state" hidden>
        <div class="booking-feedback-card">
          <div class="booking-loader" aria-hidden="true"></div>
          <h3>Booking your workshop...</h3>
          <p>Please wait while we reserve your seat.</p>
        </div>
      </div>
      <div id="booking-state-success" class="booking-state" hidden>
        <div class="booking-feedback-card booking-success-card">
          <div class="booking-success-icon"><i class="fa-solid fa-check"></i></div>
          <h3>Workshop booked successfully</h3>
          <p id="booking-success-message">Your seat has been reserved successfully. A confirmation email was sent with your booking details.</p>
          <div class="booking-zoom-note"><i class="fa-solid fa-video"></i><span>The Zoom meeting link will be sent to your email before the workshop starts.</span></div>
          <div class="booking-success-actions">
            <a href="profile.php" class="btn btn-primary"><i class="fa-solid fa-user"></i> View My Bookings</a>
            <button type="button" class="btn btn-outline" id="booking-back-btn">Back to Workshops</button>
          </div>
        </div>
      </div>
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
  <!-- ASEEL ADDITION: Full workshop details with instructor popup, What you'll learn, and Good Fit For -->
  <div id="details-overlay" hidden>
    <div id="details-modal">
      <button type="button" class="details-close-btn" aria-label="Close details modal">&times;</button>

      <div class="details-header">
        <div class="details-badges">
          <span class="details-badge-category" id="details-category">Workshop</span>
          <span class="details-badge-seats" id="details-seats-badge">Seats Available</span>
        </div>
        <h2 id="details-title"></h2>
        <p id="details-description"></p>
      </div>

      <div class="details-grid">
        <!-- Instructor with hover popup -->
        <div class="details-item">
          <span class="details-label">Instructor</span>
          <p id="details-instructor" class="instructor-hover">
            <span id="details-instructor-name"></span>
            <i class="fa-solid fa-circle-info"></i>
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
        <div class="details-item" style="grid-column:1/-1;">
          <span class="details-label">Time</span>
          <p id="details-time"></p>
        </div>
      </div>

      <!-- Two-column layout for What you'll learn + Good Fit For -->
      <div class="details-bottom-grid">

        <!-- What you'll learn — green (existing) -->
        <div id="details-learn-section">
          <div class="details-info-box details-info-box-green">
            <p class="details-info-box-label">
              <i class="fa-solid fa-graduation-cap"></i> What you'll learn
            </p>
            <ul id="details-learn"></ul>
          </div>
        </div>

        <!-- Good Fit For — purple (new) -->
        <div id="details-fit-section">
          <div class="details-info-box details-info-box-purple">
            <p class="details-info-box-label">
              <i class="fa-solid fa-users"></i> Good fit for
            </p>
            <ul id="details-fit"></ul>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    window.bookedWorkshopIds = <?= json_encode(array_map('intval', $bookedWorkshopIds)) ?>;
  </script>

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
    const response = await fetch(`../api/search_workshops.php?search=${encodeURIComponent(searchValue)}&category=${encodeURIComponent(categoryValue)}`);
    const workshops = await response.json();
    grid.innerHTML  = '';

    const bookedIds = window.bookedWorkshopIds || [];
    const isAdmin   = document.body.dataset.isAdmin === '1';

    workshops.forEach(workshop => {
      const hasImg = workshop.image_path && workshop.image_path.trim() !== '';
      const imgHtml = hasImg ? `<img src="${workshop.image_path}" alt="${workshop.title}" class="card-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />` : '';
      const placeholderStyle = hasImg ? 'style="display:none"' : '';
      const placeholderHtml = `<div class="card-img-placeholder" ${placeholderStyle}><i class="fa-solid fa-book-open"></i><span>${workshop.category_name}</span></div>`;

      const instructorName      = (workshop.instructor_name      || '').trim() || '—';
      const instructorEmail     = (workshop.instructor_email     || '').trim();
      const instructorSpecialty = (workshop.instructor_specialty || '').trim();
      const instructorExp       = (workshop.instructor_experience|| '').trim();
      const learningPoints      = (workshop.learning_points      || '').trim();
      const goodFitFor          = (workshop.good_fit_for         || '').trim();
      const hookMessage         = (workshop.hook_message         || '').trim();

      let btnHtml = '';
      if (!isAdmin) {
        if (parseInt(workshop.available_seats) <= 0) {
          btnHtml = `<button type="button" class="btn btn-secondary workshop-book-btn" disabled style="margin-top:8px;"><i class="fa-solid fa-circle-xmark"></i> Full</button>`;
        } else if (bookedIds.includes(parseInt(workshop.workshop_id))) {
          btnHtml = `<button type="button" class="btn btn-booked-already" disabled><i class="fa-solid fa-circle-check"></i> Already Booked</button>`;
        } else {
          btnHtml = `<button type="button" class="btn btn-primary book-btn workshop-book-btn"
            data-workshop-id="${workshop.workshop_id}"
            data-workshop-title="${workshop.title}"
            data-workshop-date="${workshop.workshop_date}"
            data-workshop-time="${workshop.start_time} - ${workshop.end_time}"
            data-workshop-link="#">
            <i class="fa-solid fa-calendar-days"></i> Book Workshop
          </button>`;
        }
      }

      const hookHtml = hookMessage
        ? `<p class="card-hook-message"><i class="fa-solid fa-bolt"></i> ${hookMessage}</p>`
        : '';

      const viewDetailsBtn = `<button type="button" class="btn view-details-btn"
        data-title="${workshop.title}"
        data-description="${workshop.description}"
        data-category="${workshop.category_name}"
        data-instructor="${instructorName}"
        data-instructor-email="${instructorEmail}"
        data-instructor-specialty="${instructorSpecialty}"
        data-instructor-experience="${instructorExp}"
        data-learning-points="${learningPoints}"
        data-good-fit-for="${goodFitFor}"
        data-date="${workshop.workshop_date}"
        data-time="${formatTimeStr(workshop.start_time)} – ${formatTimeStr(workshop.end_time)}"
        data-seats="${workshop.available_seats}"
        data-workshop-id="${workshop.workshop_id}">
        <i class="fa-solid fa-eye"></i> View Details
      </button>`;

      grid.innerHTML += `
        <div class="card">
          ${imgHtml}${placeholderHtml}
          <div class="card-body">
            <div class="card-icon card-icon-web"><i class="fa-solid fa-laptop-code"></i></div>
            <h3>${workshop.title}</h3>
            <p>${workshop.description}</p>
            ${hookHtml}
            <div class="card-tags" style="margin-top:16px">
              <span class="tag tag-primary">${workshop.category_name}</span>
              <span class="tag tag-secondary seats-tag" id="seats-tag-${workshop.workshop_id}">${workshop.available_seats} Seats</span>
            </div>
            ${viewDetailsBtn}
            ${btnHtml}
          </div>
        </div>`;
    });
  }

  searchInput.addEventListener('keyup',    loadWorkshops);
  categoryFilter.addEventListener('change', loadWorkshops);
  </script>

  <script src="../scripts/main.js"></script>
</body>
</html>