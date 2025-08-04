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
    $position = trim($_POST['position']);
    $location = trim($_POST['location']);
    $instagram = trim($_POST['instagram_url']);
    $youtube = trim($_POST['youtube_url']);
    $bio = trim($_POST['bio']);

    // Handle profile picture upload
    $profile_picture = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profile_pictures/";
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('profile_') . '.' . $ext;
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
            $profile_picture = $filename;
        }
    }

    // Insert main profile
    $stmt = $pdo->prepare("INSERT INTO player_profiles 
        (user_id, full_name, position, location, instagram_url, youtube_url, bio, profile_picture) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $success = $stmt->execute([
        $userId, $full_name, $position, $location,
        $instagram, $youtube, $bio, $profile_picture
    ]);

    if ($success) {
        $profile_id = $pdo->lastInsertId();

        // Insert skills if provided
        if (!empty($_POST['skills']) && !empty($_POST['levels'])) {
            $skills = $_POST['skills'];
            $levels = $_POST['levels'];

            $stmtSkill = $pdo->prepare("INSERT INTO player_skills (profile_id, skill_name, level) VALUES (?, ?, ?)");
            for ($i = 0; $i < count($skills); $i++) {
                $skillName = trim($skills[$i]);
                $level = trim($levels[$i]);
                if ($skillName && $level) {
                    $stmtSkill->execute([$profile_id, $skillName, $level]);
                }
            }
        }

        // Insert awards if provided
        if (!empty($_POST['awards'])) {
            $stmtAward = $pdo->prepare("INSERT INTO player_awards (profile_id, title, awarded_by, description, award_date) VALUES (?, ?, ?, ?, ?)");

            foreach ($_POST['awards'] as $award) {
                $title = trim($award['title']);
                $awarded_by = trim($award['awarded_by']);
                $description = trim($award['description']);
                $award_date = trim($award['award_date']);

                if ($title && $awarded_by && $award_date) {
                    $stmtAward->execute([$profile_id, $title, $awarded_by, $description, $award_date]);
                }
            }
        }

        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Profile created successfully!'];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to create profile. Please try again.'];
    }

    header('Location: dashboard.php');
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid access to profile creation.'];
    header('Location: dashboard.php');
    exit;
}
