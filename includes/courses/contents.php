<?php
  require_once "./master/connect.php";

  #check valid course id
  if (empty($_GET["session"])) {
    header("location:index.php");
    exit();
  }
  else {
    $course = intval($_GET["session"]);
    $check = $connect -> query("SELECT id FROM courses WHERE id = $course") -> fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($check[0])) {
      header("location:index.php");
      exit();
    }
    else {
      $course = $connect -> query("SELECT * FROM courses WHERE id = $course") -> fetchAll(PDO::FETCH_ASSOC);
      $course = $course[0];
    }
  }

  #check valid session id
  if (isset($_GET["id"])) {
    $session = intval($_GET["id"]);
    $check = $connect -> query("SELECT id FROM courses_sections WHERE type = 'element' AND id = $session") -> fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($check[0])) {
      header("location:course.php?session=".$course["id"]);
      exit();
    }
    else {
      $session = $connect -> query("SELECT * FROM courses_sections WHERE id = $session") -> fetchAll(PDO::FETCH_ASSOC);
      $session = $session[0];
    }
  }
?>
<!--contents-->
 <div class="contents" session-id="<?= $course["id"]; ?>">
  <aside>
   <?php
     #contents
     $course_categories = $connect -> query("SELECT * FROM courses_sections WHERE type = 'category' AND course_id = ".$course["id"]);
     while ($course_category = $course_categories -> fetch()):
   ?>
   <details>
    <summary><?= $course_category["title"]; ?></summary>
    <?php
     $category_elements = $connect -> query("SELECT * FROM courses_sections WHERE type = 'element' AND category_id = ".$course_category["id"]);
     while ($category_element = $category_elements -> fetch()):
    ?>
    <button type="button" data-id="<?= $category_element["id"]; ?>"><i class="fas fa-play fa-rotate-180"></i><?= $category_element["title"]; ?></button>
    <hr>
    <?php endwhile; ?>
   </details>
   <?php endwhile; ?>
  </aside>
  <?php if (!empty($_GET["id"])): ?>
  <?php $course_item = $connect -> query("SELECT * FROM courses_sections WHERE id = ".$session["id"]) -> fetchAll(PDO::FETCH_ASSOC); $course_item = $course_item[0]; ?>
   <div class="content">
    <h6><?= $course_item["title"] ?></h6>
    <video src="<?= $course_item["video"] ?>" controls controlsList="nodownload">المتصفح الذي تستخدمه لا يفتح الفيديوهات</video>
    <?php
     if (!empty($course_item["test"])):
    ?>
    <div class="test">
     <span>اختبار على الدرس</span>
     <a href="<?= $course_item["test"] ?>" target="_blank"><button type="button">بدء الإختبار</button></a>
    </div>
    <?php endif; ?>
   </div>
   <?php endif; ?>
 </div>