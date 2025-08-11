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

$stmt = $pdo->query("
  SELECT 
    u.id AS user_id,
    u.username,
    u.is_verified,
    p.id AS profile_id,
    p.full_name,
    p.position,
    p.location,
    p.created_at
  FROM player_profiles p
  JOIN users u ON p.user_id = u.id
  ORDER BY p.created_at DESC
");
$players = $stmt->fetchAll();

// Count flagged reviews
$flaggedStmt = $pdo->query("
    SELECT COUNT(*) FROM player_reviews 
    WHERE flagged_unfair = 1 AND comment IS NOT NULL AND comment != ''
");
$flaggedCount = $flaggedStmt->fetchColumn();


// Total players
$totalPlayers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Total awards
$totalAwards = $pdo->query("SELECT COUNT(*) FROM player_awards")->fetchColumn();

// Total reviews
$totalReviews = $pdo->query("SELECT COUNT(*) FROM player_reviews")->fetchColumn();

// Flagged reviews
$flaggedReviews = $pdo->query("SELECT COUNT(*) FROM player_reviews WHERE flagged_unfair = 1")->fetchColumn();


// Signups per month (last 6 months)
$signupStats = $pdo->query("
  SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
  FROM player_profiles
  GROUP BY month
  ORDER BY month DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);
$signupMonths = json_encode(array_reverse(array_keys($signupStats)));
$signupCounts = json_encode(array_reverse(array_values($signupStats)));

// Chart data populate 
function getMonthlyData($pdo, $column, $filter = '') {
    $query = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM player_reviews
        WHERE $column $filter
        GROUP BY month
        ORDER BY month DESC
        LIMIT 6
    ";
    return $pdo->query($query)->fetchAll(PDO::FETCH_KEY_PAIR);
}

$signupStats = $pdo->query("
  SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
  FROM users
  GROUP BY month
  ORDER BY month DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$reviewStats        = getMonthlyData($pdo, '1'); // total reviews
$approvedStats      = getMonthlyData($pdo, 'comment_approved = 1');
$rejectedStats      = getMonthlyData($pdo, 'comment_approved = -1');
$flaggedStats       = getMonthlyData($pdo, 'flagged_unfair = 1');

// X-axis: months
$months = array_reverse(array_unique(array_merge(
    array_keys($signupStats),
    array_keys($reviewStats),
    array_keys($approvedStats),
    array_keys($rejectedStats),
    array_keys($flaggedStats)
)));

$labels = json_encode($months);

// Fill missing months with 0 for each dataset
function fillData($months, $dataMap) {
    $filled = [];
    foreach ($months as $month) {
        $filled[] = isset($dataMap[$month]) ? (int)$dataMap[$month] : 0;
    }
    return json_encode($filled);
}

$signupData    = fillData($months, $signupStats);
$reviewData    = fillData($months, $reviewStats);
$approvedData  = fillData($months, $approvedStats);
$rejectedData  = fillData($months, $rejectedStats);
$flaggedData   = fillData($months, $flaggedStats);

// Players signups per day
$playersByDay = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE role = 'player' 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Reviews per day
$reviewsByDay = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM player_reviews  
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Flagged reviews per day
$flaggedByDay = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM player_reviews 
    WHERE flagged_unfair = 1 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Approved reviews per day
$approvedByDay = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM player_reviews 
    WHERE comment_approved = 1 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Rejected reviews per day
$rejectedByDay = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM player_reviews 
    WHERE comment_approved = 0 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Merge all dates
$allDates = array_unique(array_merge(
    array_keys($playersByDay),
    array_keys($reviewsByDay),
    array_keys($flaggedByDay),
    array_keys($approvedByDay),
    array_keys($rejectedByDay)
));
sort($allDates);


?>
<!DOCTYPE html>
<html>
<head>
    <title>NextKick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/toast.css">
     <link rel="stylesheet" href="../css/style.css">
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

<?php if ($flaggedCount > 0): ?>
    <div class="alert alert-danger d-flex justify-content-between align-items-start p-3 mb-4 rounded shadow-sm position-relative"
         style="background: linear-gradient(135deg, #fff1f1, #ffe0e0); border-left: 5px solid #dc3545;">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-flag-fill text-danger fs-5"></i>
            <div>
                <strong class="text-dark"><?= $flaggedCount ?> review<?= $flaggedCount > 1 ? 's have' : ' has' ?> been flagged as unfair</strong>
                <div class="text-muted small">Player(s) have requested admin review</div>
                <a href="flagged_reviews.php" class="btn btn-sm btn-outline-danger mt-2">Review Flags</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card shadow-sm border-start border-primary border-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">👤 Total Players</h6>
        <h4 class="text-dark"><?= $totalPlayers ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-start border-success border-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">🏅 Total Awards</h6>
        <h4 class="text-dark"><?= $totalAwards ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-start border-warning border-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">⭐ Total Reviews</h6>
        <h4 class="text-dark"><?= $totalReviews ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-start border-danger border-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">🚩 Flagged Reviews</h6>
        <h4 class="text-dark"><?= $flaggedReviews ?></h4>
      </div>
    </div>
  </div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3">📈 Platform Trends (Last 6 Months)</h5>
    <canvas id="platformChart" height="120"></canvas>
  </div>
</div>



</div>


<h2 class="mb-4">👑 Admin Panel - All Player Profiles</h2>

<div class="table-responsive">
<table class="table table-bordered table-hover">
    <thead class="table-dark" >
        <tr>
            <th>SUBSCRIPTION</th>
            <th>NAME</th>
            <th>LOCATION</th>
            <th>POSITION</th>
            <th>CREATED</th>
            <th>ACTIONS</th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($players as $player): ?>
    <tr>
        <td>
          <?php if (!empty($player['is_verified'])): ?>
            <span class="badge bg-success">✔ Verified</span>
          <?php else: ?>
            <span class="badge bg-secondary">Free</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($player['full_name']) ?></td>
        <td><?= htmlspecialchars($player['location']) ?></td>
        <td><?= htmlspecialchars($player['position']) ?></td>    
        <td><?= date('d M Y', strtotime($player['created_at'])) ?></td>
        <td>
           <a href="admin_edit_player.php?id=<?= $player['user_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
           <a href="../public/profile.php?id=<?= $player['profile_id'] ?>&ref=admin" class="btn btn-sm btn-outline-info" target="_blank">View</a>
           <!-- <a href="delete_player.php?id=<?= $player['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this player?')">Delete</a> -->
        </td>
    </tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



<script>
  const ctx = document.getElementById('platformChart').getContext('2d');
  const platformChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= $labels ?>,
      datasets: [
        {
          label: '👤 Signups',
          data: <?= $signupData ?>,
          borderColor: '#007bff',
          backgroundColor: 'rgba(0, 123, 255, 0.1)',
          tension: 0.3,
          fill: false
        },
        {
          label: '⭐ Reviews',
          data: <?= $reviewData ?>,
          borderColor: '#ffc107',
          backgroundColor: 'rgba(255, 193, 7, 0.1)',
          tension: 0.3,
          fill: false
        },
        {
          label: '🚩 Reviews Flagged',
          data: <?= $flaggedData ?>,
          borderColor: '#6f42c1',
          backgroundColor: 'rgba(111, 66, 193, 0.1)',
          tension: 0.3,
          fill: false
        }
      ]
    },
    options: {
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            font: {
              size: 13
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  });
</script>


<script src="../js/toast.js"></script>
<?php include '../includes/toast.php'; ?>
</body>
</html>
