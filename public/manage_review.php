<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$review_id = $_POST['review_id'] ?? null;
$action = $_POST['action'] ?? null;
$user_id = $_SESSION['user_id'];

// Verify the logged-in user owns the profile linked to this review
$stmt = $pdo->prepare("
    SELECT pr.profile_id, pp.user_id
    FROM player_reviews pr
    JOIN player_profiles pp ON pr.profile_id = pp.id
    WHERE pr.id = ?
");
$stmt->execute([$review_id]);
$review = $stmt->fetch();

if (!$review || $review['user_id'] != $user_id) {
    die("Unauthorized action");
}

switch ($action) {
    case 'approve':
        $stmt = $pdo->prepare("UPDATE player_reviews SET comment_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Comment approved!'];
        break;

    case 'reject':
        $stmt = $pdo->prepare("UPDATE player_reviews SET comment_approved = -1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Comment rejected.'];
        break;

    case 'flag_unfair':
        // Mark as flagged and hide the comment
        $stmt = $pdo->prepare("UPDATE player_reviews SET flagged_unfair = 1, comment_approved = -1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['toast'] = ['type' => 'warning', 'message' => 'Review flagged as unfair.'];
        break;

    default:
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Invalid action.'];
        break;
}

header("Location: ../public/profile.php?id=" . $review['profile_id']);
exit;
