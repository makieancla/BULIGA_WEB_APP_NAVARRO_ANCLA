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
<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-calendar-event me-2"></i>Volunteer Opportunities</h1>
        <p>Discover events and make a difference in your community.</p>
    </div>
</div>

<div class="container">
    <!-- Search & Filter Bar -->
    <div class="row g-2 mb-4 align-items-center">
        <div class="col-md-6 search-bar">
            <i class="bi bi-search"></i>
            <input type="text" id="eventSearch" class="form-control"
                   data-search-table="#eventsTable"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search events, locations, organizers…" />
        </div>
        <div class="col-md-6 d-flex gap-2 flex-wrap">
            <a href="?status=all"
               class="btn btn-sm <?= $filter === 'all' ? 'btn-green' : 'btn-outline-buliga' ?>">All</a>
            <a href="?status=open"
               class="btn btn-sm <?= $filter === 'open' ? 'btn-green' : 'btn-outline-buliga' ?>">Open</a>
            <a href="?status=registered"
               class="btn btn-sm <?= $filter === 'registered' ? 'btn-green' : 'btn-outline-buliga' ?>">Registered</a>

            <form method="GET" class="ms-auto d-flex gap-2 align-items-center">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>" />
                <input type="text" name="search" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search…" />
                <button type="submit" class="btn btn-green btn-sm">Go</button>
            </form>
        </div>
    </div>

    <?php if ($events): ?>
    <div class="row g-4">
        <?php foreach ($events as $ev):
            $slotsFull = $ev['slots_taken'] >= $ev['slots'];
        ?>
        <div class="col-sm-6 col-lg-4">
            <div class="buliga-card h-100 d-flex flex-col">
                <!-- Event Image or Placeholder -->
                <?php if ($ev['image_url']): ?>
                    <img src="<?= htmlspecialchars($ev['image_url']) ?>"
                         class="card-img-top" alt="Event Image" />
                <?php else: ?>
                    <div class="event-card-placeholder">🌿</div>
                <?php endif; ?>

                <div class="p-3 d-flex flex-column flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="status-badge status-<?= $ev['event_status'] ?>">
                            <?= ucfirst($ev['event_status']) ?>
                        </span>
                        <?php if ($ev['reg_status']): ?>
                            <span class="status-badge status-<?= $ev['reg_status'] ?>">
                                <?= ucfirst($ev['reg_status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-sora mb-1" style="font-size:.97rem">
                        <?= htmlspecialchars($ev['title']) ?>
                    </h6>
                    <p class="small text-muted mb-2" style="line-height:1.4">
                        <?= htmlspecialchars(substr($ev['description'], 0, 90)) ?>…
                    </p>

                    <div class="small text-muted mb-1">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= date('M d, Y', strtotime($ev['event_date'])) ?>
                        &nbsp;·&nbsp;
                        <?= date('g:i A', strtotime($ev['start_time'])) ?>
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?= htmlspecialchars($ev['location']) ?>
                    </div>
                    <div class="small mb-3">
                        <i class="bi bi-people me-1 text-green"></i>
                        <span class="<?= $slotsFull ? 'text-danger' : 'text-green' ?>">
                            <?= $ev['slots_taken'] ?>/<?= $ev['slots'] ?> slots filled
                        </span>
                    </div>

                    <div class="mt-auto">
                        <?php if ($ev['reg_status']): ?>
                            <a href="/student/event-detail.php?id=<?= $ev['id'] ?>"
                               class="btn btn-outline-buliga btn-sm w-100">View Details</a>
                        <?php elseif ($ev['event_status'] !== 'open' || $slotsFull): ?>
                            <button class="btn btn-sm w-100" disabled
                                    style="background:#eee;border-radius:var(--radius-pill);">
                                <?= $slotsFull ? 'Full' : 'Closed' ?>
                            </button>
                        <?php else: ?>
                            <a href="/student/event-detail.php?id=<?= $ev['id'] ?>"
                               class="btn btn-green btn-sm w-100">
                                <i class="bi bi-plus-circle me-1"></i>Register Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <span class="empty-icon">🔍</span>
        <p>No events match your search. Try different keywords.</p>
        <a href="/student/events.php" class="btn btn-green btn-sm">Clear Search</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>