<header class="admin-header">
 <div class="header-container">
    <a class="admin-logo" href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../index.php' : './index.php'; ?>">
     <img src="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../../assets/images/logo.png' : '../assets/images/logo.png'; ?>" alt="المبسط">
     <span>لوحة التحكم</span>
    </a>
    <nav class="main-nav">
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../index.php' : './index.php'; ?>" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>الرئيسية</a>
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../courses.php' : './courses.php'; ?>" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'class="active"' : ''; ?>>الكورسات</a>
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../sections.php' : './sections.php'; ?>" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'sections.php' ? 'class="active"' : ''; ?>>الأقسام والمحتوى</a>
   <a href="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../logout.php' : './logout.php'; ?>" class="logout-btn">تسجيل الخروج</a>
    </nav>
 </div>
</header>
<script src="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../../assets/libs/js/jquery.js' : '../assets/libs/js/jquery.js'; ?>"></script>
<script src="<?php echo basename(dirname($_SERVER["PHP_SELF"])) == 'exams' ? '../../assets/js/script.js' : '../assets/js/script.js'; ?>"></script>