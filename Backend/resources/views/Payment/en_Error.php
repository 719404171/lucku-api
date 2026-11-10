<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Payment Result</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body{
            text-align:center
        }
        .img{
            width:300px;
            height: 300px;
            margin-top: 40%;
        }
        .msg{
            font-size: 80px;
            margin-top: 100px;
            color: #f8712f;
        }
        .returns{
            font-size: 38px;
            margin-top: 100px;
            color: #f8712f;
        }
        .btn{
            display:block;
            padding: 20px 20px;
            font-size: 60px;
            margin-top: 100px;
            color: #ffffff;
            background-color: #f8712f;
            border-radius: 50px;
        }
        .hint{
            display:none;
            font-size: 30px;
            margin-top: 40px;
            color: #999999;
        }

        @media (max-width: 768px) {
            .img{
                width: 160px;
                height: 160px;
                margin-top: 25%;
            }
            .msg{
                font-size: 40px;
                margin-top: 50px;
            }
            .returns{
                font-size: 22px;
                margin-top: 50px;
                padding: 0 24px;
            }
            .btn{
                font-size: 24px;
                margin: 50px 24px 0;
            }
            .hint{
                font-size: 18px;
                padding: 0 24px;
            }
        }
    </style>
</head>
<body>
<div class="body">
    <img src="https://brain-storm.oss-cn-hongkong.aliyuncs.com/2024-12-25/1735114822756632.png" alt="" class="img">
    <div class="msg">Payment Failed</div>
    <div class="returns" id="returns">After 3 seconds, it will return to the APP automatically. If nothing happens, please tap the button.</div>
    <a class="btn" id="btnReturn" onclick="returnApp()">Return</a>
</div>

<!-- uni SDK -->
<script type="text/javascript" src="https://js.cdn.aliyun.dcloud.net.cn/dev/uni-app/uni.webview.1.5.2.js"></script>
<script>
(function () {
    var RETURN_URL = 'https://www.luckutcg.com/';   // website url for normal browsers
    var bridgeReady = false;
    var returned = false;
    var tip = document.getElementById('returns');

    document.addEventListener('UniAppJSBridgeReady', function () { bridgeReady = true; });

    // Close the page inside the App webview, otherwise go to the website
    window.returnApp = function () {
        if (returned) return;
        returned = true;

        // 1) 5+ App / uni-app native webview: close current page directly
        try {
            if (window.plus && plus.webview && plus.webview.currentWebview) {
                plus.webview.currentWebview().close();
            }
        } catch (e) {}

        // 2) uni-app embedded webview: notify the App to close this page
        try {
            if (bridgeReady && window.uni && typeof uni.postMessage === 'function') {
                uni.postMessage({ data: { payStatus: 'cancel' } });
            }
        } catch (e) {}

        // 3) Fallback: if the page is still alive after 1s (normal browser / App did not react), go to the website
        setTimeout(function () {
            try { window.location.href = RETURN_URL; } catch (e) {}
        }, 1000);
    };

    // Countdown starts on page load (no App bridge required, works in browsers)
    var left = 3;
    var timer = setInterval(function () {
        left--;
        if (left > 0) {
            if (tip) tip.innerText = 'After ' + left + ' seconds, it will return to the APP automatically.';
            return;
        }
        clearInterval(timer);
        window.returnApp();
    }, 1000);
})();
</script>
</body>
</html>
