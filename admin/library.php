<?php
declare(strict_types=1);

require_once 'config.php';

$page_title = "إدارة المكتبة";

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR;
$maxSize = 10 * 1024 * 1024;
$message = "";
$error = "";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $error = "تعذر إنشاء مجلد المكتبة.";
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function humanFileSize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' بايت';
    }

    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1) . ' كيلوبايت';
    }

    return number_format($bytes / (1024 * 1024), 2) . ' ميجابايت';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_file'])) {
        $deleteName = basename((string)$_POST['delete_file']);
        $deletePath = $uploadDir . $deleteName;

        if (
            strtolower(pathinfo($deleteName, PATHINFO_EXTENSION)) === 'pdf' &&
            is_file($deletePath) &&
            is_writable($deletePath)
        ) {
            if (unlink($deletePath)) {
                $message = "تم حذف الملف بنجاح.";
            } else {
                $error = "تعذر حذف الملف.";
            }
        } else {
            $error = "الملف المطلوب حذفه غير صالح.";
        }
    }

    elseif (isset($_FILES['pdf_file'])) {
        $file = $_FILES['pdf_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "حدث خطأ أثناء رفع الملف.";
        } elseif ((int)$file['size'] >= $maxSize) {
            $error = "حجم الملف يجب أن يكون أقل من 10 ميجابايت.";
        } else {
            $originalName = basename((string)$file['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($extension !== 'pdf') {
                $error = "مسموح برفع ملفات PDF فقط.";
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $error = "الملف المرفوع غير صالح.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                if ($mime !== 'application/pdf') {
                    $error = "الملف ليس ملف PDF";
                } else {
                    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                    $safeBaseName = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', $baseName);
                    $safeBaseName = trim((string)$safeBaseName);

                    if ($safeBaseName === '') {
                        $safeBaseName = 'file';
                    }

                    $safeBaseName = function_exists('mb_substr')
                        ? mb_substr($safeBaseName, 0, 80, 'UTF-8')
                        : substr($safeBaseName, 0, 80);

                    $target = $uploadDir . $safeBaseName . ".pdf";

                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $message = "تم رفع ملف PDF بنجاح.";
                    } else {
                        $error = "تعذر حفظ الملف على الخادم.";
                    }
                }
            }
        }
    }
}

$pdfFiles = [];

if (is_dir($uploadDir)) {
    foreach (new DirectoryIterator($uploadDir) as $file) {
        if ($file->isDot() || !$file->isFile()) {
            continue;
        }

        if (strtolower($file->getExtension()) !== 'pdf') {
            continue;
        }

        $pdfFiles[] = [
            'name' => $file->getBasename(),
            'size' => $file->getSize(),
            'modified' => $file->getMTime()
        ];
    }
}

