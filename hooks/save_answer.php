<?php
require_once "../master/connect.php";
session_start();
header('Content-Type: application/json');
$user_id = $_COOKIE['id'] ?? null;
if (!$user_id) { echo json_encode(['ok'=>false]); exit; }
$result_id = intval($_POST['result_id'] ?? 0);
$question_id = intval($_POST['question_id'] ?? 0);
$selected = $_POST['selected_choice'] ?? null;
$time = intval($_POST['time_taken_seconds'] ?? 0);

// verify ownership
$stmt = $connect->prepare("SELECT user_id FROM results WHERE id = ?");
$stmt->execute([$result_id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r || $r['user_id'] !== $user_id) { echo json_encode(['ok'=>false]); exit; }

// insert or update answer
$exists = $connect->prepare("SELECT id FROM answers WHERE result_id = ? AND question_id = ?");
$exists->execute([$result_id, $question_id]);
$e = $exists->fetch(PDO::FETCH_ASSOC);
if ($e) {
  $connect->prepare("UPDATE answers SET selected_choice = ?, time_taken_seconds = time_taken_seconds + ? WHERE id = ?")
    ->execute([$selected, $time, $e['id']]);
} else {
  $connect->prepare("INSERT INTO answers (result_id, question_id, selected_choice, time_taken_seconds) VALUES (?, ?, ?, ?)")
    ->execute([$result_id, $question_id, $selected, $time]);
}
echo json_encode(['ok'=>true]);