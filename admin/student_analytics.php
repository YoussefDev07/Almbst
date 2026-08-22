<?php
require_once 'config.php';

$page_title = 'إحصائيات الطلاب والاختبارات';
$error = '';
$selected_user_id = isset($_GET['user_id']) ? trim((string) $_GET['user_id']) : '';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function initials(string $firstName, string $lastName): string
{
    $first = trim($firstName) !== '' ? mb_substr(trim($firstName), 0, 1, 'UTF-8') : '';
    $last = trim($lastName) !== '' ? mb_substr(trim($lastName), 0, 1, 'UTF-8') : '';
    return $first . $last ?: 'ط';
}

function scorePercent($score, $numQuestions): ?float
{
    if ($score === null || $score === '') {
        return null;
    }

    $raw = trim((string) $score);

    if (preg_match('/^(\d+(?:\.\d+)?)\s*[/\\]\s*(\d+(?:\.\d+)?)$/u', $raw, $m)) {
        $denominator = (float) $m[2];
        return $denominator > 0 ? max(0, min(100, ((float) $m[1] / $denominator) * 100)) : null;
    }

    if (preg_match('/^(\d+(?:\.\d+)?)\s*من\s*(\d+(?:\.\d+)?)$/u', $raw, $m)) {
        $denominator = (float) $m[2];
        return $denominator > 0 ? max(0, min(100, ((float) $m[1] / $denominator) * 100)) : null;
    }

    if (is_numeric($raw)) {
        $value = (float) $raw;
        if ($value < 0) {
            return null;
        }
        if ($value <= 100) {
            return $value;
        }
    }

    return null;
}

