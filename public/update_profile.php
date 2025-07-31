<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$profileId = $_POST['profile_id'] ?? null;

if (!$profileId) {
    die('Invalid request.');
}

// Sanitize inputs
$fullName = trim($_POST['full_name']);
$position = trim($_POST['position']);
$location = trim($_POST['location']);
$instagram = trim($_POST['instagram_url']);
$youtube = trim($_POST['youtube_url']);
$bio = trim($_POST['bio']);

// Optional file upload
$profilePicture = null;

if (!empty($_FILES['profile_picture']['name'])) {
    $targetDir = "../uploads/profile_pictures/";
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_') . '.' . $ext;
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
        $profilePicture = $filename;
    }
}

// Update query
if ($profilePicture) {
    $stmt = $pdo->prepare("UPDATE player_profiles SET 
        full_name = ?, position = ?, location = ?, instagram_url = ?, youtube_url = ?, bio = ?, profile_picture = ?
        WHERE id = ? AND user_id = ?");
    $stmt->execute([$fullName, $position, $location, $instagram, $youtube, $bio, $profilePicture, $profileId, $userId]);
} else {
    $stmt = $pdo->prepare("UPDATE player_profiles SET 
        full_name = ?, position = ?, location = ?, instagram_url = ?, youtube_url = ?, bio = ?
        WHERE id = ? AND user_id = ?");
    $stmt->execute([$fullName, $position, $location, $instagram, $youtube, $bio, $profileId, $userId]);
}

header("Location: dashboard.php");
exit;
