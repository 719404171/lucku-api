<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>恭喜, 项目创建成功！！</title>
    <style>
        .container {
            width: 60%;
            margin: 10% auto 0;
            background-color: #f0f0f0;
            padding: 2% 5%;
            border-radius: 10px
        }

        ul {
            padding-left: 20px;
        }

            ul li {
                line-height: 2.3
            }

        a {
            color: #20a53a
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>恭喜, 项目{{$APP_NAME}}创建成功！</h1>
        <h3>请确认项目信息无误</h3>
        <ul>
            <img src=<?php echo $APP_LOGO; ?> style="width: 150px; height: 150px; margin-top: 80px;border-radius: 20px;"/>
            <li><h4>项目接口地址:{{$APP_URL}}</h4></li>
        </ul>
    </div>
</body>
</html>