try {
    $students = $db->query("SELECT id, fristname, lastname, email, avatar FROM accounts ORDER BY fristname ASC, lastname ASC, email ASC")->fetchAll(PDO::FETCH_ASSOC);

    $selected_student = null;
    foreach ($students as $student) {
        if ($selected_user_id !== '' && (string) $student['id'] === $selected_user_id) {
            $selected_student = $student;
            break;
        }
    }

    $summary = [
        'attempts' => 0,
        'unique_exams' => 0,
        'average' => null,
        'best' => null,
        'finished' => 0,
    ];
    $exam_rows = [];

    if ($selected_student) {
        $stmt = $db->prepare("SELECT r.id, r.exam_id, r.score, r.started_at, r.finished_at, r.total_time_seconds, r.allowed_seconds, e.title AS exam_title, e.num_questions FROM results r INNER JOIN exams e ON e.id = r.exam_id WHERE r.user_id = ? ORDER BY COALESCE(r.finished_at, r.started_at) DESC, r.id DESC");
        $stmt->execute([$selected_student['id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary['attempts'] = count($results);
        $exam_ids = [];
        $percentages = [];

        foreach ($results as $result) {
            $percentage = scorePercent($result['score'], (int) $result['num_questions']);
            if ($percentage !== null) {
                $percentages[] = $percentage;
            }
            $exam_ids[(int) $result['exam_id']] = true;
            if (!empty($result['finished_at'])) {
                $summary['finished']++;
            }

            $minutes = floor(((int) $result['total_time_seconds']) / 60);
            $seconds = ((int) $result['total_time_seconds']) % 60;

            $exam_rows[] = [
                'id' => (int) $result['id'],
                'exam_id' => (int) $result['exam_id'],
                'exam_title' => $result['exam_title'],
                'num_questions' => (int) $result['num_questions'],
                'score' => $result['score'],
                'percentage' => $percentage,
                'finished_at' => $result['finished_at'],
                'time' => sprintf('%d:%02d', $minutes, $seconds),
            ];
        }

        $summary['unique_exams'] = count($exam_ids);
        if ($percentages) {
            $summary['average'] = array_sum($percentages) / count($percentages);
            $summary['best'] = max($percentages);
        }
    }
} catch (PDOException $e) {
    $students = [];
    $selected_student = null;
    $exam_rows = [];
    $error = 'تعذر قراءة بيانات الطلاب أو نتائج الاختبارات. تأكد من إعداد قاعدة البيانات بشكل صحيح.';
}

$full_name = $selected_student
    ? trim(($selected_student['fristname'] ?? '') . ' ' . ($selected_student['lastname'] ?? ''))
    : '';
$full_name = $full_name !== '' ? $full_name : 'طالب بدون اسم';
$avatar = $selected_student ? trim((string) ($selected_student['avatar'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
     .student-select-card{margin-bottom:24px}.student-picker{position:relative}.student-picker-button{width:100%;min-height:68px;padding:10px 14px;border:2px solid #e2e8f0;border-radius:12px;background:#fff;color:#2d3748;display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;text-align:right}.student-picker-button.open{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.08)}.student-picker-current{display:flex;align-items:center;gap:12px;min-width:0}.student-picker-avatar,.student-option-avatar{width:44px;height:44px;border-radius:50%;object-fit:cover;flex:0 0 44px;background:linear-gradient(135deg,#edf2ff,#e9d8fd);border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.08)}.student-picker-avatar-fallback,.student-option-avatar-fallback{display:flex;align-items:center;justify-content:center;color:#667eea;font-weight:700}.student-picker-text,.student-option-content{min-width:0}.student-picker-name,.student-option-name{display:block;font-weight:700;color:#2d3748;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.student-picker-email,.student-option-email{display:block;margin-top:3px;color:#718096;font-size:12px;direction:ltr;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.student-picker-arrow{font-size:18px;color:#718096;transition:.2s}.student-picker-button.open .student-picker-arrow{transform:rotate(180deg)}.student-picker-menu{position:absolute;top:calc(100% + 8px);right:0;left:0;z-index:50;display:none;max-height:340px;overflow-y:auto;padding:8px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 35px rgba(15,23,42,.15)}.student-picker-menu.open{display:block}.student-option{width:100%;border:0;background:transparent;display:flex;align-items:center;gap:12px;padding:10px;border-radius:9px;cursor:pointer;text-align:right}.student-option:hover,.student-option.selected{background:#f7fafc}.student-option-content{flex:1}.student-option-check{color:#667eea;font-weight:800;opacity:0}.student-option.selected .student-option-check{opacity:1}.student-profile{display:flex;align-items:center;gap:18px;margin-bottom:25px}.student-avatar-large{width:86px;height:86px;border-radius:50%;object-fit:cover;flex:0 0 86px;background:linear-gradient(135deg,#edf2ff,#e9d8fd);border:3px solid #fff;box-shadow:0 4px 15px rgba(0,0,0,.10)}.student-avatar-fallback{display:flex;align-items:center;justify-content:center;color:#667eea;font-size:28px;font-weight:700}.student-profile h2{margin:0 0 4px;color:#2d3748}.student-email{color:#718096;direction:ltr;text-align:right;word-break:break-word}.student-id{margin-top:4px;color:#a0aec0;font-size:13px;direction:ltr;text-align:right}.stats-grid.student-stats{grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:30px}.student-stats .stat-card{padding:22px 18px}.student-stats .stat-number{font-size:34px;margin-bottom:4px}.student-stats .stat-label{margin-bottom:0}.stat-subtitle{margin-top:6px;color:#a0aec0;font-size:12px}.charts-grid{display:grid;grid-template-columns:1fr;gap:22px;margin-bottom:30px}.chart-card{background:#fff;border-radius:15px;padding:20px;box-shadow:0 4px 15px rgba(15,23,42,.06);border:1px solid #edf2f7}.chart-card h3{margin:0 0 16px;color:#2d3748}.chart-wrap{position:relative;height:300px}.table-wrapper{overflow-x:auto}.results-table{width:100%;border-collapse:collapse;min-width:900px}.results-table th,.results-table td{padding:13px 12px;border-bottom:1px solid #edf2f7;text-align:right;vertical-align:middle}.results-table th{background:#f7fafc;color:#4a5568;font-weight:700}.results-table td{color:#4a5568}.score-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-weight:700;background:#ebf4ff;color:#434190;direction:ltr}.exam-link{color:#4c51bf;text-decoration:none;font-weight:700}.exam-link:hover{text-decoration:underline}.muted{color:#a0aec0}.empty-state{padding:35px 20px;text-align:center;color:#718096}.hint{color:#718096;font-size:13px;margin-top:8px}.welcome-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:24px;border-radius:15px;margin-bottom:25px;box-shadow:0 4px 15px rgba(102,126,234,.20)}.welcome-card h2{margin:0 0 7px;font-size:22px}.welcome-card p{margin:0;opacity:.9}@media(max-width:600px){.student-profile{align-items:flex-start}.student-avatar-large{width:68px;height:68px;flex-basis:68px}.student-profile h2{font-size:20px}}
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h1><?php echo e($page_title); ?></h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="form-card student-select-card">
        <h2>اختيار الطالب</h2>
        <div class="form-group" style="margin-bottom:0;">
            <label for="studentPickerButton">الطلاب</label>
            <div class="student-picker" id="studentPicker">
                <button type="button" class="student-picker-button" id="studentPickerButton" aria-expanded="false" aria-controls="studentPickerMenu">
                    <span class="student-picker-current">
                        <?php if ($selected_student): ?>
                            <?php if ($avatar !== ''): ?>
                                <img class="student-picker-avatar" src="<?php echo e($avatar); ?>" alt="<?php echo e($full_name); ?>">
                            <?php else: ?>
                                <span class="student-picker-avatar student-picker-avatar-fallback"><?php echo e(initials($selected_student['fristname'] ?? '', $selected_student['lastname'] ?? '')); ?></span>
                            <?php endif; ?>
                            <span class="student-picker-text">
                                <span class="student-picker-name"><?php echo e($full_name); ?></span>
                                <span class="student-picker-email"><?php echo e($selected_student['email'] ?? ''); ?></span>
                            </span>
                        <?php else: ?>
                            <span class="student-picker-text"><span class="student-picker-name">اختر طالبًا</span><span class="student-picker-email">لعرض بياناته وإحصائياته</span></span>
                        <?php endif; ?>
                    </span>
                    <span class="student-picker-arrow">⌄</span>
                </button>
                <div class="student-picker-menu" id="studentPickerMenu" role="menu">
                    <button type="button" class="student-option" data-user-id="" role="menuitem"><span class="student-option-content"><span class="student-option-name">اختر طالبًا</span><span class="student-option-email">عرض صفحة الاختيار</span></span><span class="student-option-check">✓</span></button>
                    <?php foreach ($students as $student): ?>
                        <?php $name=trim(($student['fristname']??'').' '.($student['lastname']??'')); $name=$name!==''?$name:'طالب بدون اسم'; $studentAvatar=trim((string)($student['avatar']??'')); $studentSelected=$selected_student && (string)$selected_student['id']===(string)$student['id']; ?>
                        <button type="button" class="student-option<?php echo $studentSelected ? ' selected' : ''; ?>" data-user-id="<?php echo e($student['id']); ?>" role="menuitem">
                            <?php if ($studentAvatar !== ''): ?><img class="student-option-avatar" src="<?php echo e($studentAvatar); ?>" alt="<?php echo e($name); ?>"><?php else: ?><span class="student-option-avatar student-option-avatar-fallback"><?php echo e(initials($student['fristname']??'', $student['lastname']??'')); ?></span><?php endif; ?>
                            <span class="student-option-content"><span class="student-option-name"><?php echo e($name); ?></span><span class="student-option-email"><?php echo e($student['email']??''); ?></span></span><span class="student-option-check">✓</span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="hint">اضغط على القائمة لاختيار الطالب، وستظهر صورته واسمه مباشرة بدل قائمة select التقليدية.</div>
        </div>
    </div>

    <?php if ($selected_student): ?>
        <div class="table-card">
            <div class="student-profile">
                <?php if ($avatar !== ''): ?>
                    <img class="student-avatar-large" src="<?php echo e($avatar); ?>" alt="<?php echo e($full_name); ?>">
                <?php else: ?>
                    <div class="student-avatar-large student-avatar-fallback">
                        <?php echo e(initials($selected_student['fristname'] ?? '', $selected_student['lastname'] ?? '')); ?>
                    </div>
                <?php endif; ?>

                <div>
                    <h2><?php echo e($full_name); ?></h2>
                    <div class="student-email"><?php echo e($selected_student['email'] ?? ''); ?></div>
                    <div class="student-id">ID: <?php echo e($selected_student['id']); ?></div>
                </div>
            </div>

            <div class="stats-grid student-stats">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-number"><?php echo (int) $summary['attempts']; ?></div>
                    <div class="stat-label">إجمالي المحاولات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-number"><?php echo (int) $summary['unique_exams']; ?></div>
                    <div class="stat-label">اختبارات مختلفة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number">
                        <?php echo $summary['average'] !== null ? number_format($summary['average'], 1) . '%' : '—'; ?>
                    </div>
                    <div class="stat-label">متوسط النتائج</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-number">
                        <?php echo $summary['best'] !== null ? number_format($summary['best'], 1) . '%' : '—'; ?>
                    </div>
                    <div class="stat-label">أفضل نتيجة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-number"><?php echo (int) $summary['finished']; ?></div>
                    <div class="stat-label">اختبارات مكتملة</div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>نتائج الاختبارات</h3>
                <div class="chart-wrap">
                    <canvas id="scoresChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-card">
            <h2>تفاصيل الاختبارات والنتائج</h2>
            <?php if ($exam_rows): ?>
                <div class="table-wrapper">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>الاختبار</th>
                                <th>عدد الأسئلة</th>
                                <th>النتيجة المسجلة</th>
                                <th>النسبة</th>
                                <th>الوقت المستغرق</th>
                                <th>تاريخ الإنهاء</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($exam_rows as $row): ?>
                            <tr>
                                <td>
                                    <a class="exam-link" href="../exam/view_result.php?id=<?php echo (int) $row['id']; ?>" target="_blank">
                                        <?php echo e($row['exam_title']); ?>
                                    </a>
                                </td>
                                <td><?php echo (int) $row['num_questions']; ?></td>
                                <td><?php echo e($row['score']); ?></td>
                                <td>
                                    <?php if ($row['percentage'] !== null): ?>
                                        <span class="score-pill">
                                            <?php echo number_format($row['percentage'], 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="muted">غير قابلة للتحويل</span>
                                    <?php endif; ?>
                                </td>
                                <td style="direction:ltr;text-align:right;"><?php echo e($row['time']); ?></td>
                                <td>
                                    <?php if ($row['finished_at']): ?>
                                        <?php echo e(date('Y-m-d H:i', strtotime($row['finished_at']))); ?>
                                    <?php else: ?>
                                        <span class="muted">غير مكتمل</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">لا توجد نتائج أو محاولات اختبار مسجلة لهذا الطالب حتى الآن.</div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="welcome-card">
            <h2>اختر طالبًا لعرض الإحصائيات</h2>
            <p>الصفحة تعرض الصورة والاسم والبريد الإلكتروني، ثم ملخصًا لنتائج الاختبارات وتفاصيل كل محاولة.</p>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    const picker = document.getElementById('studentPicker');
    const button = document.getElementById('studentPickerButton');
    const menu = document.getElementById('studentPickerMenu');
    
    if (picker && button && menu) {
        const closePicker = () => {
            button.classList.remove('open');
            menu.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
        };
        button.addEventListener('click', function () {
            const isOpen = menu.classList.toggle('open');
            button.classList.toggle('open', isOpen);
            button.setAttribute('aria-expanded', String(isOpen));
        });
        menu.querySelectorAll('.student-option').forEach(function (option) {
            option.addEventListener('click', function () {
                const userId = option.getAttribute('data-user-id') || '';
                closePicker();
                window.location.href = '?user_id=' + encodeURIComponent(userId);
            });
        });
        document.addEventListener('click', function (event) {
            if (!picker.contains(event.target)) closePicker();
        });
    }

    const examRows = <?php echo json_encode($exam_rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    
    if (typeof Chart !== 'undefined' && examRows.length) {
        const validRows = examRows.filter(row => row.percentage !== null).reverse();
        const scoresCanvas = document.getElementById('scoresChart');
        
        if (scoresCanvas && validRows.length) {
            new Chart(scoresCanvas, {
                type: 'bar',
                data: {
                    labels: validRows.map(row => row.exam_title),
                    datasets: [{
                        label: 'النسبة %',
                        data: validRows.map(row => Number(row.percentage)),
                        backgroundColor: '#667eea',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            max: 100, 
                            ticks: { callback: value => value + '%' } 
                        } 
                    },
                    plugins: { 
                        legend: { display: false } 
                    }
                }
            });
        }
    }
})();
</script>
</body>
</html>