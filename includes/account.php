<!--account-->
 <nav class="account">
  <?php if (empty($_COOKIE["id"])): ?>
  <button type="button" onclick="login()">تسجيل</button>
  <?php else: ?>
  <button type="button" onclick="location.assign('http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7/account.php')">الحساب</button>
  <?php endif; ?>
 </nav>