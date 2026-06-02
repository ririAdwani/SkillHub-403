<?php
/*
 * api/search_workshops.php
 * Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3
 * Name=Shahenaz Abushanab, ID=2215050, Section=DAR, Date=20/3
 * Name=Raghad Abdullah Alzahrani, ID=2206740, Section=DAR, Date=20/3
 *
 * Returns workshops matching the search keyword and/or category filter.
 * Called via AJAX from services.php on every keyup and category change.
 * Returns JSON — no page reload needed.
 *
 * Returns instructor_name, instructor_email, instructor_specialty,
 * instructor_experience, and learning_points so the View Details modal
 * can show the full instructor hover popup and What you'll learn bullets.
 */

require_once __DIR__ . '/../includes/db.php';

$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';

$sql = "
    SELECT
        workshops.*,
        categories.category_name,
        TRIM(CONCAT(COALESCE(i.title,''), ' ', COALESCE(i.full_name,''))) AS instructor_name,
        i.email      AS instructor_email,
        i.specialty  AS instructor_specialty,
        i.experience AS instructor_experience
    FROM workshops
    JOIN categories ON workshops.category_id = categories.category_id
    LEFT JOIN instructors i ON workshops.instructor_id = i.instructor_id
    WHERE (
        workshops.title             LIKE :search1
        OR workshops.description    LIKE :search2
        OR categories.category_name LIKE :search3
    )
";

if (!empty($category)) {
    $sql .= " AND categories.category_name = :category";
}

$sql .= " ORDER BY workshops.workshop_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);

if (!empty($category)) {
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
}

$stmt->execute();
$workshops = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($workshops);