<!--
  /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->

<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath = '../';
$currentPage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About – SkillHub</title>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../global/main.css" />
  <link rel="stylesheet" href="../global/print.css" media="print" />
</head>
<body>

  <!-- ===== HEADER ===== -->
 <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <!-- ===== PAGE HERO ===== -->
  <div class="page-hero">
    <div class="container">
      <div class="badge"><i class="fa-solid fa-circle-info"></i> About Us</div>
      <h1>About SkillHub</h1>
      <p>Learn about our mission, who we serve, and what makes SkillHub the go-to platform for student skill development.</p>
    </div>
  </div>

  <!-- ===== ABOUT CONTENT ===== -->
  <section class="section">
    <div class="container" style="max-width:860px;">

      <div class="info-card">
        <div class="info-card-header">
          <div class="info-icon"><i class="fa-solid fa-face-smile"></i></div>
          <h2>Who We Are</h2>
        </div>
        <p style="font-size:1.05rem; line-height:1.85; color:var(--text);">
          <strong>SkillHub</strong> is a student-focused educational platform that connects learners with short, practical workshops designed to build real-world skills.
          Founded with the belief that education extends beyond the classroom, we provide accessible, hands-on learning opportunities that complement traditional academic programs and prepare students for the modern workforce.
        </p>
      </div>

      <!-- 
      This block presents the mission of the platform in a highlighted information card.
      It explains the main goals of the website, which are helping students find short workshops,
      improve their practical skills, and support continuous learning.
      -->
      <div class="info-card" style="background:linear-gradient(135deg,#EBF4FF,#ECFDF5);">
        <div class="info-card-header">
          <div class="info-icon"><i class="fa-solid fa-rocket"></i></div>
          <h2>Our Mission</h2>
        </div>
        <p>We are dedicated to empowering the next generation of skilled professionals through:</p>

        <ul class="mission-list">
          <li>
            <div class="bullet"></div>
            <p style="margin:0;">
              <strong>Helping students discover short workshops</strong> that teach in-demand skills in compact, focused sessions outside their regular curriculum.
            </p>
          </li>
          <li>
            <div class="bullet" style="background:var(--secondary);"></div>
            <p style="margin:0;">
              <strong>Improving skills outside regular classes</strong> by offering supplementary learning that bridges the gap between theory and real-world practice.
            </p>
          </li>
          <li>
            <div class="bullet"></div>
            <p style="margin:0;">
              <strong>Encouraging continuous learning</strong> and fostering a growth mindset among students at every level of their academic journey.
            </p>
          </li>
        </ul>
      </div>

      <div class="info-card">
        <div class="info-card-header">
          <div class="info-icon"><i class="fa-solid fa-users"></i></div>
          <h2>Who It's For</h2>
        </div>
        <p>SkillHub is designed for:</p>

        <ul class="mission-list">
          <li>
            <div class="bullet"></div>
            <p style="margin:0;">
              <span class="bold">University students</span> looking to enhance their academic journey with practical skill workshops
            </p>
          </li>
          <li>
            <div class="bullet" style="background:var(--secondary);"></div>
            <p style="margin:0;">
              <span class="bold">Beginners</span> exploring technology, design, and professional skills for the very first time
            </p>
          </li>
          <li>
            <div class="bullet"></div>
            <p style="margin:0;">
              <span class="bold">Students who want to build practical skills</span> that complement their degree and improve their employability
            </p>
          </li>
        </ul>
      </div>

      <!-- 
      This block introduces the main workshop categories available on the platform.
      It groups the offered workshops into four skill areas to help users quickly
      understand the types of topics they can explore and learn.
    -->
      <div class="info-card">
        <div class="info-card-header">
          <div class="info-icon"><i class="fa-solid fa-book-open"></i></div>
          <h2>Workshop Categories</h2>
        </div>
        <p>We offer a diverse range of workshops across four key skill areas:</p>

        <div class="category-grid">
          <div class="category-item">
            <div style="font-size:1.5rem;"><i class="fa-solid fa-laptop-code"></i></div>
            <div>
              <h5 style="margin-bottom:2px;">Web Development</h5>
              <p style="font-size:0.8rem; margin:0;">HTML, CSS, JavaScript</p>
            </div>
          </div>
          <div class="category-item">
            <div style="font-size:1.5rem;"><i class="fa-solid fa-palette"></i></div>
            <div>
              <h5 style="margin-bottom:2px;">UI/UX Design</h5>
              <p style="font-size:0.8rem; margin:0;">Figma, Prototyping</p>
            </div>
          </div>
          <div class="category-item">
            <div style="font-size:1.5rem;"><i class="fa-solid fa-robot"></i></div>
            <div>
              <h5 style="margin-bottom:2px;">AI Tools</h5>
              <p style="font-size:0.8rem; margin:0;">ChatGPT, Automation</p>
            </div>
          </div>
          <div class="category-item">
            <div style="font-size:1.5rem;"><i class="fa-solid fa-briefcase"></i></div>
            <div>
              <h5 style="margin-bottom:2px;">Career Skills</h5>
              <p style="font-size:0.8rem; margin:0;">Resume, Interviews</p>
            </div>
          </div>
        </div>
      </div>

      <blockquote>
        "Education is not preparation for life; education is life itself."
        <cite>— John Dewey</cite>
      </blockquote>

    </div>
  </section>

  <!-- ===== FOOTER ===== -->
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
            <li><a href="../index.html">Home</a></li>
            <li><a href="services.html">Services</a></li>
            <li><a href="schedule.html">Schedule</a></li>
            <li><a href="video.html">Video</a></li>
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

  <script src="../scripts/main.js"></script>
</body>
</html>