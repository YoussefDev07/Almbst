<?php
include "../config.php";

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $questions = json_decode($_POST['questions_json'], true);

  if (!$title) {
    $err = 'أدخل اسم الاختبار';
  } else if (empty($questions)) {
    $err = 'يجب إضافة سؤال واحد على الأقل';
  } else {
    $connect->beginTransaction();
    try {
      if (!empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $connect->prepare("UPDATE exams SET title = ?, num_questions = ? WHERE id = ?");
        $stmt->execute([$title, count($questions), $id]);
        $connect->prepare("DELETE FROM questions WHERE exam_id = ?")->execute([$id]);
      } else {
        $stmt = $connect->prepare("INSERT INTO exams (title, num_questions) VALUES (?, ?)");
        $stmt->execute([$title, count($questions)]);
        $id = $connect->lastInsertId();
      }

      $ins = $connect->prepare("INSERT INTO questions (exam_id, q_image, choice_a, choice_b, choice_c, choice_d, correct_choice, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $pos = 1;
      foreach ($questions as $q) {
        $ins->execute([
          $id,
          $q['q_image'] ?? '',
          $q['a'] ?? '',
          $q['b'] ?? '',
          $q['c'] ?? '',
          $q['d'] ?? '',
          strtoupper($q['correct'] ?? 'A'),
          $pos++
        ]);
      }

      $connect->commit();
    } catch (Exception $e) {
      $connect->rollBack();
      $err = "حدث خطأ: " . $e->getMessage();
    }

    if (!$err) {
      header("Location:index.php");
      exit();
    }
  }
}

$edit = null;
$qs = [];
if (!empty($_GET['id'])) {
  $id = intval($_GET['id']);
  $stmt = $connect->prepare("SELECT * FROM exams WHERE id = ?");
  $stmt->execute([$id]);
  $edit = $stmt->fetch(PDO::FETCH_ASSOC);

  $qs_stmt = $connect->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY position ASC");
  $qs_stmt->execute([$id]);
  $qs = $qs_stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!$edit) {
    $err = "الاختبار غير موجود.";
    $edit = null;
    $qs = [];
  }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $edit ? 'تعديل' : 'إنشاء' ?> اختبار</title>
<style>
/* تنسيق عام للصفحة */
body {
  font-family: sans-serif;
  background-color: #f9f9f9;
  direction: rtl;
  margin: 0;
  padding: 0;
  color: #333;
}

