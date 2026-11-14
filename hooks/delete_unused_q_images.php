<?php
require_once "../master/connect.php";

$imagesDir = $_SERVER['DOCUMENT_ROOT']."/exam/questions/";

if (!is_dir($imagesDir)) {
    die("مجلد الصور غير موجود في المسار المحدد: $imagesDir");
}

$stmt = $connect->query("SELECT DISTINCT q_image FROM questions WHERE q_image IS NOT NULL AND q_image != ''");
$usedImages = [];

while ($row = $stmt->fetch()) {
    $fullUrl = $row['q_image'];
    
    $filename = basename($fullUrl);
    
    if (!empty($filename)) {
        $usedImages[$filename] = true;
    }
}

$files = scandir($imagesDir);
$deletedCount = 0;
$keptCount = 0;

echo "<h3>تقرير فحص وحذف صور الأسئلة:</h3>";
echo "<ul>";

foreach ($files as $file) {
    if ($file === '.' || $file === '..' || is_dir($imagesDir . $file) || strpos($file, '.') === 0) {
        continue;
    }

    $filePath = $imagesDir . $file;

    if (isset($usedImages[$file])) {
        $keptCount++;
        echo "<li><span style='color: lightgreen;'>موجودة (تم الاحتفاظ بها):</span> " . htmlspecialchars($file) . "</li>";
    } 
    else {
        if (unlink($filePath)) {
            $deletedCount++;
            echo "<li><strong style='color: orange;'>غير موجودة بالداتا بيز (تم الحذف):</strong> " . htmlspecialchars($file) . "</li>";
        } else {
            echo "<li><strong style='color: yellow;'>فشل في حذف الملف:</strong> " . htmlspecialchars($file) . "</li>";
        }
    }
}

echo "</ul>";
echo "<hr>";
echo "<p style='color: green;'><strong>عدد الصور التي تم الاحتفاظ بها (مستخدمة):</strong> $keptCount</p>";
echo "<p style='color: red;'><strong>عدد الصور غير المستخدمة التي تم حذفها:</strong> $deletedCount</p>";
