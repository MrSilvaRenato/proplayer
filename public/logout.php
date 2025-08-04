<?php
session_start();

// Save toast in a temp variable
$toast = ['type' => 'success', 'message' => 'You have been logged out.'];

// Destroy the session
session_unset();
session_destroy();

// Start a fresh session to store the toast
session_start();
$_SESSION['toast'] = $toast;

header('Location: ../index.php');
exit;