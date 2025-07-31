<?php
require_once 'includes/db.php';
session_start();

// Fetch recent or random player profiles (limit 12 for homepage)
$stmt = $pdo->prepare("SELECT * FROM player_profiles ORDER BY created_at DESC LIMIT 12");
$stmt->execute();
$players = $stmt->fetchAll();

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

$sql = "SELECT * FROM player_profiles";
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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-title { font-size: 1.2rem; font-weight: bold; }
        .card-text { font-size: 0.95rem; color: #555; }
        .btn-view { font-size: 0.9rem; }
        .card:hover {
            transform: scale(1.02);
            transition: all 0.2s ease-in-out;
        }
        .player-photo {
            height: 320px;
            object-fit: cover;
            width: 100%;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
    </style>
</head>
<body class="bg-light">

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
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <?php if (!empty($player['profile_picture'])): ?>
                            <img class="card-img-top player-photo" src="uploads/profile_pictures/<?= htmlspecialchars($player['profile_picture']) ?>" alt="Player photo">
                        <?php else: ?>
                            <img src="https://placehold.co/400x220?text=Player+Photo" class="card-img-top player-photo" alt="Default photo">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($player['full_name']) ?></h5>
                            <p class="card-text">
                                <strong>Position:</strong> <?= htmlspecialchars($player['position']) ?><br>
                                <strong>Location:</strong> <?= htmlspecialchars($player['location']) ?><br>
                                <small><?= htmlspecialchars(substr($player['bio'], 0, 70)) ?>...</small>
                            </p>
                            <a href="public/profile.php?id=<?= $player['id'] ?>" class="btn btn-outline-primary btn-sm">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No player profiles yet. Be the first to <a href="register.php">create one</a>!</div>
    <?php endif; ?>
</div>

</body>
</html>
