<?php
require_once "../master/connect.php";
session_start();
$id = intval($_GET['id'] ?? 0);
$stmt = $connect->prepare("SELECT r.*, t.title FROM results r JOIN exams t ON t.id = r.exam_id WHERE r.id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) exit('غير موجود');
$user_id = $_COOKIE['id'] ?? null;
if (!isset($_SESSION['admin_logged_in']) && $user_id !== $r['user_id']) { http_response_code(403); exit; }
$answers = $connect->prepare("SELECT a.*, q.q_image, q.choice_a, q.choice_b, q.choice_c, q.choice_d, q.correct_choice FROM answers a JOIN questions q ON q.id = a.question_id WHERE a.result_id = ?");
$answers->execute([$id]);
$answers = $answers->fetchAll(PDO::FETCH_ASSOC);

$student_name = $connect -> query("SELECT fristname, lastname FROM accounts WHERE id = $user_id") -> fetchAll(PDO::FETCH_ASSOC);
$student_name = $student_name[0]["fristname"]." ".$student_name[0]["lastname"];
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name = "viewport" content = "width=device-width, initial-scale=1.0"/>
<style>
/* تنسيق عام للصفحة */
body {
  font-family: "Tajawal", "Cairo", sans-serif;
  background: #f5f7fa;
  margin: 0;
  padding: 0;
  color: #333;
  line-height: 1.6;
}

/* الحاوية الرئيسية */
.container {
  max-width: 900px;
  margin: 30px auto;
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* العناوين */
h2 {
  text-align: center;
  color: #2c3e50;
  margin-bottom: 15px;
}

h3 {
  margin-top: 30px;
  color: #34495e;
  border-bottom: 2px solid #3498db;
  padding-bottom: 8px;
}

/* الفقرات */
p {
  font-size: 16px;
  margin: 8px 0;
}

/* البطاقات الخاصة بالأسئلة */
.card {
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 15px;
  margin-bottom: 15px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.12);
}

/* النصوص داخل البطاقة */
.card strong {
  color: #2c3e50;
}

.card div {
  margin: 6px 0;
}

.card img {
  width: 100%;
  border-radius: 5px;
}

/* إبراز الإجابة الصحيحة */
.card div.correct {
  background: #eafaf1;
  border-left: 4px solid #2ecc71;
  padding: 6px;
  border-radius: 6px;
}

/* إبراز الإجابة الخاطئة */
.card div.wrong {
  background: #f2cac7;
  border-left: 4px solid #e74c3c;
  padding: 6px;
  border-radius: 6px;
}

/* Media Queries */

@media only screen and (max-width: 768px) {
  .container {
    margin: 20px auto;
    padding: 20px;
  }
  
  h2 {
    font-size: 22px;
  }
  
  h3 {
    font-size: 18px;
    margin-top: 20px;
  }
  
  p {
    font-size: 14px;
  }
  
  .card {
    padding: 10px;
  }
}
</style>
<title>عرض النتيجة</title>
</head>
<body>
<div class="container">
<h2>نتيجة <?=htmlspecialchars($r['title'])?></h2>
<p>الطالب: <?=htmlspecialchars($student_name)?></p>
<p>النتيجة: <?= $r["score"]; ?></p>
<p>الزمن المستغرق: <?= gmdate("i:s", $r['total_time_seconds']); ?></p>
<h3>تفاصيل الأسئلة</h3>
<?php foreach($answers as $a): ?>
  <div class="card">
    <div><img src="<?= $a["q_image"]; ?>"></div>
    <div class="<?= $a["is_correct"] ? "correct":"wrong"; ?>"><strong>إجابتك:</strong> <?= choices($a['selected_choice']?:'لا توجد إجابة'); ?></div>
    <?php if (!$a["is_correct"]): ?>
    <div><strong>الإجابة الصحيحة:</strong> <?=choices($a['correct_choice'])?></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
</body>
</html>
<?php
function choices($choice) {
  if ($choice == "A") {
    return "أ";
  }
  elseif ($choice == "B") {
    return "ب";
  }
  elseif ($choice == "C") {
    return "ج";
  }
  elseif ($choice == "D") {
    return "د";
  }
}
?>