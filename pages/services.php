<!--
 /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath = '../';
$currentPage = 'services';

require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("
    SELECT workshops.*, categories.category_name
    FROM workshops
    JOIN categories
    ON workshops.category_id = categories.category_id
");

$workshops = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Services – SkillHub</title>
    <!-- Font Awesome CDN -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../global/main.css" />
    <link rel="stylesheet" href="../global/print.css" media="print" />
  </head>

  <!-- Attributes inside Body tag lets main.js know if the user is logged in -->
  <body
    data-logged-in="<?= is_logged_in() ? '1' : '0' ?>"
    data-user-name="<?= h($_SESSION['full_name'] ?? '') ?>"
    data-user-email="<?= h($_SESSION['email'] ?? '') ?>"
  >
    <!-- ===== HEADER ===== -->
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <!-- ===== PAGE HERO ===== -->
    <div class="page-hero services-hero">
        <div class="container">
        <div class="badge"><i class="fa-solid fa-box"></i> Our Workshops</div>
        <h1>Workshop Categories</h1>
        <p>
          Choose from our curated selection of workshops designed specifically
          for students who want to build practical, in-demand skills.
        </p>
      </div>
    </div>

    <!-- ===== WORKSHOP CARDS SECTION ===== -->
<section class="section services-section">
        <div class="container">
        <!-- Search and category filter for workshop browsing. -->
        <div class="services-search-panel">
          <div class="services-search-copy">
            <h2>Search Workshops</h2>
            <p>Find workshops by title, description, or category.</p>
          </div>

          <div class="services-search-controls">
            <input
              type="text"
              id="searchInput"
              placeholder="Search workshops..."
            />

            <select id="categoryFilter">
              <option value="">All Categories</option>
              <option value="Web Development">Web Development</option>
              <option value="UI/UX Design">UI/UX Design</option>
              <option value="Data Analysis">Data Analysis</option>
              <option value="Cybersecurity">Cybersecurity</option>
            </select>
          </div>
        </div>
        <div class="grid-2">
              <?php foreach ($workshops as $workshop): ?>

    <div class="card">

        <img
            src="<?php echo htmlspecialchars($workshop['image_path']); ?>"
            alt="<?php echo $workshop['title']; ?>"
            class="card-img"
        />

        <div class="card-body">

            <div class="card-icon card-icon-web">
                <i class="fa-solid fa-laptop-code"></i>
            </div>

            <h3>
                <?php echo $workshop['title']; ?>
            </h3>

            <p>
                <?php echo $workshop['description']; ?>
            </p>

            <div class="card-tags" style="margin-top: 16px">

                <span class="tag tag-primary">
                    <?php echo $workshop['category_name']; ?>
                </span>

                <span class="tag tag-secondary">
                    <?php echo $workshop['available_seats']; ?> Seats
                </span>

            </div>

          <button
            type="button"
            class="btn btn-primary book-btn workshop-book-btn"
            data-workshop-id="<?= h((string) $workshop['workshop_id']) ?>"
            data-workshop-title="<?= h($workshop['title']) ?>"
            data-workshop-date="<?= h($workshop['workshop_date']) ?>"
            data-workshop-time="<?= h($workshop['start_time'] . ' - ' . $workshop['end_time']) ?>"
            data-workshop-link="#"
          >
            <i class="fa-solid fa-calendar-days"></i>
            Book Workshop
          </button>

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

    <!-- ===== FOOTER ===== -->
    <footer>
      <div class="container">
        <div id="footer-grid">
          <div id="footer-brand">
            <h3><i class="fa-solid fa-book-open"></i> SkillHub</h3>
            <p>
              Empowering students to discover and develop new skills through
              short, focused workshops.
            </p>
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
            <address>
              Email: info@skillhub.edu<br />
            </address>
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
        <p id="modal-badge">
          <i class="fa-solid fa-calendar-days"></i> Workshop Booking
        </p>
        <h2 id="modal-title">Confirm Booking</h2>
      </div>

      <button
        type="button"
        id="modal-close"
        aria-label="Close booking window"
      >
        &times;
      </button>
    </div>

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
        <button type="button" class="btn btn-outline" id="booking-cancel-btn">
          Back
        </button>

        <button type="button" class="btn btn-primary" id="booking-confirm-btn">
          <i class="fa-solid fa-circle-check"></i>
          Confirm Booking
        </button>
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
        <div class="booking-success-icon">
          <i class="fa-solid fa-check"></i>
        </div>

        <h3>Workshop booked successfully</h3>

        <p>
          Your seat has been reserved successfully.
          A confirmation email was sent with your booking details.
        </p>

        <div class="booking-zoom-note">
          <i class="fa-solid fa-video"></i>
          <span>The Zoom meeting link for this workshop will be sent to your email before it starts.</span>
        </div>

        <div class="booking-success-actions">
          <a href="profile.php" class="btn btn-primary">
            <i class="fa-solid fa-user"></i>
            View My Bookings
          </a>

          <button type="button" class="btn btn-outline" id="booking-back-btn">
            Back to Workshops
          </button>
        </div>
      </div>
    </div>

    <div id="booking-state-error" class="booking-state" hidden>
      <div class="booking-feedback-card booking-error-card">
        <div class="booking-error-icon">
          <i class="fa-solid fa-xmark"></i>
        </div>

        <h3>Booking failed</h3>
        <p id="booking-error-message">Something went wrong while booking this workshop.</p>

        <button type="button" class="btn btn-outline" id="booking-error-back-btn">
          Back
        </button>
      </div>
    </div>
  </div>
</div>

    <script>
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const grid = document.querySelector('.grid-2');

async function loadWorkshops() {
    const searchValue = searchInput.value;
    const categoryValue = categoryFilter.value;

    const response = await fetch(
        `../api/search_workshops.php?search=${encodeURIComponent(searchValue)}&category=${encodeURIComponent(categoryValue)}`
    );

    const workshops = await response.json();

    grid.innerHTML = '';

    workshops.forEach(workshop => {
        grid.innerHTML += `
            <div class="card">
                <img
                    src="${workshop.image_path}"
                    alt="${workshop.title}"
                    class="card-img"
                />

                <div class="card-body">
                    <div class="card-icon card-icon-web">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>

                    <h3>${workshop.title}</h3>
                    <p>${workshop.description}</p>

                    <div class="card-tags" style="margin-top: 16px">
                        <span class="tag tag-primary">${workshop.category_name}</span>
                        <span class="tag tag-secondary">${workshop.available_seats} Seats</span>
                    </div>

                              <button
              class="btn btn-primary book-btn"
              style="margin-top: 18px; width: 100%"
              onclick="openBookingModal(
    '${workshop.workshop_id}',
    '${workshop.title}',
    '${workshop.workshop_date}',
    '${workshop.start_time} - ${workshop.end_time}',
    '#'
)"
          >
              <i class="fa-solid fa-calendar-days"></i>
              Book Workshop
          </button>
                </div>
            </div>
        `;
    });
}

searchInput.addEventListener('keyup', loadWorkshops);
categoryFilter.addEventListener('change', loadWorkshops);
</script>
    
    <script src="../scripts/main.js"></script>
 

  </body>
</html>
