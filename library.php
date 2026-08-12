<?php
declare(strict_types=1);

$libraryDir = __DIR__ . DIRECTORY_SEPARATOR . 'library';
$libraryWebPath = './library/';

$pdfFiles = [];

if (is_dir($libraryDir)) {
    foreach (new DirectoryIterator($libraryDir) as $file) {
        if ($file->isDot() || !$file->isFile()) {
            continue;
        }

        if (strtolower($file->getExtension()) !== 'pdf') {
            continue;
        }

        $name = $file->getBasename();

        $pdfFiles[] = [
            'name' => $name,
            'title' => pathinfo($name, PATHINFO_FILENAME),
            'size' => $file->getSize(),
            'modified' => $file->getMTime(),
            'url' => $libraryWebPath . rawurlencode($name),
        ];
    }
}

usort($pdfFiles, static function (array $a, array $b): int {
    return $b['modified'] <=> $a['modified'];
});

function formatFileSize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' بايت';
    }

    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1) . ' كيلوبايت';
    }

    return number_format($bytes / (1024 * 1024), 1) . ' ميجابايت';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="مكتبة ملفات PDF - المبسط">
    <title>المبسط - المكتبة</title>

    <link rel="icon" type="image/png" href="./assets/images/logo.png">
    <link rel="stylesheet" type="text/css" href="./assets/css/style.css">

    <style>
        .library-page {
            min-height: 80vh;
            background-color: #f5f5f5;
            padding: 50px 20px 90px;
        }

        .library-container {
            width: min(1200px, 100%);
            margin: 0 auto;
        }

        .library-title {
            text-align: center;
            font-size: 50px;
            color: black;
        }

        .library-underline {
            width: 100%;
            height: 5px;
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .library-underline > div {
            width: 250px;
            height: 1px;
            background-color: black;
            position: relative;
        }

        .library-underline > div > span {
            width: 100px;
            height: 4px;
            background-color: black;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .pdf-grid {
            margin-top: 35px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .pdf-card {
            background-color: white;
            border: 1px solid var(--secondary-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: 0.3s;
        }

        .pdf-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 16px rgba(0, 0, 0, 0.12);
        }

        .pdf-banner {
            height: 90px;
            background-color: var(--secondary-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pdf-icon {
            width: 68px;
            height: 68px;
            background-color: white;
            border: 5px solid var(--secondary-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--danger-color);
            font-family: Arial, sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        .pdf-content {
            padding: 25px 20px 20px;
        }

        .pdf-name {
            min-height: 58px;
            font-size: 21px;
            line-height: 1.6;
            color: black;
            margin-bottom: 14px;
            word-break: break-word;
        }

        .pdf-meta {
            color: var(--secondary-color);
            font-size: 15px;
            margin-bottom: 18px;
            line-height: 1.8;
        }

        .pdf-actions {
            display: flex;
            gap: 10px;
        }

        .pdf-actions a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            padding: 10px 8px;
            border-radius: 5px;
            font-size: 17px;
            color: white;
            transition: 0.3s;
        }

        .pdf-open {
            background-color: var(--warning-color);
        }

        .pdf-download {
            background-color: var(--success-color);
        }

        .pdf-actions a:hover {
            opacity: 0.88;
        }

        .library-empty {
            margin: 35px auto 0;
            max-width: 700px;
            background-color: white;
            border-radius: 8px;
            padding: 45px 25px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .library-empty-icon {
            font-family: Arial, sans-serif;
            font-size: 58px;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }

        .library-empty h2 {
            font-size: 26px;
            color: black;
            margin-bottom: 10px;
        }

        @media (max-width: 767px) {
            .library-page {
                padding: 28px 15px 60px;
            }

            .library-title {
                font-size: 32px;
            }

            .library-underline > div {
                width: 150px;
            }

            .library-underline > div > span {
                width: 50px;
            }

            .pdf-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }
        }
    </style>
</head>
<body>
<div class="_container">
    <header>
        <nav class="logo">
            <a href="./">
                <img title="المبسط" src="./assets/images/logo.png" alt="Almbst">
            </a>
        </nav>

        <nav class="navagators">
            <a href="./index.php">الرئيسية</a>
            <a href="./index.php#courses">الدورات</a>
            <a href="./index.php#book">كتاب المبسط</a>
            <a>المكتبة</a>
        </nav>

        <?php include "./includes/account.php"; ?>

        <button type="button" id="librarySlider" aria-label="فتح القائمة">☰</button>

        <div class="library-slider" id="librarySliderPanel">
            <nav>
                <button type="button" id="libraryCloseSlider" aria-label="إغلاق القائمة">×</button>
                <ul>
                    <li><a href="./index.php">الرئيسية</a></li>
                    <li><a href="./index.php#courses">الدورات</a></li>
                    <li><a href="./index.php#book">كتاب المبسط</a></li>
                    <li><a>المكتبة</a></li>
                </ul>
                <?php include "./includes/account.php"; ?>
            </nav>
        </div>
    </header>

    <div class="fake-header"></div>

    <main>
        <section class="library-page">
            <div class="library-container">
                <h1 class="library-title">المكتبة</h1>

                <div class="library-underline">
                    <div><span></span></div>
                </div>

                <?php if (!empty($pdfFiles)): ?>
                    <div class="pdf-grid">
                        <?php foreach ($pdfFiles as $pdf): ?>
                            <article class="pdf-card">
                                <div class="pdf-banner">
                                    <div class="pdf-icon">PDF</div>
                                </div>

                                <div class="pdf-content">
                                    <h2 class="pdf-name"><?= e($pdf['title']); ?></h2>

                                    <div class="pdf-meta">
                                        الحجم: <?= e(formatFileSize((int)$pdf['size'])); ?><br>
                                    </div>

                                    <div class="pdf-actions">
                                        <a class="pdf-open" href="<?= e($pdf['url']); ?>" target="_blank" rel="noopener">فتح الملف</a>
                                        <a class="pdf-download" href="<?= e($pdf['url']); ?>" download>تحميل</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="library-empty">
                        <div class="library-empty-icon">PDF</div>
                        <h2>لا توجد ملفات في المكتبة حاليًا</h2>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include "./includes/footer.html"; ?>
</div>

<style>
    #librarySlider {
        display: none;
        margin-right: 50px;
        background: none;
        border: none;
        font-size: 28px;
        color: black;
        cursor: pointer;
    }

    .library-slider {
        display: none;
    }

    @media (max-width: 1023px) {
        #librarySlider {
            display: block;
        }

        .library-slider {
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            right: 0;
            z-index: 9999;
        }

        .library-slider nav {
            width: 45%;
            min-width: 220px;
            height: 100vh;
            background-color: white;
            position: absolute;
            right: 0;
            top: 0;
            padding-top: 58px;
        }

        .library-slider nav ul {
            margin-top: 20px;
            direction: rtl;
        }

        .library-slider nav ul li {
            list-style: none;
            margin-top: 14px;
            margin-right: -22px;
        }

        .library-slider nav ul li a {
            text-decoration: none;
            color: black;
            font-size: 18px;
        }

        #libraryCloseSlider {
            position: absolute;
            top: 12px;
            right: 15px;
            background: none;
            border: none;
            font-size: 32px;
            color: var(--danger-color);
            cursor: pointer;
        }

        .library-slider .account {
            margin-top: 22px;
            margin-left: 0;
            display: flex;
            justify-content: center;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .library-slider nav ul li a {
            font-size: 22px;
        }
    }
</style>

<script>
(() => {
    const openButton = document.getElementById('librarySlider');
    const closeButton = document.getElementById('libraryCloseSlider');
    const panel = document.getElementById('librarySliderPanel');
    const year = document.getElementById('libraryCopyrightsYear');

    if (year) year.textContent = new Date().getFullYear();

    if (openButton && panel) {
        openButton.addEventListener('click', () => panel.style.display = 'flex');
    }

    if (closeButton && panel) {
        closeButton.addEventListener('click', () => panel.style.display = 'none');
    }

    if (panel) {
        panel.addEventListener('click', (event) => {
            if (event.target === panel) panel.style.display = 'none';
        });
    }
})();
</script>
</body>
</html>
