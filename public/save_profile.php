<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name']);
    $position = $_POST['position'];
    $location = trim($_POST['location']);
    $instagram = trim($_POST['instagram_url']);
    $youtube = trim($_POST['youtube_url']);
    $bio = trim($_POST['bio']);

    // Handle image upload
    $profile_picture = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profile_pictures/";
        $fileName = uniqid() . "_" . basename($_FILES["profile_picture"]["name"]);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
            $profile_picture = $fileName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO player_profiles 
        (user_id, full_name, position, location, instagram_url, youtube_url, bio, profile_picture) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $full_name, $position, $location,
        $instagram, $youtube, $bio, $profile_picture
    ]);

    header('Location: dashboard.php');
    exit;
}
