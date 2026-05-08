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