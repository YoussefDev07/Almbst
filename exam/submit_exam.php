<?php
require_once "../master/connect.php";
header('Content-Type: application/json');
$user_id = $_COOKIE['id'] ?? null;
if (!$user_id) { echo json_encode(['success'=>false,'message'=>'غير مصرح']); exit; }
$result_id = intval($_POST['result_id'] ?? 0);
$stmt = $connect->prepare("SELECT * FROM results WHERE id = ?");
$stmt->execute([$result_id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$res || $res['user_id'] !== $user_id) { echo json_encode(['success'=>false,'message'=>'مشكلة']); exit; }
// check time
$started = new DateTime($res['started_at']);
$allowed = intval($res['allowed_seconds']);
$now = new DateTime();
$diff = $now->getTimestamp() - $started->getTimestamp();
if ($diff > $allowed + 5) {
  // if exceeded, still evaluate but mark finished time
}
// evaluate
$rows = $connect->prepare("SELECT a.question_id, a.selected_choice, q.correct_choice FROM answers a JOIN questions q ON q.id = a.question_id WHERE a.result_id = ?");
$rows->execute([$result_id]);
$all = $rows->fetchAll(PDO::FETCH_ASSOC);
$correct = 0;
$all_q = 0;
foreach ($all as $r) {
  $all_q++;
  if ($r['selected_choice'] !== null && strtoupper($r['selected_choice']) === strtoupper($r['correct_choice'])) $correct++;
}

$correct = floor(($correct / $all_q) * 100);
$correct = "$correct%";

// update answers is_correct
$connect->prepare("UPDATE answers a JOIN questions q ON q.id = a.question_id SET a.is_correct = (a.selected_choice = q.correct_choice) WHERE a.result_id = ?")->execute([$result_id]);
// sum time
$timeSum = $connect->prepare("SELECT COALESCE(SUM(time_taken_seconds),0) as t FROM answers WHERE result_id = ?");
$timeSum->execute([$result_id]);
$t = $timeSum->fetchColumn();
$connect->prepare("UPDATE results SET score = ?, finished_at = NOW(), total_time_seconds = ? WHERE id = ?")->execute([$correct, $t, $result_id]);
echo json_encode(['success'=>true,'result_id'=>$result_id]);