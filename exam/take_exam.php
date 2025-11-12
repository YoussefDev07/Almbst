<?php
require_once "../master/connect.php";

$user_id = (isset($_COOKIE["id"])) ? $_COOKIE["id"]:null;

if (!$user_id) {
  header("Location:../account.php");
  exit();
}

$check_user_subscriptions = $connect -> query("SELECT id FROM subscriptions WHERE user_id = $user_id") -> fetchAll(PDO::FETCH_COLUMN);
    
if (empty($check_user_subscriptions[0])) {
  header("location:../account.php");
  exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);

$stmt = $connect->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) { 
    echo 'اختبار غير موجود'; 
    exit(); 
}

$qs = $connect->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY position ASC");
$qs->execute([$exam_id]);
$questions = $qs->fetchAll(PDO::FETCH_ASSOC);

if (count($questions) === 0) {
    echo 'لا توجد أسئلة في هذا الاختبار';
    exit();
}

$num = count($questions);
$allowed = $num*60 + 60;

// بدء سجل النتيجة
$insert = $connect->prepare("INSERT INTO results (user_id, exam_id, score, started_at, allowed_seconds) VALUES (?, ?, 0, NOW(), ?)");
$insert->execute([$user_id, $exam_id, $allowed]);
$result_id = $connect->lastInsertId();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name = "viewport" content = "width=device-width, initial-scale=1.0"/>
    <title>أداء الاختبار</title>
<style>
:root {
  --bg: #f8fafc;
  --card: #ffffff;
  --text: #0f172a;
  --muted: #475569;
  --primary: #2563eb;
  --primary-700: #1d4ed8;
  --accent: #14b8a6;
  --success: #22c55e;
  --danger: #ef4444;
  --border: #e2e8f0;
  --ring: #93c5fd;
  --shadow: 0 10px 20px rgba(2, 12, 27, 0.06);
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  margin: 0;
  background: linear-gradient(180deg, var(--bg), #eef2ff);
  color: var(--text);
  font-family: "Tajawal", "Cairo", "Noto Naskh Arabic", system-ui, sans-serif;
  line-height: 1.7;
  padding: 24px;
  direction: rtl;
}

/* الحاوية الأساسية */
.container {
  max-width: 900px;
  margin-inline: auto;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: var(--shadow);
  padding: 28px;
}

/* العنوان وعداد الوقت */
.container > h2 {
  margin: 0 0 8px 0;
  font-size: 28px;
  letter-spacing: 0.2px;
}

#globalTimer {
  display: inline-block;
  font-weight: 700;
  color: var(--primary);
  background: #eaf2ff;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  padding: 6px 10px;
  min-width: 70px;
  text-align: center;
}

.container > hr {
  border: none;
  height: 1px;
  background: var(--border);
  margin: 20px 0;
}

/* منطقة السؤال */
#questionArea h3 {
  margin-top: 0;
  font-size: 20px;
  color: var(--text);
}

#questionArea .options {
  display: grid;
  gap: 10px;
}

.exam-image {
  width: 100%;
  border-radius: 5px;
  margin-bottom: 25px;
}

/* خيار واحد */
#questionArea label {
  display: grid;
  grid-template-columns: 20px 1fr;
  align-items: start;
  gap: 12px;
  background: #f9fafb;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px 14px;
  transition: border-color .2s ease, background .2s ease, transform .06s ease;
  cursor: pointer;
}

#questionArea label:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

#questionArea input[type="radio"] {
  accent-color: var(--primary);
  margin-top: 2px;
}

/* حالة الخيار المحدد */
#questionArea input[type="radio"]:checked + span {
  font-weight: 600;
  color: var(--text);
}

/* أزرار التحكم */
#prev, #next, #submitBtn {
  appearance: none;
  border: 1px solid var(--border);
  background: #fff;
  color: var(--text);
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: transform .06s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease, color .2s ease;
  margin-inline-end: 8px;
  box-shadow: 0 2px 6px rgba(2, 12, 27, 0.06);
}

