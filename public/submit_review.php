<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$profile_id = $_POST['profile_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

// Validate inputs
if (!$profile_id || !$rating || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Prevent self-review
$stmt = $pdo->prepare("SELECT user_id FROM player_profiles WHERE id = ?");
$stmt->execute([$profile_id]);
$profileOwner = $stmt->fetchColumn();

if ($profileOwner == $user_id) {
    echo json_encode(['success' => false, 'message' => "You can't review your own profile."]);
    exit;
}

// Check if user already reviewed
$stmt = $pdo->prepare("SELECT id FROM player_reviews WHERE profile_id = ? AND reviewer_user_id = ?");
$stmt->execute([$profile_id, $user_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Update review
    $stmt = $pdo->prepare("UPDATE player_reviews SET rating = ?, comment = ?, comment_approved = 0, updated_at = NOW() WHERE id = ?");

    $success = $stmt->execute([$rating, $comment, $existing['id']]);
} else {
    // Insert new review
    $stmt = $pdo->prepare("INSERT INTO player_reviews (profile_id, reviewer_user_id, rating, comment, comment_approved) VALUES (?, ?, ?, ?, 0)");

    $success = $stmt->execute([$profile_id, $user_id, $rating, $comment]);
}

echo json_encode(['success' => $success]);

