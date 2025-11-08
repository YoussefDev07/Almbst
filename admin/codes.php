<?php 
require_once "config.php";

$page_title = "إدارة رموز التفعيل";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <?php
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['add_code'])) {

                function createRandomPassword() { 

                      $chars = "abcdefghijkmnopqrstuvwxyz023456789"; 
                      srand((double)microtime()*1000000); 
                      $i = 0; 
                      $pass = '' ; 

                       while ($i <= 12) { 
                            $num = rand() % 33; 
                            $tmp = substr($chars, $num, 1); 
                           $pass = $pass . $tmp; 
                           $i++; 
                       } 

                     return $pass; 

                }

                $stmt = $db->prepare("INSERT INTO activate_codes (code) VALUES (?)");
                $stmt->execute([createRandomPassword()]);

            }
            elseif (isset($_POST['code_id_for_delete'])) {
                $stmt = $db->exec("DELETE FROM activate_codes WHERE code = '".$_POST["code_id_for_delete"]."'");
            }
      }

      $codes = $db->query("SELECT * FROM activate_codes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="container">
        <div class="table-card">
            <h2>قائمة الرموز</h2>
             <form method="POST" action="">
              <button type="submit" name="add_code" class="btn btn-primary">إضافة رمز</button>
             </form>
            <?php if (count($codes) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $code): ?>
                            <tr>
                                <td><?= $code["code"]; ?></td>
                                <td>
                                  <a class="btn-edit" style="cursor:pointer;" onclick="copy_code('<?= $code["code"]; ?>')">💾 نسخ</a>
                                  <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الرمز؟');">
                                        <input type="hidden" name="code_id_for_delete" value="<?php echo $code['code']; ?>">
                                        <button type="submit" class="btn-delete">🗑️ حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">لا توجد رموز حالياً. قم بإضافة رمز جديد.</p>
            <?php endif; ?>
            <script>
             function copy_code(code) {
                navigator.clipboard.writeText(code);
                alert("تم نسخ الرمز!");
             }
            </script>
        </div>
    </div>
</body>
</html>
