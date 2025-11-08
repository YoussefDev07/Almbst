<?php
require_once "./master/connect.php";

if (empty($_GET["code"]) && empty($_COOKIE["id"])) {
  header("location:index.php");
  exit;
}

if (isset($_GET["code"])) {
  #google
  if (isset($_GET["scope"]) && str_contains($_GET["scope"], "googleapis")) {
    require_once "./master/google.php";

    $google -> fetchAccessTokenWithAuthCode($_GET["code"]);
    $googleUser = (new Google_Service_Oauth2($google)) -> userinfo -> get();

    setcookie("id", $googleUser -> id, time() + ((3600 * 24) * 30), "/");

    $stmt = $connect -> prepare("SELECT COUNT(id) FROM accounts WHERE id = ?");
    $stmt -> execute([$googleUser -> id]);
    $checkId = $stmt -> fetchAll(PDO::FETCH_COLUMN);
    
    if ($checkId[0] == 0) {
      $stmt = $connect -> prepare("INSERT INTO accounts(id, via, email, fristname, lastname, avatar, created) VALUES(?, ?, ?, ?, ?, ?, ?)");
      $stmt -> execute([$googleUser -> id, "google", $googleUser -> email, $googleUser -> givenName, $googleUser -> familyName, $googleUser -> picture, date("Y-m-d")]);
    }
    else {
      $connect -> exec("UPDATE accounts SET fristname = '".$googleUser -> givenName."', lastname = '".$googleUser -> familyName."', avatar = '".$googleUser -> picture."' WHERE id = '".$googleUser -> id."'");
    }
    
    header("location:account.php");
    exit;
  }
}

$stmt = $connect -> prepare("SELECT * FROM accounts WHERE id = ?");
$stmt -> execute([$_COOKIE["id"]]);
$userInfo = $stmt -> fetch(PDO::FETCH_ASSOC);

if (!$userInfo) {
  setcookie("id", "", time() - 3600);
  header("location:index.php");
  exit;
}

$stmt = $connect -> prepare("SELECT courses.*, subscriptions.subscription_date, subscriptions.expire_date FROM subscriptions INNER JOIN courses ON subscriptions.course_id = courses.id WHERE subscriptions.user_id = ? AND subscriptions.expired = 0");
$stmt -> execute([$_COOKIE["id"]]);
$userCourses = $stmt -> fetchAll(PDO::FETCH_ASSOC);
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
   <meta name = "robots" content = "all"/>
   <meta name = "title" content = "إعدادات الحساب"/>
   <meta name = "description" content = "إعدادات حسابك على منصة المبسط"/>
   <meta name = "twitter:card" content = "summary"/>
   <meta name = "twitter:url" content = "http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7"/>
   <meta name = "twitter:title" content = "إعدادات الحساب"/>
   <meta name = "twitter:description" content = "إعدادات حسابك على منصة المبسط"/>
   <meta name = "twitter:image" content = "./assets/images/logo.png"/>
   <meta name = "twitter:image:alt" content = "Almbst"/>
   <meta property = "og:type" content = "website"/>
   <meta property = "og:url" content = "/"/>
   <meta property = "og:site_name" content = "المبسط"/>
   <meta property = "og:title" content = "إعدادات الحساب"/>
   <meta property = "og:description" content = "إعدادات حسابك على منصة المبسط"/>
   <meta property = "og:image" content = "./assets/images/logo.png"/>
   <meta property = "og:image:alt" content = "Almbst"/>
  <!--link-->
   <link rel="canonical" href="http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7/"/>
   <link rel="icon" type="image/png" href="./assets/images/logo.png"/>
   <link rel="stylesheet" type="text/css" href="./assets/css/style.css"/>
   <link rel="stylesheet" media="all" href="./assets/libs/css/fontawesome.css"/>
  <!--title-->
   <title>إعدادات الحساب</title>
  <!--script-->
   <script src="./assets/libs/js/jquery.js"></script>
   <script src="./assets/libs/js/wow.js"></script>
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
      <!--accounts-->
       <div class="account-container">
        <div class="account-content">
         <div class="profile-section">
          <div class="profile-avatar">
           <img src="<?= $userInfo["avatar"] ?>" alt="<?= $userInfo["fristname"] . " " . $userInfo["lastname"] ?>">
          </div>
          <div class="profile-info">
           <div class="profile-name"><?= $userInfo["fristname"] . " " . $userInfo["lastname"] ?></div>
           <div class="profile-email"><i class="fas fa-envelope"></i> <?= $userInfo["email"] ?></div>
           <div class="profile-joined"><i class="fas fa-calendar-alt"></i><span>عضو منذ: <?= date("Y/m/d", strtotime($userInfo["created"])) ?></span></div>
          </div>
          <button class="logout-btn" onclick="logout()"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</button>
        </div>
        <div class="courses-section">
         <h3 class="section-title">دوراتي المسجلة</h3>
          <div class="course-title-underline">
           <div><span></span></div>
          </div>
          <?php if (count($userCourses) > 0): ?>
          <div class="my-courses">
           <?php foreach ($userCourses as $course): ?>
            <div class="enrolled-course">
             <div class="enrolled-course-banner">
              <span class="enrolled-course-title"><?= $course["title"] ?></span>
              <span class="enrolled-course-icon"><i class="<?= $course["icon"] ?>"></i></span>
             </div>
             <div class="enrolled-course-info">
              <div class="enrolled-course-detail"><i class="fas fa-calendar-check"></i><span>تاريخ الاشتراك: <?= date("Y/m/d", strtotime($course["subscription_date"])) ?></span></div>
               <div class="enrolled-course-detail"><i class="fas fa-clock"></i><span>ينتهي في: <?= date("Y/m/d", strtotime($course["expire_date"])) ?></span></div>
                <a class="enrolled-course-link" href="./course.php?session=<?= $course["id"] ?>">
                 <button>الدخول إلى الدورة <i class="fas fa-arrow-left"></i></button>
                </a>
               </div>
              </div>
           <?php endforeach; ?>
          </div>
         <?php else: ?>
          <div class="no-courses">
           <div class="no-courses-icon"><i class="fas fa-graduation-cap"></i></div>
           <p>لم تقم بالتسجيل في أي دورة بعد</p>
           <button class="browse-courses-btn" onclick="location.assign('./#courses')">تصفح الدورات المتاحة</button>
          </div>
         <?php endif; ?>
        </div>
       </div>
      </div>
     </main>
    <?php include "./includes/footer.html"; ?>
   </div>
 </body>
</html>