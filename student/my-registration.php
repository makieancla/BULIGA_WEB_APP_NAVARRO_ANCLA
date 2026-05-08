<?php
// ============================================================
// student/my-registrations.php – All Registrations (with sort)
// Demonstrates: INNER JOIN, sort, full data table
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('student');

$db  = getDB();
$uid = currentUserId();

// ── INNER JOIN: Registrations × Events × Users ─────────────
// INNER JOIN ensures we only get registrations for existing events
// with existing organizers (no orphaned rows).
$stmt = $db->prepare("
    SELECT
        r.id           AS reg_id,
        r.status       AS reg_status,
        r.hours_rendered,
        r.registered_at,
        e.id           AS event_id,
        e.title        AS event_title,
        e.event_date,
        e.location,
        e.start_time,
        e.end_time,
        e.status       AS event_status,
        u.full_name    AS organizer_name
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id       -- INNER JOIN: event data
    INNER JOIN users u  ON e.organizer_id = u.id   -- INNER JOIN: organizer data
    WHERE r.student_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$uid]);
$regs = $stmt->fetchAll();

// ── Summary stats ──────────────────────────────────────────
$total_hours = array_sum(array_column($regs, 'hours_rendered'));

$pageTitle = 'My Registrations';
require_once __DIR__ . '/../includes/header.php';
?>