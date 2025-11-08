<header class="admin-header">
 <div class="header-container">
  <div class="logo">
   <h2>🎓 لوحة التحكم</h2>
  </div> 
  <nav class="main-nav">
   <a href="index.php" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>الرئيسية</a>
   <a href="courses.php" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'class="active"' : ''; ?>>الكورسات</a>
   <a href="sections.php" class="r" <?php echo basename($_SERVER['PHP_SELF']) == 'sections.php' ? 'class="active"' : ''; ?>>الأقسام والمحتوى</a>
   <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
  </nav>
 </div>
</header>