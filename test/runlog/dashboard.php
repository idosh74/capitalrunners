<?php
require_once 'php/html_page_init.php';
$bundle = __DIR__ . '/dashboard-ui/dist/dashboard.js';
$stylesheet = __DIR__ . '/dashboard-ui/dist/dashboard.css';
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>רצי הבירה - לוח הקבוצה</title>
    <?php if (is_file($stylesheet)) { ?>
        <link rel="stylesheet" href="dashboard-ui/dist/dashboard.css?v=<?php echo filemtime($stylesheet); ?>">
    <?php } ?>
</head>
<body>
    <div id="dashboard-root">
        <p>טוען את לוח הקבוצה...</p>
        <?php if (!is_file($bundle) || !is_file($stylesheet)) { ?>
            <p>קובצי לוח הקבוצה חסרים. יש לבנות את הפרויקט לפני פריסתו.</p>
        <?php } ?>
    </div>
    <?php if (is_file($bundle)) { ?>
        <script type="module" src="dashboard-ui/dist/dashboard.js?v=<?php echo filemtime($bundle); ?>"></script>
    <?php } ?>
</body>
</html>
