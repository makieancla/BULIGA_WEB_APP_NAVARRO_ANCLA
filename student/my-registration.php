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
<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-bookmark-check me-2"></i>My Registrations</h1>
        <p>Track all your volunteer event registrations and hours.</p>
    </div>
</div>

<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= count($regs) ?></div>
                <div class="stat-label">Total</div>
                <i class="bi bi-list-check stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($regs, fn($r) => $r['reg_status'] === 'approved')) ?></div>
                <div class="stat-label">Approved</div>
                <i class="bi bi-check2-circle stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($regs, fn($r) => $r['reg_status'] === 'completed')) ?></div>
                <div class="stat-label">Completed</div>
                <i class="bi bi-trophy stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($total_hours, 1) ?>h</div>
                <div class="stat-label">Total Hours</div>
                <i class="bi bi-clock stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-3 search-bar" style="max-width:360px;">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control"
               data-search-table="#regTable"
               placeholder="Search registrations…" />
    </div>

    <?php if ($regs): ?>
    <div class="buliga-table">
        <table class="table mb-0" id="regTable">
            <thead>
                <tr>
                    <th data-sortable>Event</th>
                    <th data-sortable>Date</th>
                    <th data-sortable>Location</th>
                    <th data-sortable>Organizer</th>
                    <th data-sortable>My Status</th>
                    <th data-sortable>Hours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($regs as $r): ?>
                <tr>
                    <td>
                        <a href="/student/event-detail.php?id=<?= $r['event_id'] ?>"
                           class="fw-sora text-dark" style="font-size:.9rem;">
                            <?= htmlspecialchars($r['event_title']) ?>
                        </a>
                    </td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($r['event_date'])) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($r['location']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['organizer_name']) ?></td>
                    <td>
                        <span class="status-badge status-<?= $r['reg_status'] ?>">
                            <?= ucfirst($r['reg_status']) ?>
                        </span>
                    </td>
                    <td><?= number_format($r['hours_rendered'], 1) ?>h</td>
                    <td>
                        <a href="/student/event-detail.php?id=<?= $r['event_id'] ?>"
                           class="btn btn-outline-buliga btn-sm">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">🌱</span>
            <p>You haven't registered for any events yet. Start volunteering today!</p>
            <a href="/student/events.php" class="btn btn-green">Browse Events</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>