<?php
// SkillHub database connection using PDO.
// Update these values after creating the MySQL database on your host.
// This file contains the database connection only.
// It does not create tables. It just lets PHP talk to MySQL.
// It connects PHP to MySQL 

$DB_HOST = 'localhost';
$DB_NAME = 'skillhub_db';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Keep the real error away from users. Log it during development if needed.
    die('Database connection failed.');
}