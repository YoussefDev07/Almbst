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
     $uncategorized_elements = $connect -> query(
       "SELECT * FROM courses_sections
        WHERE type = 'element'
        AND course_id = ".$course["id"]."
        AND (category_id IS NULL OR category_id = 0)
        ORDER BY id ASC"
     );
     while ($uncategorized_element = $uncategorized_elements -> fetch()):
   ?>
   <button type="button" data-id="<?= $uncategorized_element["id"]; ?>"><i class="fas fa-play fa-rotate-180"></i><?= $uncategorized_element["title"]; ?></button>
   <hr>
   <?php endwhile; ?>

   <?php
     # categories
     $course_categories = $connect -> query(
       "SELECT * FROM courses_sections
        WHERE type = 'category'
        AND course_id = ".$course["id"]."
        ORDER BY id ASC"
     );
     while ($course_category = $course_categories -> fetch()):
   ?>
   <details>
    <summary><?= $course_category["title"]; ?></summary>
    <?php
     $category_elements = $connect -> query(
       "SELECT * FROM courses_sections
        WHERE type = 'element'
        AND category_id = ".$course_category["id"]."
        ORDER BY id ASC"
     );
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
    $tests = preg_split('/\r\n|\r|\n/', $course_item["test"]);

    $tests = array_values(array_filter(array_map('trim', $tests)));

    $test_count = count($tests);

    foreach ($tests as $index => $test):
?>
    <div class="test">
        <span>
            اختبار على الدرس<?= $test_count > 1 ? ' (' . ($index + 1) . ')' : '' ?>
        </span>

        <a href="<?= htmlspecialchars($test) ?>" target="_blank">
            <button type="button">بدء الإختبار</button>
        </a>
    </div>
<?php
    endforeach;
endif;
?>
   </div>
   <?php endif; ?>
 </div>
