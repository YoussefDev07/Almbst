<?php require_once "./master/connect.php"; ?>
<html lang="ar" dir="rtl" type="text/html">
 <head>
  <!--meta-->
   <meta charset = "utf-8"/>
   <meta name = "viewport" content = "width=device-width, initial-scale=1.0"/>
   <meta name = "keywords" content = "سياسة المبسط"/>
   <meta name = "theme-color" content = "#ffffff"/>
   <meta name = "color-scheme" content = "light"/>
   <meta name = "author" content = "Youssef Dev"/>
   <meta name = "owner" content = "إبراهيم عبدالمنعم"/>
   <meta name = "google" content = "notranslate"/>
   <meta name = "robots" content = "all"/>
   <meta name = "title" content = "سياسة الاسترداد والإلغاء"/>
   <meta name = "twitter:card" content = "summary"/>
   <meta name = "twitter:url" content = "http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7/privacy.php"/>
   <meta name = "twitter:title" content = "سياسة الاسترداد والإلغاء"/>
   <meta name = "twitter:image" content = "./assets/images/logo.png"/>
   <meta name = "twitter:image:alt" content = "Almbst"/>
   <meta property = "og:type" content = "website"/>
   <meta property = "og:url" content = "/"/>
   <meta property = "og:site_name" content = "المبسط"/>
   <meta property = "og:title" content = "سياسة الاسترداد والإلغاء"/>
   <meta property = "og:image" content = "./assets/images/logo.png"/>
   <meta property = "og:image:alt" content = "Almbst"/>
  <!--link-->
   <link rel="canonical" href="http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7/privacy.php"/>
   <link rel="icon" type="image/png" href="./assets/images/logo.png"/>
   <link rel="stylesheet" type="text/css" href="./assets/css/style.css"/>
   <link rel="stylesheet" media="all" href="./assets/libs/css/fontawesome.css"/>
  <!--title-->
   <title>سياسة الاسترداد والإلغاء</title>
  <!--script-->
   <script src="./assets/libs/js/jquery.js"></script>
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
      <!--conditions-->
       <sections class="conditions">
        <h1>سياسة الاسترداد والإلغاء</h1>
        <span>أخر تعديل: 2025/11/07مـ | ١٤٤٧/٠٥/١٦هـ</span>
        <p>نشكركم على الاشتراك في دوراتنا. نرجو قراءة سياسة الاسترداد بعناية قبل الاشتراك في أي من دوراتنا.</p>
        <h3>شروط عامة</h3>
        <ul>
         <li>لا يمكن استرداد قيمة الاشتراك بعد تفعيل أي دورة.</li>
         <li>بمجرد تفعيل الدورة والوصول للمحتوى يعتبر الاشتراك نهائياً ولا يمكن استرداده.</li>
        </ul>
        <h3>سياسة الإلغاء</h3>
        <ul>
         <li>لا يمكن إلغاء الاشتراك واسترداد المبلغ بعد تفعيل أي دورة.</li>
         <li>في حال تم اكتشاف أي نشاط مخالف لشروط الاستخدام، يحق لنا إلغاء اشتراك المستخدم دون استرداد المبلغ المدفوع.</li>
        </ul>
        <br>
       </section>
     </main>
    <?php include "./includes/footer.html"; ?>
   </div>
 </body>
</html>