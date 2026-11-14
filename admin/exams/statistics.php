<?php
require_once "../config.php";

function examStatsEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function examStatsInitials($firstName, $lastName): string
{
    $firstName = trim((string) $firstName);
    $lastName = trim((string) $lastName);

    if (function_exists('mb_substr')) {
        $first = $firstName !== '' ? mb_substr($firstName, 0, 1, 'UTF-8') : '';
        $last = $lastName !== '' ? mb_substr($lastName, 0, 1, 'UTF-8') : '';
    } else {
        $first = $firstName !== '' ? substr($firstName, 0, 1) : '';
        $last = $lastName !== '' ? substr($lastName, 0, 1) : '';
    }

    return ($first . $last) !== '' ? ($first . $last) : 'ط';
}

function examStatsCsvSafe($value): string
{
    $value = (string) $value;

    if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {
        return "'" . $value;
    }

    return $value;
}

function examStatsTimeAgo($datetime) {
    if (empty($datetime)) {
        return 'لا توجد محاولة';
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'الآن';
    } 
    elseif ($diff < 3600) {
        $mins = round($diff / 60);
        if ($mins == 1) return 'قبل دقيقة';
        if ($mins == 2) return 'قبل دقيقتين';
        if ($mins <= 10) return "قبل $mins دقائق";
        return "قبل $mins دقيقة";
    } 
    elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        if ($hours == 1) return 'قبل ساعة';
        if ($hours == 2) return 'قبل ساعتين';
        if ($hours <= 10) return "قبل $hours ساعات";
        return "قبل $hours ساعة";
    } 
    elseif ($diff < 604800) {
        $days = round($diff / 86400);
        if ($days == 1) return 'منذ يوم';
        if ($days == 2) return 'منذ يومين';
        if ($days <= 10) return "منذ $days أيام";
        return "منذ $days يوم";
    } 
    elseif ($diff < 2592000) {
        $weeks = round($diff / 604800);
        if ($weeks == 1) return 'منذ أسبوع';
        if ($weeks == 2) return 'منذ أسبوعين';
        return "منذ $weeks أسابيع";
    } 
    elseif ($diff < 31536000) {
        $months = round($diff / 2592000);
        if ($months == 1) return 'منذ شهر';
        if ($months == 2) return 'منذ شهرين';
        if ($months <= 10) return "منذ $months أشهر";
        return "منذ $months شهر";
    } 
    else {
        $years = round($diff / 31536000);
        if ($years == 1) return 'منذ سنة';
        if ($years == 2) return 'منذ سنتين';
        return "منذ $years سنوات";
    }
}

