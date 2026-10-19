<?php
require_once __DIR__ . '/master/connect.php';
require_once __DIR__ . '/includes/leaderboard_helpers.php';

$loadError = false;
$students = [];
try {
    $stmt = $connect->query('SELECT id, student_name, score, image_filename FROM top_students ORDER BY id DESC');
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('leaderboard public page error: ' . $e->getMessage());
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" type="text/html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="لوحة الشرف في المبسط في القدرات الكمي.">
    <link rel="icon" type="image/png" href="./assets/images/logo.png">
    <link rel="preload" as="font" href="./assets/fonts/el-messiri.ttf" crossorigin>
    <link rel="stylesheet" type="text/css" href="./assets/css/style.css">
    <link rel="stylesheet" media="all" href="./assets/libs/css/fontawesome.css">
    <link rel="stylesheet" href="./assets/libs/css/animate.css">
    <title>المتفوقين | المبسط في القدرات الكمي</title>
    <script src="./assets/libs/js/jquery.js"></script>
    <script src="./assets/libs/js/wow.js"></script>
    <script src="./assets/libs/js/sweetalert.js"></script>
    <script type="application/javascript" src="./assets/js/script.js" async></script>
    <style>
        .top-students-page{min-height:80vh;background:#f5f5f5;padding:45px 20px 90px;font-family:var(--font)}
        .top-students-container{width:min(1200px,100%);margin:0 auto}
        .top-students-title{text-align:center;font-size:clamp(34px,5vw,50px);color:#111;margin:0}
        .top-students-intro{text-align:center;font-size:19px;color:#555;margin:12px auto 0;max-width:720px}
        .top-students-count{text-align:center;margin-top:16px;color:var(--secondary-color);font-size:17px}
        .top-students-grid{margin-top:35px;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px}
        .top-student-card{width:90%;background:#fff;border:1px solid var(--secondary-color);border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:transform .25s,box-shadow .25s}
        .top-student-card:hover{transform:translateY(-4px);box-shadow:0 5px 16px rgba(0,0,0,.12)}
        .top-student-banner{min-height:92px;padding:18px;background:var(--secondary-color);color:#fff;display:flex;align-items:center;justify-content:center;gap:12px;text-align:center}
        .top-student-medal{width:54px;height:54px;flex:0 0 54px;border:3px solid rgba(255,255,255,.8);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:25px}
        .top-student-name{font-size:22px;font-weight:700;overflow-wrap:anywhere}
        .top-student-content{padding:22px 20px 20px;text-align:center}
        .top-student-score-label{color:var(--secondary-color);font-size:17px}
        .top-student-score{color:var(--success-color);font-size:34px;line-height:1.5;font-weight:700;overflow-wrap:anywhere}
        .top-student-view{width:90%;min-height:46px;margin-top:18px;padding:6px 12px;display:inline-flex;align-items:center;justify-content:center;gap:9px;background:var(--info-color);color:#fff;text-decoration:none;border-radius:5px;font-size:17px;transition:.25s}
        .top-student-view:hover{background:var(--success-color);color:#fff}
        .top-students-state{margin:35px auto 0;padding:45px 20px;max-width:850px;background:#fff;border:1px solid #e2e2e2;border-radius:8px;text-align:center;color:#555}
        .top-students-state>i{display:block;font-size:42px;color:var(--info-color);margin-bottom:12px}
        .top-students-state h2{color:#111;font-size:26px;margin-bottom:8px}
        .top-students-state p{font-size:17px}
        @media(max-width:767px){.top-students-page{padding:30px 15px 60px}.top-students-intro{font-size:17px}.top-students-grid{grid-template-columns:1fr;gap:18px;margin-top:25px}.top-student-banner{justify-content:flex-start;text-align:right}}
    </style>
</head>
<body>
<div class="_container">
   <!--header-->
     <header>
      <!--logo-->
       <nav class="logo">
        <a href="./"><img title="المبسط" src="./assets/images/logo.png" alt="Almbst"></a>
       </nav>
      <!--navagators-->
       <nav class="navagators">
        <a href="./">الرئيسية</a>
        <a href="#courses">الدورات</a>
        <a href="#book">كتاب المبسط</a>
        <a href="./library.php">المكتبة</a>
        <a>لوحة الشرف</a>
       </nav>
      <?php include "./includes/account.php"; ?>
      <!--slider-->
       <button type="menu" id="slider"><i class="fas fa-bars"></i></button>
       <div class="slider">
        <nav>
         <button type="close" id="closeSlider"><i class="fas fa-times"></i></button>
         <ul>
          <li><a href="./">الرئيسية</a></li>
          <li><a href="#courses">الدورات</a></li>
          <li><a href="#book">كتاب المبسط</a></li>
          <li><a href="./library.php">المكتبة</a></li>
          <li><a>لوحة الشرف</a></li>
         </ul>
         <?php include "./includes/account.php"; ?>
        </nav>
       </div>
     </header>
    <!--fake-header-->
     <div class="fake-header"></div>
    <main class="top-students-page">
        <div class="top-students-container">
            <h1 class="top-students-title">لوحة الشرف</h1>
            <div class="title-underline"><div><span></span></div></div>
            <p class="top-students-intro">نحتفي بطلابنا المتفوقين ونشارككم درجاتهم وإنجازاتهم.</p>
            <?php if (!$loadError && $students): ?>
                <p class="top-students-count"><i class="fas fa-users" aria-hidden="true"></i> عدد الطلاب المتفوقين: <?= count($students) ?></p>
                <section class="top-students-grid" aria-label="قائمة الطلاب المتفوقين">
                    <?php foreach ($students as $student): ?>
                        <article class="top-student-card">
                            <div class="top-student-banner">
                                <div class="top-student-medal"><i class="fas fa-medal" aria-hidden="true"></i></div>
                                <h2 class="top-student-name"><?= top_students_escape($student['student_name']) ?></h2>
                            </div>
                            <div class="top-student-content">
                                <div class="top-student-score-label">الدرجة</div>
                                <div class="top-student-score"><?= top_students_escape($student['score']) ?></div>
                                <a class="top-student-view" href="./uploads/leaderboard/<?= rawurlencode($student['image_filename']) ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-image" aria-hidden="true"></i> عرض صورة الدرجة
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php elseif ($loadError): ?>
                <section class="top-students-state"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><h2>تعذر تحميل القائمة</h2><p>حدثت مشكلة مؤقتة أثناء تحميل بيانات المتفوقين. يرجى المحاولة لاحقًا.</p></section>
            <?php else: ?>
                <section class="top-students-state"><i class="fas fa-award" aria-hidden="true"></i><h2>قريبًا نحتفي بمتفوقينا</h2><p>لم تتم إضافة أسماء المتفوقين بعد.</p></section>
            <?php endif; ?>
        </div>
    </main>
    <?php include "./includes/footer.html"; ?>
       <!--whatsapp-->
        <div class="whatsapp" title="التواصل عبر واتساب">
         <i class="fab fa-whatsapp"></i>
        </div>
</div>
</body>
</html>