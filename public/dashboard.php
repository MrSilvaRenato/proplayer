<?php
session_start();
require_once '../includes/db.php';


// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch player profile
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
require_once '../includes/header.php';
?>

    <h2 class="mb-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

    <?php if ($profile): ?>
        <div class="alert alert-success">
            ✅ Your player profile is complete. <a href="profile.php?id=<?= $profile['id'] ?>">View Profile</a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            ⚠️ You haven't created your player profile yet.
        </div>
        <a href="create_profile.php" class="btn btn-primary">Create Your Profile</a>
    <?php endif; ?>

    <a href="logout.php" class="btn btn-link mt-4">Logout</a>
</div>
</body>
</html>
