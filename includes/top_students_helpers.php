<?php

function top_students_upload_image(array $file, bool $optional = false): ?string
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('ملف الصورة غير صالح.');
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($optional) {
            return null;
        }
        throw new RuntimeException('يرجى اختيار صورة الدرجة.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('حدث خطأ أثناء رفع الصورة. حاول مرة أخرى.');
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('تعذر التحقق من الملف المرفوع.');
    }
    if (!isset($file['size']) || $file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('حجم الصورة يجب ألا يتجاوز 3 ميجابايت.');
    }

    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedMimeTypes = [
        'jpg'  => ['image/jpeg', IMAGETYPE_JPEG],
        'jpeg' => ['image/jpeg', IMAGETYPE_JPEG],
        'png'  => ['image/png', IMAGETYPE_PNG],
        'webp' => ['image/webp', IMAGETYPE_WEBP],
    ];
    if (!isset($allowedMimeTypes[$extension])) {
        throw new RuntimeException('صيغة الصورة غير مسموحة. استخدم JPG أو PNG أو WEBP.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false || !isset($imageInfo['mime']) || !in_array($imageInfo[2] ?? null, array_column($allowedMimeTypes, 1), true)) {
        throw new RuntimeException('الملف المحدد ليس صورة صالحة.');
    }
    if ($imageInfo['mime'] !== $allowedMimeTypes[$extension][0]) {
        throw new RuntimeException('امتداد الصورة لا يطابق نوع الملف.');
    }

    $rootDirectory = dirname(__DIR__);
    $uploadDirectory = $rootDirectory . '/uploads/top_students';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('تعذر إنشاء مجلد حفظ الصور.');
    }

    $safeExtension = $extension === 'jpeg' ? 'jpg' : $extension;
    try {
        $filename = bin2hex(random_bytes(18)) . '.' . $safeExtension;
    } catch (Throwable $e) {
        $filename = uniqid('student_', true) . '.' . $safeExtension;
    }
    $destination = $uploadDirectory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('تعذر حفظ الصورة على الخادم.');
    }

    return $filename;
}

function top_students_delete_image(?string $filename): void
{
    if (!$filename || basename($filename) !== $filename) {
        return;
    }

    $path = dirname(__DIR__) . '/uploads/top_students/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function top_students_escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}