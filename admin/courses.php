<?php 
require_once 'config.php';

$page_title = "إدارة الكورسات";
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'add') {
                $stmt = $db->prepare("INSERT INTO courses (title, icon, duration, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['title'],
                    str_replace('"></i>', "", str_replace('<i class="', "", $_POST['icon'])),
                    $_POST['duration'],
                    $_POST['price']
                ]);
                $message = "تم إضافة الكورس بنجاح";
            } 
            elseif ($_POST['action'] == 'edit') {
                $stmt = $db->prepare("UPDATE courses SET title = ?, icon = ?, duration = ?, price = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['title'],
                    str_replace('"></i>', "", str_replace('<i class="', "", $_POST['icon'])),
                    $_POST['duration'],
                    $_POST['price'],
                    $_POST['id']
                ]);
                $message = "تم تعديل الكورس بنجاح";
            }
            elseif ($_POST['action'] == 'delete') {
                $stmt = $db->prepare("DELETE FROM courses_sections WHERE course_id = ?");
                $stmt->execute([$_POST['id']]);
                $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "تم حذف الكورس بنجاح";
            }
        } catch(PDOException $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    }
}

$edit_course = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
}

$courses = $db->query("SELECT * FROM courses ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
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
        <h1><?php echo $page_title; ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <h2><?php echo $edit_course ? 'تعديل الكورس' : 'إضافة كورس جديد'; ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $edit_course ? 'edit' : 'add'; ?>">
                <?php if ($edit_course): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_course['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">عنوان الكورس</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo $edit_course ? htmlspecialchars($edit_course['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="icon">الأيقونة <a target="_blank" href="https://fontawesome.com/v5/search?ic=free&o=r">(Font Awesome)</a></label>
                    <input type="text" id="icon" name="icon" placeholder="fas fa-book" 
                           value="<?php echo $edit_course ? htmlspecialchars($edit_course['icon']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">المدة</label>
                        <input type="text" id="duration" name="duration" placeholder="3m" 
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['duration']) : ''; ?>">
                        <small>مثال: 3m (3 شهور)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">السعر (ريال)</label>
                        <input type="number" id="price" name="price" step="0.01" 
                               value="<?php echo $edit_course ? $edit_course['price'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_course ? 'تعديل' : 'إضافة'; ?>
                    </button>
                    <?php if ($edit_course): ?>
                        <a href="courses.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-card">
            <h2>قائمة الكورسات</h2>
            <?php if (count($courses) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>العنوان</th>
                            <th>الأيقونة</th>
                            <th>المدة</th>
                            <th>السعر</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo $course['id']; ?></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><i class="<?php echo htmlspecialchars($course['icon']); ?>"></i> <?php echo htmlspecialchars($course['icon']); ?></td>
                                <td><?php echo htmlspecialchars($course['duration']); ?></td>
                                <td><?php echo $course['price']; ?> ريال</td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $course['id']; ?>" class="btn-edit">✏️ تعديل</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكورس؟');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn-delete">🗑️ حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">لا توجد كورسات حالياً. قم بإضافة كورس جديد.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
