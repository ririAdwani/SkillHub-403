<!--
 /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath = '../';
$currentPage = 'services';
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
        <div class="grid-2">
          <!-- Card 1 -->
          <div class="card">
            <img
              src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=600&h=220&fit=crop"
              alt="Web Development"
              class="card-img"
            />
            <div class="card-body">
              <div class="card-icon card-icon-web">
                <i class="fa-solid fa-laptop-code"></i>
              </div>
              <h3>Web Development Basics</h3>
              <p>
                Learn the fundamentals of
                <strong>HTML, CSS, and JavaScript</strong>. Build responsive
                websites from scratch and understand how the web works. Perfect
                for students starting their coding journey.
              </p>
              <ul>
                <li>HTML structure and semantic elements</li>
                <li>CSS styling, layouts, and Flexbox</li>
                <li>JavaScript basics and DOM manipulation</li>
                <li>Responsive design principles</li>
              </ul>
              <div class="card-tags" style="margin-top: 16px">
                <span class="tag tag-primary">Beginner</span>
                <span class="tag tag-secondary">6 Hours</span>
              </div>
              <button
                class="btn btn-primary book-btn"
                style="margin-top: 18px; width: 100%"
                onclick="
                  openBookingModal(
                    'Web Development Basics',
                    'Monday & Wednesday',
                    '10:00 AM – 12:00 PM',
                    'https://skillhub.edu/live/web-dev',
                  )
                "
              >
                <i class="fa-solid fa-calendar-days"></i> Book Workshop
              </button>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="card">
            <img
              src="https://images.unsplash.com/photo-1561070791-2526d30994b5?w=600&h=220&fit=crop"
              alt="UI/UX Design"
              class="card-img"
            />
            <div class="card-body">
              <div class="card-icon card-icon-uiux"> <!-- shows the workshop icon with colors related to the UI/UX category -->
                <i class="fa-solid fa-palette"></i>
              </div>
              <h3>UI/UX Design Introduction</h3>
              <p>
                Discover the principles of
                <em>user interface and user experience design</em>. Learn to use
                modern design tools, create wireframes, and build beautiful
                prototypes that users love.
              </p>
              <ul>
                <li>Design thinking and user research</li>
                <li>Wireframing and prototyping</li>
                <li>Color theory and typography</li>
                <li>Figma basics and component design</li>
              </ul>
              <div class="card-tags" style="margin-top: 16px">
                <span class="tag tag-primary">Beginner</span>
                <span class="tag tag-secondary">8 Hours</span>
              </div>
              <button
                class="btn btn-primary book-btn"
                style="margin-top: 18px; width: 100%"
                onclick="
                  openBookingModal(
                    'UI/UX Design Introduction',
                    'Monday & Thursday',
                    '2:00 PM – 4:00 PM',
                    'https://skillhub.edu/live/ui-ux',
                  )
                "
              >
                <i class="fa-solid fa-calendar-days"></i> Book Workshop
              </button>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="card">
            <img
              src="https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&h=220&fit=crop"
              alt="AI Tools"
              class="card-img"
            />
            <div class="card-body">
              <div class="card-icon card-icon-ai"> <!-- shows the workshop icon with colors related to the AI category -->
                <i class="fa-solid fa-robot"></i>
              </div>
              <h3>AI Tools for Students</h3>
              <p>
                Explore how <strong>artificial intelligence</strong> can boost
                your productivity. Learn to use AI assistants, generate content,
                analyze data, and automate repetitive tasks effectively.
              </p>
              <ul>
                <li>Introduction to AI and prompt engineering</li>
                <li>Using ChatGPT and Copilot effectively</li>
                <li>AI-powered image and video generation</li>
                <li>Ethical use of AI in academics</li>
              </ul>
              <div class="card-tags" style="margin-top: 16px">
                <span class="tag tag-primary">All Levels</span>
                <span class="tag tag-secondary">4 Hours</span>
              </div>
              <button
                class="btn btn-primary book-btn"
                style="margin-top: 18px; width: 100%"
                onclick="
                  openBookingModal(
                    'AI Tools for Students',
                    'Tuesday',
                    '11:00 AM – 1:00 PM',
                    'https://skillhub.edu/live/ai-tools',
                  )
                "
              >
                <i class="fa-solid fa-calendar-days"></i> Book Workshop
              </button>
            </div>
          </div>

          <!-- Card 4 -->
          <div class="card">
            <img
              src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=600&h=220&fit=crop"
              alt="Career Development"
              class="card-img"
            />
            <div class="card-body">
              <div class="card-icon card-icon-career"> <!-- shows the workshop icon with colors related to the career skills category -->
                <i class="fa-solid fa-briefcase"></i>
              </div>
              <h3>Career Development Skills</h3>
              <p>
                Build a standout portfolio, master
                <em>interview techniques</em>, and develop professional
                communication skills. Prepare yourself for a successful career
                after graduation.
              </p>
              <ul>
                <li>Building a professional portfolio</li>
                <li>Resume writing and LinkedIn profile</li>
                <li>Interview preparation and mock sessions</li>
                <li>Networking and professional communication</li>
              </ul>
              <div class="card-tags" style="margin-top: 16px">
                <span class="tag tag-primary">All Levels</span>
                <span class="tag tag-secondary">5 Hours</span>
              </div>
              <button
                class="btn btn-primary book-btn"
                style="margin-top: 18px; width: 100%"
                onclick="
                  openBookingModal(
                    'Career Development Skills',
                    'Wednesday',
                    '9:00 AM – 11:00 AM',
                    'https://skillhub.edu/live/career',
                  )
                "
              >
                <i class="fa-solid fa-calendar-days"></i> Book Workshop
              </button>
            </div>
          </div>
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
              <li><a href="../index.html">Home</a></li>
              <li><a href="services.html">Services</a></li>
              <li><a href="schedule.html">Schedule</a></li>
              <li><a href="video.html">Guide</a></li>
              <li><a href="feedback.html">Feedback</a></li>
              <li><a href="about.html">About</a></li>
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
        It shows the workshop name, date, time, joining link, and a short note to inform
        the user that the booking details will be sent to their email after confirmation.
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
              >Please enter a valid email address.</span
            >
          </div>

          <!-- 
            The block informs the user about the information they will receive by email
            after completing the workshop booking. It lists the main booking details and
            supporting materials that will be sent for confirmation and preparation.
          -->
          <div id="booking-will-receive">
            <p>
              <i class="fa-solid fa-envelope-open-text"></i>
              <strong
                >After booking, you will receive an email containing:</strong
              >
            </p>
            <ul>
              <li>
                <i class="fa-solid fa-circle-check"></i> Your booking
                confirmation
              </li>
              <li>
                <i class="fa-solid fa-calendar-days"></i> Workshop date and time
              </li>
              <li>
                <i class="fa-solid fa-link"></i> Direct link to join the
                workshop
              </li>
              <li>
                <i class="fa-solid fa-clipboard-list"></i> Workshop syllabus and
                preparation notes
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

    <script src="../scripts/main.js"></script>
  </body>
</html>
