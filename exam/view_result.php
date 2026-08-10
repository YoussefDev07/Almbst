<?php
require_once "../master/connect.php";
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) exit('رقم النتيجة غير صحيح');

$stmt = $connect->prepare("
    SELECT
        r.*,
        e.title,
        a.fristname AS student_firstname,
        a.lastname AS student_lastname
    FROM results r
    INNER JOIN exams e ON e.id = r.exam_id
    LEFT JOIN accounts a ON a.id = r.user_id
    WHERE r.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) exit('النتيجة غير موجودة');

$current_user_id = isset($_COOKIE['id']) ? (int)$_COOKIE['id'] : 0;
$is_admin = !empty($_SESSION['admin_logged_in']);
if (!$is_admin && ($current_user_id <= 0 || $current_user_id !== (int)$r['user_id'])) {
    http_response_code(403);
    exit('غير مصرح');
}

$answersStmt = $connect->prepare("SELECT a.*, q.q_image, q.choice_a, q.choice_b, q.choice_c, q.choice_d, q.correct_choice, q.position FROM answers a JOIN questions q ON q.id = a.question_id WHERE a.result_id = ? ORDER BY q.position ASC, q.id ASC");
$answersStmt->execute([$id]);
$answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

$student_name = trim(
    (string)($r['student_firstname'] ?? '') . ' ' .
    (string)($r['student_lastname'] ?? '')
);

if ($student_name === '') {
    $student_name = 'الطالب رقم ' . (int)$r['user_id'];
}

function choices($choice) {
    $map = ['A' => 'أ', 'B' => 'ب', 'C' => 'ج', 'D' => 'د'];
    $choice = strtoupper(trim((string)$choice));
    return $map[$choice] ?? 'لا توجد إجابة';
}

$score = (int)$r['score'];
$totalTime = max(0, (int)($r['total_time_seconds'] ?? 0));
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { font-family:"Tajawal","Cairo",sans-serif; background:#f5f7fa; margin:0; padding:0; color:#333; line-height:1.6; }
.container { max-width:900px; margin:30px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.1); }
h2 { text-align:center; color:#2c3e50; margin-bottom:15px; }
h3 { margin-top:30px; color:#34495e; border-bottom:2px solid #3498db; padding-bottom:8px; }
p { font-size:16px; margin:8px 0; }
.card { background:#f9f9f9; border:1px solid #e0e0e0; border-radius:10px; padding:15px; margin-bottom:15px; }
.card strong { color:#2c3e50; }
.card > div { margin:6px 0; }
.card img { display:block; max-width:100%; height:auto; border-radius:5px; }
.card div.correct { background:#eafaf1; border-right:4px solid #2ecc71; padding:6px; border-radius:6px; }
.card div.wrong { background:#f2cac7; border-right:4px solid #e74c3c; padding:6px; border-radius:6px; }
@media(max-width:768px){ .container{margin:20px 10px;padding:20px} h2{font-size:22px} h3{font-size:18px} p{font-size:14px} }
</style>
<title>عرض النتيجة</title>
</head>
<body>
<div class="container">
<h2>نتيجة <?= htmlspecialchars($r['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
<p>الطالب: <?= htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8') ?></p>
<p>النتيجة: <?= $score ?>%</p>
<p>الزمن المستغرق: <?= gmdate('H:i:s', $totalTime) ?></p>
<h3>تفاصيل الأسئلة</h3>
<?php foreach ($answers as $a): ?>
  <div class="card">
    <?php if (!empty($a['q_image'])): ?>
      <div><img src="<?= htmlspecialchars($a['q_image'], ENT_QUOTES, 'UTF-8') ?>" alt="صورة السؤال"></div>
    <?php endif; ?>
    <?php $isCorrect = (int)$a['is_correct'] === 1; ?>
    <div class="<?= $isCorrect ? 'correct' : 'wrong' ?>">
      <strong>إجابتك:</strong> <?= htmlspecialchars(choices($a['selected_choice'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php if (!$isCorrect): ?>
      <div><strong>الإجابة الصحيحة:</strong> <?= htmlspecialchars(choices($a['correct_choice'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
</body>
</html>
