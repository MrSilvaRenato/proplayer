<?php
session_start();
require_once '../includes/db.php';

// 🔐 Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'player';
$profileId = $_POST['profile_id'] ?? null;

if (!$profileId) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid profile update request.'];
    header("Location: dashboard.php");
    exit;
}

// 🔍 Verify the profile belongs to user or admin
$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE id = ?");
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

if (!$profile) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Profile not found.'];
    header("Location: dashboard.php");
    exit;
}

if ($profile['user_id'] != $userId && $userRole !== 'admin') {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Unauthorized update attempt.'];
    header("Location: dashboard.php");
    exit;
}

// ✏️ Sanitize inputs
$fullName = trim($_POST['full_name']);
$position = trim($_POST['position']);
$location = trim($_POST['location']);
$instagram = trim($_POST['instagram_url']);
$youtube = trim($_POST['youtube_url']);
$bio = trim($_POST['bio']);

// 📸 Handle profile picture upload
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

// 📝 Update player profile
if ($profilePicture) {
    $stmt = $pdo->prepare("UPDATE player_profiles SET 
        full_name = ?, position = ?, location = ?, instagram_url = ?, youtube_url = ?, bio = ?, profile_picture = ?
        WHERE id = ?");
    $stmt->execute([$fullName, $position, $location, $instagram, $youtube, $bio, $profilePicture, $profileId]);
} else {
    $stmt = $pdo->prepare("UPDATE player_profiles SET 
        full_name = ?, position = ?, location = ?, instagram_url = ?, youtube_url = ?, bio = ?
        WHERE id = ?");
    $stmt->execute([$fullName, $position, $location, $instagram, $youtube, $bio, $profileId]);
}

// 🔁 Update Skills
$pdo->prepare("DELETE FROM player_skills WHERE profile_id = ?")->execute([$profileId]);

if (!empty($_POST['skills']) && is_array($_POST['skills'])) {
    $skills = $_POST['skills'];
    $levels = $_POST['levels'];
    $stmt = $pdo->prepare("INSERT INTO player_skills (profile_id, skill_name, level) VALUES (?, ?, ?)");

    for ($i = 0; $i < count($skills); $i++) {
        $skill = trim($skills[$i]);
        $level = trim($levels[$i]);
        if (!empty($skill) && !empty($level)) {
            $stmt->execute([$profileId, $skill, $level]);
        }
    }
}

// 🔁 Update Awards
$pdo->prepare("DELETE FROM player_awards WHERE profile_id = ?")->execute([$profileId]);

if (!empty($_POST['awards']) && is_array($_POST['awards'])) {
    $stmt = $pdo->prepare("INSERT INTO player_awards (profile_id, title, awarded_by, description, award_date) VALUES (?, ?, ?, ?, ?)");
    foreach ($_POST['awards'] as $award) {
        $title = trim($award['title'] ?? '');
        $by = trim($award['awarded_by'] ?? '');
        $desc = trim($award['description'] ?? '');
        $date = $award['award_date'] ?? null;
        if ($title && $by) {
            $stmt->execute([$profileId, $title, $by, $desc, $date]);
        }
    }
}

// Save or update stats
// 🔁 Insert new season-based stats
if (!empty($_POST['season']) && is_array($_POST['season'])) {
    $stmt = $pdo->prepare("INSERT INTO player_stats 
        (profile_id, season, club, league_title, from_date, to_date, games_played, goals, assists, clean_sheets, yellow_cards, red_cards, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    for ($i = 0; $i < count($_POST['season']); $i++) {
        $season = trim($_POST['season'][$i] ?? '');
        $club = trim($_POST['club'][$i] ?? '');
        $league = trim($_POST['league_title'][$i] ?? '');
        $from = $_POST['from_date'][$i] ?? null;
        $to = $_POST['to_date'][$i] ?? null;

        $games = (int) ($_POST['games_played'][$i] ?? 0);
        $goals = (int) ($_POST['goals'][$i] ?? 0);
        $assists = (int) ($_POST['assists'][$i] ?? 0);
        $clean = (int) ($_POST['clean_sheets'][$i] ?? 0);
        $yellow = (int) ($_POST['yellow_cards'][$i] ?? 0);
        $red = (int) ($_POST['red_cards'][$i] ?? 0);

        if ($season && $club) {
            $stmt->execute([
                $profileId, $season, $club, $league, $from, $to,
                $games, $goals, $assists, $clean, $yellow, $red
            ]);
        }
    }
}


// ✅ Success feedback
$_SESSION['toast'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
header($userRole === 'admin' ? "Location: ../admin/admin_dashboard.php" : "Location: dashboard.php");
exit;
