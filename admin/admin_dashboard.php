<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch all player profiles
$stmt = $pdo->query("SELECT p.*, u.username FROM player_profiles p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$players = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>NextKick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">NextKick</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">

<h2 class="mb-4">👑 Admin Panel - All Player Profiles</h2>

<div class="table-responsive">
<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>Player</th>
            <th>Position</th>
            <th>Location</th>
            <th>Username</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($players as $player): ?>
        <tr>
            <td><?= htmlspecialchars($player['full_name']) ?></td>
            <td><?= htmlspecialchars($player['position']) ?></td>
            <td><?= htmlspecialchars($player['location']) ?></td>
            <td><?= htmlspecialchars($player['username']) ?></td>
            <td><?= date('d M Y', strtotime($player['created_at'])) ?></td>
            <td>
                <a href="../public/profile.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-info" target="_blank">View</a>
                <a href="delete_player.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this player?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

</div>

</body>
</html>
