<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

$reviewId = $_POST['review_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$reviewId || !in_array($action, ['restore', 'delete'])) {
    die("Invalid request");
}

if ($action === 'restore') {
    $stmt = $pdo->prepare("UPDATE player_reviews SET flagged_unfair = 0, comment_approved = 1 WHERE id = ?");
    $stmt->execute([$reviewId]);
} elseif ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM player_reviews WHERE id = ?");
    $stmt->execute([$reviewId]);
}

header("Location: flagged_reviews.php");
exit;