#prev:hover, #next:hover {
  border-color: var(--primary);
  background: #f8fafc;
  box-shadow: 0 6px 14px rgba(2, 12, 27, 0.08);
  transform: translateY(-1px);
}

#next {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}
#next:hover {
  background: var(--primary-700);
  border-color: var(--primary-700);
}

#submitBtn {
  color: #fff !important;
  background: var(--danger);
  border-color: var(--danger);
}
#submitBtn:hover {
  filter: brightness(0.95);
}

/* حالة التعطيل */
button:disabled {
  opacity: .6;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* شريط التنقل بين الأسئلة */
#questionNav {
  margin-top: 18px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

#questionNav .qnav {
  min-width: 42px;
  height: 42px;
  padding: 0 10px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: #fff;
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: background .2s ease, border-color .2s ease, transform .06s ease, box-shadow .2s ease;
}

#questionNav .qnav:hover {
  border-color: var(--primary);
  background: #f8fafc;
  box-shadow: 0 6px 14px rgba(2, 12, 27, 0.08);
  transform: translateY(-1px);
}

/* السؤال الحالي */
#questionNav .qnav.current {
  border-color: var(--primary);
  background: #eef2ff;
  color: var(--primary);
}

/* الأسئلة المُجاب عنها (يتم تلوينها أخضر) */
#questionNav .qnav.answered {
  background: #ecfdf5;
  border-color: #d1fae5;
  color: var(--success);
}

/* تحسينات وصول وتركيز */
button:focus-visible, .qnav:focus-visible, label:focus-within {
  outline: 3px solid var(--ring);
  outline-offset: 2px;
}

/* تباعد مجموعة الأزرار */
.container > div:last-of-type {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

#questionArea label br { display: none; }

@media only screen and (max-width: 768px) {
  .container {
    padding: 20px;
  }
  
  .container > h2 {
    font-size: 24px;
  }
  
  #questionArea h3 {
    font-size: 20px;
  }
  
  #questionNav {
    gap: 8px;
  }
  
  #questionNav .qnav {
    min-width: 40px;
    height: 40px;
    border-radius: 10px;
  }
  
  #prev, #next, #submitBtn {
    padding: 8px 16px;
    font-size: 16px;
  }
}
</style>
    <script src="../assets/libs/js/jquery.js"></script>
</head>
<body>
<div class="container">
    <h2><?= htmlspecialchars($exam['title']) ?></h2>
    <div>وقت الإختبار: <span id="globalTimer"></span></div>
    <hr>
    <div id="questionArea"></div>
    <hr>
    <div>
        <button id="prev">السابق</button>
        <button id="next">التالي</button>
        <button id="submitBtn" style="color: red; border-color: red;">إنهاء الاختبار</button>
    </div>
    <div id="questionNav"></div>
</div>

<script>
const questions = <?= json_encode($questions) ?>;
const resultId = <?= json_encode(intval($result_id)) ?>;
let globalRemaining = <?= json_encode($allowed) ?>;

let current = 0;
let num = questions.length;
let answers = {}; 
let qStart = Date.now();
let timerInterval = null; 
let isSubmitting = false; 

function renderQuestion(i){
  const q = questions[i];
  let html = `<h3>س ${i+1} من ${num}</h3><br>`;
  html += `<img class="exam-image" src="${escapeHtml(q.q_image)}">`;
  html += `<div>
    <label><input type="radio" name="choice" value="A">${escapeHtml(q.choice_a||'')}</label><br>
    <label><input type="radio" name="choice" value="B">${escapeHtml(q.choice_b||'')}</label><br>
    <label><input type="radio" name="choice" value="C">${escapeHtml(q.choice_c||'')}</label><br>
    <label><input type="radio" name="choice" value="D">${escapeHtml(q.choice_d||'')}</label><br>
  </div>`;
  
  $('#questionArea').html(html);

  if (answers[q.id] && answers[q.id].selected) {
    $(`input[name=choice][value="${answers[q.id].selected}"]`).prop('checked', true);
  }

  buildNav();
  qStart = Date.now();

  $('#prev').prop('disabled', i === 0);
  $('#next').prop('disabled', i === num - 1);
}

