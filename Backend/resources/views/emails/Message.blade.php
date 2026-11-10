<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mail Notifier</title>
</head>
<body>
<p style="FONT-SIZE: 14px; FONT-WEIGHT: bold; COLOR: #f90">{{ $info['Message'] ?? '' }}</p>

@if(!empty($info['Link']))
<p style="MARGIN-TOP: 20px; TEXT-ALIGN: center;">
    <a href="{{ $info['Link'] }}" style="DISPLAY: inline-block; PADDING: 12px 30px; BACKGROUND: #409eff; COLOR: #fff; TEXT-DECORATION: none; BORDER-RADIUS: 6px; FONT-SIZE: 15px;">{{ $info['LinkText'] ?? '點擊驗證郵箱' }}</a>
</p>
<p style="FONT-SIZE: 12px; COLOR: #909399; TEXT-ALIGN: center; MARGIN-TOP: 10px;">如果按鈕無法點擊，請複製以下鏈接到瀏覽器打開：</p>
<p style="FONT-SIZE: 12px; COLOR: #606266; TEXT-ALIGN: center; WORD-BREAK: break-all;">{{ $info['Link'] }}</p>
@endif

<DIV class=footer style="MARGIN-TOP: 30px">
    <P></P>
</DIV>
<DIV class=tip style="COLOR: #cccccc; TEXT-ALIGN: center">This email is sent by the system, please do not reply</DIV>
</body>
</html>
