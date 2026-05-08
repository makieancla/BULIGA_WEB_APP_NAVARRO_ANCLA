<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: /' . currentRole() . '/dashboard.php');
    exit;
}
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'student';

    if (!$full_name || !$email || !$password || !$confirm) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student', 'organizer'])) {
        $error = 'Invalid role selected.';
    } else {
        $db   = getDB();
        // Check if email already exists
        $chk  = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'This email is already registered. Try logging in.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare(
                "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
            );
            $ins->execute([$full_name, $email, $hash, $role]);
            setFlash('success', 'Account created! Please log in.');
            header('Location: /auth/login.php');
            exit;
        }
    }
}
?>