<?php
/*
 * api/search_workshops.php
 * Returns workshops matching search keyword and/or category filter.
 * Returns all fields including hook_message, good_fit_for,
 * learning_points, instructor_name, instructor_email,
 * instructor_specialty, instructor_experience.
 */
require_once __DIR__ . '/../includes/db.php';

$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';
// Use Saudi time so search results match the visible Services page.
$now = (new DateTime('now', new DateTimeZone('Asia/Riyadh')))->format('Y-m-d H:i:s');

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
    WHERE TIMESTAMP(workshops.workshop_date, workshops.end_time) >= :now
    AND (
        workshops.title             LIKE :search1
        OR workshops.description    LIKE :search2
        OR categories.category_name LIKE :search3
    )
";
if (!empty($category)) $sql .= " AND categories.category_name = :category";
$sql .= " ORDER BY workshops.workshop_date ASC, workshops.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':now', $now, PDO::PARAM_STR);
$stmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);
if (!empty($category)) $stmt->bindValue(':category', $category, PDO::PARAM_STR);
$stmt->execute();

header('Content-Type: application/json');
echo json_encode($stmt->fetchAll());