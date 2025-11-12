<?php
$uploadDir = "../../exam/questions/"; 
$publicBase = "https://localhost/www/المبسط/exam/questions/"; 

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) { 
        echo json_encode(['status' => 'error', 'message' => 'فشل إنشاء مجلد الرفع']);
        exit();
    }
}

if (!empty($_FILES['image']['name'])) {
    
    // التحقق من وجود أخطاء في الرفع
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء الرفع: رمز الخطأ ' . $_FILES['image']['error']]);
        exit();
    }
    
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp']; 

    if (!in_array($ext, $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'امتداد غير مسموح']);
        exit();
    }

    // توليد اسم ملف فريد وآمن
    $fileName = uniqid('q_', true) . '.' . $ext;
    
    // المسار الكامل للحفظ على الخادم
    $target = $uploadDir . $fileName; 
    
    $publicPath = $publicBase . $fileName; 

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode(['status' => 'ok', 'path' => $publicPath]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل نقل الملف إلى المجلد']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'لم يتم تحديد أي ملف']);
}