function buildNav(){
  $('#questionNav').empty();
  for(let i=0; i < num; i++){
    const btn = $(`<button class="qnav" data-i="${i}">${i+1}</button>`);
    if (i === current) btn.css('font-weight','bold');
    if (answers[questions[i].id] && answers[questions[i].id].selected) {
        btn.css('background-color', '#d4edda');
    }
    $('#questionNav').append(btn);

    // style
    if (i === current) btn.addClass('current');
    if (answers[questions[i].id] && answers[questions[i].id].selected) {
      btn.addClass('answered');
    }
  }
}

$('#questionNav').on('click','.qnav', function(){ 
    saveAndGoto(parseInt($(this).data('i'))); 
});
$('#prev').click(()=> saveAndGoto(Math.max(0, current - 1)));
$('#next').click(()=> saveAndGoto(Math.min(num - 1, current + 1)));

function saveAndGoto(next_index){
  if (next_index === current) return;

  const q = questions[current];
  const chosen = $('input[name=choice]:checked').val() || null;
  const elapsed = Math.round((Date.now() - qStart) / 1000);

  answers[q.id] = answers[q.id] || {selected: null, timeUsed: 0};
  answers[q.id].selected = chosen;
  answers[q.id].timeUsed += elapsed;
  
  $.post('../hooks/save_answer.php', { 
    result_id: resultId, 
    question_id: q.id, 
    selected_choice: chosen, 
    time_taken_seconds: elapsed 
  });
  
  current = next_index;
  renderQuestion(current);
}

$('#submitBtn').click(()=> finishTest(false));

function finishTest(auto = false){
  if (isSubmitting) return; 
  isSubmitting = true;
  clearInterval(timerInterval);
  $('#submitBtn').prop('disabled', true).text('جاري الإنهاء...');
  $('.qnav, #prev, #next').prop('disabled', true);

  const q = questions[current];
  const chosen = $('input[name=choice]:checked').val() || null;
  const elapsed = Math.round((Date.now() - qStart) / 1000);
  answers[q.id] = answers[q.id] || {selected: null, timeUsed: 0};
  answers[q.id].selected = chosen;
  answers[q.id].timeUsed += elapsed;

  $.post('../hooks/save_answer.php', { 
    result_id: resultId, 
    question_id: q.id, 
    selected_choice: chosen, 
    time_taken_seconds: elapsed 
  })
  .always(function() {
    $.post('./submit_exam.php', { result_id: resultId }, function(r){
      if (r.success) {
        window.location = 'view_result.php?id=' + r.result_id;
      } else {
        alert(r.message || 'حدث خطأ أثناء إنهاء الاختبار.');
        isSubmitting = false; 
        $('#submitBtn').prop('disabled', false).text('إنهاء الاختبار');
      }
    }, 'json')
    .fail(function() {
        alert('خطأ في الاتصال بالخادم عند محاولة الإنهاء.');
        isSubmitting = false;
        $('#submitBtn').prop('disabled', false).text('إنهاء الاختبار');
    });
  });
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = (seconds % 60).toString().padStart(2, '0');
    return `${mins}:${secs}`;
}

function updateTimer() {
    if (globalRemaining <= 0) { 
        if (!isSubmitting) { 
            alert("انتهى الوقت!");
            finishTest(true); 
        }
        return; 
    }
    
    globalRemaining--;
    $('#globalTimer').text(formatTime(globalRemaining));
}

function escapeHtml(s){ 
  if(!s) return ''; 
  return $('<div>').text(s).html(); 
}

$(function(){ 
    renderQuestion(0); 
    timerInterval = setInterval(updateTimer, 1000);
});
</script>
</body>
</html>