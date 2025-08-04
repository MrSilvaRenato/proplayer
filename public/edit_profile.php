<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}



$userId = $_SESSION['user_id'];

$isAdminViewing = isset($_GET['ref']) && $_GET['ref'] === 'admin';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid profile ID.";
    exit;
}
// Fetch profile
$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: create_profile.php');
    exit;
}

// Fetch skills
$skillsStmt = $pdo->prepare("SELECT * FROM player_skills WHERE profile_id = ?");
$skillsStmt->execute([$profile['id']]);
$skills = $skillsStmt->fetchAll();

// Fetch awards
$awardsStmt = $pdo->prepare("SELECT * FROM player_awards WHERE profile_id = ?");
$awardsStmt->execute([$profile['id']]);
$awards = $awardsStmt->fetchAll();

// Fetch stats
$statsStmt = $pdo->prepare("SELECT * FROM player_stats WHERE profile_id = ?");
$statsStmt->execute([$profile['id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<h2>Edit Your Profile</h2>
<!-- Top Controls -->
<div class="d-flex justify-content-between align-items-start mb-3">
  <a href="<?= $isAdminViewing ? '../admin/admin_dashboard.php' : 'profile.php?id='.$profile['id'] ?>" class="btn btn-outline-secondary">← Back</a></div>

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

    <!-- Skills -->
    <h5>Update Skills</h5>
    <div id="skillsContainer">
        <?php foreach ($skills as $index => $skill): ?>
        <div class="row mb-2">
            <div class="col-md-6">
                <input type="text" name="skills[]" class="form-control" value="<?= htmlspecialchars($skill['skill_name']) ?>" placeholder="e.g., Dribbling">
            </div>
            <div class="col-md-6">
                <select name="levels[]" class="form-control">
                    <?php foreach (['Developing', 'Competent', 'Proficient', 'Elite'] as $level): ?>
                    <option <?= $skill['level'] === $level ? 'selected' : '' ?>><?= $level ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addSkill()">+ Add Skill</button>

    <hr>
    <h5 class="mt-4">Update Stats</h5>
<h5 class="mt-4">Add New Season Stats</h5>
<div id="newStatsSection" class="border p-3 rounded bg-light">
  <div class="row g-2">
    <div class="col-md-3">
      <label>Season</label>
      <input name="season[]" class="form-control" placeholder="e.g. 2024/25">
    </div>
    <div class="col-md-3">
      <label>Club</label>
      <input name="club[]" class="form-control" placeholder="e.g. Brisbane FC">
    </div>
    <div class="col-md-3">
      <label>League Title</label>
      <input name="league_title[]" class="form-control" placeholder="e.g. QLD Premier League">
    </div>
    <div class="col-md-3">
      <label>From Date</label>
      <input name="from_date[]" type="date" class="form-control">
    </div>
    <div class="col-md-3">
      <label>To Date</label>
      <input name="to_date[]" type="date" class="form-control">
    </div>
    <div class="col-md-2">
      <label>Games</label>
      <input name="games_played[]" class="form-control" type="number" min="0" value="0">
    </div>
    <div class="col-md-2">
      <label>Goals</label>
      <input name="goals[]" class="form-control" type="number" min="0" value="0">
    </div>
    <div class="col-md-2">
      <label>Assists</label>
      <input name="assists[]" class="form-control" type="number" min="0" value="0">
    </div>
    <div class="col-md-2">
      <label>Clean Sheets</label>
      <input name="clean_sheets[]" class="form-control" type="number" min="0" value="0">
    </div>
    <div class="col-md-1">
      <label>Yellows</label>
      <input name="yellow_cards[]" class="form-control" type="number" min="0" value="0">
    </div>
    <div class="col-md-1">
      <label>Reds</label>
      <input name="red_cards[]" class="form-control" type="number" min="0" value="0">
    </div>
  </div>
</div>


    <hr>
    <!-- Awards -->
    <h5>Add Awards</h5>
    <div id="awardsContainer">
        <?php foreach ($awards as $index => $award): ?>
        <div class="border p-2 mb-2">
            <input type="text" name="awards[<?= $index ?>][title]" class="form-control mb-1" value="<?= htmlspecialchars($award['title']) ?>" placeholder="Award Title">
            <input type="text" name="awards[<?= $index ?>][awarded_by]" class="form-control mb-1" value="<?= htmlspecialchars($award['awarded_by']) ?>" placeholder="Awarded By">
            <textarea name="awards[<?= $index ?>][description]" class="form-control mb-1" placeholder="Award Description"><?= htmlspecialchars($award['description']) ?></textarea>
            <input type="date" name="awards[<?= $index ?>][award_date]" class="form-control mb-1" value="<?= htmlspecialchars($award['award_date']) ?>">
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addAward()">+ Add Award</button>

    <div class="mb-3 mt-4">
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

<script src="../js/toast.js"></script>
<?php include '../includes/toast.php'; ?>

<script>
function addSkill() {
  const container = document.getElementById('skillsContainer');
  const row = document.createElement('div');
  row.className = 'row mb-2';
  row.innerHTML = `
    <div class="col-md-6">
      <input type="text" name="skills[]" class="form-control" placeholder="e.g., Passing">
    </div>
    <div class="col-md-6">
      <select name="levels[]" class="form-control">
        <option>Developing</option>
        <option>Competent</option>
        <option>Proficient</option>
        <option>Elite</option>
      </select>
    </div>
  `;
  container.appendChild(row);
}

function addAward() {
  const container = document.getElementById('awardsContainer');
  const index = container.children.length;
  const div = document.createElement('div');
  div.className = 'border p-2 mb-2';
  div.innerHTML = `
    <input type="text" name="awards[${index}][title]" class="form-control mb-1" placeholder="Award Title">
    <input type="text" name="awards[${index}][awarded_by]" class="form-control mb-1" placeholder="Awarded By">
    <textarea name="awards[${index}][description]" class="form-control mb-1" placeholder="Award Description"></textarea>
    <input type="date" name="awards[${index}][award_date]" class="form-control mb-1">
  `;
  container.appendChild(div);
}
</script>

</body>
</html>
