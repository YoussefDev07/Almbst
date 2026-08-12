<?php
require_once 'config.php';

$page_title = "المشتركون في الدورات";
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'cancel' && isset($_POST['subscription_id'])) {
            $stmt = $db->prepare("UPDATE subscriptions SET expired = 1 WHERE id = ?");
            $stmt->execute([(int) $_POST['subscription_id']]);
            $message = "تم إلغاء الاشتراك بنجاح";
        }

        if ($_POST['action'] === 'update_expire_date'
            && isset($_POST['subscription_id'], $_POST['expire_date'])
            && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $_POST['expire_date'])) {

            $expire_date = $_POST['expire_date'];
            $stmt = $db->prepare("UPDATE subscriptions SET expire_date = ?, expired = 0 WHERE id = ?");
            $stmt->execute([$expire_date, (int) $_POST['subscription_id']]);
            $message = "تم تعديل تاريخ انتهاء الاشتراك بنجاح";
        }
    } catch (PDOException $e) {
        $error = "خطأ: " . $e->getMessage();
    }
}

$courses = $db->query("SELECT id, title FROM courses ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$selected_course_id = isset($_GET['course_id']) && $_GET['course_id'] !== ''
    ? (int) $_GET['course_id']
    : (isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0);

$selected_course = null;
foreach ($courses as $course) {
    if ((int) $course['id'] === $selected_course_id) {
        $selected_course = $course;
        break;
    }
}

$subscribers = [];
$subscriber_count = 0;

if ($selected_course_id > 0 && $selected_course) {
    $stmt = $db->prepare("SELECT
                            s.id AS subscription_id,
                            s.subscription_date,
                            s.expire_date,
                            a.id AS user_id,
                            a.fristname,
                            a.lastname,
                            a.avatar,
                            a.email
                          FROM subscriptions s
                          INNER JOIN accounts a ON a.id = s.user_id
                          WHERE s.course_id = ?
                            AND s.expired = 0
                            AND s.expire_date >= CURDATE()
                          ORDER BY s.subscription_date DESC, s.id DESC");
    $stmt->execute([$selected_course_id]);
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $subscriber_count = count($subscribers);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .subscriber-filter {
            margin-bottom: 25px;
        }

        .subscriber-count {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px 25px;
            background: #f7fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .subscriber-count-label {
            color: #718096;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .subscriber-count-number {
            color: #667eea;
            font-size: 34px;
            font-weight: bold;
        }

        .subscriber-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .subscriber-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            background: #e2e8f0;
            flex: 0 0 48px;
        }

        .subscriber-name {
            font-weight: 600;
            color: #2d3748;
        }

        .subscriber-email {
            color: #718096;
            font-size: 13px;
            margin-top: 2px;
            direction: ltr;
            text-align: right;
        }

        .date-cell {
            white-space: nowrap;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .expire-date-form {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .expire-date-input {
            width: 145px;
            padding: 7px 8px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            font-size: 13px;
            direction: ltr;
        }

        .btn-update-date {
            background: #667eea;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-update-date:hover {
            background: #5a67d8;
        }

        .btn-cancel {
            background: #f56565;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #e53e3e;
        }

        @media (max-width: 768px) {
            .subscriber-count {
                align-items: flex-start;
                flex-direction: column;
            }

            .subscriber-info {
                min-width: 180px;
            }

            .data-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-card subscriber-filter">
            <h2>اختيار الكورس</h2>
            <form method="GET" action="">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="course_id">الكورس</label>
                    <select id="course_id" name="course_id" onchange="this.form.submit()">
                        <option value="">اختر الكورس</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo (int) $course['id']; ?>" <?php echo $selected_course_id === (int) $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_course): ?>
            <div class="table-card">
                <div class="subscriber-count">
                    <div>
                        <div class="subscriber-count-label">عدد المشتركين في الكورس</div>
                        <div class="subscriber-count-number"><?php echo $subscriber_count; ?></div>
                    </div>
                    <div>
                        <span class="badge badge-primary"><?php echo htmlspecialchars($selected_course['title']); ?></span>
                    </div>
                </div>

                <?php if ($subscriber_count > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>المشترك</th>
                                <th>تاريخ الاشتراك</th>
                                <th>تاريخ انتهاء الاشتراك</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscribers as $subscriber): ?>
                                <?php
                                    $full_name = trim($subscriber['fristname'] . ' ' . $subscriber['lastname']);
                                    $full_name = $full_name !== '' ? $full_name : 'مشترك بدون اسم';
                                    $initial = function_exists('mb_substr') ? mb_substr($full_name, 0, 1, 'UTF-8') : substr($full_name, 0, 1);
                                    $avatar = trim((string) $subscriber['avatar']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="subscriber-info">
                                            <?php if ($avatar !== ''): ?>
                                                <img class="subscriber-avatar" src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php else: ?>
                                                <div class="subscriber-avatar" style="display:flex;align-items:center;justify-content:center;font-weight:700;color:#667eea;">
                                                    <?php echo htmlspecialchars($initial); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="subscriber-name"><?php echo htmlspecialchars($full_name); ?></div>
                                                <?php if (!empty($subscriber['email'])): ?>
                                                    <div class="subscriber-email"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="date-cell"><?php echo htmlspecialchars($subscriber['subscription_date']); ?></td>
                                    <td class="date-cell"><?php echo htmlspecialchars($subscriber['expire_date']); ?></td>
                                    <td class="actions">
                                        <form method="POST" class="expire-date-form">
                                            <input type="hidden" name="action" value="update_expire_date">
                                            <input type="hidden" name="subscription_id" value="<?php echo (int) $subscriber['subscription_id']; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                            <input
                                                type="date"
                                                name="expire_date"
                                                class="expire-date-input"
                                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                                value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($subscriber['expire_date']))); ?>"
                                                required
                                                title="اختر تاريخ انتهاء الاشتراك الجديد"
                                            >
                                            <button type="submit" class="btn-update-date">تعديل تاريخ الإنتهاء</button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء اشتراك هذا المستخدم؟');">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="subscription_id" value="<?php echo (int) $subscriber['subscription_id']; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                            <button type="submit" class="btn-cancel">إلغاء الاشتراك</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">لا يوجد مشتركون حاليون في هذا الكورس.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($selected_course_id > 0): ?>
            <div class="table-card">
                <p class="no-data">الكورس المطلوب غير موجود.</p>
            </div>
        <?php else: ?>
            <div class="table-card">
                <p class="no-data">اختر كورسًا من القائمة لعرض عدد المشتركين وبيانات اشتراكاتهم.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
