<?php
require_once "../master/connect.php";
header('Content-Type: application/json; charset=utf-8');

function response_json($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = isset($_COOKIE['id']) ? (int)$_COOKIE['id'] : 0;
if ($user_id <= 0) response_json(false, 'غير مصرح');

$result_id = isset($_POST['result_id']) ? (int)$_POST['result_id'] : 0;
if ($result_id <= 0) response_json(false, 'رقم النتيجة غير صحيح');

try {
    $stmt = $connect->prepare("SELECT * FROM results WHERE id = ? LIMIT 1");
    $stmt->execute([$result_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$res) response_json(false, 'النتيجة غير موجودة');
    if ((int)$res['user_id'] !== $user_id) response_json(false, 'غير مصرح بهذه النتيجة');
    if (!empty($res['finished_at'])) {
        response_json(true, 'تم إنهاء الاختبار مسبقاً', ['result_id' => $result_id]);
    }

    $connect->beginTransaction();

    $qStmt = $connect->prepare("SELECT id, correct_choice FROM questions WHERE exam_id = ? ORDER BY position ASC, id ASC");
    $qStmt->execute([(int)$res['exam_id']]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    $answerStmt = $connect->prepare("SELECT id, selected_choice, time_taken_seconds FROM answers WHERE result_id = ? AND question_id = ? ORDER BY id DESC LIMIT 1");
    $updateAnswer = $connect->prepare("UPDATE answers SET is_correct = ? WHERE id = ?");

    $startedAt = !empty($res['started_at']) ? strtotime($res['started_at']) : false;
    $allowedSeconds = max(0, (int)($res['allowed_seconds'] ?? 0));
    $timeExpired = $startedAt !== false
        && $allowedSeconds > 0
        && time() >= ($startedAt + $allowedSeconds);

    $correctCount = 0;
    $totalTime = 0;
    $unanswered = [];

    foreach ($questions as $q) {
        $answerStmt->execute([$result_id, (int)$q['id']]);
        $a = $answerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$a) {
            $unanswered[] = (int)$q['id'];
            continue;
        }

        $selected = strtoupper(trim((string)($a['selected_choice'] ?? '')));
        $correct = strtoupper(trim((string)($q['correct_choice'] ?? '')));

        if ($selected === '') {
            $unanswered[] = (int)$q['id'];
        }

        $isCorrect = ($selected !== '' && $correct !== '' && $selected === $correct) ? 1 : 0;

        if ($isCorrect) $correctCount++;
        $totalTime += max(0, (int)($a['time_taken_seconds'] ?? 0));
        $updateAnswer->execute([$isCorrect, (int)$a['id']]);
    }

    if (!empty($unanswered) && !$timeExpired) {
        $connect->rollBack();
        response_json(false, 'لا يمكن إنهاء الاختبار قبل الإجابة عن جميع الأسئلة.', [
            'unanswered_count' => count($unanswered)
        ]);
    }

    $totalQuestions = count($questions);
    $score = $totalQuestions > 0 ? (int)floor(($correctCount / $totalQuestions) * 100) : 0;

    $updateResult = $connect->prepare("UPDATE results SET score = ?, finished_at = NOW(), total_time_seconds = ? WHERE id = ?");
    $updateResult->execute([$score, $totalTime, $result_id]);

    $connect->commit();
    response_json(true, 'تم إنهاء الاختبار بنجاح', ['result_id' => $result_id, 'score' => $score]);
} catch (Throwable $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    response_json(false, 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage());
}
