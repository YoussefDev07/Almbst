<?php 
require_once 'config.php';

$page_title = "إدارة الأقسام والمحتوى";
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'add') {
                $stmt = $db->prepare("INSERT INTO courses_sections (type, title, course_id, category_id, video, test) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['type'],
                    $_POST['title'],
                    $_POST['course_id'],
                    $_POST['category_id'] ?: null,
                    $_POST['video'] ?: null,
                    $_POST['test'] ?: null
                ]);
                $message = "تم إضافة القسم بنجاح";
            } 
            elseif ($_POST['action'] == 'edit') {
                $stmt = $db->prepare("UPDATE courses_sections SET type = ?, title = ?, course_id = ?, category_id = ?, video = ?, test = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['type'],
                    $_POST['title'],
                    $_POST['course_id'],
                    $_POST['category_id'] ?: null,
                    $_POST['video'] ?: null,
                    $_POST['test'] ?: null,
                    $_POST['id']
                ]);
                $message = "تم تعديل القسم بنجاح";
            }
            elseif ($_POST['action'] == 'delete') {
                $stmt = $db->prepare("DELETE FROM courses_sections WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "تم حذف القسم بنجاح";
            }
        } catch(PDOException $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    }
}

$edit_section = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM courses_sections WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_section = $stmt->fetch(PDO::FETCH_ASSOC);
}

$courses = $db->query("SELECT id, title FROM courses ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query("SELECT id, title FROM courses_sections WHERE type = 'category' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$sections = $db->query("SELECT cs.*, c.title as course_title, cat.title as category_title 
                        FROM courses_sections cs 
                        LEFT JOIN courses c ON cs.course_id = c.id 
                        LEFT JOIN courses_sections cat ON cs.category_id = cat.id 
                        ORDER BY cs.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleFields() {
            const type = document.getElementById('type').value;
            const videoField = document.getElementById('video-field');
            const testField = document.getElementById('test-field');
            const categoryField = document.getElementById('category-field');
            
            if (type === 'category') {
                videoField.style.display = 'none';
                testField.style.display = 'none';
                categoryField.style.display = 'none';
            } else {
                videoField.style.display = 'block';
                testField.style.display = 'block';
                categoryField.style.display = 'block';
            }
        }
        
        window.onload = toggleFields;
    </script>
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
            <h2><?php echo $edit_section ? 'تعديل القسم' : 'إضافة قسم/محتوى جديد'; ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $edit_section ? 'edit' : 'add'; ?>">
                <?php if ($edit_section): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_section['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">النوع</label>
                        <select id="type" name="type" required onchange="toggleFields()">
                            <option value="category" <?php echo ($edit_section && $edit_section['type'] == 'category') ? 'selected' : ''; ?>>
                                قسم رئيسي (Category)
                            </option>
                            <option value="element" <?php echo ($edit_section && $edit_section['type'] == 'element') ? 'selected' : ''; ?>>
                                محتوى/درس (Element)
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_id">الكورس</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">اختر الكورس</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" 
                                        <?php echo ($edit_section && $edit_section['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">العنوان</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo $edit_section ? htmlspecialchars($edit_section['title']) : ''; ?>">
                </div>
                
                <div class="form-group" id="category-field">
                    <label for="category_id">القسم الرئيسي (اختياري)</label>
                    <select id="category_id" name="category_id">
                        <option value="">لا يوجد</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($edit_section && $edit_section['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="video-field">
                    <label for="video">رابط الفيديو</label>
                    <input type="text" id="video" name="video" placeholder="https://www.youtube.com/watch?v=..." 
                           value="<?php echo $edit_section ? htmlspecialchars($edit_section['video']) : ''; ?>">
                </div>
                
                <div class="form-group" id="test-field">
                    <label for="test">رابط الاختبار</label>
                    <textarea id="test" name="test" rows="3" placeholder="يمكنك إدخال رابط أو محتوى الاختبار"><?php echo $edit_section ? htmlspecialchars($edit_section['test']) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_section ? 'تعديل' : 'إضافة'; ?>
                    </button>
                    <?php if ($edit_section): ?>
                        <a href="sections.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-card">
            <h2>قائمة الأقسام والمحتوى</h2>
            <?php if (count($sections) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>النوع</th>
                            <th>العنوان</th>
                            <th>الكورس</th>
                            <th>القسم الرئيسي</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                            <tr>
                                <td><?php echo $section['id']; ?></td>
                                <td>
                                    <span class="badge <?php echo $section['type'] == 'category' ? 'badge-primary' : 'badge-secondary'; ?>">
                                        <?php echo $section['type'] == 'category' ? 'قسم رئيسي' : 'محتوى'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($section['title']); ?></td>
                                <td><?php echo htmlspecialchars($section['course_title']); ?></td>
                                <td><?php echo $section['category_title'] ? htmlspecialchars($section['category_title']) : '-'; ?></td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $section['id']; ?>" class="btn-edit">✏️ تعديل</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا القسم؟');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $section['id']; ?>">
                                        <button type="submit" class="btn-delete">🗑️ حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">لا توجد أقسام حالياً. قم بإضافة قسم جديد.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
