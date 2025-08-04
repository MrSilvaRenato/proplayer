<?php
session_start();
require_once '../includes/db.php';

// Admin access check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch all flagged unfair reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username AS reviewer_name, pp.full_name AS player_name, pp.id AS player_id
    FROM player_reviews r
    JOIN users u ON r.reviewer_user_id = u.id
    JOIN player_profiles pp ON r.profile_id = pp.id
    WHERE r.flagged_unfair = 1
    ORDER BY r.updated_at DESC
");
$stmt->execute();
$flaggedReviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flagged Reviews – Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">NextKick Admin</a>
        <div>
            <a href="admin_dashboard.php" class="btn btn-sm btn-outline-light">Dashboard</a>
            <a href="../public/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-4"><i class="bi bi-flag-fill text-danger"></i> Flagged Reviews</h3>

    <?php if (empty($flaggedReviews)): ?>
        <div class="alert alert-secondary">No flagged reviews at this time.</div>
    <?php else: ?>
        <?php foreach ($flaggedReviews as $review): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h6>
                        <span class="text-muted">Player:</span>
                        <a href="../public/profile.php?id=<?= $review['player_id'] ?>&ref=admin"><?= htmlspecialchars($review['player_name']) ?></a>
                    </h6>
                    <p>
                        <strong>Reviewer:</strong> <?= htmlspecialchars($review['reviewer_name']) ?><br>
                        <strong>Rating:</strong> <?= str_repeat('⭐', $review['rating']) ?><br>
                        <strong>Comment:</strong> <?= htmlspecialchars($review['comment']) ?>
                    </p>

                    <form action="handle_flagged_review.php" method="post" class="d-flex gap-2">
                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                        <button name="action" value="restore" class="btn btn-sm btn-success">✅ Restore</button>
                        <button name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this review permanently?')">🗑️ Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
