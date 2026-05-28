<!--
 /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->
<?php
require_once __DIR__ . '/../includes/auth.php';

$basePath = '../';
$currentPage = 'schedule';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule – SkillHub</title>
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
          <i class="fa-solid fa-calendar-days"></i> Spring 2026
        </div>
        <h1>Workshop Schedule</h1>
        <p>
          Plan your week with our organized workshop timetable. All sessions are
          designed to fit student schedules.
        </p>
        <button
          class="btn btn-outline"
          onclick="window.print()"
          style="margin-top: 20px"
        >
          <i class="fa-solid fa-print"></i> Print Schedule
        </button>
      </div>
    </div>

    <!-- ===== SCHEDULE TABLE ===== -->
    <section class="section">
      <div class="container">
        <div class="table-wrapper">
          <table id="schedule-table">
            <caption>
              Weekly Workshop Schedule
              <p>Spring 2026 Semester — All times are in local timezone</p>
            </caption>

            <!-- 
            This block defines the header row of the schedule table.
            It labels each column clearly so users can understand the
            workshop schedule information shown in the table.
          -->
            <thead>
              <tr>
                <th scope="col">Day</th>
                <th scope="col">Time</th>
                <th scope="col">Workshop</th>
                <th scope="col">Instructor</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td rowspan="2" class="day-cell">Monday</td> <!-- rowspan is used because Monday has two workshop sessions -->
                <td>10:00 AM – 12:00 PM</td>
                <td>
                  <span class="workshop-name">Web Development Basics</span>
                  <span class="workshop-meta"
                    >Room 201 &middot; Beginner Level</span
                  >
                </td>
                <td>Dr. Ahmed Hassan</td> <!-- displays the instructor name -->
              </tr>
              <tr>
                <td>2:00 PM – 4:00 PM</td>
                <td>
                  <span class="workshop-name">UI/UX Design Introduction</span>
                  <span class="workshop-meta"
                    >Lab 102 &middot; Beginner Level</span
                  >
                </td>
                <td>Prof. Lina Torres</td>
              </tr>

              <tr>
                <td class="day-cell">Tuesday</td>
                <td>11:00 AM – 1:00 PM</td>
                <td>
                  <span class="workshop-name">AI Tools for Students</span>
                  <span class="workshop-meta">Lab 305 &middot; All Levels</span>
                </td>
                <td>Dr. Sarah Kim</td>
              </tr>

              <tr>
                <td rowspan="2" class="day-cell">Wednesday</td>
                <td>9:00 AM – 11:00 AM</td>
                <td>
                  <span class="workshop-name">Career Development Skills</span>
                  <span class="workshop-meta"
                    >Room 110 &middot; All Levels</span
                  >
                </td>
                <td>Mr. James Park</td>
              </tr>
              <tr>
                <td>1:00 PM – 3:00 PM</td>
                <td>
                  <span class="workshop-name">Web Development Basics</span>
                  <span class="workshop-meta"
                    >Room 201 &middot; Intermediate</span
                  >
                </td>
                <td>Dr. Ahmed Hassan</td> <!-- displays the instructor name -->
              </tr>

              <tr>
                <td class="day-cell">Thursday</td>
                <td>10:00 AM – 12:00 PM</td>
                <td>
                  <span class="workshop-name">UI/UX Design Advanced</span>
                  <span class="workshop-meta"
                    >Lab 102 &middot; Intermediate</span
                  >
                </td>
                <td>Prof. Lina Torres</td> <!-- displays the instructor name -->
              </tr>

              <tr>
                <td class="day-cell">Friday</td>
                <td>10:00 AM – 2:00 PM</td>
                <td colspan="2">
                  <span class="workshop-name"
                    ><i class="fa-solid fa-star"></i> Combined Workshop:
                    Portfolio Building &amp; Career Fair Prep</span
                  >
                  <span class="workshop-meta"
                    >Main Hall &middot; All Instructors &middot; Special Event —
                    All students welcome</span
                  >
                </td>
              </tr>

           <tr>
            <td class="day-cell muted-text">
              Saturday
            </td>
            <td colspan="3" class="schedule-note-cell"> <!-- spans across three columns to show one centered note instead of separate cells -->
              Open Lab Hours — Self-paced practice and mentoring available
              (10 AM – 4 PM)
            </td>
          </tr>

             <tr>
              <td class="day-cell muted-text">
                Sunday
              </td>
              <td colspan="3" class="schedule-note-cell">
                No sessions — Rest &amp; review day
              </td>
            </tr>
            </tbody>
          </table>
        </div>

        <p class="schedule-note">
          <strong>Note:</strong> Schedule is subject to change. Check back
          regularly for updates.
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

    <script src="../scripts/main.js"></script>
  </body>
</html>
