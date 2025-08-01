<?php
session_start();
require_once '../includes/db.php';

// Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Validate ID
if (!isset($_GET['id'])) {
    echo "Invalid player ID.";
    exit;
}
$playerId = intval($_GET['id']);

// Fetch player profile
$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE id = ?");
$stmt->execute([$playerId]);
$player = $stmt->fetch();

if (!$player) {
    echo "Player not found.";
    exit;
}

// Handle update
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $location = trim($_POST['location']);
    $bio = trim($_POST['bio']);
    $instagram_url = trim($_POST['instagram_url']);
    $youtube_url = trim($_POST['youtube_url']);

    $stmt = $pdo->prepare("UPDATE player_profiles SET full_name = ?, position = ?, location = ?, bio = ?, instagram_url = ?, youtube_url = ? WHERE id = ?");
    $stmt->execute([$full_name, $position, $location, $bio, $instagram_url, $youtube_url, $playerId]);
    $success = true;

    // Refresh player data
    $stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Player - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3 class="mb-4">✏️ Edit Player Profile (Admin)</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Profile updated successfully.</div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($player['full_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Position</label>
            <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($player['position']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Location</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($player['location']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Instagram URL</label>
            <input type="url" name="instagram_url" class="form-control" value="<?= htmlspecialchars($player['instagram_url']) ?>">
        </div>
        <div class="mb-3">
            <label>YouTube URL</label>
            <input type="url" name="youtube_url" class="form-control" value="<?= htmlspecialchars($player['youtube_url']) ?>">
        </div>
        <div class="mb-3">
            <label>Bio</label>
            <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($player['bio']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
</body>
</html>

