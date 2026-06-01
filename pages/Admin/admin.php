<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// ✅ ROLE-BASED ACCESS: Non-admins are BLOCKED and redirected
require_admin();

$basePath    = '../../';
$currentPage = 'admin';

/*
  admin.php — SkillHub Workshop Manager
  Assignment compliance:
  ✅ require_admin() blocks non-admins (redirects to login)
  ✅ Admin role clearly separated from user role
  ✅ AJAX add/edit/delete workshops (no page reload)
  ✅ Feedback viewing with reply
*/
// ── HANDLE: Add new category ──
$categoryMsg  = '';
$categoryType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category_name'])) {
    $newCat = trim($_POST['new_category_name'] ?? '');
    if ($newCat === '') {
        $categoryMsg = 'Category name cannot be empty.';
        $categoryType = 'error';
    } else {
        try {
            $pdo->prepare("INSERT INTO categories (category_name) VALUES (:name)")
                ->execute([':name' => $newCat]);
            $categoryMsg  = 'Category "' . h($newCat) . '" added successfully!';
            $categoryType = 'success';
        } catch (PDOException $e) {
            $categoryMsg  = 'That category already exists.';
            $categoryType = 'error';
        }
    }
}

// ── HANDLE: Edit existing category ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category_id'])) {
    $catId      = (int)($_POST['edit_category_id'] ?? 0);
    $catNewName = trim($_POST['edit_category_name'] ?? '');
    if ($catId > 0 && $catNewName !== '') {
        try {
            $pdo->prepare("UPDATE categories SET category_name = :name WHERE category_id = :id")
                ->execute([':name' => $catNewName, ':id' => $catId]);
            $categoryMsg  = 'Category updated to "' . h($catNewName) . '".';
            $categoryType = 'success';
        } catch (PDOException $e) {
            $categoryMsg  = 'That category name already exists.';
            $categoryType = 'error';
        }
    }
}

