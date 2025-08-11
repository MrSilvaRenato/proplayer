<?php
session_start();
require_once '../includes/db.php';

// Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$profileId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;


if (!$profileId) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid profile update request.'];
    header("Location: dashboard.php");
    exit;
}

    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $location = trim($_POST['location']);
    $bio = trim($_POST['bio']);
    $instagram_url = trim($_POST['instagram_url']);
    $youtube_url = trim($_POST['youtube_url']);

    $stmt = $pdo->prepare("UPDATE player_profiles SET 
        full_name = ?, position = ?, location = ?, bio = ?, instagram_url = ?, youtube_url = ?
        WHERE user_id = ?");
    $stmt->execute([$full_name, $position, $location, $bio, $instagram_url, $youtube_url, $profileId]);

    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Player profile updated successfully.'];
    header("Location: admin_dashboard.php");

    exit;
?>