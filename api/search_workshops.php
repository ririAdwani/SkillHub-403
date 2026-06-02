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
 * FIX: Now JOINs the instructors table so instructor_name is included
 * in the response. This fixes the View Details modal showing blank instructor.
 */

require_once __DIR__ . '/../includes/db.php';

// Read search keyword and category filter from GET parameters
$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';

// Build SQL query with instructor JOIN so View Details modal has full data.
// LEFT JOIN means workshops without an assigned instructor still appear.
$sql = "
    SELECT
        workshops.*,
        categories.category_name,
        TRIM(CONCAT(COALESCE(i.title,''), ' ', COALESCE(i.full_name,''))) AS instructor_name
    FROM workshops
    JOIN categories ON workshops.category_id = categories.category_id
    LEFT JOIN instructors i ON workshops.instructor_id = i.instructor_id
    WHERE (
        workshops.title       LIKE :search1
        OR workshops.description  LIKE :search2
        OR categories.category_name LIKE :search3
    )
";

// Add category filter only when a specific category is selected
if (!empty($category)) {
    $sql .= " AND categories.category_name = :category";
}

$sql .= " ORDER BY workshops.workshop_date ASC";

$stmt = $pdo->prepare($sql);

// Bind search term three times — once per searchable column
$stmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);

if (!empty($category)) {
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
}

$stmt->execute();

$workshops = $stmt->fetchAll();

// Return results as JSON for the frontend AJAX handler
header('Content-Type: application/json');
echo json_encode($workshops);