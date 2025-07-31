<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Player Profile - NextKick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php require_once '../includes/header.php'; ?>
    <h2 class="mb-4">Create Your Player Profile</h2>

   <form method="post" action="save_profile.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Position</label>
            <select name="position" class="form-control" required>
                <option value="">Select</option>
                <option>Goalkeeper</option>
                <option>Defender</option>
                <option>Midfielder</option>
                <option>Winger</option>
                <option>Striker</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Location (City, State)</label>
            <input type="text" name="location" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Instagram URL</label>
            <input type="url" name="instagram_url" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">YouTube Highlight Video URL</label>
            <input type="url" name="youtube_url" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Short Bio</label>
            <textarea name="bio" class="form-control" rows="4"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Profile Picture (optional)</label>
            <input type="file" name="profile_picture" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn btn-success">Save Profile</button>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
</body>
</html>
