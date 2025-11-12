<?php
include "../config.php";

$sql = "
    SELECT 
        r.id, 
        a.fristname AS student_name, 
        t.title AS exam_title, 
        r.score, 
        r.started_at, 
        r.finished_at 
    FROM 
        results r 
    JOIN 
        exams t ON t.id = r.exam_id 
    JOIN 
        accounts a ON a.id = r.user_id
    ORDER BY 
        r.finished_at DESC
";

try {
    $stmt = $connect->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css"/>
    <title>نتائج الطلاب</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
    direction: rtl;
    color: #333;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px;
    background-color: #ffffff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}

h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 16px;
}

.table th,
.table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: center;
}

.table th {
    background-color: #3498db;
    color: white;
    font-weight: bold;
}

.table tr:nth-child(even) {
    background-color: #f2f2f2;
}

.table tr:hover {
    background-color: #eaf2f8;
}

a {
    color: #2980b9;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

/* Media Queries */

@media only screen and (max-width: 768px) {
  .container {
    margin: 20px auto;
    padding: 15px;
  }
  
  h2 {
    font-size: 18px;
    margin-bottom: 20px;
  }
  
  .table {
    font-size: 14px;
  }
  
  .table th, .table td {
    padding: 8px 10px;
  }
}
</style>
</head>
<body>
<?php include "../header.php"; ?>
<div class="container">
    <h2>تتبع أداء الطلاب</h2>
    <table class="table">
        <tr><th>الطالب</th><th>الاختبار</th><th>النتيجة</th><th>بدء</th><th>انتهى</th><th>عرض</th></tr>
        <?php if (count($rows) > 0): ?>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?=htmlspecialchars($r['student_name'])?></td> 
                <td><?=htmlspecialchars($r['exam_title'])?></td>
                <td><?=htmlspecialchars($r['score'])?></td>
                <td><?=htmlspecialchars($r['started_at'])?></td>
                <td><?=htmlspecialchars($r['finished_at'])?></td>
                <td><a href="../../exam/view_result.php?id=<?=htmlspecialchars($r['id'])?>">عرض</a></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">لا توجد نتائج لعرضها حاليًا.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>