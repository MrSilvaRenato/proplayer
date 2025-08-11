<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// / is verified?
$userId = $_SESSION['user_id'];
// Get filters if submitted
$position = $_GET['position'] ?? '';
$location = $_GET['location'] ?? '';

$sql = "SELECT p.*, u.username, u.subscription_plan, u.is_verified
        FROM player_profiles p
        JOIN users u ON p.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($position)) {
    $sql .= " AND p.position = ?";
    $params[] = $position;
}

if (!empty($location)) {
    $sql .= " AND p.location LIKE ?";
    $params[] = "%$location%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$players = $stmt->fetchAll();




?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Players - NextKick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php require_once '../includes/header.php'; ?>
    <h2 class="mb-4">Search Player Profiles</h2>

    <form method="get" class="row mb-4">
        <div class="col-md-4 mb-2">
            <label class="form-label">Position</label>
            <select name="position" class="form-select">
                <option value="">Any</option>
                <option <?= $position == 'Goalkeeper' ? 'selected' : '' ?>>Goalkeeper</option>
                <option <?= $position == 'Defender' ? 'selected' : '' ?>>Defender</option>
                <option <?= $position == 'Midfielder' ? 'selected' : '' ?>>Midfielder</option>
                <option <?= $position == 'Winger' ? 'selected' : '' ?>>Winger</option>
                <option <?= $position == 'Striker' ? 'selected' : '' ?>>Striker</option>
            </select>
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($location) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end mb-2">
            <button type="submit" class="btn btn-primary me-2">Search</button>
            <a href="search.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <?php if ($players): ?>
        <div class="row">
            <?php foreach ($players as $player): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
    <h5><?= htmlspecialchars($player['full_name']) ?>
<?php if (!empty($player['is_verified'])): ?>
  <img src="https://upload.wikimedia.org/wikipedia/commons/e/e4/Twitter_Verified_Badge.svg" 
       alt="Verified" title="Verified Account"
       style="width:18px; height:18px; margin-left:6px; vertical-align:middle;">
<?php endif; ?>
</h5>
                            <p><strong><?= htmlspecialchars($player['position']) ?></strong><br>
                               <?= htmlspecialchars($player['location']) ?></p>
                            <a href="profile.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No players found. Try adjusting your search.</div>
    <?php endif; ?>
</div>
</body>
</html>
