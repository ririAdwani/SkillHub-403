<!--
 /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath = '../';
$currentPage = 'services';
/*----------*/
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("
    SELECT workshops.*, categories.category_name
    FROM workshops
    JOIN categories
    ON workshops.category_id = categories.category_id
");

$workshops = $stmt->fetchAll();
/*----------*/
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
  <body>
    <!-- ===== HEADER ===== -->
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <!-- ===== PAGE HERO ===== -->
    <div class="page-hero">
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
    <section class="section">
      <div class="container">
         <div style="margin-bottom: 30px;">
          <h2 style="margin-bottom: 12px;">
    Search Workshops
</h2>

<p style="margin-bottom: 20px; color: #666;">
    Find workshops by title, description, or category.
</p>
    
    <input
        type="text"
        id="searchInput"
        placeholder="Search workshops..."
        style="
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 16px;
        "
    />

    <select
    id="categoryFilter"
    style="
        width: 100%;
        margin-top: 14px;
        padding: 14px;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 16px;
    "
>
    <option value="">All Categories</option>
    <option value="Web Development">Web Development</option>
    <option value="UI/UX Design">UI/UX Design</option>
    <option value="Data Analysis">Data Analysis</option>
    <option value="Cybersecurity">Cybersecurity</option>
</select>

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
        class="btn btn-primary book-btn"
        style="margin-top: 18px; width: 100%"
        onclick="openBookingModal(
    '<?php echo htmlspecialchars($workshop['workshop_id']); ?>',
    '<?php echo htmlspecialchars($workshop['title']); ?>',
    '<?php echo htmlspecialchars($workshop['workshop_date']); ?>',
    '<?php echo htmlspecialchars($workshop['start_time'] . ' - ' . $workshop['end_time']); ?>',
    '#'
)"
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
              SkillHub Platform<br />
              University Campus, Building A<br />
              Email: info@skillhub.edu<br />
              Phone: +1 (555) 123-4567
            </address>
          </div>
        </div>
      </div>
      <div id="footer-copyright">
        &copy; 2026 SkillHub – Student Workshops Platform. All rights reserved.
      </div>
    </footer>

    <!-- 
  Booking modal displayed when the user selects a workshop.
  It allows users to enter their booking information,
  upload supporting files, and confirm their reservation.
-->
    <!-- ===== BOOKING MODAL ===== -->
    <div id="booking-overlay" style="display: none">
      <div id="booking-modal">
        <div id="modal-header">
          <div>
            <p id="modal-badge">
              <i class="fa-solid fa-calendar-days"></i> Workshop Booking
            </p>
            <h2 id="modal-title">Reserve Your Seat</h2>
          </div>
          <button
            id="modal-close"
            onclick="closeBookingModal()"
            aria-label="Close"
          >
            &times;
          </button>
        </div>

     <!-- 
  Displays the selected workshop information inside the booking modal.
  Users can review workshop details, enter booking information,
  and optionally upload supporting files before confirming the reservation.
-->

        <div id="modal-workshop-info">
          <p id="info-name"></p>
          <div id="info-details">
            <span id="info-date"></span>
            <span id="info-time"></span>
          </div>
          <a id="info-link" href="#" target="_blank"></a>
          <p id="info-email-note">
            <i class="fa-solid fa-envelope"></i> The workshop details, date,
            time, and joining link will be sent to your email upon booking.
          </p>
        </div>

        <form id="booking-form" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label for="b-firstname"
                >First Name <span class="required">*</span></label
              >
              <input
                type="text"
                id="b-firstname"
                placeholder="e.g. Sara"
                autocomplete="given-name"
              />
              <span class="error-msg" id="b-firstname-error"
                >First name is required.</span
              >
            </div>

          <div class="form-group">
              <label for="b-lastname">
                Last Name
                <span class="optional-label">(optional)</span> <!-- shows that this field is not required -->
              </label>
              <input
                type="text"
                id="b-lastname"
                placeholder="e.g. Ahmed"
                autocomplete="family-name"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="b-email"
              >Email Address <span class="required">*</span></label
            > <!-- label for the required email input field -->
            <input
              type="email"
              id="b-email"
              placeholder="your.email@example.com"
              autocomplete="email"
            />
            <span class="error-msg" id="b-email-error"
              >Please enter a valid email address.</span>

                  <div class="form-group" style="margin-top: 18px">

      <label for="supporting-file">
        Upload CV / Certificate
        <span class="optional-label">(optional)</span>
      </label>

      <input
        type="file"
        id="supporting-file"
        name="supporting_file"
        accept=".pdf,.jpg,.jpeg,.png"
      />

      <small style="color: #666">
        Allowed files: PDF, JPG, PNG (Max 2MB)
      </small>

    </div>
          </div>

        <!-- 
  Optional file upload field.
  Allows users to upload supporting documents such as
  certificates, CVs, or portfolio files with validation rules.
-->
          <div id="booking-will-receive">
  <p>
    <i class="fa-solid fa-file-arrow-down"></i>
    <strong>
      After booking, you can download a booking summary containing:
    </strong>
  </p>

  <ul>
    <li>
      <i class="fa-solid fa-circle-check"></i> Your booking confirmation
    </li>
    <li>
      <i class="fa-solid fa-calendar-days"></i> Workshop date and time
    </li>
    <li>
      <i class="fa-solid fa-chair"></i> Workshop availability status
    </li>
    <li>
      <i class="fa-solid fa-file"></i> Uploaded file confirmation
    </li>
  </ul>
</div>
          <button
            type="submit"
            class="btn btn-primary"
            style="width: 100%; justify-content: center; margin-top: 8px"
          >
            <i class="fa-solid fa-circle-check"></i> Confirm Booking
          </button>
        </form>
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
