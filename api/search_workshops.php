<?php
/*
Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3
Name=Shahenaz Abushanab, ID=2215050, Section=DAR, Date=20/3
Name=Raghad Abdullah Alzahrani, ID=2206740, Section=DAR, Date=20/3
*/

require_once __DIR__ . '/../includes/db.php';

// Retrieves search and filtering values sent from the frontend
// using AJAX requests. These values are used to dynamically
// filter workshops from the database without page reload.

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';


// SQL query for retrieving workshops and matching categories
// based on live search keywords and selected filters.

$sql = "
    SELECT workshops.*, categories.category_name
    FROM workshops
    JOIN categories ON workshops.category_id = categories.category_id
    WHERE 
        (
            workshops.title LIKE :search1
            OR workshops.description LIKE :search2
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