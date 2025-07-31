<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch profile
$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: create_profile.php');
    exit;
}

require_once '../includes/header.php';
?>

<h2>Edit Your Profile</h2>

<form action="update_profile.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">

    <div class="mb-3">
        <label for="full_name" class="form-label">Full Name</label>
        <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($profile['full_name']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="position" class="form-label">Position</label>
        <input type="text" name="position" id="position" class="form-control" value="<?= htmlspecialchars($profile['position']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="location" class="form-label">Location</label>
        <input type="text" name="location" id="location" class="form-control" value="<?= htmlspecialchars($profile['location']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="instagram_url" class="form-label">Instagram URL</label>
        <input type="url" name="instagram_url" id="instagram_url" class="form-control" value="<?= htmlspecialchars($profile['instagram_url']) ?>">
    </div>

    <div class="mb-3">
        <label for="youtube_url" class="form-label">YouTube URL</label>
        <input type="url" name="youtube_url" id="youtube_url" class="form-control" value="<?= htmlspecialchars($profile['youtube_url']) ?>">
    </div>

    <div class="mb-3">
        <label for="bio" class="form-label">Short Bio</label>
        <textarea name="bio" id="bio" rows="3" class="form-control" required><?= htmlspecialchars($profile['bio']) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="profile_picture" class="form-label">Change Profile Photo</label>
        <input type="file" name="profile_picture" id="profile_picture" class="form-control">
        <?php if (!empty($profile['profile_picture'])): ?>
            <p class="mt-2">Current: <img src="../uploads/profile_pictures/<?= htmlspecialchars($profile['profile_picture']) ?>" alt="Current photo" width="100"></p>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-success">Save Changes</button>
    <a href="dashboard.php" class="btn btn-link">Cancel</a>
</form>

</div>
</body>
</html>
