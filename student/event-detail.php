<?php
// ============================================================
// student/event-detail.php – Event Detail & Register/Unregister
// Demonstrates: INNER JOIN, CRUD (Create registration)
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('student');

$db     = getDB();
$uid    = currentUserId();
$eid    = (int)($_GET['id'] ?? 0);

if (!$eid) { header('Location: /student/events.php'); exit; }

// ── INNER JOIN: Event + organizer details ──────────────────
// Only fetches if event exists (required join).
$evStmt = $db->prepare("
    SELECT
        e.*,
        u.full_name  AS organizer_name,
        u.email      AS organizer_email,
        (SELECT COUNT(*) FROM registrations r2
         WHERE r2.event_id = e.id AND r2.status != 'rejected') AS slots_taken
    FROM events e
    INNER JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ?
");
$evStmt->execute([$eid]);
$event = $evStmt->fetch();

if (!$event) { setFlash('error', 'Event not found.'); header('Location: /student/events.php'); exit; }

// Check if student already registered
$regStmt = $db->prepare("SELECT * FROM registrations WHERE student_id = ? AND event_id = ?");
$regStmt->execute([$uid, $eid]);
$myReg = $regStmt->fetch();

// Handle POST: Register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register' && !$myReg) {
        if ($event['status'] !== 'open') {
            setFlash('error', 'This event is no longer open for registration.');
        } elseif ($event['slots_taken'] >= $event['slots']) {
            setFlash('error', 'Sorry, all slots are filled for this event.');
        } else {
            // CREATE (INSERT) – CRUD: Create
            $ins = $db->prepare(
                "INSERT INTO registrations (student_id, event_id) VALUES (?, ?)"
            );
            $ins->execute([$uid, $eid]);
            setFlash('success', 'You have successfully registered for this event! 🎉');
        }
    } elseif ($action === 'unregister' && $myReg && $myReg['status'] === 'pending') {
        // DELETE – CRUD: Delete
        $del = $db->prepare("DELETE FROM registrations WHERE student_id = ? AND event_id = ?");
        $del->execute([$uid, $eid]);
        setFlash('success', 'Your registration has been cancelled.');
    }

    header("Location: /student/event-detail.php?id=$eid");
    exit;
}
// ── Announcements for this event ───────────────────────────
$annStmt = $db->prepare("
    SELECT a.*, u.full_name AS author
    FROM announcements a
    INNER JOIN users u ON a.author_id = u.id
    WHERE a.event_id = ?
    ORDER BY a.created_at DESC
");
$annStmt->execute([$eid]);
$announcements = $annStmt->fetchAll();

$pageTitle = $event['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:800px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/student/events.php">Events</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($event['title']) ?></li>
        </ol>
    </nav>

    <div class="buliga-card mb-4">
        <?php if ($event['image_url']): ?>
            <img src="<?= htmlspecialchars($event['image_url']) ?>"
                 class="card-img-top" style="height:260px;object-fit:cover;" alt="" />
        <?php else: ?>
            <div class="event-card-placeholder" style="height:200px;font-size:5rem;">🌿</div>
        <?php endif; ?>

        <div class="p-4">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <span class="status-badge status-<?= $event['status'] ?>">
                    <?= ucfirst($event['status']) ?>
                </span>
                <?php if ($myReg): ?>
                    <span class="status-badge status-<?= $myReg['status'] ?>">
                        My Status: <?= ucfirst($myReg['status']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <h2 class="fw-sora mb-3"><?= htmlspecialchars($event['title']) ?></h2>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="small text-muted fw-bold mb-1">📅 Date & Time</div>
                    <div><?= date('F d, Y', strtotime($event['event_date'])) ?></div>
                    <div class="small text-muted">
                        <?= date('g:i A', strtotime($event['start_time'])) ?> –
                        <?= date('g:i A', strtotime($event['end_time'])) ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-bold mb-1">📍 Location</div>
                    <div><?= htmlspecialchars($event['location']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-bold mb-1">👥 Slots</div>
                    <div>
                        <?= $event['slots_taken'] ?> / <?= $event['slots'] ?> filled
                        <?php if ($event['slots_taken'] >= $event['slots']): ?>
                            <span class="ms-2 status-badge" style="background:#fde8e8;color:#c0392b;">Full</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-bold mb-1">🧑‍💼 Organizer</div>
                    <div><?= htmlspecialchars($event['organizer_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($event['organizer_email']) ?></div>
                </div>
            </div>

            <h6 class="fw-sora mb-2">About This Event</h6>
            <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

            <!-- Action Buttons -->
            <?php if (!$myReg && $event['status'] === 'open' && $event['slots_taken'] < $event['slots']): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <button type="submit" class="btn btn-green px-4 py-2">
                        <i class="bi bi-plus-circle me-2"></i>Register as Volunteer
                    </button>
                </form>
            <?php elseif ($myReg && $myReg['status'] === 'pending'): ?>
                <form method="POST"
                      onsubmit="return confirm('Cancel your registration for this event?')">
                    <input type="hidden" name="action" value="unregister">
                    <button type="submit" class="btn btn-sm"
                            style="background:#fde8e8;color:#c0392b;border-radius:var(--radius-pill);border:1.5px solid #f5c6c6;">
                        <i class="bi bi-x-circle me-1"></i>Cancel Registration
                    </button>
                </form>
            <?php elseif ($myReg): ?>
                <div class="p-3 rounded-3 bg-green-pale small">
                    ✅ You are registered for this event. Status: <strong><?= ucfirst($myReg['status']) ?></strong>.
                </div>
            <?php elseif ($event['status'] !== 'open'): ?>
                <div class="p-3 rounded-3" style="background:#f0e6d3;" class="small">
                    ⚠️ Registration is closed for this event.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcements -->
    <?php if ($announcements): ?>
    <h5 class="fw-sora mb-3"><i class="bi bi-megaphone me-2 text-green"></i>Announcements</h5>
    <?php foreach ($announcements as $a): ?>
        <div class="announcement-card">
            <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="ann-meta">
                By <?= htmlspecialchars($a['author']) ?> ·
                <?= date('M d, Y g:i A', strtotime($a['created_at'])) ?>
            </div>
            <p class="small mt-2 mb-0"><?= nl2br(htmlspecialchars($a['body'])) ?></p>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>