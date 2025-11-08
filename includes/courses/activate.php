<!--pay-card-->
 <div class="pay-card">
  <div class="course wow animate__zoomIn" data-wow-duration="800ms" data-wow-offset="1">
   <div class="course-banner">
    <span class="course-title"><?= $session["title"]; ?></span>
    <span class="course-icon"><i class="<?= $session["icon"]; ?>"></i></span>
   </div>
   <ul class="course-info">
    <li><i class="fas fa-clock"></i> المدة: <?php echo convert_duration_to_arabic($session["duration"]); ?></li>
    <li><i class="fas fa-money-bill-wave"></i> السعر: <?= $session["price"]; ?> <embed type="image/svg+xml" src="./assets/svg/SAR.svg" width="12px" height="auto"></embed></li>
   </ul>
   <?php
    if (empty($_COOKIE["id"])) {
      echo '<a class="course-buy" onclick="login()"><button>شراء الدورة</button></a>';
    }
    else {
      $course_id = $_GET["session"];
      echo '<a class="course-buy" onclick="activateCourse('.$course_id.')"><button>تفعيل الدورة</button></a>';
    }
   ?>
  </div>
 </div>