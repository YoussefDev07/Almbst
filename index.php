<?php require_once "./master/connect.php"; ?>
<html lang="ar" dir="rtl" type="text/html">
 <head>
  <!--meta-->
   <meta charset = "utf-8"/>
   <meta name = "viewport" content = "width=device-width, initial-scale=1.0"/>
   <meta name = "keywords" content = "قدرات، قدرات كمي، تأسيس قدرات، تأسيس قدرات كمي، دورة قدرات، دورة قدرات كمي، قدرات تأسيس، قدرات كمي تأسيس"/>
   <meta name = "theme-color" content = "#ffffff"/>
   <meta name = "color-scheme" content = "light"/>
   <meta name = "author" content = "Youssef Dev"/>
   <meta name = "owner" content = "إبراهيم عبدالمنعم"/>
   <meta name = "google" content = "notranslate"/>
   <meta name = "robots" content = "all"/>
   <meta name = "title" content = "المبسط في القدرات الكمي"/>
   <meta name = "description" content = "المبسط فى القدرات هو دليلك لضمان النجاح والتفوق ابدأ معنا الأن وحقق نجاحك."/>
   <meta name = "twitter:card" content = "summary"/>
   <meta name = "twitter:url" content = "http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7"/>
   <meta name = "twitter:title" content = "المبسط في القدرات الكمي"/>
   <meta name = "twitter:description" content = "المبسط فى القدرات هو دليلك لضمان النجاح والتفوق ابدأ معنا الأن وحقق نجاحك."/>
   <meta name = "twitter:image" content = "./assets/images/logo.png"/>
   <meta name = "twitter:image:alt" content = "Almbst"/>
   <meta property = "og:type" content = "website"/>
   <meta property = "og:url" content = "/"/>
   <meta property = "og:site_name" content = "المبسط"/>
   <meta property = "og:title" content = "المبسط في القدرات الكمي"/>
   <meta property = "og:description" content = "المبسط فى القدرات هو دليلك لضمان النجاح والتفوق ابدأ معنا الأن وحقق نجاحك."/>
   <meta property = "og:image" content = "./assets/images/logo.png"/>
   <meta property = "og:image:alt" content = "Almbst"/>
  <!--link-->
   <link rel="canonical" href="http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7/"/>
   <link rel="icon" type="image/png" href="./assets/images/logo.png"/>
   <link rel="stylesheet" type="text/css" href="./assets/css/style.css"/>
   <link rel="stylesheet" media="all" href="./assets/libs/css/fontawesome.css"/>
   <link rel="stylesheet" href="./assets/libs/css/animate.css"/>
  <!--title-->
   <title>المبسط في القدرات الكمي</title>
  <!--script-->
   <script src="./assets/libs/js/jquery.js"></script>
   <script src="./assets/libs/js/wow.js"></script>
   <script src="./assets/libs/js/sweetalert.js"></script>
   <script src="./assets/libs/js/particles.js"></script>
   <script src="./assets/libs/js/particles.app.js" defer></script>
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
        <a>الرئيسية</a>
        <a href="#courses">الدورات</a>
        <a href="#book">كتاب المبسط</a>
       </nav>
      <?php include "./includes/account.php"; ?>
      <!--slider-->
       <button type="menu" id="slider"><i class="fas fa-bars"></i></button>
       <div class="slider">
        <nav>
         <button type="close" id="closeSlider"><i class="fas fa-times"></i></button>
         <ul>
          <li><a>الرئيسية</a></li>
          <li><a href="#courses">الدورات</a></li>
          <li><a href="#book">كتاب المبسط</a></li>
         </ul>
         <?php include "./includes/account.php"; ?>
        </nav>
       </div>
     </header>
    <!--fake-header-->
     <div class="fake-header"></div>
    <!--main-->
     <main>
      <!--interface-->
       <section id="particles" class="interface">
        <img src="./assets/images/interface.png" alt="interface-logo" fetchpriority="high">
       </section>
      <!--courses-->
       <section id="courses">
        <h3>دورات المبسط</h3>
        <div class="title-underline">
         <div>
          <span></span>
         </div>
        </div>
        <section class="courses">
         <?php
          include "./includes/convert_duration_to_arabic.php";
          $courses = $connect -> query("SELECT * FROM courses");
          while ($course = $courses -> fetch()):
         ?>
         <div class="course wow animate__fadeInUp" data-wow-duration="800ms" data-wow-offset="1">
          <div class="course-banner">
           <span class="course-title"><?= $course["title"]; ?></span>
           <span class="course-icon"><i class="<?= $course["icon"] ?>"></i></span>
          </div>
          <ul class="course-info">
           <li><i class="fas fa-clock"></i> المدة: <?php echo convert_duration_to_arabic($course["duration"]); ?></li>
           <li><i class="fas fa-money-bill-wave"></i> السعر: <?= $course["price"]; ?> <embed type="image/svg+xml" src="./assets/svg/SAR.svg" width="12px" height="auto"></embed></li>
          </ul>
          <?php
           if (empty($_COOKIE["id"])) {
             echo '<a class="course-buy" onclick="login()"><button>شراء الدورة</button></a>';
           }
           else {
             $course_subscriptions = $connect -> query("SELECT id FROM subscriptions WHERE course_id = ".$course["id"]." AND expired = false AND user_id = ".$_COOKIE["id"]) -> fetchAll(PDO::FETCH_COLUMN);
             if (isset($course_subscriptions[0])) {
               echo '<a class="course-view" href="./course.php?session='.$course["id"].'"><button>الدخول إلى الدورة</button></a>';
             }
             else {
               echo '<a class="course-buy" href="./course.php?session='.$course["id"].'"><button>شراء الدورة</button></a>';
             }
           }
          ?>
         </div>
         <?php endwhile; ?>
        </section>
       </section>
      <!--book-->
       <section id="book">
        <div>
         <img class="wow animate__fadeInRight" data-wow-duration="800ms" data-wow-offset="1" src="./assets/images/almbst_book.png" alt="كتاب المبسط" loading="lazy">
          <!--book-info-->
           <div class="book-info">
            <h2 class="wow animate__fadeInDown" data-wow-duration="800ms" data-wow-offset="1">كتاب المبسط</h2>
            <p class="wow animate__fadeInLeft" data-wow-duration="800ms" data-wow-offset="1">تأسيس قدرات كمي</p>
            <a href="" download><button type="button" class="wow animate__fadeInUp" data-wow-duration="800ms" data-wow-offset="1"><i class="fas fa-download"></i>تحميل الكتاب بصيغة PDF</button></a>
           </div>
        </div>
       </section>
     </main>
    <?php include "./includes/footer.html"; ?>
   </div>
 </body>
</html>