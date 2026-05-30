<!--
  /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->

<?php
require_once __DIR__ . '/includes/auth.php';

$basePath = '';
$currentPage = 'home';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SkillHub – Student Workshops Platform</title>
    <!-- Font Awesome CDN -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="global/main.css" />
    <link rel="stylesheet" href="global/print.css" media="print" />
  </head>
  <body>
    <!-- ===== HEADER ===== -->
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <!-- ===== HERO SECTION ===== -->
    <section id="home-hero">
      <div class="container">
        <div id="hero-content">
          <div class="badge">
            <i class="fa-solid fa-sparkles"></i> Student Workshops Platform
          </div>

          <h1 id="hero-title">
            Level Up Your Skills<br />with
            <span class="highlight">SkillHub</span>
          </h1>

          <p id="hero-subtitle">
            Discover short, focused workshops designed to help students build
            practical skills in web development, design, AI, and career growth.
          </p>

          <div id="hero-actions">
            <a href="pages/services.php" class="btn btn-primary"
              ><i class="fa-solid fa-rocket"></i> Explore Workshops</a
            >
            <a href="pages/schedule.php" class="btn btn-outline"
              ><i class="fa-solid fa-calendar-days"></i> View Schedule</a
            >
          </div>

          <!-- 
            This block displays quick website statistics in the hero section.
            It shows summary numbers about the available workshops, participating
            students, and instructors to give users a fast overview of the platform.
          -->
          <div id="hero-stats">
            <div class="stat-item">
              <span class="stat-number">20+</span>
              <span class="stat-label">Workshops</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">500+</span>
              <span class="stat-label">Students</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">15+</span>
              <span class="stat-label">Instructors</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== PURPOSE SECTION ===== -->
    <section class="section-alt">
      <div class="container">
        <div class="section-header">
          <h2>
            Why Choose <span class="text-primary">SkillHub</span>?  <!-- highlights the website name using the main theme color -->
          </h2>
          <p>
            We believe every student deserves access to quality skill-building
            opportunities beyond the classroom.
          </p>
        </div>

        <div class="grid-3">
         <div class="card">
          <div class="card-body">
            <div class="card-icon card-icon-focus">
              <i class="fa-solid fa-bullseye"></i>
            </div>
            <h3>Focused Learning</h3>
            <p>
              Short, targeted workshops that fit into your busy student
              schedule. No long commitments needed.
            </p>
          </div>
        </div>

          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-community"> <!-- shows the card icon with a light background style -->
                <i class="fa-solid fa-users"></i>
              </div>
              <h3>Community Driven</h3>
              <p>
                Learn alongside peers and connect with industry professionals
                who guide every session.
              </p>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-ideas"> <!-- shows the card icon with a light background style -->
              <i class="fa-solid fa-lightbulb"></i>
            </div>
              <h3>Practical Skills</h3>
              <p>
                Hands-on experience with real-world tools and technologies
                employers are looking for.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FEATURED WORKSHOPS ===== -->
    <section class="section">
      <div class="container">
        <div class="section-header">
          <h2>Featured Workshops</h2>
          <p>Explore our most popular workshop categories</p>
        </div>

        <div class="grid-4">
          <div class="card">
            <div class="card-body">
             <div class="card-icon card-icon-blue"> <!-- shows the card icon with a light blue background style -->
              <i class="fa-solid fa-laptop-code"></i>
            </div>
              <h4>Web Development</h4>
              <p>Learn HTML, CSS & JavaScript fundamentals</p>
              <div class="card-tags">
                <span class="tag tag-primary">Beginner</span>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-purple"> <!-- shows the card icon with a light purple background style -->
                <i class="fa-solid fa-palette"></i>
              </div>
              <h4>UI/UX Design</h4>
              <p>Create beautiful, user-centered interfaces</p>
              <div class="card-tags">
                <span class="tag tag-secondary">Creative</span>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-green"> 
                <i class="fa-solid fa-robot"></i>
              </div>
              <h4>AI Tools</h4>
              <p>Harness AI to boost your productivity</p>
              <div class="card-tags">
                <span class="tag tag-primary">Trending</span>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-yellow"> <!-- shows the card icon with a light yellow background style -->
                <i class="fa-solid fa-briefcase"></i>
              </div>
              <h4>Career Skills</h4>
              <p>Build your portfolio and interview skills</p>
              <div class="card-tags">
                <span class="tag tag-secondary">Essential</span>
              </div>
            </div>
          </div>
        </div>

        <div style="text-align: center; margin-top: 40px">
          <a href="pages/services.php" class="btn btn-primary"
            >View All Workshops →</a
          >
        </div>
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
              <li><a href="index.php">Home</a></li>
              <li><a href="pages/services.php">Services</a></li>
              <li><a href="pages/schedule.php">Schedule</a></li>
              <li><a href="pages/video.php">Guide</a></li>
              <li><a href="pages/feedback.php">Feedback</a></li>
              <li><a href="pages/about.php">About</a></li>
            </ul>
          </div>

          <div id="footer-contact">
            <p class="footer-heading">Contact</p>
            <address>
              SkillHub Platform<br />
              Email: info@skillhub.edu<br />
            </address>
          </div>
        </div>
      </div>

      <div id="footer-copyright">
        &copy; 2026 SkillHub – Student Workshops Platform. All rights reserved.
      </div>
    </footer>

    <script src="scripts/main.js"></script>
  </body>
</html>
