<?php
session_start();

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// // Fetch player profile
// $userId = $_SESSION['user_id'];
// $stmt = $pdo->prepare("SELECT * FROM player_profiles WHERE user_id = ?");
// $stmt->execute([$userId]);
// $profile = $stmt->fetch();


$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.subscription_plan, u.is_verified
    FROM player_profiles p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$awards = [];
$skills = [];
$allStats = [];

if ($profile) {
    $profileId = $profile['id'];

    // Fetch all raw stats
    $stmt = $pdo->prepare("SELECT * FROM player_stats WHERE profile_id = ?");
    $stmt->execute([$profileId]);
    $allStats = $stmt->fetchAll();

    // Fetch player awards
    $stmt = $pdo->prepare("SELECT * FROM player_awards WHERE profile_id = ?");
    $stmt->execute([$profileId]);
    $awards = $stmt->fetchAll();

    // Fetch player skills
    $stmt = $pdo->prepare("SELECT * FROM player_skills WHERE profile_id = ?");
    $stmt->execute([$profileId]);
    $skills = $stmt->fetchAll();

// Fetch rating counts (only approved reviews)
$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$stmt = $pdo->prepare("SELECT rating, COUNT(*) as count FROM player_reviews WHERE profile_id = ? AND comment_approved = 1 GROUP BY rating");
$stmt->execute([$profileId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $rating = (int)$row['rating'];
    $count = (int)$row['count'];
    $ratingCounts[$rating] = $count;
}
// Fetch rating counts (only approved reviews)
$stmt = $pdo->prepare("SELECT AVG(rating) FROM player_reviews WHERE profile_id = ? AND comment_approved = 1");
$stmt->execute([$profileId]);
$averageRating = $stmt->fetchColumn() ?: 0;

}

// Fetch count of unapproved reviews for this user's profile
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM player_reviews r
    JOIN player_profiles p ON r.profile_id = p.id
    WHERE p.user_id = ? AND r.comment IS NOT NULL AND r.comment != '' AND r.comment_approved = 0
");
$stmt->execute([$_SESSION['user_id']]);
$newReviewsCount = $stmt->fetchColumn();






require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body { background-color: #f4f6f9; }
    .dashboard-container { display: flex; flex-direction: row; gap: 30px; margin-top: 20px; }
    .sidebar {
        width: 220px; background: #fff; border: 1px solid #ddd; border-radius: 8px;
        padding: 20px; height: fit-content; position: sticky; top: 20px;
    }
    .sidebar h5 { font-weight: bold; margin-bottom: 15px; }
    .sidebar a { display: block; color: #007bff; margin-bottom: 10px; text-decoration: none; transition: 0.2s; }
    .sidebar a:hover { text-decoration: underline; }
    .main-panel {
        flex: 1; padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #ddd;
    }
    .stats-cards {
        display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;
    }
    .card-stat {
        background: #fff; border: 1px solid #ddd; border-left: 5px solid #007bff;
        padding: 15px 20px; border-radius: 6px; flex: 1 1 200px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .card-stat h5 { margin: 0; font-size: 1.2rem; }
    .card-stat small { color: #777; }
    .chart-row {
        display: flex; flex-wrap: wrap; gap: 20px;
    }
    .chart-container {
        flex: 1; min-width: 300px;
    }

    /* reviews alert */
    .alert-warning {
  background: linear-gradient(135deg, #fff3cd, #ffeeba);
  border-left: 6px solid #ffc107;

  [data-bs-toggle="tooltip"] {
    position: relative;
    z-index: 1051;
}

}
</style>

<h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! 
<?php if (!empty($profile['is_verified'])): ?>
  <img src="https://upload.wikimedia.org/wikipedia/commons/e/e4/Twitter_Verified_Badge.svg" 
       alt="Verified" title="Verified Account"
       style="width:18px; height:18px; margin-left:6px; vertical-align:middle;">
<?php endif; ?>
</h2>

<div class="dashboard-container">
    <div class="sidebar">
        <h5>📋 Menu</h5>
        <a href="profile.php?id=<?= $profile['id'] ?>" class="btn btn-outline-primary btn-sm mt-2">View Full Profile</a>
        <a href="#stats">📊 Stats</a>
        <a href="#charts">📈 Charts</a>
        <a href="#awards">🏆 Awards</a>
        <a href="#reviews">💬 Reviews</a>
        <a href="edit_profile.php?id=<?= $profile['id'] ?>">✏️ Edit Profile</a>
        <a href="logout.php">🚪 Logout</a>

<?php if ($newReviewsCount > 0): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-start p-3 mt-3 rounded shadow-sm position-relative"
         style="background: linear-gradient(135deg, #fffdf0, #fff4cc); border-left: 5px solid #ffc107;">
        <div class="d-flex align-items-start gap-1">
            <i class="bi bi-bell-fill text-warning fs-5"></i>
            <div>
                <strong class="text-dark"><?= $newReviewsCount ?> new review<?= $newReviewsCount > 1 ? 's' : '' ?></strong>
                <div class="text-muted small">pending your approval</div>
                <a href="profile.php?id=<?= $profileId ?>#manage-reviews" class="btn btn-sm btn-outline-warning mt-2">
                    Manage Reviews
                </a>
            </div>
        </div>

        <!-- Tooltip icon in the corner -->
        <span style="margin-left:70px;" data-bs-toggle="tooltip"
              data-bs-placement="right"
              title="Rejecting a review only hides the comment. The star rating still counts toward your average and cannot be removed."
              class="position-absolute bottom-0"
              style="cursor: help;">
            <i class="bi bi-exclamation-circle text-muted fs-7"></i>
        </span>
    </div>
<?php endif; ?>

        
    </div>

<div class="main-panel">
    <?php if ($profile): ?>
        <div class="row align-items-start">
            <!-- 📄 Profile Info on the Left -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person-circle me-2"></i> Profile Overview</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($profile['full_name']) ?></p>
                        <p><strong>Position:</strong> <?= htmlspecialchars($profile['position']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($profile['location']) ?></p>
                        <p><strong>Instagram:</strong>
                            <?php if (!empty($profile['instagram_url'])): ?>
                                <a href="<?= htmlspecialchars($profile['instagram_url']) ?>" target="_blank"><?= $profile['instagram_url'] ?></a>
                            <?php else: ?>
                                <span class="text-muted">Not provided</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- 📊 Review Analytics on the Right -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h6 class="card-title">Review Ratings</h6>
                        <h6 class="card-subtitle mb-2 text-muted">
                            Average Rating: <strong><?= number_format($averageRating, 1) ?> ⭐</strong>
                        </h6>
                        <canvas id="reviewPieChart" width="160" height="160"></canvas>
                    </div>
                </div>
            </div>
        </div>

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="filterSeason">Filter by Season:</label>
                    <select id="filterSeason" class="form-select" onchange="updateStats()">
                        <option value="all">All</option>
                       <?php
                            $seasons = array_unique(array_filter(array_column($allStats, 'season'), fn($s) => !empty($s)));
                            sort($seasons);
                            foreach ($seasons as $season) {
                                echo "<option value='" . htmlspecialchars($season) . "'>" . htmlspecialchars($season) . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="filterClub">Filter by Club:</label>
                    <select id="filterClub" class="form-select" onchange="updateStats()">
                        <option value="all">All</option>
                   <?php
                        $clubs = array_unique(array_filter(array_column($allStats, 'club'), fn($c) => !empty($c)));
                        sort($clubs);
                        foreach ($clubs as $club) {
                            echo "<option value='" . htmlspecialchars($club) . "'>" . htmlspecialchars($club) . "</option>";
                        }
                        ?>

                    </select>
                </div>
            </div>

            <!-- Stat Cards -->
          <!-- Stat Cards -->
<div class="row" id="stats">
    <div class="col-md-4 col-lg-2 mb-3">
        <div class="card shadow-sm text-center border-left-primary">
            <div class="card-body">
                <div class="display-5 mb-2">🧑‍⚽</div>
                <h5 class="stat-value">0</h5>
                <small class="text-muted">Games Played</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2 mb-3">
        <div class="card shadow-sm text-center border-left-success">
            <div class="card-body">
                <div class="display-5 mb-2">🥅</div>
                <h5 class="stat-value">0</h5>
                <small class="text-muted">Goals</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2 mb-3">
        <div class="card shadow-sm text-center border-left-info">
            <div class="card-body">
                <div class="display-5 mb-2">🅰️</div>
                <h5 class="stat-value">0</h5>
                <small class="text-muted">Assists</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2 mb-3">
        <div class="card shadow-sm text-center border-left-warning">
            <div class="card-body">
                <div class="display-5 mb-2">🧤</div>
                <h5 class="stat-value">0</h5>
                <small class="text-muted">Clean Sheets</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2 mb-3">
        <div class="card shadow-sm text-center border-left-warning">
            <div class="card-body">
                <div class="display-5 mb-2">🟨</div>
                <h5 class="stat-value">0</h5>
                <small class="text-muted">Yellows</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2 mb-3">
        <div class="card shadow-sm text-center border-left-danger">
            <div class="card-body">
                <div class="display-5 mb-2">🟥</div>
                <h5 class="stat-value">0</h5>
                <small class="text-muted">Reds</small>
            </div>
        </div>
    </div>
</div>


            <!-- Charts -->
            <div id="charts" class="mb-4">
                <h4>📈 Performance Charts</h4>
                <div class="chart-row">
                    <div class="chart-container">
                        <canvas id="skillsRadar" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <canvas id="goalsTrend" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Awards -->
            <div id="awards" class="mb-4">
                <h4>🏆 Awards</h4>
                <?php if ($awards): ?>
                    <ul>
                        <?php foreach ($awards as $award): ?>
                            <li><strong><?= htmlspecialchars($award['title']) ?></strong> – <?= htmlspecialchars($award['awarded_by']) ?> (<?= htmlspecialchars($award['award_date']) ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No awards added yet.</p>
                <?php endif; ?>
            </div>

            <!-- Reviews -->
            <div id="reviews" class="mb-4">
                <h4>💬 Reviews</h4>
                <?php
                $reviewStmt = $pdo->prepare("
                    SELECT r.*, u.username AS reviewer_name
                    FROM player_reviews r
                    JOIN users u ON r.reviewer_user_id = u.id
                    WHERE r.profile_id = ?
                    ORDER BY r.created_at DESC
                ");
                $reviewStmt->execute([$profile['id']]);
                $reviews = $reviewStmt->fetchAll();
                ?>
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="border rounded p-2 mb-2">
                            <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                            <small><?= htmlspecialchars($review['created_at']) ?></small>
                            <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="alert alert-warning">⚠️ You haven’t created a player profile yet.</div>
            <a href="create_profile.php" class="btn btn-primary">Create Your Profile</a>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<script>
    
const rawStats = <?= json_encode($allStats) ?>;
function updateStats() {
    const season = document.getElementById('filterSeason').value;
    const club = document.getElementById('filterClub').value;

    const filtered = rawStats.filter(stat =>
        (season === 'all' || stat.season === season) &&
        (club === 'all' || stat.club === club)
    );

    let totals = {
        games_played: 0, goals: 0, assists: 0,
        clean_sheets: 0, yellow_cards: 0, red_cards: 0
    };

    filtered.forEach(stat => {
        totals.games_played += parseInt(stat.games_played || 0);
        totals.goals += parseInt(stat.goals || 0);
        totals.assists += parseInt(stat.assists || 0);
        totals.clean_sheets += parseInt(stat.clean_sheets || 0);
        totals.yellow_cards += parseInt(stat.yellow_cards || 0);
        totals.red_cards += parseInt(stat.red_cards || 0);
    });

    const cards = document.querySelectorAll('#stats .card-body');
    const keys = Object.keys(totals);
    keys.forEach((key, i) => {
        cards[i].querySelector('.stat-value').textContent = totals[key];
    });

    // 🔽 Add this block at the end of updateStats()
    const labels = filtered.map(stat => {
        const s = stat.season || 'N/A';
        const c = stat.club || 'N/A';
        return `${s} - ${c}`;
    });

    const goalsData = filtered.map(stat => parseInt(stat.goals || 0));
    const assistsData = filtered.map(stat => parseInt(stat.assists || 0));

    goalsChart.data.labels = labels;
    goalsChart.data.datasets[0].data = goalsData;
    goalsChart.data.datasets[1].data = assistsData;
    goalsChart.update();
}



// Chart.js - Radar for Skills


const radarCtx = document.getElementById('skillsRadar').getContext('2d');

const skillLevels = <?= json_encode(array_map(fn($s) =>
    $s['level'] === 'Developing' ? 1 :
    ($s['level'] === 'Competent' ? 2 :
    ($s['level'] === 'Proficient' ? 3 : 4)), $skills)) ?>;

const skillColors = skillLevels.map(level => {
    if (level === 1) return '#007bff'; // Blue
    if (level === 2) return '#00c3ff'; // Light Blue
    if (level === 3) return '#ffc107'; // Amber
    return '#ffd700'; // Gold
});

new Chart(radarCtx, {
    type: 'radar',
    data: {
        labels: <?= json_encode(array_column($skills, 'skill_name')) ?>,
        datasets: [{
            label: 'Skill Level',
            data: skillLevels,
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderColor: '#007bff',
            borderWidth: 2,
            pointBackgroundColor: skillColors,
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: skillColors
        }]
    },
 options: {
  plugins: {
    datalabels: {
      color: '#333',
      font: { weight: 'bold', size: 16 },
      formatter: value => value
    }
  },
  scales: {
    r: {
      suggestedMin: 0,
      suggestedMax: 4,
      ticks: {
        stepSize: 1,
        callback: function (value) {
          const labels = ['–', 'Developing', 'Competent', 'Proficient', 'Elite'];
          return labels[value] || value;
        }
      },
      pointLabels: {
        font: {
          size: 16,  // 👈 Skill name font size
          weight: 'bold'
        },
        color: '#000' // 👈 Optional: makes it darker/clearer
      }
    }
  }
},
    plugins: [ChartDataLabels]
});


// Line Chart - Goals Trend (Placeholder)
const trendCtx = document.getElementById('goalsTrend').getContext('2d');
goalsChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: [], // will be updated
        datasets: [
            { label: 'Goals', data: [], borderColor: 'green', fill: false },
            { label: 'Assists', data: [], borderColor: 'blue', fill: false }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Initial stats rendering
updateStats();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<!-- Chart.js Pie Chart Script -->
 
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('reviewPieChart').getContext('2d');

    const chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
            datasets: [{
                label: 'Review Count',
                data: [
                    <?= $ratingCounts[1] ?>,
                    <?= $ratingCounts[2] ?>,
                    <?= $ratingCounts[3] ?>,
                    <?= $ratingCounts[4] ?>,
                    <?= $ratingCounts[5] ?>
                ],
                backgroundColor: [
                    '#dc3545', // red
                    '#fd7e14', // orange
                    '#ffc107', // yellow
                    '#0d6efd', // blue
                    '#198754'  // green
                ],
                borderWidth: 1
            
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const count = context.raw;
                            const label = context.label;
                            return `${label}: ${count} review${count !== 1 ? 's' : ''}`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 14
                    },
                    formatter: function (value, context) {
                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = total ? ((value / total) * 100).toFixed(1) : 0;
                        return `${percentage}%`;
                    }
                }
            }
        },
        plugins: [ChartDataLabels] // 👈 Register the plugin
    });
});

</script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<script src="../js/toast.js"></script>
<?php include '../includes/toast.php'; ?>

</body>

</html>
