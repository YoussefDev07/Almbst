<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/top_students_helpers.php';

if (empty($_SESSION['top_students_csrf'])) {
    $_SESSION['top_students_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['top_students_csrf'];
$message = '';
$error = '';
$newUploadedFile = null;

$statusMessages = [
    'added' => 'تمت إضافة الطالب إلى قائمة المتفوقين بنجاح.',
    'updated' => 'تم تعديل بيانات الطالب بنجاح.',
    'deleted' => 'تم حذف الطالب من قائمة المتفوقين.',
];
if (isset($_GET['status']) && isset($statusMessages[$_GET['status']])) {
    $message = $statusMessages[$_GET['status']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $postedToken = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals($csrfToken, $postedToken)) {
            throw new RuntimeException('انتهت صلاحية الطلب. حدّث الصفحة ثم حاول مرة أخرى.');
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'add' || $action === 'edit') {
            $studentName = trim((string)($_POST['student_name'] ?? ''));
            $score = trim((string)($_POST['score'] ?? ''));
            if ($studentName === '' || $score === '') {
                throw new RuntimeException('يرجى إدخال اسم الطالب والدرجة.');
            }
            if (function_exists('mb_strlen')) {
                if (mb_strlen($studentName, 'UTF-8') > 180 || mb_strlen($score, 'UTF-8') > 100) {
                    throw new RuntimeException('اسم الطالب أو الدرجة أطول من المسموح.');
                }
            } elseif (strlen($studentName) > 720 || strlen($score) > 400) {
                throw new RuntimeException('اسم الطالب أو الدرجة أطول من المسموح.');
            }

            if ($action === 'add') {
                $newUploadedFile = top_students_upload_image($_FILES['grade_image'] ?? [], true);
                $stmt = $db->prepare('INSERT INTO top_students (student_name, score, image_filename) VALUES (:name, :score, :image)');
                $stmt->execute([':name' => $studentName, ':score' => $score, ':image' => $newUploadedFile]);
                header('Location: top_students.php?status=added');
                exit;
            }

            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                throw new RuntimeException('رقم الطالب غير صحيح.');
            }
            $find = $db->prepare('SELECT * FROM top_students WHERE id = ?');
            $find->execute([$id]);
            $currentStudent = $find->fetch(PDO::FETCH_ASSOC);
            if (!$currentStudent) {
                throw new RuntimeException('الطالب المطلوب غير موجود.');
            }

            $imageFilename = $currentStudent['image_filename'];
            if (isset($_FILES['grade_image']) && ($_FILES['grade_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
               $newUploadedFile = top_students_upload_image($_FILES['grade_image'], true);
                if ($newUploadedFile !== null) {
                   $imageFilename = $newUploadedFile;
                }
            }

            $stmt = $db->prepare('UPDATE top_students SET student_name = :name, score = :score, image_filename = :image WHERE id = :id');
            $stmt->execute([':name' => $studentName, ':score' => $score, ':image' => $imageFilename, ':id' => $id]);
            if ($newUploadedFile !== null && !empty($currentStudent['image_filename'])) {
                top_students_delete_image($currentStudent['image_filename']);
            }
            header('Location: top_students.php?status=updated');
            exit;
        }

        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                throw new RuntimeException('رقم الطالب غير صحيح.');
            }
            $find = $db->prepare('SELECT image_filename FROM top_students WHERE id = ?');
            $find->execute([$id]);
            $student = $find->fetch(PDO::FETCH_ASSOC);
            if (!$student) {
                throw new RuntimeException('الطالب المطلوب غير موجود أو تم حذفه مسبقًا.');
            }
            $stmt = $db->prepare('DELETE FROM top_students WHERE id = ?');
            $stmt->execute([$id]);
            top_students_delete_image($student['image_filename']);
            header('Location: top_students.php?status=deleted');
            exit;
        }

        throw new RuntimeException('العملية المطلوبة غير معروفة.');
    } catch (Throwable $e) {
        if ($newUploadedFile !== null) {
            top_students_delete_image($newUploadedFile);
        }
        if ($e instanceof RuntimeException) {
            $error = $e->getMessage();
        } else {
            error_log('Top students admin error: ' . $e->getMessage());
            $error = 'حدث خطأ أثناء حفظ البيانات. تحقق من إعدادات قاعدة البيانات ومجلد الصور ثم حاول مرة أخرى.';
        }
    }
}

$editStudent = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $db->prepare('SELECT * FROM top_students WHERE id = ?');
        $stmt->execute([$editId]);
        $editStudent = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
