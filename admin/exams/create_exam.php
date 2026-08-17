<?php
include "../config.php";

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $rawQuestions = (string)($_POST['questions_json'] ?? '');
  $questions = json_decode($rawQuestions, true);

  if ($title === '') {
    $err = 'أدخل اسم الاختبار';
  } elseif (json_last_error() !== JSON_ERROR_NONE || !is_array($questions) || empty($questions)) {
    $err = 'بيانات الأسئلة غير صحيحة أو لا يوجد سؤال واحد على الأقل.';
  } else {
    $cleanQuestions = [];
    foreach ($questions as $index => $q) {
      if (!is_array($q)) {
        $err = 'بيانات السؤال رقم ' . ($index + 1) . ' غير صحيحة.';
        break;
      }

      $correct = strtoupper(trim((string)($q['correct'] ?? '')));
      if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        $err = 'يجب تحديد الإجابة الصحيحة للسؤال رقم ' . ($index + 1) . '.';
        break;
      }

      $cleanQuestions[] = [
        'question_id' => (int)($q['question_id'] ?? 0),
        'q_image' => trim((string)($q['q_image'] ?? '')),
        'a' => trim((string)($q['a'] ?? '')),
        'b' => trim((string)($q['b'] ?? '')),
        'c' => trim((string)($q['c'] ?? '')),
        'd' => trim((string)($q['d'] ?? '')),
        'correct' => $correct
      ];
    }

    if ($err === '' && count($cleanQuestions) === 0) {
      $err = 'يجب إضافة سؤال واحد على الأقل.';
    }

    if ($err === '') {
      $connect->beginTransaction();
      try {
        $postedId = (int)($_POST['id'] ?? 0);

        if ($postedId > 0) {
          $check = $connect->prepare('SELECT id FROM exams WHERE id = ? FOR UPDATE');
          $check->execute([$postedId]);
          if (!$check->fetchColumn()) {
            throw new RuntimeException('الاختبار المطلوب تعديله غير موجود.');
          }
          $id = $postedId;

          $oldQuestionsStmt = $connect->prepare(
            'SELECT id FROM questions WHERE exam_id = ? FOR UPDATE'
          );
          $oldQuestionsStmt->execute([$id]);
          $oldQuestionRows = $oldQuestionsStmt->fetchAll(PDO::FETCH_ASSOC);

          $oldQuestionIds = [];
          foreach ($oldQuestionRows as $oldRow) {
            $oldQuestionIds[(int)$oldRow['id']] = true;
          }

          $stmt = $connect->prepare(
            'UPDATE exams SET title = ?, num_questions = ? WHERE id = ?'
          );
          $stmt->execute([$title, count($cleanQuestions), $id]);

          $updateQuestion = $connect->prepare('UPDATE questions SET q_image = ?, choice_a = ?, choice_b = ?, choice_c = ?, choice_d = ?, correct_choice = ?, position = ? WHERE id = ? AND exam_id = ?');

          $insertQuestion = $connect->prepare('INSERT INTO questions (exam_id, q_image, choice_a, choice_b, choice_c, choice_d, correct_choice, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

          $keptQuestionIds = [];

          foreach ($cleanQuestions as $pos => $q) {
            $questionId = (int)($q['question_id'] ?? 0);

            // تحديث سؤال موجود فقط إذا كان تابعًا لهذا الاختبار.
            if ($questionId > 0 && isset($oldQuestionIds[$questionId])) {
              $updateQuestion->execute([
                $q['q_image'],
                $q['a'],
                $q['b'],
                $q['c'],
                $q['d'],
                $q['correct'],
                $pos + 1,
                $questionId,
                $id
              ]);

              $keptQuestionIds[$questionId] = true;
            } else {
              // سؤال جديد.
              $insertQuestion->execute([
                $id,
                $q['q_image'],
                $q['a'],
                $q['b'],
                $q['c'],
                $q['d'],
                $q['correct'],
                $pos + 1
              ]);

              $newQuestionId = (int)$connect->lastInsertId();
              if ($newQuestionId <= 0) {
                throw new RuntimeException('تعذر إضافة سؤال جديد.');
              }

              $keptQuestionIds[$newQuestionId] = true;
            }
          }

          // حذف الأسئلة التي أزيلت من الاختبار وإجاباتها السابقة.
          $deleteAnswer = $connect->prepare(
            'DELETE FROM answers WHERE question_id = ?'
          );
          $deleteQuestion = $connect->prepare(
            'DELETE FROM questions WHERE id = ? AND exam_id = ?'
          );

          foreach ($oldQuestionIds as $oldQuestionId => $_unused) {
            if (!isset($keptQuestionIds[$oldQuestionId])) {
              $deleteAnswer->execute([$oldQuestionId]);
              $deleteQuestion->execute([$oldQuestionId, $id]);
            }
          }

          $recalculateAnswers = $connect->prepare("UPDATE answers SET is_correct = CASE WHEN UPPER(TRIM(COALESCE(selected_choice, ''))) = (SELECT UPPER(TRIM(correct_choice)) FROM questions WHERE questions.id = answers.question_id AND questions.exam_id = ?) THEN 1 ELSE 0 END WHERE question_id IN (SELECT id FROM questions WHERE exam_id = ?)");
          $recalculateAnswers->execute([$id, $id]);

          $resultsStmt = $connect->prepare(
            'SELECT id FROM results WHERE exam_id = ?'
          );
          $resultsStmt->execute([$id]);
          $resultIds = $resultsStmt->fetchAll(PDO::FETCH_COLUMN);

          $countCorrect = $connect->prepare(
            'SELECT COUNT(*)
               FROM answers a
               INNER JOIN questions q ON q.id = a.question_id
              WHERE a.result_id = ?
                AND q.exam_id = ?
                AND a.is_correct = 1'
          );

          $updateScore = $connect->prepare(
            'UPDATE results SET score = ? WHERE id = ? AND exam_id = ?'
          );

          $questionCount = count($cleanQuestions);

          foreach ($resultIds as $resultId) {
            $countCorrect->execute([(int)$resultId, $id]);
            $correctCount = (int)$countCorrect->fetchColumn();

            $newScore = $questionCount > 0
              ? (int)round(($correctCount / $questionCount) * 100)
              : 0;

            $updateScore->execute([$newScore, (int)$resultId, $id]);
          }

        } else {
          $stmt = $connect->prepare('INSERT INTO exams (title, num_questions) VALUES (?, ?)');
          $stmt->execute([$title, count($cleanQuestions)]);
          $id = (int)$connect->lastInsertId();
          if ($id <= 0) {
            throw new RuntimeException('تعذر إنشاء الاختبار في قاعدة البيانات.');
          }

          $ins = $connect->prepare(
            'INSERT INTO questions
             (exam_id, q_image, choice_a, choice_b, choice_c, choice_d, correct_choice, position)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
          );

          foreach ($cleanQuestions as $pos => $q) {
            $ins->execute([
              $id,
              $q['q_image'],
              $q['a'],
              $q['b'],
              $q['c'],
              $q['d'],
              $q['correct'],
              $pos + 1
            ]);
          }
        }

        $connect->commit();
      } catch (Throwable $e) {
        if ($connect->inTransaction()) {
          $connect->rollBack();
        }
        $err = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
      }

      if ($err === '') {
        header('Location: index.php');
        exit();
      }
    }
  }
}
$edit = null;
$qs = [];
if (!empty($_GET['id'])) {
  $id = (int)$_GET['id'];
  if ($id > 0) {
    $stmt = $connect->prepare('SELECT * FROM exams WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($edit) {
      $qs_stmt = $connect->prepare('SELECT * FROM questions WHERE exam_id = ? ORDER BY position ASC, id ASC');
      $qs_stmt->execute([$id]);
      $qs = $qs_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $err = 'الاختبار غير موجود.';
      $edit = null;
      $qs = [];
    }
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
  data.question_id = Number(data.question_id || 0);
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
    <input type="hidden" class="q_id" value="${data.question_id}">
    <br>
    <input type="hidden" class="a" value="أ">
    <input type="hidden" class="b" value="ب">
    <input type="hidden" class="c" value="ج">
    <input type="hidden" class="d" value="د">
    الإجابة الصحيحة:
    <div class="correct-options">
     <label><input type="radio" name="correct_${i}" value="A" class="correct"> أ</label>
     <label><input type="radio" name="correct_${i}" value="B" class="correct"> ب</label>
     <label><input type="radio" name="correct_${i}" value="C" class="correct"> ج</label>
     <label><input type="radio" name="correct_${i}" value="D" class="correct"> د</label>
    </div>

  </div>`);

  $q.find('.a').val(data.a);
  $q.find('.b').val(data.b);
  $q.find('.c').val(data.c);
  $q.find('.d').val(data.d);
  $q.find('.correct').prop('checked', false);
  $q.find(`.correct[value="${String(data.correct).toUpperCase()}"]`).prop('checked', true);

  return $q;
}

let counter = 1;
$('#addQ').click(() => $('#questionsContainer').append(makeQ(counter++)));

$('#questionsContainer').on('click','.removeQ',function(){
  $(this).closest('.q').remove();
  $('#questionsContainer .q').each((i,el)=>{
    $(el).find('h4').html(`سؤال ${i+1} <button type="button" class="removeQ">حذف</button>`);
    $(el).attr('data-i',i+1);
    $(el).find('input.correct').attr('name', `correct_${i+1}`);
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
    const question_id = Number($(this).find(".q_id").val() || 0);
    const q_image = $(this).find(".q_image_path").val() || '';
    const a = $(this).find(".a").val().trim() || "أ";
    const b = $(this).find(".b").val().trim() || "ب";
    const c = $(this).find(".c").val().trim() || "ج";
    const d = $(this).find(".d").val().trim() || "د";
    const correct = $(this).find("input.correct:checked").val();

    if(!q_image || !a || !b || !c || !d || !correct){
      isValid = false;
      alert(`يرجى ملء جميع الحقول ورفع صورة للسؤال رقم ${$(this).data('i')}`);
      return false;
    }
    questions.push({
      question_id: question_id,
      q_image: q_image,
      a: a,
      b: b,
      c: c,
      d: d,
      correct: correct
    });
  });

  if (!isValid) {
    e.preventDefault();
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
let existing = <?= json_encode($qs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
if(existing && existing.length>0){
  $('#questionsContainer').empty();
  existing.forEach((q,i)=>{
    const qData = {
      question_id: q.id,
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