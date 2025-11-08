<?php 
require_once "config.php";

$page_title = "لوحة التحكم الرئيسية";

$total_courses = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$total_sections = $db->query("SELECT COUNT(*) FROM courses_sections")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>مرحباً بك في لوحة التحكم</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div class="stat-label">إجمالي الكورسات</div>
                <a href="courses.php" class="stat-link">إدارة الكورسات</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📑</div>
                <div class="stat-number"><?php echo $total_sections; ?></div>
                <div class="stat-label">إجمالي الأقسام والمحتوى</div>
                <a href="sections.php" class="stat-link">إدارة الأقسام</a>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2>الإجراءات</h2>
            <div class="action-buttons">
                <a href="courses.php?action=add" class="action-btn">
                    <span>➕</span>
                    <span>إضافة كورس جديد</span>
                </a>
                <a href="sections.php?action=add" class="action-btn">
                    <span>📝</span>
                    <span>إضافة قسم جديد</span>
                </a>
                <a href="./codes.php" class="action-btn">
                    <span>🔢</span>
                    <span>إدارة رموز التفعيل</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
