<?php
include "../config.php";

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
  header("Location: login.php");
  exit;
}
$exams = $connect->query("SELECT * FROM exams ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style.css"/>
<title>الاختبارات - لوحة التحكم</title>
<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f9f9f9;
  margin: 0;
  padding: 0;
  direction: rtl;
}

.container {
  max-width: 900px;
  margin: 40px auto;
  background-color: #fff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h2 {
  margin-bottom: 20px;
  color: #333;
  text-align: center;
}

.btn {
  display: inline-block;
  background-color: #007bff;
  color: #fff;
  padding: 10px 20px;
  margin-bottom: 20px;
  text-decoration: none;
  border-radius: 5px;
  transition: background-color 0.3s ease;
}

.btn:hover {
  background-color: #0056b3;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 12px 15px;
  border: 1px solid #ddd;
  text-align: center;
}

.table th {
  background-color: #f1f1f1;
  color: #555;
}

.table tr:nth-child(even) {
  background-color: #f9f9f9;
}

.table tr:hover {
  background-color: #f0f8ff;
}

button {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

button:hover {
  background-color: #218838;
}

a {
  color: #007bff;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

/* Media Queries */

@media (min-width: 601px) and (max-width: 900px) {
  .container {
    padding: 25px;
  }

  h2 {
    font-size: 24px;
  }

  .btn {
    padding: 10px 16px;
    font-size: 15px;
  }

  .table th,
  .table td {
    padding: 10px;
    font-size: 15px;
  }

  button {
    padding: 6px 12px;
    font-size: 14px;
  }
}
</style>
</head>
<body>
<?php include "../header.php"; ?>
<div class="container">
  <h2>قائمة الاختبارات</h2>
  <a href="create_exam.php" class="btn">إنشاء اختبار جديد</a>
  <table class="table">
    <tr><th>الاسم</th><th>عدد الأسئلة</th><th>الإجراءات</th></tr>
    <?php foreach($exams as $test): ?>
    <tr>
      <td><?=htmlspecialchars($test['title'])?></td>
      <td><?=$test['num_questions']?></td>
      <td>
        <a href="./create_exam.php?id=<?=$test['id']?>">تعديل</a> |
        <a href="./delete_exam.php?id=<?=$test['id']?>" onclick="return confirm('سوف يتم حذف الاختبار!')">حذف</a> |
        <button onclick="copyExamLink(<?= $test["id"]; ?>)">نسخ رابط</button>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<script>
function copyExamLink(examID) {
  navigator.clipboard.writeText(`https://localhost/www/المبسط/exam/take_exam.php?exam_id=${examID}`);
  alert("تم النسخ");
}
</script>
</body>
</html>