/* الحاوية الرئيسية */
.container {
  max-width: 800px;
  margin: 40px auto;
  background-color: #fff;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* العناوين */
h2, h3, h4 {
  color: #2c3e50;
  margin-bottom: 20px;
}

h4 {
  font-size: 18px;
  margin-top: 30px;
}

/* التنبيهات */
.alert {
  background-color: #ffe0e0;
  color: #c0392b;
  padding: 10px 15px;
  border-radius: 6px;
  margin-bottom: 20px;
  border: 1px solid #e74c3c;
}

/* النموذج */
form label {
  display: block;
  margin-bottom: 10px;
  font-weight: bold;
}

form input[type="text"] {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  margin-bottom: 15px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-sizing: border-box;
}

form input[type="file"] {
  margin-top: 10px;
}

.correct-options {
  margin-top: 10px;
  margin-bottom: 15px;
  display: flex;
  gap: 20px;
}

.correct-options label {
  display: flex;
  font-size: 25px;
  align-items: center;
  font-weight: bold;
  cursor: pointer;
}

.correct-options input[type="radio"] {
  width: 20px;
  height: 20px;
  margin-left: 6px;
  accent-color: #3498db;
}

form button {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
  margin-top: 10px;
}

form button:hover {
  background-color: #2980b9;
}

/* الأسئلة */
.q {
  background-color: #f0f8ff;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  border: 1px solid #dce6f1;
}

.q img.preview {
  max-width: 100%;
  height: auto;
  margin-top: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

/* زر حذف السؤال */
.removeQ {
  background-color: #e74c3c;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  margin-right: 10px;
}

.removeQ:hover {
  background-color: #c0392b;
}

/* خط فاصل */
hr {
  margin: 30px 0;
  border: none;
  border-top: 1px solid #ccc;
}

/* Media Queries */

@media only screen and (max-width: 768px) {
  .container {
    margin: 20px auto;
    padding: 20px;
  }
  
  h2, h3, h4 {
    font-size: 18px;
    margin-bottom: 15px;
  }
  
  h4 {
    font-size: 16px;
    margin-top: 20px;
  }
  
  form label {
    font-size: 14px;
  }
  
  form input[type="text"] {
    padding: 8px;
  }

  .correct-options {
    flex-direction: column;
    gap: 10px;
  }
  
  .correct-options label {
    font-size: 18px;
  }
  
  form button {
    padding: 8px 16px;
    font-size: 14px;
  }
  
  .q {
    padding: 15px;
  }
  
  .removeQ {
    padding: 4px 8px;
    font-size: 12px;
  }
}
</style>
<script src="../../assets/libs/js/jquery.js"></script>
</head>
<body>
<div class="container">
  <h2><?= $edit ? 'تعديل' : 'إنشاء' ?> اختبار</h2>
  <?php if($err): ?><div class="alert"><?=$err?></div><?php endif; ?>

  <form id="createForm" method="post">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <label>اسم الاختبار:
      <input type="text" name="title" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required>
    </label>

    <h3>الأسئلة (صورة لكل سؤال)</h3>
    <div id="questionsContainer"></div>
    <button type="button" id="addQ">أضف سؤال</button>

    <input type="hidden" name="questions_json" id="questions_json">
    <hr>
    <button type="submit"><?= $edit ? 'حفظ' : 'إنشاء' ?></button>
  </form>
</div>

<script>
function makeQ(i, data = {}) {
  data.q_image = data.q_image || '';
  data.a = data.a || '';
  data.b = data.b || '';
  data.c = data.c || '';
  data.d = data.d || '';
  data.correct = data.correct || 'A';

  const $q = $(`<div class="q" data-i="${i}">
    <h4>سؤال ${i} <button type="button" class="removeQ">حذف</button></h4>
    <label>صورة السؤال:</label>
    <input type="file" accept="image/*" class="q_image"><br>
    ${data.q_image ? `<img src="${data.q_image}" class="preview">` : ''}
    <input type="hidden" class="q_image_path" value="${data.q_image}">
    <br>
    <input type="hidden" class="a" value="أ">
    <input type="hidden" class="b" value="ب">
    <input type="hidden" class="c" value="ج">
    <input type="hidden" class="d" value="د">
    الإجابة الصحيحة:
    <div class="correct-options">
     <label><input type="radio" name="correct" value="A" class="correct"> أ</label>
     <label><input type="radio" name="correct" value="B" class="correct"> ب</label>
     <label><input type="radio" name="correct" value="C" class="correct"> ج</label>
     <label><input type="radio" name="correct" value="D" class="correct"> د</label>
    </div>

  </div>`);

  $q.find('.a').val(data.a);
  $q.find('.b').val(data.b);
  $q.find('.c').val(data.c);
  $q.find('.d').val(data.d);
  $q.find('.correct').val(data.correct);

  return $q;
}

let counter = 1;
$('#addQ').click(() => $('#questionsContainer').append(makeQ(counter++)));

$('#questionsContainer').on('click','.removeQ',function(){
  $(this).closest('.q').remove();
  $('#questionsContainer .q').each((i,el)=>{
    $(el).find('h4').html(`سؤال ${i+1} <button type="button" class="removeQ">حذف</button>`);
    $(el).attr('data-i',i+1);
  });
  counter = $('#questionsContainer .q').length + 1;
});

// رفع الصورة مباشرة
$('#questionsContainer').on('change','.q_image',function(){
  const fileInput = this;
  const formData = new FormData();
  formData.append('image', fileInput.files[0]);

  $.ajax({
    url: './upload.php',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(res){
      try {
        const data = JSON.parse(res);
        if(data.status === 'ok'){
          
          // 1. تعيين المسار المخفي
          $(fileInput).siblings('.q_image_path').val(data.path);

          // 2. البحث عن صورة معاينة موجودة
          const $preview = $(fileInput).siblings('img.preview');

          if ($preview.length > 0) {
            // 3a. إذا كانت موجودة، قم بتحديث المصدر فقط
            $preview.attr('src', data.path);
          } else {
            // 3b. إذا لم تكن موجودة، أضف وسماً جديداً
            $(fileInput).after(`<img src="${data.path}" class="preview">`);
          }
        }else{
          alert('❌ فشل رفع الصورة: '+data.message);
        }
      }catch(e){alert('❌ خطأ في رفع الصورة'); console.log(e);}
    }
  });
});

$('#createForm').submit(function(e){
  const questions = [];
  let isValid = true;

  $('#questionsContainer .q').each(function(){
    const q_image = $(this).find(".q_image_path").val();
    const a = $(this).find(".a").val().trim() || "أ";
    const b = $(this).find(".b").val().trim() || "ب";
    const c = $(this).find(".c").val().trim() || "ج";
    const d = $(this).find(".d").val().trim() || "د";
    const correct = $(this).find("input.correct:checked").val();

    if(!q_image || !a || !b || !c || !d){
      isValid = false;
      alert(`يرجى ملء جميع الحقول ورفع صورة للسؤال رقم ${$(this).data('i')}`);
      return false;
    }
    questions.push({q_image,a,b,c,d,correct});
  });

  // التحقق من الصلاحية خارج الحلقة
  if (!isValid) {
    e.preventDefault(); // إيقاف الإرسال إذا كان هناك خطأ
    return;
  }

  if(questions.length===0){
    alert('يجب إضافة سؤال واحد على الأقل.');
    e.preventDefault();
    return;
  }

  $('#questions_json').val(JSON.stringify(questions));
});

// تحميل الأسئلة الموجودة عند التعديل
let existing = <?= json_encode($qs) ?>;
if(existing && existing.length>0){
  $('#questionsContainer').empty();
  existing.forEach((q,i)=>{
    const qData = {
      q_image: q.q_image,
      a: q.choice_a,
      b: q.choice_b,
      c: q.choice_c,
      d: q.choice_d,
      correct: q.correct_choice
    };

    $('#questionsContainer').append(makeQ(i+1,qData));
  });
  counter = existing.length + 1;
} else if(counter===1) $('#addQ').click();
</script>
</body>
</html>