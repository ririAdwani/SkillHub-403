<?php
/*
  api/workshops.php — REST API for Workshop CRUD
  ================================================
  Receives POST requests from admin.js and returns JSON.
  Actions: create, update, delete
  Fields: title, description, hook_message, good_fit_for,
          category_id, instructor_id, workshop_date, start_time,
          end_time, available_seats, image_path, learning_points
*/

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create': createWorkshop($pdo); break;
    case 'update': updateWorkshop($pdo); break;
    case 'delete': deleteWorkshop($pdo); break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

function createWorkshop(PDO $pdo): void
{
    $title          = trim($_POST['title']            ?? '');
    $description    = trim($_POST['description']      ?? '');
    $hookMessage    = trim($_POST['hook_message']      ?? '');
    $goodFitFor     = trim($_POST['good_fit_for']      ?? '');
    $categoryId     = (int)($_POST['category_id']     ?? 0);
    $instructorId   = (int)($_POST['instructor_id']   ?? 0) ?: null;
    $date           = trim($_POST['workshop_date']     ?? '');
    $startTime      = trim($_POST['start_time']        ?? '');
    $endTime        = trim($_POST['end_time']          ?? '');
    $seats          = (int)($_POST['available_seats']  ?? 0);
    $imagePath      = trim($_POST['image_path']        ?? '');
    $learningPoints = trim($_POST['learning_points']   ?? '');

    $errors = [];
    if ($title === '')       $errors[] = 'Title is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if ($categoryId <= 0)    $errors[] = 'Please select a category.';
    if ($date === '')        $errors[] = 'Date is required.';
    if ($startTime === '')   $errors[] = 'Start time is required.';
    if ($endTime === '')     $errors[] = 'End time is required.';
    if ($startTime >= $endTime) $errors[] = 'End time must be after start time.';
    if ($seats < 1 || $seats > 500) $errors[] = 'Seats must be between 1 and 500.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO workshops
                (title, description, hook_message, good_fit_for, category_id,
                 instructor_id, workshop_date, start_time, end_time,
                 available_seats, image_path, learning_points)
            VALUES
                (:title, :description, :hook_message, :good_fit_for, :category_id,
                 :instructor_id, :workshop_date, :start_time, :end_time,
                 :available_seats, :image_path, :learning_points)
        ");

        $stmt->execute([
            ':title'           => $title,
            ':description'     => $description,
            ':hook_message'    => $hookMessage    ?: null,
            ':good_fit_for'    => $goodFitFor     ?: null,
            ':category_id'     => $categoryId,
            ':instructor_id'   => $instructorId,
            ':workshop_date'   => $date,
            ':start_time'      => $startTime,
            ':end_time'        => $endTime,
            ':available_seats' => $seats,
            ':image_path'      => $imagePath      ?: null,
            ':learning_points' => $learningPoints ?: null,
        ]);

        $newId = (int) $pdo->lastInsertId();

        $catStmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = :id");
        $catStmt->execute([':id' => $categoryId]);
        $categoryName = $catStmt->fetchColumn() ?: '';

        echo json_encode([
            'success'  => true,
            'workshop' => [
                'workshop_id'     => $newId,
                'title'           => $title,
                'description'     => $description,
                'hook_message'    => $hookMessage,
                'good_fit_for'    => $goodFitFor,
                'category_id'     => $categoryId,
                'category_name'   => $categoryName,
                'workshop_date'   => $date,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'available_seats' => $seats,
                'image_path'      => $imagePath,
                'learning_points' => $learningPoints,
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateWorkshop(PDO $pdo): void
{
    $workshopId     = (int)($_POST['workshop_id']     ?? 0);
    $title          = trim($_POST['title']            ?? '');
    $description    = trim($_POST['description']      ?? '');
    $hookMessage    = trim($_POST['hook_message']      ?? '');
    $goodFitFor     = trim($_POST['good_fit_for']      ?? '');
    $categoryId     = (int)($_POST['category_id']     ?? 0);
    $instructorId   = (int)($_POST['instructor_id']   ?? 0) ?: null;
    $date           = trim($_POST['workshop_date']     ?? '');
    $startTime      = trim($_POST['start_time']        ?? '');
    $endTime        = trim($_POST['end_time']          ?? '');
    $seats          = (int)($_POST['available_seats']  ?? 0);
    $imagePath      = trim($_POST['image_path']        ?? '');
    $learningPoints = trim($_POST['learning_points']   ?? '');

    $errors = [];
    if ($workshopId <= 0)    $errors[] = 'Invalid workshop ID.';
    if ($title === '')       $errors[] = 'Title is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if ($categoryId <= 0)    $errors[] = 'Please select a category.';
    if ($date === '')        $errors[] = 'Date is required.';
    if ($startTime === '')   $errors[] = 'Start time is required.';
    if ($endTime === '')     $errors[] = 'End time is required.';
    if ($startTime >= $endTime) $errors[] = 'End time must be after start time.';
    if ($seats < 1 || $seats > 500) $errors[] = 'Seats must be between 1 and 500.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE workshops SET
                title           = :title,
                description     = :description,
                hook_message    = :hook_message,
                good_fit_for    = :good_fit_for,
                category_id     = :category_id,
                instructor_id   = :instructor_id,
                workshop_date   = :workshop_date,
                start_time      = :start_time,
                end_time        = :end_time,
                available_seats = :available_seats,
                image_path      = :image_path,
                learning_points = :learning_points
            WHERE workshop_id = :workshop_id
        ");

        $stmt->execute([
            ':title'           => $title,
            ':description'     => $description,
            ':hook_message'    => $hookMessage    ?: null,
            ':good_fit_for'    => $goodFitFor     ?: null,
            ':category_id'     => $categoryId,
            ':instructor_id'   => $instructorId,
            ':workshop_date'   => $date,
            ':start_time'      => $startTime,
            ':end_time'        => $endTime,
            ':available_seats' => $seats,
            ':image_path'      => $imagePath      ?: null,
            ':learning_points' => $learningPoints ?: null,
            ':workshop_id'     => $workshopId,
        ]);

        $catStmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = :id");
        $catStmt->execute([':id' => $categoryId]);
        $categoryName = $catStmt->fetchColumn() ?: '';

        $instructorName = '';
        if ($instructorId) {
            $instStmt = $pdo->prepare("SELECT full_name, title FROM instructors WHERE instructor_id = :id");
            $instStmt->execute([':id' => $instructorId]);
            $inst = $instStmt->fetch();
            if ($inst) $instructorName = trim(($inst['title'] ?? '') . ' ' . $inst['full_name']);
        }

        echo json_encode([
            'success'  => true,
            'workshop' => [
                'workshop_id'     => $workshopId,
                'title'           => $title,
                'description'     => $description,
                'hook_message'    => $hookMessage,
                'good_fit_for'    => $goodFitFor,
                'category_id'     => $categoryId,
                'category_name'   => $categoryName,
                'instructor_id'   => $instructorId,
                'instructor_name' => $instructorName,
                'workshop_date'   => $date,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'available_seats' => $seats,
                'image_path'      => $imagePath,
                'learning_points' => $learningPoints,
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteWorkshop(PDO $pdo): void
{
    $workshopId = (int)($_POST['workshop_id'] ?? 0);
    if ($workshopId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid workshop ID.']);
        return;
    }
    try {
        $pdo->prepare("DELETE FROM workshops WHERE workshop_id = :id")->execute([':id' => $workshopId]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}