$rawExamId = $_GET['id'] ?? '';
$examId = filter_var($rawExamId, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$exam = null;
$summary = [
    'students_count' => 0,
    'total_attempts' => 0,
    'completed_attempts' => 0,
    'average_score' => null,
    'highest_score' => null,
];
$students = [];
$pageError = '';

if ($examId === false || $examId === null) {
    $pageError = 'رقم الاختبار غير صحيح. افتح الصفحة باستخدام id الاختبار في الرابط.';
} else {
    try {
        $examStmt = $db->prepare(
            'SELECT e.id, e.title, e.num_questions, e.created_at,
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS actual_questions
             FROM exams e
             WHERE e.id = :exam_id
             LIMIT 1'
        );
        $examStmt->execute([':exam_id' => $examId]);
        $exam = $examStmt->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            $pageError = 'الاختبار المطلوب غير موجود.';
        } else {
            $summaryStmt = $db->prepare(
                'SELECT COUNT(*) AS total_attempts,
                        COUNT(DISTINCT user_id) AS students_count,
                        SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_attempts,
                        AVG(CASE WHEN finished_at IS NOT NULL THEN CAST(score AS DECIMAL(10,2)) ELSE NULL END) AS average_score,
                        MAX(CASE WHEN finished_at IS NOT NULL THEN CAST(score AS DECIMAL(10,2)) ELSE NULL END) AS highest_score
                 FROM results
                 WHERE exam_id = :exam_id'
            );
            $summaryStmt->execute([':exam_id' => $examId]);
            $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $summary['students_count'] = (int) ($summaryRow['students_count'] ?? 0);
            $summary['total_attempts'] = (int) ($summaryRow['total_attempts'] ?? 0);
            $summary['completed_attempts'] = (int) ($summaryRow['completed_attempts'] ?? 0);
            $summary['average_score'] = $summaryRow['average_score'] !== null
                ? (float) $summaryRow['average_score']
                : null;
            $summary['highest_score'] = $summaryRow['highest_score'] !== null
                ? (float) $summaryRow['highest_score']
                : null;

            $studentsStmt = $db->prepare(
                'SELECT r.user_id,
                        a.fristname,
                        a.lastname,
                        a.email,
                        a.avatar,
                        COUNT(*) AS attempts_count,
                        SUM(CASE WHEN r.finished_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_count,
                        MAX(CASE WHEN r.finished_at IS NOT NULL THEN CAST(r.score AS DECIMAL(10,2)) ELSE NULL END) AS best_score,
                        MAX(r.started_at) AS last_attempt_at
                 FROM results r
                 LEFT JOIN accounts a ON a.id = r.user_id
                 WHERE r.exam_id = :exam_id
                 GROUP BY r.user_id, a.fristname, a.lastname, a.email, a.avatar
                 ORDER BY attempts_count DESC, best_score DESC, a.fristname ASC, a.lastname ASC'
            );
            $studentsStmt->execute([':exam_id' => $examId]);
            $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Exam statistics page database error: ' . $e->getMessage());
        $pageError = 'تعذر تحميل إحصائيات الاختبار بسبب مشكلة في قاعدة البيانات.';
    }
}

$examTitle = $exam ? (string) $exam['title'] : 'إحصائيات الاختبار';

if (($_GET['export'] ?? '') === 'csv' && $exam && $pageError === '') {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = rawurlencode($examTitle) . "-" . date("YmdHis") . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $output = fopen('php://output', 'w');

    if ($output === false) {
        http_response_code(500);
        exit('تعذر إنشاء ملف التصدير.');
    }

    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'اسم الطالب',
        'النتيجة',
        'تاريخ البدء',
        'الوقت المستغرق'
    ]);

    $attemptsStmt = $db->prepare(
        'SELECT
            r.score,
            r.started_at,
            r.finished_at,
            a.fristname,
            a.lastname
        FROM results r
        LEFT JOIN accounts a ON a.id = r.user_id
        WHERE r.exam_id = :exam_id
          AND r.finished_at IS NOT NULL
        ORDER BY r.finished_at DESC'
    );

    $attemptsStmt->execute([
        ':exam_id' => $examId
    ]);

    while ($attempt = $attemptsStmt->fetch(PDO::FETCH_ASSOC)) {

    $studentName = trim(
        (($attempt['fristname'] ?? '') . ' ' . ($attempt['lastname'] ?? ''))
    );

    if ($studentName === '') {
        $studentName = 'طالب بدون اسم';
    }

    $duration = '';

    if (!empty($attempt['started_at']) && !empty($attempt['finished_at'])) {

        $start = strtotime($attempt['started_at']);
        $finish = strtotime($attempt['finished_at']);

        $seconds = max(0, $finish - $start);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    fputcsv($output, [
        examStatsCsvSafe($studentName),
        ($attempt['score'] ?? '') . '%',
        examStatsCsvSafe($attempt['started_at'] ?? ''),
        $duration
    ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css"/>
    <title><?php echo examStatsEscape($examTitle); ?></title>
    <style>
        .exam-stats-page { max-width: 1200px; margin: 30px auto; padding: 0 20px 35px; }
        .page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; flex-wrap: wrap; margin-bottom: 22px; }
        .page-heading h1 { margin: 0 0 6px; font-size: 28px; }
        .page-subtitle { color: #718096; font-size: 14px; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; padding: 9px 14px; border-radius: 9px; background: #edf2ff; color: #4c51bf; text-decoration: none; font-weight: 700; }
        .back-link:hover { background: #e2e8ff; }
        .exam-info-card, .students-card { background: #fff; border: 1px solid #edf2f7; border-radius: 15px; padding: 22px; box-shadow: 0 4px 15px rgba(0,0,0,.05); margin-bottom: 22px; }
        .exam-info-card h2, .students-card h2 { color: #2d3748; font-size: 19px; margin: 0 0 17px; }
        .exam-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 13px; }
        .exam-info-item { padding: 13px 15px; background: #f7fafc; border-radius: 10px; min-width: 0; }
        .exam-info-label { display: block; color: #718096; font-size: 12px; margin-bottom: 4px; }
        .exam-info-value { display: block; color: #2d3748; font-weight: 700; overflow-wrap: anywhere; }
        .exam-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 22px; }
        .exam-stat-card { background: #fff; padding: 20px; border-radius: 14px; text-align: center; border: 1px solid #edf2f7; box-shadow: 0 4px 15px rgba(0,0,0,.05); }
        .exam-stat-icon { font-size: 25px; margin-bottom: 7px; }
        .exam-stat-number { color: #667eea; font-size: 34px; line-height: 1.25; font-weight: 800; margin-bottom: 4px; }
        .exam-stat-label { color: #718096; font-size: 14px; }
        .students-card-heading { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        .students-card-heading h2 { margin: 0; }
        .students-count-label { color: #718096; font-size: 13px; }
        .students-card-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .export-button { display: inline-flex; align-items: center; gap: 7px; padding: 9px 14px; border-radius: 9px; background: #e6fffa; color: #276749; text-decoration: none; font-weight: 700; transition: background .2s ease; }
        .export-button:hover { background: #b2f5ea; }
        .table-scroll { overflow-x: auto; }
        .students-table { width: 100%; min-width: 760px; border-collapse: collapse; }
        .students-table th, .students-table td { padding: 13px 12px; border-bottom: 1px solid #edf2f7; text-align: right; vertical-align: middle; }
        .students-table th { background: #f7fafc; color: #4a5568; font-weight: 700; white-space: nowrap; }
        .students-table td { color: #4a5568; }
        .students-table tbody tr:hover { background: #fbfcff; }
        .student-cell { display: flex; align-items: center; gap: 11px; min-width: 230px; }
        .student-avatar, .student-avatar-fallback { width: 46px; height: 46px; flex: 0 0 46px; border-radius: 50%; object-fit: cover; background: linear-gradient(135deg,#edf2ff,#e9d8fd); border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .student-avatar-fallback { display: flex; align-items: center; justify-content: center; color: #667eea; font-weight: 800; }
        .student-name { display: block; color: #2d3748; font-weight: 700; margin-bottom: 2px; }
        .student-email { display: block; color: #718096; font-size: 12px; direction: ltr; text-align: right; overflow-wrap: anywhere; }
        .number-pill { display: inline-block; min-width: 35px; padding: 4px 10px; text-align: center; border-radius: 20px; color: #434190; background: #ebf4ff; font-weight: 700; }
        .score-pill { display: inline-block; min-width: 60px; padding: 5px 10px; text-align: center; border-radius: 20px; color: #276749; background: #f0fff4; font-weight: 800; direction: ltr; }
        .muted { color: #a0aec0; }
        .table-note { margin-top: 13px; color: #718096; font-size: 12px; }
        .empty-state, .error-state { padding: 30px 18px; border-radius: 12px; text-align: center; }
        .empty-state { color: #718096; background: #f7fafc; }
        .error-state { color: #9b2c2c; background: #fff5f5; border: 1px solid #fed7d7; }
        @media (max-width: 600px) {
            .exam-stats-page { margin-top: 20px; padding: 0 12px 25px; }
            .page-heading h1 { font-size: 23px; }
            .exam-info-card, .students-card { padding: 16px; }
            .exam-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .exam-stat-card { padding: 15px 9px; }
            .exam-stat-number { font-size: 29px; }
        }
    </style>
</head>
<body>
<?php include "../header.php"; ?>

<main class="exam-stats-page">
    <div class="page-heading">
        <div>
            <h1><?php echo examStatsEscape($examTitle); ?></h1>
            <p class="page-subtitle">إحصائيات الطلاب ومحاولات هذا الاختبار</p>
        </div>
        <a class="back-link" href="../exams/">→ العودة إلى الاختبارات</a>
    </div>

    <?php if ($pageError !== ''): ?>
        <div class="error-state"><?php echo examStatsEscape($pageError); ?></div>
    <?php elseif ($exam): ?>
        <section class="exam-info-card">
            <h2>معلومات الاختبار</h2>
            <div class="exam-info-grid">
                <div class="exam-info-item">
                    <span class="exam-info-label">عدد الأسئلة</span>
                    <span class="exam-info-value"><?php echo (int) $exam['actual_questions']; ?></span>
                </div>
            </div>
        </section>

        <section class="exam-stat-grid" aria-label="ملخص إحصائيات الاختبار">
            <div class="exam-stat-card">
                <div class="exam-stat-icon">👨‍🎓</div>
                <div class="exam-stat-number"><?php echo number_format($summary['students_count']); ?></div>
                <div class="exam-stat-label">عدد الطلاب الذين اختبروا</div>
            </div>
            <div class="exam-stat-card">
                <div class="exam-stat-icon">📝</div>
                <div class="exam-stat-number"><?php echo number_format($summary['completed_attempts']); ?></div>
                <div class="exam-stat-label">إجمالي مرات الاختبار</div>
            </div>
            <div class="exam-stat-card">
                <div class="exam-stat-icon">📊</div>
                <div class="exam-stat-number">
                    <?php echo $summary['average_score'] !== null ? examStatsEscape(number_format($summary['average_score'], 1) . '%') : '—'; ?>
                </div>
                <div class="exam-stat-label">متوسط درجات المحاولات</div>
            </div>
        </section>

        <section class="students-card">
            <div class="students-card-heading">
                <h2>الطلاب المشاركون في الاختبار</h2>
                <div class="students-card-actions">
                    <span class="students-count-label"><?php echo number_format($summary['completed_attempts']); ?> محاولة</span>
                    <a class="export-button" href="?<?php echo examStatsEscape(http_build_query(['id' => $examId, 'export' => 'csv'])); ?>">
                        &#8659; تصدير بيانات الطلاب Exel
                    </a>
                </div>
            </div>

            <?php if (!empty($students)): ?>
                <div class="table-scroll">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>عدد مرات الاختبار</th>
                                <th>أعلى نتيجة</th>
                                <th>آخر محاولة</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php
                                $firstName = (string) ($student['fristname'] ?? '');
                                $lastName = (string) ($student['lastname'] ?? '');
                                $studentName = trim($firstName . ' ' . $lastName);
                                if ($studentName === '') {
                                    $studentName = 'طالب بدون اسم';
                                }
                                $avatar = trim((string) ($student['avatar'] ?? ''));
                                $email = trim((string) ($student['email'] ?? ''));
                                $bestScore = $student['best_score'] !== null ? (float) $student['best_score'] : null;
                            ?>
                            <tr>
                                <td>
                                    <div class="student-cell">
                                        <?php if ($avatar !== ''): ?>
                                            <img class="student-avatar"
                                                 src="<?php echo examStatsEscape($avatar); ?>"
                                                 alt="<?php echo examStatsEscape($studentName); ?>"
                                                 loading="lazy"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <span class="student-avatar-fallback" style="display:none;"><?php echo examStatsEscape(examStatsInitials($firstName, $lastName)); ?></span>
                                        <?php else: ?>
                                            <span class="student-avatar-fallback"><?php echo examStatsEscape(examStatsInitials($firstName, $lastName)); ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <span class="student-name"><?php echo examStatsEscape($studentName); ?></span>
                                            <span class="student-email"><?php echo $email !== '' ? examStatsEscape($email) : 'لا يوجد بريد إلكتروني'; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="number-pill"><?php echo (int) $student['completed_count']; ?></span></td>
                                <td>
                                    <?php if ($bestScore !== null): ?>
                                        <span class="score-pill"><?php echo examStatsEscape(number_format($bestScore, 0)); ?>%</span>
                                    <?php else: ?>
                                        <span class="muted">لا توجد نتيجة مكتملة</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo examStatsEscape(examStatsTimeAgo($student['last_attempt_at'] ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">لم يبدأ أي طالب هذا الاختبار حتى الآن.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
