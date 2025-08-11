<?php
require_once 'includes/db.php';
session_start();

// // Fetch recent or random player profiles (limit 12 for homepage)
// $stmt = $pdo->prepare("SELECT * FROM player_profiles ORDER BY created_at DESC LIMIT 12");
// $stmt->execute();
// $players = $stmt->fetchAll();

// $sql = "SELECT p.*, u.username, u.subscription_plan, u.is_verified
//         FROM player_profiles p
//         JOIN users u ON p.user_id = u.id
//         WHERE 1=1";
// $stmt = $pdo->prepare($sql);
// $players = $stmt->fetchAll();




// Get filter values
$position = $_GET['position'] ?? '';
$location = $_GET['location'] ?? '';
$name = $_GET['name'] ?? '';

// Build WHERE clause dynamically
$where = [];
$params = [];

if ($position) {
    $where[] = "position = ?";
    $params[] = $position;
}
if ($location) {
    $where[] = "location = ?";
    $params[] = $location;
}
if ($name) {
    $where[] = "full_name LIKE ?";
    $params[] = '%' . $name . '%';
}

$sql = "SELECT p.*, u.username, u.subscription_plan, u.is_verified
        FROM player_profiles p
        JOIN users u ON p.user_id = u.id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC LIMIT 12";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$players = $stmt->fetchAll();
?>



<!DOCTYPE html>
<html>
<head>
    <title>Explore Players - ProPlayer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="css/style.css"> -->
    <link rel="stylesheet" href="css/toast.css">
    <style>
.card-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #222;
}

.card-text p {
  margin-bottom: 4px;
  font-size: 0.8rem;
  color: #444;
}

.card-text small {
  font-size: 0.85rem;
  color: #777;
}

.btn-view {
  font-size: 0.85rem;
  padding: 5px 10px;
}

.card-body {
  padding: 0.75rem;
}

@media (max-width: 768px) {
  .card-title {
    font-size: 1rem;
  }
  .card-text p {
    font-size: 0.9rem;
  }
  .player-photo {
    width: 100px !important;
    height: 100px !important;
  }
}

        .card:hover {
            transform: scale(1.02);
            transition: all 0.2s ease-in-out;
        }
     
    </style>
</head>
<body class="bg-light">
    
<?php if (!empty($success_message)): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>

  <script>showToast('error', <?= json_encode($error_message) ?>);</script>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">NextKick</a>
        <div class="d-flex">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="public/dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                <a href="public/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            <?php else: ?>
                <a href="public/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
                <a href="public/register.php" class="btn btn-outline-success btn-sm">Join Free</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4 text-center">🔥 Featured Players</h2>

   <!-- Advanced Search Filters -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="name" class="form-control" placeholder="Search by name" value="<?= htmlspecialchars($name) ?>">
        </div>
        <div class="col-md-3">
            <select name="position" class="form-select">
                <option value="">All Positions</option>
                <option value="Winger" <?= $position === 'Winger' ? 'selected' : '' ?>>Winger</option>
                <option value="Striker" <?= $position === 'Striker' ? 'selected' : '' ?>>Striker</option>
                <option value="Goalkeeper" <?= $position === 'Goalkeeper' ? 'selected' : '' ?>>Goalkeeper</option>
                <option value="Midfielder" <?= $position === 'Midfielder' ? 'selected' : '' ?>>Midfielder</option>
                <option value="Defender" <?= $position === 'Defender' ? 'selected' : '' ?>>Defender</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="location" class="form-select">
                <option value="">All Locations</option>
                <option value="Brisbane" <?= $location === 'Brisbane' ? 'selected' : '' ?>>Brisbane</option>
                <option value="Sydney, NSW" <?= $location === 'Sydney, NSW' ? 'selected' : '' ?>>Sydney, NSW</option>
                <option value="Melbourne, VIC" <?= $location === 'Melbourne, VIC' ? 'selected' : '' ?>>Melbourne, VIC</option>
                <option value="Gold Coast, QLD" <?= $location === 'Gold Coast, QLD' ? 'selected' : '' ?>>Gold Coast, QLD</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if (count($players) > 0): ?>
        <div class="row">
            <?php foreach ($players as $player): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100 border-0 d-flex flex-row align-items-center p-2" style="min-height: 140px;">
                        <div class="me-3">
                        <?php if (!empty($player['profile_picture'])): ?>
                            <img src="uploads/profile_pictures/<?= htmlspecialchars($player['profile_picture']) ?>" 
                                alt="Player photo" class="rounded" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://placehold.co/120x120?text=Photo" 
                                alt="Default" class="rounded" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php endif; ?>
                        </div>

                        <div class="flex-grow-1">
                        <h5 class="card-title mb-1">
                        <?= htmlspecialchars($player['full_name']) ?>
                        <?php if (!empty($player['is_verified'])): ?>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/e/e4/Twitter_Verified_Badge.svg"
                                alt="Verified" title="Verified Account"
                                style="width:18px; height:18px; vertical-align:middle; margin-left:4px;">
                        <?php endif; ?>
                        </h5>
                        <div class="card-text">
                        <p><strong>Position:</strong> <?= htmlspecialchars($player['position']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($player['location']) ?></p>
                        <!-- <small><?= htmlspecialchars(substr($player['bio'], 0, 70)) ?>...</small>    -->
                        </div>
                        <a href="public/profile.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-primary btn-view mt-2">View Profile</a>
                        </div>
                    </div>
                    </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No player profiles yet. Be the first to <a href="register.php">create one</a>!</div>
    <?php endif; ?>
</div>
<script src="js/toast.js"></script>
<?php include 'includes/toast.php';?>
</body>
</html>
