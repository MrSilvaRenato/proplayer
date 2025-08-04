<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$isAdminViewing = isset($_GET['ref']) && $_GET['ref'] === 'admin';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid profile ID.";
    exit;
}

$profileId = $_GET['id'];

// Fetch profile
$stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE id = ?");
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

if (!$profile) {
    echo "Profile not found.";
    exit;
}

// Fetch skills
$skillsStmt = $pdo->prepare("SELECT * FROM player_skills WHERE profile_id = ?");
$skillsStmt->execute([$profileId]);
$skills = $skillsStmt->fetchAll();

// Fetch awards
$awardsStmt = $pdo->prepare("SELECT * FROM player_awards WHERE profile_id = ?");
$awardsStmt->execute([$profileId]);
$awards = $awardsStmt->fetchAll();


// Fetch all reviews for this player
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM player_reviews WHERE profile_id = ?");
$stmt->execute([$profile['id']]);
$avgData = $stmt->fetch();
$avgRating = round($avgData['avg_rating'] ?? 0, 1);

// Get current user's review (if any)
$userRating = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT rating FROM player_reviews WHERE profile_id = ? AND reviewer_user_id = ?");
    $stmt->execute([$profile['id'], $_SESSION['user_id']]);
    $userRating = $stmt->fetchColumn();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($profile['full_name']) ?> | Player Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/toast.css">
 <style>
  /* ============================
     🌐 Global Page Styles
     ============================ */
  body {
    background: #f7f9fb;
    font-family: 'Segoe UI', sans-serif;
  }

  .resume-container {
    max-width: 1000px;
    margin: 40px auto;
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  }

  /* ============================
     🌟 Player Profile Header UI
     ============================ */
  .profile-header {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    border-radius: 12px;
    background-color: #f0f4fa;
    padding: 20px 24px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    margin-bottom: 30px;
    border-left: 6px solid #0d6efd;
  }

  .profile-header img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 10px;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  }

  .profile-info h2 {
    margin: 0 0 6px;
    font-size: 1.85rem;
    font-weight: 700;
    color: #111;
  }

  .profile-info p {
    margin: 3px 0;
    font-size: 0.95rem;
  }

  .profile-info p strong {
    color: #444;
    width: 90px;
    display: inline-block;
  }

  .edit-btn {
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 6px;
    background: #0d6efd;
    color: #fff;
    text-decoration: none;
    transition: background 0.2s ease;
  }

  .edit-btn:hover {
    background: #084ec1;
  }

  /* ============================
     📁 Section Headers
     ============================ */
  .section h5 {
    font-weight: 600;
    margin-bottom: 12px;
  }

  /* ============================
     🎯 Skills Badges
     ============================ */
  .skills-list .badge {
    margin: 5px 6px 5px 0;
    font-size: 0.9rem;
    padding: 8px 14px;
    background-color: #edf2ff;
    color: #1a3d8f;
    border-radius: 30px;
  }

  /* ============================
     🏆 Awards / Achievements
     ============================ */
  .award-card {
    background: #f6f6f6;
    border-left: 4px solid #28a745;
    padding: 14px 16px;
    border-radius: 6px;
    margin-bottom: 12px;
  }

  /* ============================
     📹 YouTube Embed Section
     ============================ */
  .youtube-wrapper {
    aspect-ratio: 16 / 9;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
  }

  /* ============================
     📱 Mobile Responsive Header
     ============================ */
  @media (max-width: 576px) {
    .profile-header {
      flex-direction: column;
      text-align: center;
    }
  }

  /* ============================
     📊 Player Stats Grid
     ============================ */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
    margin-top: 16px;
  }

  .stat-box {
    background: #f1f5ff;
    border-radius: 10px;
    padding: 16px 12px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .stat-value {
    font-size: 1.8rem;
    font-weight: 600;
    color: #0d47a1;
    display: block;
  }

  .stat-label {
    font-size: 0.9rem;
    color: #555;
    margin-top: 6px;
    display: block;
  }

  .stat-value.yellow {
    color: #ffc107;
  }

  .stat-value.red {
    color: #dc3545;
  }

  /* ============================
     🏅 Trophy Shelf Cards
     ============================ */
  .trophy-shelf {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
  }

  .trophy-card {
    background: linear-gradient(to top right, #fffefa, #fff5e0);
    border: 2px solid #ffd700;
    border-radius: 12px;
    padding: 16px 20px;
    position: relative;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
  }

  .trophy-card::before {
    content: "🏆";
    font-size: 1.3rem;
    position: absolute;
    top: -8px;
    left: -23px;
    background: #fff;
    border: 2px solid #ffd700;
    border-radius: 50%;
    padding: 6px;
  }

  .trophy-card strong {
    font-size: 1.1rem;
    color: #333;
  }

  .trophy-card small {
    color: #666;
    display: block;
    margin-top: 4px;
    font-size: 0.85rem;
  }

  .trophy-card p {
    font-size: 0.9rem;
    margin-top: 8px;
    color: #444;
  }

  /* === Rating Summary Section === */
.rating-summary {
  margin-top: 10px;
}
.rating-summary h5 {
  font-size: 1.2rem;
  color: #ff9900;
}
.rating-bars .progress {
  background-color: #e0e0e0;
}
.rating-bars .progress-bar {
  transition: width 0.4s ease;
}

.text-warning {
  color: #ffc107 !important;
}

</style>

</head>
<body>

<div class="container resume-container">

<!-- Top Controls -->
<div class="d-flex justify-content-between align-items-start mb-3">
  <a href="<?= $isAdminViewing ? '../admin/admin_dashboard.php' : 'dashboard.php' ?>" class="btn btn-outline-secondary">← Back</a>

  <?php if (!$isAdminViewing && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile['user_id']): ?>
    <a href="edit_profile.php?id=<?= $profile['id'] ?>" class="edit-btn">Edit Profile</a>
  <?php endif; ?>
</div>

  <!-- Header -->
  <div class="profile-header">
        <?php if (!empty($profile['profile_picture'])): ?>
        <div class="text-center">
            <img src="../uploads/profile_pictures/<?= htmlspecialchars($profile['profile_picture']) ?>" alt="Profile Picture">
            
         <?php
    // avgRating and userRating must already be set earlier in your PHP
                // see Step 1 in previous message
            ?>
            <div class="mt-2 text-center">
                <div id="starRating" class="d-flex justify-content-center gap-1">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php
                        $starClass = 'text-muted';
                        if ($avgRating >= $i) $starClass = 'text-warning';
                        elseif ($avgRating >= $i - 0.5) $starClass = 'text-warning opacity-75';
                    ?>
                    <i class="bi bi-star-fill fs-4 <?= $starClass ?>" style="cursor:pointer" data-star="<?= $i ?>"></i>
                <?php endfor; ?>
                </div>
                <small class="text-muted">
                    <?= number_format($avgRating, 1) ?> out of 5
                    <?= $userRating ? '(You rated: ' . $userRating . ')' : '– Tap to rate this player' ?>
                </small>
            </div>

        </div>
        <?php endif; ?>


    <div class="profile-info">
      <h2><?= htmlspecialchars($profile['full_name']) ?></h2>
      <p><strong>Position:</strong> <?= htmlspecialchars($profile['position']) ?></p>
      <p><strong>Location:</strong> <?= htmlspecialchars($profile['location']) ?></p>
      <?php if (!empty($profile['instagram_url'])): ?>
        <p><strong>Instagram:</strong> <a href="<?= htmlspecialchars($profile['instagram_url']) ?>" target="_blank"><?= htmlspecialchars($profile['instagram_url']) ?></a></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bio -->
  <?php if (!empty($profile['bio'])): ?>
    <div class="section mb-4">
      <h5>About Me</h5>
      <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
    </div>
  <?php endif; ?>

  <!-- Skills -->
  <?php if ($skills): ?>
    <div class="section mb-4">
      <h5>Skills</h5>
      <div class="skills-list">
        <?php foreach ($skills as $skill): ?>
          <span class="badge"><?= htmlspecialchars($skill['skill_name']) ?> (<?= $skill['level'] ?>)</span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php
// Fetch stats

$stmt = $pdo->prepare("SELECT * FROM player_stats WHERE profile_id = ?");
$stmt->execute([$profile['id']]);
$stats = $stmt->fetchAll();
?>
<!-- Player Stats -->
<?php if ($stats): ?>
  <div class="section mb-4" id="performance-section">
    <h5><i class="bi bi-bar-chart-fill me-2"></i>Performance Stats</h5>

    <!-- Filters -->
    <div class="row mb-3">
      <div class="col-md-3">
        <select class="form-select" id="seasonFilter">
          <option value="all">All Seasons</option>
          <?php
          $seasons = array_unique(array_column($stats, 'season'));
          foreach ($seasons as $season) {
              echo '<option value="' . htmlspecialchars($season) . '">' . htmlspecialchars($season) . '</option>';
          }
          ?>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="clubFilter">
          <option value="all">All Clubs</option>
          <?php
          $clubs = array_unique(array_column($stats, 'club'));
          foreach ($clubs as $club) {
              echo '<option value="' . htmlspecialchars($club) . '">' . htmlspecialchars($club) . '</option>';
          }
          ?>
        </select>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-box">
        <span class="stat-value" id="gamesStat">0</span>
        <span class="stat-label">Games</span>
      </div>
      <div class="stat-box">
        <span class="stat-value" id="goalsStat">0</span>
        <span class="stat-label">Goals</span>
      </div>
      <div class="stat-box">
        <span class="stat-value" id="assistsStat">0</span>
        <span class="stat-label">Assists</span>
      </div>
      <div class="stat-box">
        <span class="stat-value" id="cleanStat">0</span>
        <span class="stat-label">Clean Sheets</span>
      </div>
      <div class="stat-box">
        <span class="stat-value yellow" id="yellowStat">0</span>
        <span class="stat-label">Yellows</span>
      </div>
      <div class="stat-box">
        <span class="stat-value red" id="redStat">0</span>
        <span class="stat-label">Reds</span>
      </div>
    </div>
  </div>
<?php endif; ?>



  <!-- Awards -->
<?php if ($awards): ?>
  <div class="section mb-4">
    <h5><i class="bi bi-award-fill me-2 text-warning"></i>Trophy Cabinet</h5>
    <div class="trophy-shelf">
      <?php foreach ($awards as $award): ?>
        <div class="trophy-card">
          <strong><?= htmlspecialchars($award['title']) ?></strong>
          <small>Awarded by <?= htmlspecialchars($award['awarded_by']) ?> on <?= date('F j, Y', strtotime($award['award_date'])) ?></small>
          <?php if (!empty($award['description'])): ?>
            <p><?= htmlspecialchars($award['description']) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>


  <!-- YouTube -->
  <?php if (!empty($profile['youtube_url'])): ?>
    <div class="section mb-2">
      <h5>Highlight Video</h5>
      <div class="youtube-wrapper">
        <iframe class="w-100 h-100"
          src="<?= str_replace("watch?v=", "embed/", htmlspecialchars($profile['youtube_url'])) ?>"
          frameborder="0" allowfullscreen></iframe>
      </div>
    </div>
  <?php endif; ?>

  <!-- Approved Reviews Feed -->
<div class="mt-5">
  <h5><i class="bi bi-chat-dots text-primary"></i> Player Reviews</h5>
<p class="text-muted small">
  <i class="bi bi-info-circle"></i>
  Comments are moderated by the profile owner to prevent inappropriate content. Star ratings remain independent to ensure fairness and integrity.
</p>
<!-- Approved Reviews Feed -->
<div class="mt-5">
  <h5><i class="bi bi-chat-dots text-primary"></i> Player Reviews</h5>
  <?php
    $stmt = $pdo->prepare("
     SELECT r.*, u.username AS reviewer_name
      FROM player_reviews r
      JOIN users u ON r.reviewer_user_id = u.id
      WHERE r.profile_id = ? AND r.comment_approved = 1
      ORDER BY r.created_at DESC
    ");
    $stmt->execute([$profile['id']]);

    $reviews = $stmt->fetchAll();

    if (count($reviews) === 0) {
        echo "<p class='text-muted'>No approved comments yet.</p>";
    } else {
        foreach ($reviews as $review) {
            $stars = str_repeat("⭐", (int)$review['rating']);
            echo "<div class='mb-3 p-3 border rounded bg-light'>";
            echo "<strong>{$review['reviewer_name']}</strong> <span class='text-warning'>$stars</span><br>";
            echo "<p class='mb-0'>{$review['comment']}</p>";
            echo "</div>";
        }
    }
  ?>
</div>

<!-- Manage comments -->
  
<!-- Manage comments -->
<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile['user_id']): ?>
  <div class="mt-5">
    <h5><i class="bi bi-shield-check text-secondary" id="manage-reviews"></i> Manage Comments</h5>
    <?php
      $stmt = $pdo->prepare("
        SELECT r.*, u.username AS reviewer_name
        FROM player_reviews r
        JOIN users u ON r.reviewer_user_id = u.id
        WHERE r.profile_id = ? AND r.comment IS NOT NULL AND r.comment != ''
        AND r.comment_approved = 0
        ORDER BY r.created_at DESC
      ");
      $stmt->execute([$profile['id']]);
      $pending = $stmt->fetchAll();

      if (count($pending) === 0) {
          echo "<p class='text-muted'>No pending comments for approval.</p>";
      } else {
          foreach ($pending as $review):
    ?>
        <div class="mb-3 p-3 border rounded bg-white">
          <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
          <span class='text-warning'><?= str_repeat("⭐", (int)$review['rating']) ?></span><br>
          <p><?= htmlspecialchars($review['comment']) ?></p>

          <?php if ($review['flagged_unfair']): ?>
            <span class="badge bg-secondary mb-2">🚩 Flagged as Unfair</span>
          <?php else: ?>
            <form method="post" action="../public/manage_review.php" onsubmit="return confirm('Flag this review as unfair?');" class="mb-2">
            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
            <input type="hidden" name="action" value="flag_unfair">
            <button type="submit" class="btn btn-sm btn-warning">🚩 Flag as Unfair</button>
          </form>
          <?php endif; ?>

          <form method="post" action="../public/manage_review.php" class="d-flex gap-2">
            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
            <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
            <button name="action" value="reject" class="btn btn-danger btn-sm">Hide</button>
          </form>
        </div>
    <?php
          endforeach;
      }
    ?>
  </div>
<?php endif; ?>



</div>



<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="reviewForm">
      <div class="modal-header">
        <h5 class="modal-title" id="reviewModalLabel">Leave a Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
        <input type="hidden" id="ratingInput" name="rating" value="">
        <div class="mb-3">
          <label for="comment" class="form-label">Your Comment (optional):</label>
          <textarea class="form-control" name="comment" id="comment" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Submit Review</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS for modal functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Your JS logic -->
<script>
document.querySelectorAll('#starRating i').forEach(star => {
  star.addEventListener('click', () => {
    const rating = star.dataset.star;
    document.getElementById('ratingInput').value = rating;
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  });
});

document.getElementById('reviewForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  const res = await fetch('../public/submit_review.php', {
    method: 'POST',
    body: formData
  });

  const result = await res.json();
  if (result.success) {
    alert("Thanks for your review!");
    location.reload();
  } else {
    alert(result.message || "Something went wrong.");
  }
});

