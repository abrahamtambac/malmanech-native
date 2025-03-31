<?php
include_once './controllers/ClassroomController.php';
date_default_timezone_set('Asia/Jakarta');

$classroomController = new ClassroomController($conn);
$meeting_code = $_GET['code'] ?? null;

if (!$meeting_code) {
    include '404/not_found_1.php';
    exit();
}

$meeting = $classroomController->getMeetingDetails($meeting_code);
if (!$meeting) {
    include '404/not_found_1.php';
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    header('Location: index.php?page=login');
    exit();
}

$classroom_id = $meeting['classroom_id'];
$classroom = $classroomController->getClassroomDetails($classroom_id);

// Tentukan apakah pengguna adalah lecturer
$isLecturer = false;
if ($classroom && $user_id) {
    foreach ($classroom['members'] as $member) {
        if ($member['id'] == $user_id && $member['role'] === 'lecturer') {
            $isLecturer = true;
            break;
        }
    }
}
?>

<?php include './_partials/_admin_head.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <h1 class="fw-bolder mb-3 text-dark"><?php echo htmlspecialchars($meeting['classroom_title']); ?> - Video Call Meeting</h1>
                    <?php if ($meeting['type'] === 'scheduled'): ?>
                        <p class="text-muted">Scheduled at: <?php echo htmlspecialchars($meeting['scheduled_at']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Call Section -->
    <div class="card border shadow-sm mt-4" style="border-radius: 15px;">
        <div class="card-header bg-primary text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
            <h5 class="mb-0">Video Call - <?php echo htmlspecialchars($meeting['classroom_title']); ?></h5>
        </div>
        <div class="card-body p-0 d-flex">
            <div id="participant-sidebar" class="bg-light p-3" style="width: 250px; height: 60vh; overflow-y: auto; border-right: 1px solid #ddd;">
                <h6 class="fw-bold">Peserta</h6>
                <ul id="participant-list" class="list-unstyled"></ul>
            </div>
            <div id="video-container" class="flex-grow-1 position-relative" style="height: 60vh; background: #000;">
                <div id="loading-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-75" style="z-index: 10;">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div id="screen-share-container" class="w-100 d-none" style="height: 50%;">
                    <video id="screen-share-video" autoplay playsinline class="w-100 h-100" style="border: 2px solid #ff5733; border-radius: 10px; object-fit: contain;"></video>
                    <span id="screen-share-label" class="video-label" style="background: rgba(255, 87, 51, 0.7);"></span>
                </div>
                <div id="participant-videos" class="d-flex flex-wrap justify-content-center w-100" style="height: 50%; overflow-y: auto;"></div>
            </div>
        </div>
        <div class="card-footer" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
            <div id="video-controls" class="d-flex align-items-center justify-content-between">
                <div class="me-auto d-flex align-items-center">
                    <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" id="mute-audio" title="Mute"><i class="bi bi-mic"></i></button>
                    <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" id="disable-video" title="Turn off camera"><i class="bi bi-camera-video"></i></button>
                    <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" id="share-screen" title="Share screen"><i class="bi bi-display"></i></button>
                    <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" id="speaker-view-btn" title="Speaker View"><i class="bi bi-person-video"></i></button>
                    <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" id="participants-view-btn" title="Participants View"><i class="bi bi-grid-3x3-gap"></i></button>
                    <select id="cameraSelect" class="form-select me-2" style="width: auto;"></select>
                    <select id="micSelect" class="form-select me-2" style="width: auto;"></select>
                    <canvas id="audioVisualizer" width="100" height="50" class="me-2"></canvas>
                    <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" id="refresh-connection" title="Refresh Connection"><i class="bi bi-arrow-repeat"></i></button>
                </div>
                <button class="btn btn-danger shadow-sm" id="end-video-call-btn" style="border-radius: 10px;">End Call</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://unpkg.com/interactjs/dist/interact.min.js"></script>

<script>
    window.userId = <?php echo json_encode($user_id); ?>;
    window.classroomId = <?php echo json_encode($classroom_id); ?>;
    window.classroomMembers = <?php echo json_encode(array_column($classroom['members'], 'id')); ?>;
    window.memberNames = <?php echo json_encode(array_column($classroom['members'], 'name', 'id')); ?>;
    window.isLecturer = <?php echo json_encode($isLecturer); ?>;
    window.meetingCode = <?php echo json_encode($meeting_code); ?>;
</script>
<script src="./js/video_call.js"></script>

<style>
    .video-wrapper { width: 320px; height: 240px; position: relative; margin: 5px; }
    .remote-video { width: 100%; height: 100%; border: 2px solid #007bff; background: #000; object-fit: cover; border-radius: 10px; }
    .video-label { position: absolute; bottom: 5px; left: 5px; background: rgba(0, 0, 0, 0.7); color: white; padding: 2px 8px; border-radius: 5px; font-size: 0.9em; }
    #audioVisualizer { background: #f0f2f5; border-radius: 5px; }
    .meet-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; padding: 0; }
    .meet-btn i { font-size: 1.2rem; }
    #participant-sidebar { background: #f8f9fa; }
    #participant-list li { padding: 5px 0; }
    #loading-overlay { transition: opacity 0.3s; }
    #loading-overlay.hidden { opacity: 0; pointer-events: none; }
</style>