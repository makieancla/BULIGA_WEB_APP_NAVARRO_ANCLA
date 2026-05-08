<?php
// ============================================================
// student/profile.php – View & Edit Profile
// Demonstrates: CRUD Update (UPDATE query)
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db  = getDB();
$uid = currentUserId();

// Fetch current user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');
    $password  = $_POST['new_password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!$full_name) {
        setFlash('error', 'Full name is required.');
    } elseif ($password && $password !== $confirm) {
        setFlash('error', 'New passwords do not match.');
    } elseif ($password && strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters.');
    } else {
        if ($password) {
            // UPDATE with new password – CRUD: Update
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $upd  = $db->prepare(
                "UPDATE users SET full_name = ?, bio = ?, password = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            $upd->execute([$full_name, $bio, $hash, $uid]);
        } else {
            // UPDATE without password change
            $upd = $db->prepare(
                "UPDATE users SET full_name = ?, bio = ?, updated_at = NOW() WHERE id = ?"
            );
            $upd->execute([$full_name, $bio, $uid]);
        }
        $_SESSION['full_name'] = $full_name;
        setFlash('success', 'Profile updated successfully!');
        header('Location: /student/profile.php');
        exit;
    }
}