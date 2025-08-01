<?php
require_once '../includes/db.php';
$isAdminViewing = isset($_GET['ref']) && $_GET['ref'] === 'admin';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid profile ID.";
    exit;
}

$profileId = $_GET['id'];

// Fetch profile from DB
$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE id = ?");
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

if (!$profile) {
    echo "Profile not found.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($profile['full_name']) ?> - Player Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .player-card {
            max-width: 800px;
            margin: auto;
        }
        .player-img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">
<?php 
require_once '../includes/header.php';
?> 

    <a href="dashboard.php" class="btn btn-sm btn-secondary mb-3">← Back</a>
    <a href="edit_profile.php" class="btn btn-sm btn-secondary mb-3">Edit Profile</a>

    <div class="card shadow player-card">
        <div class="card-body">
            <?php if (!empty($profile['profile_picture'])): ?>
                <img src="../uploads/profile_pictures/<?= htmlspecialchars($profile['profile_picture']) ?>" 
                    class="img-fluid mb-3 rounded" style="max-height: 250px;" alt="Player Photo">
            <?php endif; ?>
            
            <h2 class="card-title"><?= htmlspecialchars($profile['full_name']) ?></h2>
            <p class="mb-1"><strong>Position:</strong> <?= htmlspecialchars($profile['position']) ?></p>
            <p class="mb-3"><strong>Location:</strong> <?= htmlspecialchars($profile['location']) ?></p>

            <?php if (!empty($profile['bio'])): ?>
                <h5>Bio:</h5>
                <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($profile['youtube_url'])): ?>
                <h5 class="mt-4">Highlight Video:</h5>
                <div class="ratio ratio-16x9 mb-3">
                    <iframe src="<?= str_replace("watch?v=", "embed/", htmlspecialchars($profile['youtube_url'])) ?>" 
                            frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; ?>

            <?php if (!empty($profile['instagram_url'])): ?>
                <p><strong>Instagram:</strong> <a href="<?= htmlspecialchars($profile['instagram_url']) ?>" target="_blank"><?= htmlspecialchars($profile['instagram_url']) ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>