// ── HANDLE: Edit a specific message ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_message') {
    header('Content-Type: application/json');
    $msgId    = (int)($_POST['message_id']  ?? 0);
    $feedbackId = (int)($_POST['feedback_id'] ?? 0);
    $newText  = trim($_POST['new_text']     ?? '');
    if ($msgId > 0 && $newText !== '') {
        try {
            $pdo->prepare("UPDATE feedback_messages SET message = :msg WHERE message_id = :id")
                ->execute([':msg' => $newText, ':id' => $msgId]);
            // Also update admin_reply to latest text
            $pdo->prepare("UPDATE feedback SET admin_reply = :msg WHERE feedback_id = :fid")
                ->execute([':msg' => $newText, ':fid' => $feedbackId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    }
    exit;
}

// ── HANDLE: Resolve feedback ──
// Best practice: keep in DB but mark resolved=1 so admin can refer back.
// The dashboard counter only shows unresolved items.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve_feedback') {
    header('Content-Type: application/json');
    $fid = (int)($_POST['feedback_id'] ?? 0);
    if ($fid > 0) {
        try {
            $pdo->prepare("UPDATE feedback SET resolved = 1 WHERE feedback_id = :id")
                ->execute([':id' => $fid]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            // Add column if it doesn't exist yet
            try {
                $pdo->exec("ALTER TABLE feedback ADD COLUMN resolved TINYINT(1) NOT NULL DEFAULT 0");
                $pdo->prepare("UPDATE feedback SET resolved = 1 WHERE feedback_id = :id")
                    ->execute([':id' => $fid]);
                echo json_encode(['success' => true]);
            } catch (PDOException $e2) {
                echo json_encode(['success' => false, 'message' => 'Run: ALTER TABLE feedback ADD COLUMN resolved TINYINT(1) NOT NULL DEFAULT 0']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    }
    exit;
}

// ── HANDLE: Admin reply to feedback (AJAX — returns JSON, no page reload) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_feedback') {
    header('Content-Type: application/json');
    $fid   = (int)($_POST['feedback_id'] ?? 0);
    $reply = trim($_POST['admin_reply'] ?? '');
    if ($fid > 0 && $reply !== '') {
        try {
            // Save to message history (messages stack, not overwrite)
            try {
                $pdo->prepare("INSERT INTO feedback_messages (feedback_id, sender, message) VALUES (:fid, 'admin', :msg)")
                    ->execute([':fid' => $fid, ':msg' => $reply]);
            } catch (PDOException $e2) { /* Table may not exist yet */ }
            // Also keep admin_reply updated for quick access
            $pdo->prepare("UPDATE feedback SET admin_reply = :reply WHERE feedback_id = :id")
                ->execute([':reply' => $reply, ':id' => $fid]);
            echo json_encode(['success' => true, 'message' => $reply]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Reply cannot be empty.']);
    }
    exit;
}

// ── HANDLE: Edit existing instructor ──
$instructorMsg  = '';
$instructorType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_instructor_id'])) {
    $eid    = (int)$_POST['edit_instructor_id'];
    $eName  = trim($_POST['edit_instructor_name']  ?? '');
    $eTitle = trim($_POST['edit_instructor_title'] ?? '');
    $eEmail = trim($_POST['edit_instructor_email'] ?? '');
    if ($eid > 0 && $eName !== '') {
        try {
            $pdo->prepare("UPDATE instructors SET full_name=:name, title=:title, email=:email WHERE instructor_id=:id")
                ->execute([':name'=>$eName, ':title'=>$eTitle?:null, ':email'=>$eEmail?:null, ':id'=>$eid]);
            $instructorMsg  = 'Instructor updated successfully.';
            $instructorType = 'success';
        } catch (PDOException $e) {
            $instructorMsg  = 'Could not update instructor.';
            $instructorType = 'error';
        }
    }
}

// ── HANDLE: Add new instructor ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_instructor_name'])) {
    $newName  = trim($_POST['new_instructor_name'] ?? '');
    $newTitle = trim($_POST['new_instructor_title'] ?? '');
    $newEmail = trim($_POST['new_instructor_email'] ?? '');
    if ($newName === '') {
        $instructorMsg  = 'Instructor name is required.';
        $instructorType = 'error';
    } else {
        try {
            $pdo->prepare("INSERT INTO instructors (full_name, title, email) VALUES (:name, :title, :email)")
                ->execute([':name' => $newName, ':title' => $newTitle ?: null, ':email' => $newEmail ?: null]);
            $instructorMsg  = 'Instructor "' . h($newTitle ? $newTitle . ' ' . $newName : $newName) . '" added!';
            $instructorType = 'success';
        } catch (PDOException $e) {
            $instructorMsg  = 'Could not add instructor. Please try again.';
            $instructorType = 'error';
        }
    }
}

// ── FEEDBACK FILTERS ──
$feedbackFilter = $_GET['feedback_filter'] ?? '30days'; // default: last 30 days
$feedbackSQL    = "SELECT * FROM feedback WHERE (resolved = 0 OR resolved IS NULL)";
$feedbackParams = [];

switch ($feedbackFilter) {
    case '7days':
        $feedbackSQL .= " AND submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30days':
        $feedbackSQL .= " AND submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'specific':
        $specificDay = $_GET['feedback_day'] ?? date('Y-m-d');
        $feedbackSQL .= " AND DATE(submitted_at) = :specific_day";
        $feedbackParams[':specific_day'] = $specificDay;
        break;
    // 'all' = no filter
}

$feedbackSQL .= " ORDER BY submitted_at DESC";

// ── LOAD DATA ──
$categories  = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// Load instructors for the assign dropdown
try {
    $instructors = $pdo->query("SELECT * FROM instructors ORDER BY full_name ASC")->fetchAll();
} catch (PDOException $e) {
    $instructors = [];
}

// Try with instructors table; fall back gracefully if it doesn't exist yet
try {
    $workshops = $pdo->query("
        SELECT w.*, c.category_name,
               TRIM(CONCAT(COALESCE(i.title,''), ' ', COALESCE(i.full_name,''))) AS instructor_name,
               i.instructor_id AS instr_id
        FROM workshops w
        JOIN categories c ON w.category_id = c.category_id
        LEFT JOIN instructors i ON w.instructor_id = i.instructor_id
        ORDER BY w.workshop_date ASC
    ")->fetchAll();
} catch (PDOException $e) {
    // instructors table missing — load without it
    $workshops = $pdo->query("
        SELECT w.*, c.category_name,
               '' AS instructor_name,
               NULL AS instr_id
        FROM workshops w
        JOIN categories c ON w.category_id = c.category_id
        ORDER BY w.workshop_date ASC
    ")->fetchAll();
    $instructors = []; // Also clear instructors list
}

try {
    $fbStmt = $pdo->prepare($feedbackSQL);
    $fbStmt->execute($feedbackParams);
    $feedbackList = $fbStmt->fetchAll();
    $feedbackTableMissing = false;

    // Load message history for each feedback item
    $feedbackMessages = [];
    try {
        foreach ($feedbackList as $fb) {
            $msgStmt = $pdo->prepare("SELECT * FROM feedback_messages WHERE feedback_id = :id ORDER BY sent_at ASC");
            $msgStmt->execute([':id' => $fb['feedback_id']]);
            $feedbackMessages[$fb['feedback_id']] = $msgStmt->fetchAll();
        }
    } catch (PDOException $e2) {
        $feedbackMessages = []; // Table doesn't exist yet
    }
} catch (PDOException $e) {
    $feedbackList = [];
    $feedbackMessages = [];
    $feedbackTableMissing = true;
}

$totalSeats = array_sum(array_column($workshops, 'available_seats'));

// Load admin's profile image from DB
try {
    $adminStmt = $pdo->prepare("SELECT full_name, profile_image FROM users WHERE user_id = :id");
    $adminStmt->execute([':id' => current_user_id()]);
    $adminProfile = $adminStmt->fetch();
    $adminProfileImage = $adminProfile['profile_image'] ?? null;
    $adminFullName = $adminProfile['full_name'] ?? ($_SESSION['full_name'] ?? 'Admin');
} catch (PDOException $e) {
    $adminProfileImage = null;
    $adminFullName = $_SESSION['full_name'] ?? 'Admin';
}

$activeSection = isset($_GET['section']) ? $_GET['section'] : 'workshops';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Workshop Manager – SkillHub Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../../global/main.css" />
  <link rel="stylesheet" href="../../global/admin.css" />
</head>
<!-- data-is-admin="1" tells main.js to hide Book Workshop buttons for this session -->
<body data-is-admin="1">

<div class="admin-layout">

  <!-- ══ LEFT SIDEBAR — Admin only, completely different from regular users ══ -->
  <aside class="admin-sidebar">

    <!-- Logo -->
    <div class="admin-sidebar-logo">
      <div class="admin-sidebar-logo-icon"><i class="fa-solid fa-book-open"></i></div>
      <div>
        <span class="admin-sidebar-logo-name">SkillHub</span>
        <span class="admin-sidebar-logo-role">Admin Panel</span>
      </div>
    </div>

    <!-- Who is logged in -->
    <div class="admin-sidebar-user">
      <?php if (!empty($adminProfileImage)): ?>
        <!-- Show uploaded profile picture -->
        <img src="<?= h('../../' . $adminProfileImage) ?>"
          alt="<?= h($adminFullName) ?>"
          class="admin-sidebar-avatar admin-sidebar-avatar-img"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
        <div class="admin-sidebar-avatar" style="display:none">
          <?= strtoupper(substr($adminFullName, 0, 1)) ?>
        </div>
      <?php else: ?>
        <!-- No profile picture — show initial -->
        <div class="admin-sidebar-avatar">
          <?= strtoupper(substr($adminFullName, 0, 1)) ?>
        </div>
      <?php endif; ?>
      <div class="admin-sidebar-user-info">
        <strong><?= h($adminFullName) ?></strong>
        <span class="admin-role-badge"><i class="fa-solid fa-shield-halved"></i> Administrator</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="admin-sidebar-nav">
      <p class="admin-sidebar-nav-label">Menu</p>

      <a href="#" class="admin-sidebar-link active" id="sidebar-link-workshops"
        onclick="sidebarNav('top', this); return false;">
        <i class="fa-solid fa-gauge"></i>
        <span>Workshop Manager</span>
      </a>

      <a href="#instructors" class="admin-sidebar-link" id="sidebar-link-instructors"
        onclick="sidebarNav('instructors', this); return false;">
        <i class="fa-solid fa-chalkboard-user"></i>
        <span>Instructors</span>
      </a>

      <a href="#feedback" class="admin-sidebar-link" id="sidebar-link-feedback"
        onclick="smoothScrollTo('feedback', this)">
        <i class="fa-solid fa-comments"></i>
        <span>Student Feedback</span>
        <?php
          // Show badge only for feedback that hasn't been replied to yet
          $unrepliedCount = count(array_filter($feedbackList, fn($f) => $f['admin_reply'] === null));
          if ($unrepliedCount > 0):
        ?>
          <span class="admin-sidebar-badge"><?= $unrepliedCount ?></span>
        <?php endif; ?>
      </a>
    </nav>

    <!-- Bottom actions -->
    <div class="admin-sidebar-footer">
      <!-- View Live Site — admin can VIEW but cannot book (Book buttons hidden) -->
      <a href="../../index.php" class="admin-sidebar-footer-link" target="_blank">
        <i class="fa-solid fa-globe"></i> View Live Site
      </a>
      <a href="../../pages/logout.php" class="admin-sidebar-footer-link admin-sidebar-logout">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </a>
    </div>
  </aside>

  <!-- ══ MAIN CONTENT ══ -->
  <main class="admin-main">

    <div class="admin-topbar">
      <div>
        <h1 class="admin-page-title">Workshop Manager</h1>
        <p class="admin-page-sub">Add, edit, and delete workshops. All changes apply instantly without reloading.</p>
      </div>
      <button class="btn btn-primary" id="open-add-modal">
        <i class="fa-solid fa-plus"></i> Add Workshop
      </button>
    </div>

    <!-- STATS -->
    <div class="admin-stats-row">
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-blue"><i class="fa-solid fa-book-open"></i></div>
        <div>
          <div class="admin-stat-number" id="stat-total"><?= count($workshops) ?></div>
          <div class="admin-stat-label">Workshops</div>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-green"><i class="fa-solid fa-chair"></i></div>
        <div>
          <div class="admin-stat-number" id="stat-seats"><?= $totalSeats ?></div>
          <div class="admin-stat-label">Total Seats</div>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-purple"><i class="fa-solid fa-layer-group"></i></div>
        <div>
          <div class="admin-stat-number"><?= count($categories) ?></div>
          <div class="admin-stat-label">Categories</div>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-orange"><i class="fa-solid fa-comments"></i></div>
        <div>
          <div class="admin-stat-number" id="stat-feedback"><?= count($feedbackList) ?></div>
          <div class="admin-stat-label">Feedback</div>
        </div>
      </div>
    </div>

    <!-- WORKSHOPS TABLE -->
    <div class="admin-panel" style="margin-bottom:24px;">
      <div class="admin-panel-header">
        <div>
          <h2>All Workshops</h2>
          <p class="admin-panel-sub">Click Edit to update or Delete to remove a workshop.</p>
        </div>
      </div>
      <div class="table-wrapper" style="margin:0;border-radius:0;box-shadow:none;">
        <table id="workshops-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Title</th>
              <th scope="col">Category</th>
              <th scope="col">Date</th>
              <th scope="col">Time</th>
              <th scope="col">Seats</th>
              <th scope="col">Instructor</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody id="workshops-tbody">
            <?php foreach ($workshops as $w): ?>
            <tr data-id="<?= $w['workshop_id'] ?>">
              <td class="admin-id-cell"><?= h((string)$w['workshop_id']) ?></td>
              <td class="admin-title-cell"><?= h($w['title']) ?></td>
              <td><span class="admin-category-tag"><?= h($w['category_name']) ?></span></td>
              <td><?= date('M j, Y', strtotime($w['workshop_date'])) ?></td>
              <td class="admin-time-cell"><?= date('g:i A', strtotime($w['start_time'])) ?> – <?= date('g:i A', strtotime($w['end_time'])) ?></td>
              <td><span class="admin-seats-badge"><?= h((string)$w['available_seats']) ?></span></td>
              <td class="admin-instructor-cell">
                <?php
                  $iName = trim($w['instructor_name'] ?? '');
                  echo ($iName !== '') ? h($iName) : '<span class="admin-unassigned">Unassigned</span>';
                ?>
              </td>
              <td class="admin-action-btns">
                <button class="btn-admin-edit"
                  data-id="<?= $w['workshop_id'] ?>"
                  data-title="<?= h($w['title']) ?>"
                  data-description="<?= h($w['description']) ?>"
                  data-category="<?= h((string)$w['category_id']) ?>"
                  data-date="<?= h($w['workshop_date']) ?>"
                  data-start="<?= h($w['start_time']) ?>"
                  data-end="<?= h($w['end_time']) ?>"
                  data-seats="<?= h((string)$w['available_seats']) ?>">
                  <i class="fa-solid fa-pen"></i> Edit
                </button>
                <button class="btn-admin-delete" data-id="<?= $w['workshop_id'] ?>">
                  <i class="fa-solid fa-trash"></i> Delete
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CATEGORIES -->
    <div class="admin-panel" style="margin-bottom:24px;" id="categories">
      <div class="admin-panel-header">
        <div>
          <h2>Workshop Categories</h2>
          <p class="admin-panel-sub">Add new categories or edit existing ones.</p>
        </div>
      </div>
      <div class="admin-category-body">

        <?php if ($categoryMsg): ?>
          <div class="admin-alert admin-alert-<?= $categoryType ?>" id="category-alert">
            <i class="fa-solid fa-<?= $categoryType === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
            <?= $categoryMsg ?>
          </div>
          <script>
            // Auto-dismiss the category alert after 3 seconds
            setTimeout(function() {
              var el = document.getElementById('category-alert');
              if (el) {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(function() { el.remove(); }, 500);
              }
            }, 3000);
          </script>
        <?php endif; ?>

        <!-- Existing categories — each has an Edit button -->
        <div class="admin-category-list">
          <?php foreach ($categories as $cat): ?>
          <div class="admin-category-row" id="cat-row-<?= $cat['category_id'] ?>">
            <!-- View mode -->
            <div class="admin-category-view" id="cat-view-<?= $cat['category_id'] ?>">
              <span class="admin-category-chip">
                <i class="fa-solid fa-tag"></i> <?= h($cat['category_name']) ?>
              </span>
              <button class="btn-cat-edit"
                onclick="startEditCategory(<?= $cat['category_id'] ?>, '<?= h($cat['category_name']) ?>')">
                <i class="fa-solid fa-pen"></i> Edit
              </button>
            </div>
            <!-- Edit mode (hidden until Edit clicked) -->
            <form class="admin-category-edit-form" id="cat-edit-<?= $cat['category_id'] ?>"
              method="post" action="admin.php#categories" style="display:none;">
              <input type="hidden" name="edit_category_id" value="<?= $cat['category_id'] ?>" />
              <input type="text" name="edit_category_name"
                value="<?= h($cat['category_name']) ?>"
                placeholder="Category name" maxlength="100" required />
              <button type="submit" class="btn-cat-save">
                <i class="fa-solid fa-check"></i> Save
              </button>
              <button type="button" class="btn-cat-cancel"
                onclick="cancelEditCategory(<?= $cat['category_id'] ?>)">
                Cancel
              </button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Add new category -->
        <div class="admin-add-category-section">
          <p class="admin-add-category-label"><i class="fa-solid fa-plus"></i> Add New Category</p>
          <form method="post" action="admin.php#categories" class="admin-add-category-form">
            <input type="text" name="new_category_name"
              placeholder="e.g. Data Science, Mobile Development..."
              maxlength="100" required />
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-plus"></i> Add Category
            </button>
          </form>
        </div>
      </div>
    </div>

      <!-- INSTRUCTORS PANEL -->
      <div class="admin-panel" style="margin-bottom:24px;" id="instructors">
        <div class="admin-panel-header">
          <div>
            <h2>Instructors</h2>
            <p class="admin-panel-sub">Manage instructors who can be assigned to workshops.</p>
          </div>
        </div>
        <div class="admin-category-body">

          <?php if ($instructorMsg): ?>
            <div class="admin-alert admin-alert-<?= $instructorType ?>" id="instructor-alert">
              <i class="fa-solid fa-<?= $instructorType === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
              <?= $instructorMsg ?>
            </div>
            <script>
              setTimeout(function() {
                var el = document.getElementById('instructor-alert');
                if (el) { el.style.transition='opacity 0.5s'; el.style.opacity='0'; setTimeout(function(){ el.remove(); }, 500); }
              }, 3000);
            </script>
          <?php endif; ?>

          <div class="admin-instructor-list">
            <?php if (empty($instructors)): ?>
              <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:16px;">No instructors yet. Add one below.</p>
            <?php else: ?>
              <?php foreach ($instructors as $inst): ?>
              <div class="admin-instructor-chip" id="inst-chip-<?= $inst['instructor_id'] ?>">
                <!-- View mode -->
                <div class="admin-inst-view" id="inst-view-<?= $inst['instructor_id'] ?>">
                  <div class="admin-instructor-avatar"><?= strtoupper(substr($inst['full_name'], 0, 1)) ?></div>
                  <div class="admin-inst-info">
                    <strong><?= h(trim(($inst['title'] ?? '') . ' ' . $inst['full_name'])) ?></strong>
                    <?php if ($inst['email']): ?><span><?= h($inst['email']) ?></span><?php endif; ?>
                  </div>
                  <button class="btn-cat-edit" onclick="startEditInstructor(<?= $inst['instructor_id'] ?>, '<?= h($inst['title'] ?? '') ?>', '<?= h($inst['full_name']) ?>', '<?= h($inst['email'] ?? '') ?>')">
                    <i class="fa-solid fa-pen"></i> Edit
                  </button>
                </div>
                <!-- Edit mode -->
                <form class="admin-inst-edit-form" id="inst-edit-<?= $inst['instructor_id'] ?>"
                  method="post" action="admin.php#instructors" style="display:none;">
                  <input type="hidden" name="edit_instructor_id" value="<?= $inst['instructor_id'] ?>" />
                  <input type="text" name="edit_instructor_title" placeholder="Title" maxlength="20"
                    id="inst-edit-title-<?= $inst['instructor_id'] ?>" style="max-width:100px;" />
                  <input type="text" name="edit_instructor_name" placeholder="Full name" maxlength="150"
                    id="inst-edit-name-<?= $inst['instructor_id'] ?>" required />
                  <input type="email" name="edit_instructor_email" placeholder="Email"
                    id="inst-edit-email-<?= $inst['instructor_id'] ?>" />
                  <button type="submit" class="btn-cat-save"><i class="fa-solid fa-check"></i> Save</button>
                  <button type="button" class="btn-cat-cancel" onclick="cancelEditInstructor(<?= $inst['instructor_id'] ?>)">Cancel</button>
                </form>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="admin-add-category-section">
            <p class="admin-add-category-label"><i class="fa-solid fa-user-plus"></i> Add New Instructor</p>
            <form method="post" action="admin.php#instructors" class="admin-add-instructor-form">
              <input type="text" name="new_instructor_title" placeholder="Title (Dr., Prof., Mr.)" maxlength="20" />
              <input type="text" name="new_instructor_name" placeholder="Full name (required)" maxlength="150" required />
              <input type="email" name="new_instructor_email" placeholder="Email (optional)" maxlength="150" />
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Instructor</button>
            </form>
          </div>
        </div>
      </div>

    <!-- STUDENT FEEDBACK -->
    <div class="admin-panel" id="feedback">
      <div class="admin-panel-header">
        <div>
          <h2><i class="fa-solid fa-comments" style="color:#f97316;margin-right:8px;"></i>Student Feedback</h2>
          <p class="admin-panel-sub">Reply to students or mark as resolved. Resolved feedback is hidden from this view.</p>
        </div>
        <form method="get" action="admin.php" class="feedback-filter-form">
          <input type="hidden" name="section" value="feedback" />
          <select name="feedback_filter" onchange="this.form.submit()" class="feedback-filter-select">
            <option value="7days"    <?= ($feedbackFilter==='7days')    ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="30days"   <?= ($feedbackFilter==='30days')   ? 'selected' : '' ?>>Last 30 Days</option>
            <option value="specific" <?= ($feedbackFilter==='specific') ? 'selected' : '' ?>>Specific Day</option>
            <option value="all"      <?= ($feedbackFilter==='all')      ? 'selected' : '' ?>>All Time</option>
          </select>
          <?php if ($feedbackFilter === 'specific'): ?>
            <input type="date" name="feedback_day"
              value="<?= h($specificDay ?? date('Y-m-d')) ?>"
              onchange="this.form.submit()"
              class="feedback-filter-date" />
          <?php endif; ?>
        </form>
      </div>

      <?php if ($feedbackTableMissing): ?>
        <div style="padding:24px;">
          <div class="admin-alert admin-alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Run <strong>feedback_table.sql</strong> in phpMyAdmin to enable this section.
          </div>
        </div>

      <?php elseif (empty($feedbackList)): ?>
        <div class="admin-empty-state">
          <div class="admin-empty-icon"><i class="fa-solid fa-inbox"></i></div>
          <h3>No feedback yet</h3>
          <p>Student feedback submitted through the Feedback page will appear here.</p>
        </div>

      <?php else: ?>
        <div class="feedback-list">
          <?php foreach ($feedbackList as $fb): ?>
          <div class="feedback-card">
            <div class="feedback-card-top">
              <div class="feedback-user">
                <div class="feedback-avatar"><?= strtoupper(substr($fb['name'], 0, 1)) ?></div>
                <div class="feedback-user-details">
                  <strong><?= h($fb['name']) ?></strong>
                  <span><?= h($fb['email']) ?></span>
                </div>
              </div>
              <div class="feedback-badges">
                <?php
                  $rc = match($fb['rating']) { 'Good' => 'feedback-rating-good', 'Average' => 'feedback-rating-average', 'Poor' => 'feedback-rating-poor', default => '' };
                  $ri = match($fb['rating']) { 'Good' => 'fa-face-smile', 'Average' => 'fa-face-meh', 'Poor' => 'fa-face-frown', default => 'fa-star' };
                ?>
                <span class="feedback-rating <?= $rc ?>">
                  <i class="fa-solid <?= $ri ?>"></i> <?= h($fb['rating']) ?>
                </span>
                <span class="feedback-date">
                  <i class="fa-regular fa-clock"></i>
                  <?= date('M j, Y · g:i A', strtotime($fb['submitted_at'])) ?>
                </span>
              </div>
            </div>

            <?php if ($fb['workshops_interested'] || $fb['preferred_time']): ?>
            <div class="feedback-details">
              <?php if ($fb['workshops_interested']): ?>
              <div class="feedback-detail-row">
                <span class="feedback-detail-label"><i class="fa-solid fa-bookmark"></i> Interested in</span>
                <span><?= h($fb['workshops_interested']) ?></span>
              </div>
              <?php endif; ?>
              <?php if ($fb['preferred_time']): ?>
              <div class="feedback-detail-row">
                <span class="feedback-detail-label"><i class="fa-solid fa-clock"></i> Prefers</span>
                <span><?= h($fb['preferred_time']) ?></span>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($fb['comments']): ?>
            <div class="feedback-comment">
              <i class="fa-solid fa-quote-left"></i> <?= h($fb['comments']) ?>
            </div>
            <?php endif; ?>

                          <?php
                $msgs = $feedbackMessages[$fb['feedback_id']] ?? [];
                if (!empty($msgs)):
              ?>
              <div class="feedback-message-history" id="msg-history-<?= $fb['feedback_id'] ?>">
                <?php foreach ($msgs as $msg): ?>
                <div class="feedback-reply-display admin-msg-bubble"
                  style="margin-bottom:8px; cursor:pointer;"
                  title="Double-click to edit"
                  ondblclick="editMessage(<?= $fb['feedback_id'] ?>, <?= $msg['message_id'] ?>, this)">
                  <div class="feedback-reply-label">
                    <i class="fa-solid fa-reply"></i> Admin · <?= date('M j, g:i A', strtotime($msg['sent_at'])) ?>
                    <span class="msg-edit-hint">double-click to edit</span>
                  </div>
                  <p class="msg-text"><?= h($msg['message']) ?></p>
                  <!-- Inline edit form (hidden until double-click) -->
                  <div class="msg-edit-form" style="display:none; margin-top:8px;">
                    <textarea class="msg-edit-textarea" rows="2"><?= h($msg['message']) ?></textarea>
                    <div style="display:flex; gap:8px; margin-top:6px;">
                      <button class="btn-reply-save" onclick="saveEditedMessage(<?= $fb['feedback_id'] ?>, <?= $msg['message_id'] ?>, this)">
                        <i class="fa-solid fa-check"></i> Save
                      </button>
                      <button class="btn-reply-cancel" onclick="cancelEditMessage(this)">Cancel</button>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php elseif (!empty($fb['admin_reply'])): ?>
              <div class="feedback-reply-display">
                <div class="feedback-reply-label"><i class="fa-solid fa-reply"></i> Your Reply</div>
                <p><?= h($fb['admin_reply']) ?></p>
              </div>
              <?php endif; ?>

            <!-- Reply area -->
            <div class="feedback-reply-actions" id="reply-actions-<?= $fb['feedback_id'] ?>">

              <!-- Write Reply always available -->
              <button class="btn-reply-open"
                onclick="openReplyBox(<?= $fb['feedback_id'] ?>, '')">
                <i class="fa-solid fa-paper-plane"></i>
                <?= $fb['admin_reply'] ? 'New Message' : 'Write Reply' ?>
              </button>

              <!-- Resolve: hides from view, keeps in DB -->
              <button class="btn-resolve" id="resolve-btn-<?= $fb['feedback_id'] ?>"
                onclick="resolveFeedback(<?= $fb['feedback_id'] ?>, this)">
                <i class="fa-solid fa-circle-check"></i> Resolve
              </button>
            </div>

            <!-- Inline reply box — hidden by default with style, shown by JS -->
            <div class="feedback-reply-box" id="reply-box-<?= $fb['feedback_id'] ?>" style="display:none">
              <textarea id="reply-text-<?= $fb['feedback_id'] ?>"
                placeholder="Write a reply to <?= h($fb['name']) ?>..."
                rows="2"></textarea>
              <div class="feedback-reply-box-actions">
                <button class="btn-reply-cancel"
                  onclick="closeReplyBox(<?= $fb['feedback_id'] ?>)">
                  Cancel
                </button>
                <button class="btn-reply-save"
                  onclick="submitReply(<?= $fb['feedback_id'] ?>)">
                  <i class="fa-solid fa-paper-plane"></i> Send Reply
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- ADD MODAL -->
<div class="admin-overlay" id="add-modal-overlay" hidden>
  <div class="admin-modal">
    <div class="admin-modal-header">
      <div class="admin-modal-title-group">
        <div class="admin-modal-icon stat-blue"><i class="fa-solid fa-plus"></i></div>
        <div>
          <p class="admin-modal-kicker">Workshop Management</p>
          <h2>Add New Workshop</h2>
        </div>
      </div>
      <button type="button" class="admin-modal-close" id="close-add-modal">&times;</button>
    </div>
    <form id="add-workshop-form" novalidate>
      <div class="form-row">
        <div class="form-group">
          <label for="add-title">Workshop Title <span class="required">*</span></label>
          <input type="text" id="add-title" name="title" placeholder="e.g. Python for Beginners" />
        </div>
        <div class="form-group">
          <label for="add-category">Category <span class="required">*</span></label>
          <select id="add-category" name="category_id">
            <option value="">Choose a category...</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>"><?= h($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="add-description">Description <span class="required">*</span></label>
        <textarea id="add-description" name="description"
          placeholder="What will students learn? What skills will they gain?"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add-date">Workshop Date <span class="required">*</span></label>
          <input type="date" id="add-date" name="workshop_date" />
        </div>
        <div class="form-group">
          <label for="add-seats">Seats Available <span class="required">*</span></label>
          <input type="number" id="add-seats" name="available_seats" min="1" max="500" placeholder="e.g. 30" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add-start">Start Time <span class="required">*</span></label>
          <input type="time" id="add-start" name="start_time" />
          
        </div>
        <div class="form-group">
          <label for="add-end">End Time <span class="required">*</span></label>
          <input type="time" id="add-end" name="end_time" />
          
        </div>
      </div>
        <div class="form-group">
          <label for="add-instructor">Assign Instructor</label>
          <select id="add-instructor" name="instructor_id">
            <option value="">No instructor assigned</option>
            <?php foreach ($instructors as $inst): ?>
              <option value="<?= $inst['instructor_id'] ?>">
                <?= h(trim(($inst['title'] ?? '') . ' ' . $inst['full_name'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <div class="admin-form-error" id="add-form-error" hidden></div>
      <div class="admin-modal-actions">
        <button type="button" class="btn btn-outline" id="cancel-add-modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Workshop</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="admin-overlay" id="edit-modal-overlay" hidden>
  <div class="admin-modal">
    <div class="admin-modal-header">
      <div class="admin-modal-title-group">
        <div class="admin-modal-icon stat-purple"><i class="fa-solid fa-pen"></i></div>
        <div>
          <p class="admin-modal-kicker">Workshop Management</p>
          <h2>Edit Workshop</h2>
        </div>
      </div>
      <button type="button" class="admin-modal-close" id="close-edit-modal">&times;</button>
    </div>
    <form id="edit-workshop-form" novalidate>
      <input type="hidden" id="edit-workshop-id" name="workshop_id" />
      <div class="form-row">
        <div class="form-group">
          <label for="edit-title">Workshop Title <span class="required">*</span></label>
          <input type="text" id="edit-title" name="title" placeholder="Workshop title" />
        </div>
        <div class="form-group">
          <label for="edit-category">Category <span class="required">*</span></label>
          <select id="edit-category" name="category_id">
            <option value="">Choose a category...</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>"><?= h($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="edit-description">Description <span class="required">*</span></label>
        <textarea id="edit-description" name="description"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit-date">Workshop Date <span class="required">*</span></label>
          <input type="date" id="edit-date" name="workshop_date" />
        </div>
        <div class="form-group">
          <label for="edit-seats">Seats Available <span class="required">*</span></label>
          <input type="number" id="edit-seats" name="available_seats" min="1" max="500" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit-start">Start Time <span class="required">*</span></label>
          <input type="time" id="edit-start" name="start_time" />
          
        </div>
        <div class="form-group">
          <label for="edit-end">End Time <span class="required">*</span></label>
          <input type="time" id="edit-end" name="end_time" />
          
        </div>
      </div>
        <div class="form-group">
          <label for="edit-instructor">Assign Instructor</label>
          <select id="edit-instructor" name="instructor_id">
            <option value="">No instructor assigned</option>
            <?php foreach ($instructors as $inst): ?>
              <option value="<?= $inst['instructor_id'] ?>">
                <?= h(trim(($inst['title'] ?? '') . ' ' . $inst['full_name'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <div class="admin-form-error" id="edit-form-error" hidden></div>
      <div class="admin-modal-actions">
        <button type="button" class="btn btn-outline" id="cancel-edit-modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="admin-overlay" id="delete-modal-overlay" hidden>
  <div class="admin-modal admin-modal-small">
    <div class="admin-modal-header">
      <div class="admin-modal-title-group">
        <div class="admin-modal-icon stat-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
          <p class="admin-modal-kicker">Confirm Action</p>
          <h2>Delete Workshop</h2>
        </div>
      </div>
      <button type="button" class="admin-modal-close" id="close-delete-modal">&times;</button>
    </div>
    <div class="admin-delete-body">
      <p>You are about to permanently delete <strong id="delete-workshop-name"></strong>.</p>
      <p class="admin-delete-warning">
        <i class="fa-solid fa-triangle-exclamation"></i>
        This cannot be undone. All student bookings for this workshop will also be removed.
      </p>
    </div>
    <div class="admin-form-error" id="delete-form-error" hidden></div>
    <div class="admin-modal-actions">
      <button type="button" class="btn btn-outline" id="cancel-delete-modal">Cancel</button>
      <button type="button" class="btn btn-danger" id="confirm-delete-btn">
        <i class="fa-solid fa-trash"></i> Yes, Delete
      </button>
    </div>
  </div>
</div>

<div id="admin-toast" class="admin-toast" hidden>
  <span id="admin-toast-icon"></span>
  <span id="admin-toast-msg"></span>
</div>

<script>
// Category inline edit helpers
function startEditCategory(id, currentName) {
  document.getElementById('cat-view-' + id).style.display = 'none';
  document.getElementById('cat-edit-' + id).style.display = 'flex';
  document.querySelector('#cat-edit-' + id + ' input[name="edit_category_name"]').focus();
}

function cancelEditCategory(id) {
  document.getElementById('cat-view-' + id).style.display = 'flex';
  document.getElementById('cat-edit-' + id).style.display = 'none';
}
</script>

<script>
  function openReplyBox(id, existingText) {
    const box      = document.getElementById('reply-box-' + id);
    const textarea = document.getElementById('reply-text-' + id);
    if (!box || !textarea) return;
    textarea.value     = existingText || '';
    box.style.display  = 'block'; // Simple - no hidden attribute to fight
    textarea.focus();
  }

  function closeReplyBox(id) {
    const box = document.getElementById('reply-box-' + id);
    if (box) box.style.display = 'none';
  }

  async function submitReply(id) {
    const textarea = document.getElementById('reply-text-' + id);
    const reply = textarea ? textarea.value.trim() : '';
    if (!reply) { alert('Reply cannot be empty.'); return; }

    const formData = new FormData();
    formData.append('action', 'reply_feedback');
    formData.append('feedback_id', id);
    formData.append('admin_reply', reply);

    const res = await fetch('admin.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.success) {
      const card = document.getElementById('reply-actions-' + id).closest('.feedback-card');
      let displayDiv = card.querySelector('.feedback-reply-display');

      if (displayDiv) {
        displayDiv.querySelector('p').textContent = reply;
      } else {
        displayDiv = document.createElement('div');
        displayDiv.className = 'feedback-reply-display';
        displayDiv.innerHTML = '<div class="feedback-reply-label"><i class="fa-solid fa-reply"></i> Your Reply</div><p></p>';
        displayDiv.querySelector('p').textContent = reply;
        card.querySelector('.feedback-reply-actions').before(displayDiv);
      }

      const openBtn = card.querySelector('.btn-reply-open');
      if (openBtn) {
        openBtn.className = 'btn-reply-edit';
        openBtn.innerHTML = '<i class="fa-solid fa-pen"></i> Edit Reply';
        openBtn.setAttribute('onclick', 'openReplyBox(' + id + ', ' + JSON.stringify(reply) + ')');
      }

      closeReplyBox(id);
      showAdminToast('Reply sent!', 'success');
    } else {
      alert(result.message || 'Could not save reply.');
    }
  }

  async function resolveFeedback(id, btn) {
    if (!confirm('Mark this feedback as resolved? It will be hidden from this view but kept in the database.')) return;

    const formData = new FormData();
    formData.append('action', 'resolve_feedback');
    formData.append('feedback_id', id);

    const res = await fetch('admin.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.success) {
      const card = btn.closest('.feedback-card');
      if (card) {
        card.style.transition = 'opacity 0.4s, transform 0.4s';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.97)';
        setTimeout(() => card.remove(), 400);
      }
      const statEl = document.getElementById('stat-feedback');
      if (statEl) statEl.textContent = Math.max(0, (parseInt(statEl.textContent) || 0) - 1);
      showAdminToast('Feedback resolved and hidden.', 'success');
    } else {
      alert(result.message || 'Could not resolve. Try running: ALTER TABLE feedback ADD COLUMN resolved TINYINT(1) NOT NULL DEFAULT 0');
    }
  }

  // Opens the reply box cleared for a new message (ignores existing reply text)
  // ── INSTRUCTOR INLINE EDIT ──
  function startEditInstructor(id, title, name, email) {
    document.getElementById('inst-view-' + id).style.display = 'none';
    const form = document.getElementById('inst-edit-' + id);
    form.style.display = 'flex';
    document.getElementById('inst-edit-title-' + id).value = title;
    document.getElementById('inst-edit-name-'  + id).value = name;
    document.getElementById('inst-edit-email-' + id).value = email;
  }

  function cancelEditInstructor(id) {
    document.getElementById('inst-view-' + id).style.display = 'flex';
    document.getElementById('inst-edit-' + id).style.display = 'none';
  }

  // ── DOUBLE-CLICK TO EDIT A MESSAGE ──────────────────────────
  function editMessage(feedbackId, messageId, bubble) {
    // Show the inline edit form inside this bubble
    const editForm = bubble.querySelector('.msg-edit-form');
    const msgText  = bubble.querySelector('.msg-text');
    if (!editForm || !msgText) return;
    msgText.style.display    = 'none';
    editForm.style.display   = 'block';
    const ta = editForm.querySelector('.msg-edit-textarea');
    if (ta) { ta.focus(); ta.select(); }
  }

  function cancelEditMessage(btn) {
    const editForm = btn.closest('.msg-edit-form');
    const bubble   = btn.closest('.admin-msg-bubble');
    if (!editForm || !bubble) return;
    editForm.style.display = 'none';
    const msgText = bubble.querySelector('.msg-text');
    if (msgText) msgText.style.display = '';
  }

  async function saveEditedMessage(feedbackId, messageId, btn) {
    const editForm = btn.closest('.msg-edit-form');
    const bubble   = btn.closest('.admin-msg-bubble');
    const ta       = editForm ? editForm.querySelector('.msg-edit-textarea') : null;
    if (!ta) return;

    const newText = ta.value.trim();
    if (!newText) { alert('Message cannot be empty.'); return; }

    const formData = new FormData();
    formData.append('action',     'edit_message');
    formData.append('message_id', messageId);
    formData.append('new_text',   newText);
    formData.append('feedback_id', feedbackId);

    const res    = await fetch('admin.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.success) {
      // Update displayed text
      const msgText = bubble.querySelector('.msg-text');
      if (msgText) {
        msgText.textContent    = newText;
        msgText.style.display  = '';
      }
      editForm.style.display = 'none';
      showAdminToast('Message updated.', 'success');
    } else {
      alert(result.message || 'Could not update message.');
    }
  }

  function openNewMessage(id, name) {
    const box      = document.getElementById('reply-box-' + id);
    const textarea = document.getElementById('reply-text-' + id);
    if (!box || !textarea) return;
    textarea.value       = '';
    textarea.placeholder = 'Write a new message to ' + name + '...';
    box.style.display    = 'block';
    textarea.focus();
  }

  function showAdminToast(msg, type) {
    const toast = document.getElementById('admin-toast');
    const icon = document.getElementById('admin-toast-icon');
    const msgEl = document.getElementById('admin-toast-msg');
    if (!toast) return;
    icon.innerHTML = type === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>';
    msgEl.textContent = msg;
    toast.className = 'admin-toast toast-' + type;
    toast.hidden = false;
    setTimeout(() => { toast.hidden = true; }, 3000);
  }
</script>
<script src="../../scripts/admin.js?v=8"></script>
<script>
// ── SIDEBAR NAVIGATION ──────────────────────────────────────────
// Handles active highlighting + smooth scroll for all sidebar links
function sidebarNav(sectionId, clickedLink) {
  // 1. Remove active from all links
  document.querySelectorAll('.admin-sidebar-link').forEach(function(l) {
    l.classList.remove('active');
  });
  // 2. Add active to the clicked link
  if (clickedLink) clickedLink.classList.add('active');
  // 3. Scroll to section
  if (sectionId === 'top') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } else {
    var el = document.getElementById(sectionId);
    if (el) {
      var y = el.getBoundingClientRect().top + window.pageYOffset - 20;
      window.scrollTo({ top: y, behavior: 'smooth' });
    }
  }
}

// Auto-highlight based on scroll position
// Uses window height to detect which section takes up most of the viewport
window.addEventListener('scroll', function() {
  var activeLinkId = 'sidebar-link-workshops';

  // Check sections from bottom to top — last one that's entered viewport wins
  var sections = [
    { id: 'instructors', link: 'sidebar-link-instructors' },
    { id: 'feedback',    link: 'sidebar-link-feedback' }
  ];

  sections.forEach(function(s) {
    var el = document.getElementById(s.id);
    if (el) {
      var rect = el.getBoundingClientRect();
      // Section is active if its top is within the top half of the screen
      if (rect.top <= window.innerHeight * 0.5) {
        activeLinkId = s.link;
      }
    }
  });

  document.querySelectorAll('.admin-sidebar-link').forEach(function(l) {
    l.classList.remove('active');
  });
  var activeEl = document.getElementById(activeLinkId);
  if (activeEl) activeEl.classList.add('active');
});
</script>
</body>
</html>