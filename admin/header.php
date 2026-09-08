<header class="admin-header noPrint">
 <div class="header-container">
  <div class="logo">
   <h2>🎓 لوحة التحكم</h2>
  </div> 
  <nav class="main-nav">
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../index.php' : './index.php'; ?>" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>الرئيسية</a>
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../courses.php' : './courses.php'; ?>" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'class="active"' : ''; ?>>الكورسات</a>
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../sections.php' : './sections.php'; ?>" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'sections.php' ? 'class="active"' : ''; ?>>الأقسام والمحتوى</a>
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../logout.php' : './logout.php'; ?>" class="logout-btn">تسجيل الخروج</a>
  </nav>
 </div>
</header>