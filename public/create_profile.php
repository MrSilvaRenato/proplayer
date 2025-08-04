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

    <?php if (!empty($success_message)): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>

  <script>showToast('error', <?= json_encode($error_message) ?>);</script>
<?php endif; ?>
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


      <h5>Add Skills</h5>
<div id="skillsContainer">
    <div class="row mb-2">
        <div class="col-md-6">
            <input type="text" name="skills[]" class="form-control" placeholder="e.g., Dribbling">
        </div>
        <div class="col-md-6">
            <select name="levels[]" class="form-control">
                <option>Beginner</option>
                <option>Intermediate</option>
                <option>Advanced</option>
                <option>Expert</option>
            </select>
        </div>
    </div>
</div>
<button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addSkill()">+ Add Skill</button>

<hr>

<h5>Add Awards</h5>
<div id="awardsContainer">
    <div class="mb-3 border p-2 rounded">
        <input type="text" name="awards[0][title]" class="form-control mb-2" placeholder="Award Title">
        <input type="text" name="awards[0][awarded_by]" class="form-control mb-2" placeholder="Awarded By">
        <textarea name="awards[0][description]" class="form-control mb-2" placeholder="Award Description"></textarea>
        <input type="date" name="awards[0][award_date]" class="form-control mb-2">
    </div>
</div>
<button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addAward()">+ Add Award</button>

<hr>

<h5>Add Awards</h5>
<div class="mb-3">
    <input type="text" name="awards[0][title]" class="form-control mb-2" placeholder="Award Title">
    <input type="text" name="awards[0][awarded_by]" class="form-control mb-2" placeholder="Awarded By">
    <textarea name="awards[0][description]" class="form-control mb-2" placeholder="Award Description"></textarea>
    <input type="date" name="awards[0][award_date]" class="form-control mb-2">
</div>

        <button type="submit" class="btn btn-success">Save Profile</button>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        
    </form>
</div>
<script src="../js/toast.js"></script>
<?php include '../includes/toast.php'; ?>


<script>
let awardIndex = 1;

function addSkill() {
    const container = document.getElementById('skillsContainer');
    const row = document.createElement('div');
    // row.classList.add('row', 'mb-2');
 row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col-md-6">
            <input type="text" name="skills[]" class="form-control" placeholder="e.g., Passing">
        </div>
        <div class="col-md-6">
            <select name="levels[]" class="form-control">
                <option>Beginner</option>
                <option>Intermediate</option>
                <option>Advanced</option>
                <option>Expert</option>
            </select>
        </div>
    `;

    container.appendChild(row);
}

function addAward() {
    const container = document.getElementById('awardsContainer');
    const box = document.createElement('div');
    box.classList.add('mb-3', 'border', 'p-2', 'rounded');

    box.innerHTML = `
        <input type="text" name="awards[${awardIndex}][title]" class="form-control mb-2" placeholder="Award Title">
        <input type="text" name="awards[${awardIndex}][awarded_by]" class="form-control mb-2" placeholder="Awarded By">
        <textarea name="awards[${awardIndex}][description]" class="form-control mb-2" placeholder="Award Description"></textarea>
        <input type="date" name="awards[${awardIndex}][award_date]" class="form-control mb-2">
    `;
    container.appendChild(box);
    awardIndex++;
}
</script>

</body>
</html>
