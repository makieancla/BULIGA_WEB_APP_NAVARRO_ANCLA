<?php
// ============================================================
// student/events.php – Browse & Search Events
// Demonstrates: LEFT JOIN, search, filtering
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('student');

$db  = getDB();
$uid = currentUserId();

$search = trim($_GET['search'] ?? '');
$filter = $_GET['status'] ?? 'all';

// ── LEFT JOIN: All events + student registration status ─────
// RIGHT JOIN note: MySQL supports RIGHT JOIN — we'd use it if
// we wanted all registrations even without events (rare). Here
// LEFT JOIN shows all events whether registered or not.
$params = [$uid];
$where  = ['1=1'];

if ($search) {
    $where[]  = "(e.title LIKE ? OR e.location LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'open')       $where[] = "e.status = 'open'";
if ($filter === 'registered') $where[] = "r.id IS NOT NULL";

$sql = "
    SELECT
        e.id,
        e.title,
        e.description,
        e.location,
        e.event_date,
        e.start_time,
        e.end_time,
        e.slots,
        e.status          AS event_status,
        e.image_url,
        u.full_name       AS organizer_name,
        r.id              AS reg_id,
        r.status          AS reg_status,
        -- COUNT slots taken via subquery
        (SELECT COUNT(*) FROM registrations sr
         WHERE sr.event_id = e.id AND sr.status != 'rejected') AS slots_taken
    FROM events e
    LEFT JOIN registrations r                  -- LEFT JOIN keeps all events
        ON r.event_id = e.id AND r.student_id = ?
    INNER JOIN users u ON e.organizer_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.event_date ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = 'Browse Events';
require_once __DIR__ . '/../includes/header.php';
?>
