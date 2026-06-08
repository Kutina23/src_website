<?php
// public_vote.php — accepts anonymous/public votes as PENDING (awaiting admin approval)
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaVoting.php';

$csrfFromSession = $_SESSION['csrf_token'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$votingId = (int)($input['voting_id'] ?? 0);
$choice   = strtoupper($input['choice'] ?? '');
$deviceId = $input['device_id'] ?? '';
$csrf     = $input['csrf_token'] ?? '';

if (!hash_equals($csrfFromSession, $csrf) || $votingId <= 0 || !in_array($choice, ['YES', 'NO', 'ABSTAIN'], true) || empty($deviceId)) {
    echo json_encode(['ok' => false, 'error' => (empty($deviceId) || strlen($deviceId) < 16)
        ? 'Session expired. Please refresh and try again.'
        : 'Invalid request.']);
    exit;
}

try {
    $model = new GaVoting(db());

    // Verify the voting session exists and is still open
    $vr = $model->getById($votingId);
    if (!$vr || $vr['status'] !== 'OPEN') {
        echo json_encode(['ok' => false, 'error' => 'This vote is no longer open for public voting.']);
        exit;
    }

    // Enforce device-based double-vote prevention
    $existing = $model->hasDeviceVoted($votingId, $deviceId);
    if ($existing) {
        echo json_encode(['ok' => false, 'error' => 'You have already voted on this record as ' . $existing['choice'] . '.']);
        exit;
    }

    // Also enforce user-based double-vote prevention (if arrived from a logged-in session)
    if (!empty($_SESSION['user_id'])) {
        $userVote = $model->hasVoted($votingId, (int)$_SESSION['user_id']);
        if ($userVote) {
            echo json_encode(['ok' => false, 'error' => 'You have already voted on this record.']);
            exit;
        }
    }

    // Insert as PENDING — recalcVotes keeps approved-only counts accurate
    $model->recalcVotes($votingId);
    $db = db();
    $db->execute(
        "INSERT INTO ga_vote_records (voting_id, user_id, device_id, choice, is_approved, voted_at)
         VALUES (?, NULL, ?, ?, 'pending', NOW())",
        [$votingId, $deviceId, $choice]
    );
    $model->recalcVotes($votingId);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