usort($pdfFiles, static function (array $a, array $b): int {
    return $b['modified'] <=> $a['modified'];
});
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" media="all" href="../assets/libs/css/fontawesome.css"/>

    <style>
        .library-upload-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .library-upload-card h2,
        .library-files-card h2 {
            color: #2d3748;
            margin-bottom: 18px;
            font-size: 24px;
        }

        .library-upload-note {
            display: block;
            margin: -5px 0 22px;
            color: #718096;
            font-size: 14px;
            background: #f7fafc;
            border-right: 4px solid #667eea;
            padding: 10px 12px;
            border-radius: 6px;
        }

        .library-file-input {
            width: 100%;
            padding: 12px;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            background: #fafafa;
            cursor: pointer;
            font-family: inherit;
        }

        .library-file-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .library-files-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .library-file-name {
            word-break: break-word;
            font-weight: 600;
        }

        .library-open-btn {
            display: inline-block;
            padding: 7px 13px;
            background: #4299e1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .library-open-btn:hover {
            background: #3182ce;
        }

        .library-delete-form {
            display: inline;
            margin-right: 6px;
        }

        .library-delete-btn {
            display: inline-block;
            padding: 7px 13px;
            background: #e53e3e;
            color: white;
            border: 0;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
        }

        .library-delete-btn:hover {
            background: #c53030;
        }

        .library-submit-btn {
            min-width: 120px;
            transition: opacity 0.2s ease;
        }

        .library-submit-btn:disabled {
            opacity: 0.75;
            cursor: not-allowed;
        }

        .library-loading {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(255,255,255,0.82);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }

        .library-loading.is-visible {
            display: flex;
        }

        .library-loading-box {
            background: white;
            padding: 24px 30px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            text-align: center;
            min-width: 230px;
        }

        .library-spinner {
            width: 42px;
            height: 42px;
            margin: 0 auto 14px;
            border: 4px solid #e2e8f0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: librarySpin 0.8s linear infinite;
        }

        .library-loading-text {
            color: #2d3748;
            font-weight: 600;
            font-size: 15px;
        }

        @keyframes librarySpin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .library-upload-card,
            .library-files-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1><?= h($page_title); ?></h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= h($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error); ?></div>
        <?php endif; ?>

        <div class="library-upload-card">
            <h2>رفع ملف جديد</h2>

            <small class="library-upload-note">
                مسموح برفع ملفات <strong>PDF فقط</strong>، ويجب أن يكون حجم الملف <strong>أقل من 10 ميجابايت</strong>.
            </small>

            <form method="POST" enctype="multipart/form-data" id="libraryUploadForm">
                <div class="form-group">
                    <label for="pdf_file">اختر ملف PDF</label>
                    <input
                        class="library-file-input"
                        type="file"
                        id="pdf_file"
                        name="pdf_file"
                        accept=".pdf,application/pdf"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary library-submit-btn" id="libraryUploadBtn">
                        <span class="library-submit-label">رفع الملف</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="library-files-card">
            <h2>الملفات الموجودة في المكتبة</h2>

            <?php if (!empty($pdfFiles)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الملف</th>
                            <th>الحجم</th>
                            <th>المعاينة</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pdfFiles as $pdf): ?>
                        <tr>
                            <td class="library-file-name"><?= h($pdf['name']); ?></td>
                            <td><?= h(humanFileSize((int)$pdf['size'])); ?></td>
                            <td>
                                <a
                                    class="library-open-btn"
                                    href="../library/<?= rawurlencode($pdf['name']); ?>"
                                    target="_blank"
                                    rel="noopener"
                                ><i class="fas fa-eye"></i></a>

                                <form method="POST" class="library-delete-form" onsubmit="return confirm('هل أنت متأكد من حذف هذا الملف؟');">
                                    <input type="hidden" name="delete_file" value="<?= h($pdf['name']); ?>">
                                    <button type="submit" class="library-delete-btn" title="حذف الملف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">لا توجد ملفات PDF مرفوعة حاليًا.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="library-loading" id="libraryLoading" aria-hidden="true">
        <div class="library-loading-box">
            <div class="library-spinner" aria-hidden="true"></div>
            <div class="library-loading-text">جارٍ رفع الملف، يرجى الانتظار...</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('libraryUploadForm');
            const input = document.getElementById('pdf_file');
            const button = document.getElementById('libraryUploadBtn');
            const label = button ? button.querySelector('.library-submit-label') : null;
            const loading = document.getElementById('libraryLoading');
            const maxSize = 10 * 1024 * 1024;

            if (!form || !input || !button || !loading) {
                return;
            }

            form.addEventListener('submit', function (event) {
                const file = input.files && input.files.length ? input.files[0] : null;

                if (!file) {
                    event.preventDefault();
                    alert('يرجى اختيار ملف PDF أولاً.');
                    return;
                }

                const fileName = file.name || '';
                const extension = fileName.split('.').pop().toLowerCase();

                if (extension !== 'pdf' || file.type !== 'application/pdf') {
                    event.preventDefault();
                    alert('مسموح برفع ملفات PDF فقط.');
                    input.value = '';
                    return;
                }

                if (file.size >= maxSize) {
                    event.preventDefault();
                    alert('حجم الملف يجب أن يكون أقل من 10 ميجابايت.');
                    input.value = '';
                    return;
                }

                loading.classList.add('is-visible');
                loading.setAttribute('aria-hidden', 'false');
                button.disabled = true;

                if (label) {
                    label.textContent = 'جارٍ الرفع...';
                }
            });
        });
    </script>

</body>
</html>
