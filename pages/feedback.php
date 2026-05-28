<!--
/* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */
-->

<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$basePath = '../';
$currentPage = 'feedback';
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback – SkillHub</title>
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
          <i class="fa-solid fa-comments"></i> We Value Your Input
        </div>
        <h1>Share Your Feedback</h1>
        <p>
          Help us improve SkillHub by sharing your experience, ratings, and
          suggestions.
        </p>
      </div>
    </div>

    <!-- ===== FEEDBACK FORM ===== -->
    <section class="section">
      <div class="container" style="max-width: 760px">
        <!-- 
        This block starts the feedback form and contains the user’s basic contact inputs.
        It collects the full name and email address, marks them as required fields,
        and includes placeholder error messages that can be shown during validation.
      -->
        <form id="feedback-form" class="form-card" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label for="name"
                >Full Name <span class="required">*</span></label
              >
              <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your full name"
                autocomplete="name"
              />
              <span class="error-msg" id="name-error"
                >Please enter your full name.</span
              >
            </div>

            <div class="form-group">
              <label for="email"
                >Email Address <span class="required">*</span></label
              >
              <input
                type="email"
                id="email"
                name="email"
                placeholder="your.email@example.com"
                autocomplete="email"
              />
              <span class="error-msg" id="email-error"
                >Please enter a valid email.</span
              >
            </div>
          </div>

          <!-- 
          This block continues the feedback form by collecting the user’s overall rating
          and workshop interests. It provides radio buttons for selecting one required
          rating option and checkboxes for choosing one or more workshop categories of interest.
        -->
          <div class="form-group">
            <label>Overall Rating <span class="required">*</span></label>
            <div class="radio-group">
              <label class="radio-label rating-good">
                <input type="radio" name="rating" value="Good" />
                <i class="fa-solid fa-face-smile"></i> Good
              </label>

              <label class="radio-label rating-average">
                <input type="radio" name="rating" value="Average" />
                <i class="fa-solid fa-face-meh"></i> Average
              </label>

              <label class="radio-label rating-poor">
                <input type="radio" name="rating" value="Poor" />
                <i class="fa-solid fa-face-frown"></i> Poor
              </label>
            </div>
            <span class="error-msg" id="rating-error">Please select a rating.</span>
          </div>

          <div class="form-group">
            <label>Interested Workshops</label>
            <p style="font-size: 0.82rem; margin-bottom: 10px">
              Select all that apply
            </p>
            <div class="checkbox-grid">
              <label class="checkbox-label">
                <input type="checkbox" name="workshops" value="web-dev" />
                <i class="fa-solid fa-laptop-code"></i> Web Development Basics
              </label>
              <label class="checkbox-label">
                <input type="checkbox" name="workshops" value="ui-ux" />
                <i class="fa-solid fa-palette"></i> UI/UX Design Introduction
              </label>
              <label class="checkbox-label">
                <input type="checkbox" name="workshops" value="ai-tools" />
                <i class="fa-solid fa-robot"></i> AI Tools for Students
              </label>
              <label class="checkbox-label">
                <input type="checkbox" name="workshops" value="career" />
                <i class="fa-solid fa-briefcase"></i> Career Development Skills
              </label>
            </div>
          </div>

          <!-- 
          The block completes the feedback form with optional user input fields and the submit button.
          It allows the user to choose a preferred session time, write additional comments, and then
          submit the form to send their feedback.
        -->
          <div class="form-group">
            <label for="preference">Preferred Session Time</label>
            <select id="preference" name="preference">
              <option value="">Select your preferred time...</option>
              <option value="morning">Morning Sessions (9 AM – 12 PM)</option>
              <option value="afternoon">
                Afternoon Sessions (1 PM – 4 PM)
              </option>
              <option value="evening">Evening Sessions (5 PM – 8 PM)</option>
              <option value="weekend">Weekend Sessions Only</option>
              <option value="no-pref">No Preference</option>
            </select>
          </div>

          <div class="form-group">
            <label for="comments">Additional Comments</label>
            <textarea
              id="comments"
              name="comments"
              placeholder="Share any suggestions, feedback, or ideas to help us improve SkillHub..."
            ></textarea>
          </div>

          <div style="padding-top: 8px">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-paper-plane"></i> Submit Feedback
            </button>
          </div>
        </form>

        <div id="success-message">
          <div style="font-size: 3rem; margin-bottom: 16px">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <h3>Thank You!</h3>
          <p style="color: var(--text-light)">
            Your feedback has been submitted successfully. We appreciate your
            input!
          </p>
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
