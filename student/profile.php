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
// Re-fetch to show updated data
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Hours & events summary
$sumStmt = $db->prepare("
    SELECT COUNT(*) AS total, COALESCE(SUM(hours_rendered),0) AS hours
    FROM registrations WHERE student_id = ? AND status = 'completed'
");
$sumStmt->execute([$uid]);
$summary = $sumStmt->fetch();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-person-circle me-2"></i>My Profile</h1>
        <p>Manage your account information and view your volunteering record.</p>
    </div>
</div>

<div class="container" style="max-width:700px;">

    <div class="row g-4 mb-4">
        <div class="col-sm-4 text-center">
            <div style="width:110px;height:110px;border-radius:50%;background:var(--green-pale);
                        border:3px solid var(--green-mid);display:flex;align-items:center;
                        justify-content:center;font-size:3rem;margin:0 auto 1rem;">
                🧑
            </div>
            <div class="fw-sora fs-5"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
            <div class="mt-2">
                <span class="role-badge"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-value"><?= $summary['total'] ?></div>
                        <div class="stat-label">Events Completed</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-value"><?= number_format($summary['hours'], 1) ?>h</div>
                        <div class="stat-label">Volunteer Hours</div>
                    </div>
                </div>
            </div>
            <?php if ($user['bio']): ?>
                <p class="mt-3 text-muted small"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="buliga-form-card">
        <h5 class="fw-sora mb-4"><i class="bi bi-pencil-square me-2 text-green"></i>Edit Profile</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($user['full_name']) ?>" required />
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control"
                       value="<?= htmlspecialchars($user['email']) ?>" disabled />
                <div class="form-text">Email cannot be changed.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Bio / About Me</label>
                <textarea name="bio" class="form-control" rows="3"
                          placeholder="Tell others about your passion for volunteering…"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <hr />
            <h6 class="fw-sora mb-3">Change Password <span class="text-muted fw-normal small">(optional)</span></h6>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="Min. 6 characters" />
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="Repeat password" />
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-green px-4">
                    <i class="bi bi-check2 me-2"></i>Save Changes
                </button>
                <a href="javascript:history.back()" class="btn btn-outline-buliga ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>