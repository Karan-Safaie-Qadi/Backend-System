<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>بازیابی رمز عبور</title>
</head>
<body style="font-family: Tahoma, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 30px;">
        <h1 style="color: #333;">بازیابی رمز عبور</h1>
        <p>سلام <?= htmlspecialchars($username ?? 'کاربر') ?>,</p>
        <p>برای بازیابی رمز عبور خود روی لینک زیر کلیک کنید:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="<?= htmlspecialchars($resetLink) ?>"
               style="background: #007bff; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                بازیابی رمز عبور
            </a>
        </p>
        <p>این لینک تا ۱ ساعت دیگر معتبر است.</p>
        <p>اگر شما درخواست بازیابی رمز نداده‌اید، این ایمیل را نادیده بگیرید.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #888; font-size: 12px;"><?= htmlspecialchars($appName ?? 'Backend System') ?></p>
    </div>
</body>
</html>
