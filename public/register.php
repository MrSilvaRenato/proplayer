<?php
require_once '../includes/db.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $errors[] = "All fields are required.";
    }

    // Email format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Password complexity check
    if (strlen($password) < 8 || 
        !preg_match('/[A-Za-z]/', $password) || 
        !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must be at least 8 characters and include both letters and numbers.";
    }

    // Confirm match
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email is already registered.";
        }
    }

    // Register user
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Account registered!'];
        header('Location: login.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - NextKick</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 500px;
            margin: 60px auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .form-label {
            font-weight: 500;
        }
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .toggle-password {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 35px;
            z-index: 10;
            font-size: 0.9rem;
            color: #999;
        }
    </style>
</head>
<body>

<?php require_once '../includes/header.php'; ?>

<div class="form-container">
    <h3 class="mb-4 text-center">🎯 Create Your Account</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <script>showToast('error', <?= json_encode($error_message) ?>);</script>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" placeholder="e.g. player123" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            <div class="form-text">We'll never share your email with anyone.</div>
        </div>

        <div class="mb-3 position-relative">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            <div class="form-text">Minimum 8 characters letters and numbers.</div>
        </div>

        <div class="mb-4 position-relative">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>

        <div class="text-center">
            <a href="login.php" class="btn btn-link">Already have an account?</a>
        </div>
    </form>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}
</script>

<script src="../js/toast.js"></script>
<?php include '../includes/toast.php'; ?>

</body>
</html>
