<?php
// — Returns new messages since a given timestamp as JSON
// Called by JS every 3 seconds from messages
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$withID  = (int)($_GET['with']  ?? 0);
$lastTS  = $_GET['last_ts']     ?? '1970-01-01 00:00:00';

if (!$withID) {
    echo json_encode([]);
    exit;
}

// Validate timestamp 
if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $lastTS)) {
    $lastTS = '1970-01-01 00:00:00';
}

$stmt = $pdo->prepare("
    SELECT m.MessageID, m.MessageText, m.Timestamp,
           m.SenderID, u.Name AS SenderName
    FROM Messages m
    JOIN Users u ON u.UserID = m.SenderID
    WHERE ((m.SenderID = ? AND m.ReceiverID = ?)
        OR (m.SenderID = ? AND m.ReceiverID = ?))
      AND m.Timestamp > ?
    ORDER BY m.Timestamp ASC
");
$stmt->execute([$userID, $withID, $withID, $userID, $lastTS]);
$messages = $stmt->fetchAll();

// Format timestamp for display
foreach ($messages as &$msg) {
    $msg['formatted_time'] = date('M j, g:i a', strtotime($msg['Timestamp']));
}

echo json_encode($messages);