$students = $db->query('SELECT id, student_name, score, image_filename, created_at FROM top_students ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'إدارة المتفوقين';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= top_students_escape($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .student-thumb { width: 68px; height: 68px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
        .upload-help { display:block; margin-top:7px; color:#718096; font-size:13px; }
        .inline-form { display:inline; margin:0; }
        .score-text { font-weight:700; color:#5b4bc4; }
        .table-wrap { overflow-x:auto; }
        .form-card .current-image { display:flex; align-items:center; gap:12px; margin:0 0 18px; }
        @media(max-width:768px){ .form-card,.table-card{padding:18px;} h1{font-size:26px;} }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<div class="container">
    <h1><?= top_students_escape($pageTitle) ?></h1>
    <p style="margin:-18px 0 24px;color:#718096;">أضف اسم الطالب والدرجة يدويًا وارفع صورة النتيجة لتظهر في صفحة المتفوقين.</p>

    <?php if ($message !== ''): ?><div class="alert alert-success" role="status"><?= top_students_escape($message) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= top_students_escape($error) ?></div><?php endif; ?>

    <section class="form-card">
        <h2><?= $editStudent ? 'تعديل بيانات المتفوق' : 'إضافة متفوق جديد' ?></h2>
        <form method="post" enctype="multipart/form-data" action="top_students.php<?= $editStudent ? '?edit=' . (int)$editStudent['id'] : '' ?>" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= top_students_escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editStudent ? 'edit' : 'add' ?>">
            <?php if ($editStudent): ?><input type="hidden" name="id" value="<?= (int)$editStudent['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="student_name">اسم الطالب</label>
                    <input type="text" id="student_name" name="student_name" maxlength="180" required value="<?= top_students_escape($editStudent['student_name'] ?? '') ?>" placeholder="اكتب اسم الطالب كاملًا">
                </div>
                <div class="form-group">
                    <label for="score">الدرجة</label>
                    <input type="text" id="score" name="score" maxlength="100" required value="<?= top_students_escape($editStudent['score'] ?? '') ?>" placeholder="مثال: 99.5% أو 98 من 100">
                    <small>تُكتب الدرجة يدويًا ويمكن إدخالها كنسبة مئوية أو بصيغة نصية.</small>
                </div>
            </div>

            <?php if ($editStudent): ?>
                <div class="current-image">
                    <img class="student-thumb" src="../uploads/top_students/<?= rawurlencode($editStudent['image_filename']) ?>" alt="صورة الدرجة الحالية">
                    <span>الصورة الحالية — اختر ملفًا جديدًا فقط إذا أردت استبدالها.</span>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="grade_image">صورة الدرجة (اختياري)</label>
                <input type="file" id="grade_image" name="grade_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="display:block;width:100%;padding:12px;background:#fff;border:2px solid #e2e8f0;border-radius:8px;">
                <small class="upload-help">الصيغ المسموحة: JPG وPNG وWEBP، والحد الأقصى 3 ميجابايت.</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editStudent ? 'حفظ التعديلات' : 'إضافة المتفوق' ?></button>
                <?php if ($editStudent): ?><a href="top_students.php" class="btn btn-secondary">إلغاء التعديل</a><?php endif; ?>
                <a href="../top_students.php" target="_blank" rel="noopener" class="btn btn-secondary">عرض صفحة المتفوقين</a>
            </div>
        </form>
    </section>

    <section class="table-card">
        <h2>قائمة المتفوقين (<?= count($students) ?>)</h2>
        <?php if ($students): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>م</th><th>اسم الطالب</th><th>الدرجة</th><th>صورة الدرجة</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= top_students_escape($student['student_name']) ?></td>
                            <td class="score-text"><?= top_students_escape($student['score']) ?></td>
                            <td>
                              <?php if (!empty($student['image_filename'])): ?>
                                <a href="../uploads/top_students/<?= rawurlencode($student['image_filename']) ?>" target="_blank" rel="noopener"><img class="student-thumb" src="../uploads/top_students/<?= rawurlencode($student['image_filename']) ?>" alt="صورة درجة <?= top_students_escape($student['student_name']) ?>" loading="lazy"></a>
                              <?php else: ?>
                                <span style="color:#718096; font-size:13px;">لا توجد صورة</span>
                              <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a class="btn-edit" href="top_students.php?edit=<?= (int)$student['id'] ?>">✏️ تعديل</a>
                                <form method="post" action="top_students.php" class="inline-form" onsubmit="return confirm('هل تريد حذف هذا الطالب وصورة درجته نهائيًا؟');">
                                    <input type="hidden" name="csrf_token" value="<?= top_students_escape($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">
                                    <button type="submit" class="btn-delete">🗑️ حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data">لا يوجد متفوقون حتى الآن. أضف أول طالب من النموذج أعلاه.</p>
        <?php endif; ?>
    </section>
</div>
</body>
</html>