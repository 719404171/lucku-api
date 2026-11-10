<!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>郵箱驗證碼</title>
</head>
<body style="background:#f5f5f5;padding:30px;font-family:Arial,sans-serif;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <div style="text-align:center;margin-bottom:30px;">
        <h2 style="color:#303133;margin:0;">{{ $info['Subject'] }}</h2>
    </div>
    <p style="font-size:15px;color:#606266;line-height:1.6;">{{ $info['Message'] }}</p>
    <div style="text-align:center;margin:30px 0;padding:20px;background:#f0f2f5;border-radius:8px;letter-spacing:8px;">
        <span style="font-size:36px;font-weight:700;color:#409eff;">{{ $info['Code'] }}</span>
    </div>
    <p style="font-size:12px;color:#909399;line-height:1.6;">驗證碼有效期為10分鐘，請勿洩露給他人。</p>
    <hr style="border:none;border-top:1px solid #ebeef5;margin:20px 0;">
    <p style="font-size:11px;color:#c0c4cc;text-align:center;">此郵件由系統自動發送，請勿回覆</p>
</div>
</body>
</html>
