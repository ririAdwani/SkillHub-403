<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$basePath = '';
$currentPage = 'home';

// Load homepage statistics from the database so the hero numbers stay accurate.
$categoryCount = 0;
$workshopCount = 0;
$instructorCount = 0;

try {
    $categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $workshopCount = (int) $pdo->query('SELECT COUNT(*) FROM workshops')->fetchColumn();
    $instructorCount = (int) $pdo->query('SELECT COUNT(*) FROM instructors')->fetchColumn();
} catch (PDOException $e) {
    // Fallbacks keep the homepage readable if one stats query fails.
    $categoryCount = 5;
    $workshopCount = 10;
    $instructorCount = 5;
}

// Load up to 4 featured workshops from the database
// These replace the static hardcoded cards
$featuredWorkshops = $pdo->query("
    SELECT w.workshop_id, w.title, w.description, w.available_seats,
           w.image_path, c.category_name
    FROM workshops w
    JOIN categories c ON w.category_id = c.category_id
    ORDER BY w.created_at DESC
    LIMIT 4
")->fetchAll();

// Icons per category — fallback to book-open
$categoryIcons = [
    'Web Development' => 'fa-laptop-code',
    'UI/UX Design'    => 'fa-palette',
    'AI Tools'        => 'fa-robot',
    'Career Skills'   => 'fa-briefcase',
    'Data Analysis'   => 'fa-chart-bar',
    'Cybersecurity'   => 'fa-shield-halved',
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SkillHub – Student Workshops Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="global/main.css" />
    <link rel="stylesheet" href="global/print.css" media="print" />
  </head>
  <body>
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <!-- HERO -->
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
            <a href="pages/services.php" class="btn btn-primary">
              <i class="fa-solid fa-rocket"></i> Explore Workshops
            </a>
            <a href="pages/schedule.php" class="btn btn-outline">
              <i class="fa-solid fa-calendar-days"></i> View Schedule
            </a>
          </div>
         <div id="hero-stats">
            <div class="stat-item">
              <span class="stat-number"><?= h((string) $categoryCount) ?>+</span>
              <span class="stat-label">Categories</span>
            </div>

            <div class="stat-item">
              <span class="stat-number"><?= h((string) $workshopCount) ?>+</span>
              <span class="stat-label">Workshops</span>
            </div>

            <div class="stat-item">
              <span class="stat-number"><?= h((string) $instructorCount) ?>+</span>
              <span class="stat-label">Instructors</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- WHY SKILLHUB -->
    <section class="section-alt">
      <div class="container">
        <div class="section-header">
          <h2>Why Choose <span class="text-primary">SkillHub</span>?</h2>
          <p>We believe every student deserves access to quality skill-building opportunities beyond the classroom.</p>
        </div>
        <div class="grid-3">
          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-focus"><i class="fa-solid fa-bullseye"></i></div>
              <h3>Focused Learning</h3>
              <p>Short, targeted workshops that fit into your busy student schedule. No long commitments needed.</p>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-community"><i class="fa-solid fa-users"></i></div>
              <h3>Community Driven</h3>
              <p>Learn alongside peers and connect with industry professionals who guide every session.</p>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <div class="card-icon card-icon-ideas"><i class="fa-solid fa-lightbulb"></i></div>
              <h3>Practical Skills</h3>
              <p>Hands-on experience with real-world tools and technologies employers are looking for.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURED WORKSHOPS — loaded from the database -->
    <section class="section">
      <div class="container">
        <div class="section-header">
          <h2>Featured Workshops</h2>
          <p>Our latest workshops — updated in real time</p>
        </div>

        <div class="grid-4">
          <?php foreach ($featuredWorkshops as $fw):
            $icon = $categoryIcons[$fw['category_name']] ?? 'fa-book-open';
            $hasImg = !empty($fw['image_path']);
          ?>
          <div class="card">
            <?php if ($hasImg): ?>
              <img src="<?= h($fw['image_path']) ?>" alt="<?= h($fw['title']) ?>"
                class="card-img" style="height:120px; object-fit:cover; border-radius:12px 12px 0 0;"
                onerror="this.style.display='none'" />
            <?php endif; ?>
            <div class="card-body">
              <div class="card-icon card-icon-blue">
                <i class="fa-solid <?= $icon ?>"></i>
              </div>
              <h4><?= h($fw['title']) ?></h4>
              <p><?= h(mb_substr($fw['description'], 0, 80)) ?>...</p>
              <div class="card-tags">
                <span class="tag tag-primary"><?= h($fw['category_name']) ?></span>
                <span class="tag tag-secondary"><?= h((string)$fw['available_seats']) ?> seats</span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if (empty($featuredWorkshops)): ?>
          <!-- Fallback if no workshops in DB yet -->
          <div class="card"><div class="card-body"><p style="color:#94a3b8">No workshops yet.</p></div></div>
          <?php endif; ?>
        </div>

        <div style="text-align:center; margin-top:40px">
          <a href="pages/services.php" class="btn btn-primary">View All Workshops →</a>
        </div>
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
              <li><a href="index.php">Home</a></li>
              <li><a href="pages/services.php">Services</a></li>
              <li><a href="pages/schedule.php">Schedule</a></li>
              <li><a href="pages/video.php">Guide</a></li>
              <?php if (is_logged_in()): ?>
                <li><a href="<?= $basePath ?>pages/feedback.php">Feedback</a></li>
              <?php endif; ?>
              <li><a href="pages/about.php">About</a></li>
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

    <script src="scripts/main.js"></script>
  </body>
</html>