// Player stats script

const allStats = <?= json_encode($stats) ?>;

  const seasonFilter = document.getElementById('seasonFilter');
  const clubFilter = document.getElementById('clubFilter');

  const statEls = {
    games: document.getElementById('gamesStat'),
    goals: document.getElementById('goalsStat'),
    assists: document.getElementById('assistsStat'),
    clean: document.getElementById('cleanStat'),
    yellow: document.getElementById('yellowStat'),
    red: document.getElementById('redStat'),
  };

  function updateStats() {
    const selectedSeason = seasonFilter.value;
    const selectedClub = clubFilter.value;

    const filtered = allStats.filter(stat => {
      return (selectedSeason === 'all' || stat.season === selectedSeason) &&
             (selectedClub === 'all' || stat.club === selectedClub);
    });

    const totals = {
      games: 0, goals: 0, assists: 0, clean: 0, yellow: 0, red: 0
    };

    filtered.forEach(stat => {
      totals.games += parseInt(stat.games_played);
      totals.goals += parseInt(stat.goals);
      totals.assists += parseInt(stat.assists);
      totals.clean += parseInt(stat.clean_sheets);
      totals.yellow += parseInt(stat.yellow_cards);
      totals.red += parseInt(stat.red_cards);
    });

    statEls.games.textContent = totals.games;
    statEls.goals.textContent = totals.goals;
    statEls.assists.textContent = totals.assists;
    statEls.clean.textContent = totals.clean;
    statEls.yellow.textContent = totals.yellow;
    statEls.red.textContent = totals.red;
  }

  // Init
  seasonFilter.addEventListener('change', updateStats);
  clubFilter.addEventListener('change', updateStats);
  updateStats(); // load on page
</script>

<script src="../js/toast.js"></script>
<?php include '../includes/toast.php'; ?>
</body>
</html>