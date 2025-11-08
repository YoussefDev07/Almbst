<?php
  require_once "./master/connect.php";
  include "./includes/convert_duration_to_arabic.php";
  if (empty($_GET["session"])) {
    header("location:index.php");
    exit();
  }
  else {
    $session = $_GET["session"];
    $check = $connect -> query("SELECT id FROM courses WHERE id = ".$session) -> fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($check[0])) {
      header("location:index.php");
      exit();
    }
    else {
      $session = $connect -> query("SELECT * FROM courses WHERE id = ".$session) -> fetchAll(PDO::FETCH_ASSOC);
      $session = $session[0];
    }
  }
?>
<html lang="ar" dir="rtl" type="text/html">
 <head>
  <!--meta-->
   <meta charset = "utf-8"/>
   <meta name = "viewport" content = "width=device-width, initial-scale=1.0"/>
   <meta name = "theme-color" content = "#ffffff"/>
   <meta name = "color-scheme" content = "light"/>
   <meta name = "author" content = "Youssef Dev"/>
   <meta name = "owner" content = "إبراهيم عبدالمنعم"/>
   <meta name = "google" content = "notranslate"/>
   <meta name = "robots" content = "none"/>
   <meta name = "title" content = "<?= $session["title"]; ?>"/>
   <meta name = "description" content = "المبسط في القدرات الكمي"/>
   <meta name = "twitter:card" content = "summary"/>
   <meta name = "twitter:url" content = "http://localhost/www/%D8%A7%D9%84%D9%85%D8%A8%D8%B3%D8%B7/course.php?session=<?= $session["id"]; ?>"/>
   <meta name = "twitter:title" content = "<?= $session["title"]; ?>"/>
   <meta name = "twitter:description" content = "المبسط في القدرات الكمي"/>
   <meta name = "twitter:image" content = "./assets/images/logo.png"/>
   <meta name = "twitter:image:alt" content = "Almbst"/>
   <meta property = "og:type" content = "website"/>
   <meta property = "og:url" content = "/"/>
   <meta property = "og:site_name" content = "المبسط"/>
   <meta property = "og:title" content = "<?= $session["title"]; ?>"/>
   <meta property = "og:description" content = "المبسط في القدرات الكمي"/>
   <meta property = "og:image" content = "./assets/images/logo.png"/>
   <meta property = "og:image:alt" content = "Almbst"/>
  <!--link-->
   <link rel="canonical" href="http://localhost/www/%D8%A7%D9%84%D9%85%D8%A8%D8%B3%D8%B7/course.php?session=<?= $session["id"]; ?>"/>
   <link rel="icon" type="image/png" href="./assets/images/logo.png"/>
   <link rel="stylesheet" type="text/css" href="./assets/css/style.css"/>
   <link rel="stylesheet" media="all" href="./assets/libs/css/fontawesome.css"/>
   <link rel="stylesheet" href="./assets/libs/css/animate.css"/>
  <!--title-->
   <title>المبسط - <?= $session["title"]; ?></title>
  <!--script-->
   <script src="./assets/libs/js/jquery.js"></script>
   <script src="./assets/libs/js/jquery.cookie.js"></script>
   <script src="./assets/libs/js/wow.js"></script>
   <script src="./assets/libs/js/sweetalert.js"></script>
   <script type="application/javascript" src="./assets/js/script.js" async></script>
   <noscript>الرجاء فتح الـJavaScript لعمل الموقع بشكل كامل...</noscript>
 </head>
 <body>
  <!--container-->
   <div class="_container">
    <!--header-->
     <header>
      <!--logo-->
       <nav class="logo">
        <a href="./"><img title="المبسط" src="./assets/images/logo.png" alt="Almbst"></a>
       </nav>
      <!--navagators-->
       <nav class="navagators">
        <a href="./index.php">الرئيسية</a>
        <a href="./index.php#courses">الدورات</a>
        <a href="./index.php#book">كتاب المبسط</a>
       </nav>
      <?php include "./includes/account.php"; ?>
      <!--slider-->
       <button type="menu" id="slider"><i class="fas fa-bars"></i></button>
       <div class="slider">
        <nav>
         <button type="close" id="closeSlider"><i class="fas fa-times"></i></button>
         <ul>
          <li><a href="./index.php">الرئيسية</a></li>
          <li><a href="./index.php#courses">الدورات</a></li>
          <li><a href="./index.php#book">كتاب المبسط</a></li>
         </ul>
         <?php include "./includes/account.php"; ?>
        </nav>
       </div>
     </header>
    <!--fake-header-->
     <div class="fake-header"></div>
    <!--main-->
     <main>
      <?php
       if (empty($_COOKIE["id"])) {
         include "./includes/courses/activate.php";
       }
       else {
         $course_subscriptions = $connect -> query("SELECT id FROM subscriptions WHERE course_id = ".$session["id"]." AND expired = false AND user_id = ".$_COOKIE["id"]) -> fetchAll(PDO::FETCH_COLUMN);
         if (isset($course_subscriptions[0])) {
           include "./includes/courses/contents.php";
         }
         else {
           include "./includes/courses/activate.php";
         }
       }
      ?>
     </main>
    <?php include "./includes/footer.html"; ?>
   </div>
 </body>
</html>