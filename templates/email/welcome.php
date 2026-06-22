<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>خوش آمدید</title>
</head>
<body style="font-family: Tahoma, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 30px;">
        <h1 style="color: #333;">خوش آمدید!</h1>
        <p>سلام <?= htmlspecialchars($username ?? 'کاربر') ?>,</p>
        <p>حساب کاربری شما با موفقیت ایجاد شد. خوشحالیم که به جمع ما پیوستید.</p>
        <?php if (isset($loginLink)): ?>
        <p style="text-align: center; margin: 30px 0;">
            <a href="<?= htmlspecialchars($loginLink) ?>"
               style="background: #28a745; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                ورود به حساب
            </a>
        </p>
        <?php endif; ?>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #888; font-size: 12px;"><?= htmlspecialchars($appName ?? 'Backend System') ?></p>
    </div>
</body>
</html>
