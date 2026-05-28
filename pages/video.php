<!--
  /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath = '../';
$currentPage = 'video';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Guide – SkillHub</title>
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
        <div class="badge">
          <i class="fa-solid fa-clapperboard"></i> Platform Tutorial
        </div>
        <h1>How to Use SkillHub</h1>
        <p>
          Watch our step-by-step tutorial to learn how to navigate the platform,
          browse workshops, and track your progress.
        </p>
      </div>
    </div>

    <!-- ===== VIDEO SECTION ===== -->
    <section class="section">
      <div class="container" style="max-width: 860px">
        <!-- Embedded local video -->
         <!-- This block displays an embedded local video so users can watch website-related media directly on the page. -->
        <div class="video-box">
          <video controls class="embedded-video">
            <source src="../videos/skillhub-video-1.mp4" type="video/mp4" />
            Your browser does not support the video tag. <!-- fallback text shown if the browser cannot display the video -->
          </video>
        </div>

        <!-- About this video -->
        <div class="info-card" style="margin-top: 32px">
          <div class="info-card-header">
            <div class="info-icon">
              <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h2>About This Guide</h2>
          </div>
          <p>
            This tutorial video covers the complete walkthrough of the SkillHub
            platform, including:
          </p>

          <ol style="margin-top: 12px; line-height: 2">
            <li>Creating your student account and setting up your profile</li>
            <li>Browsing and filtering available workshop categories</li>
            <li>Registering for workshops and managing your schedule</li>
            <li>Tracking your learning progress and earning certificates</li>
            <li>Providing feedback and connecting with instructors</li>
          </ol>
        </div>

        <!-- AI tools note -->
        <div class="ai-note">
          <span><i class="fa-solid fa-circle-info"></i></span>
          <p style="margin: 0">
            <em
              >Note: <strong>AI tools were used</strong> in the creation of this
              video to assist with narration, script writing, editing, and
              visual effects generation.</em
            >
          </p>
        </div>

        <!-- Extra line to show underline formatting -->
        <p style="margin-top: 20px; text-align: center">
          <span class="underline"
            >This tutorial is intended for new student users of the
            platform.</span
          >
        </p>
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

    <script src="../scripts/main.js"></script>
  </body>
</html>
