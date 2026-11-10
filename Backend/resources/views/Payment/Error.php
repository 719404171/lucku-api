<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付結果</title>
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
        }
    </style>
</head>
<body>
<div class="body">
    <img src="https://brain-storm.oss-cn-hongkong.aliyuncs.com/2024-12-25/1735114822756632.png" alt="" class="img">
    <div class="msg">支付失敗</div>
    <div class="returns" id="returns">3秒後自動返回，若沒反應，請點擊按鈕返回</div>
    <a class="btn" id="btnReturn" onclick="returnApp()">點擊返回</a>
</div>

<!-- uni 的 SDK -->
<script type="text/javascript" src="https://js.cdn.aliyun.dcloud.net.cn/dev/uni-app/uni.webview.1.5.2.js"></script>
<script>
(function () {
    var RETURN_URL = 'https://www.luckutcg.com/';   // 瀏覽器環境下返回的官網地址
    var bridgeReady = false;
    var returned = false;
    var tip = document.getElementById('returns');

    document.addEventListener('UniAppJSBridgeReady', function () { bridgeReady = true; });

    // 返回：App 內嵌 webview 關閉當前頁；普通瀏覽器跳轉官網
    window.returnApp = function () {
        if (returned) return;
        returned = true;

        // 1) 5+ App / uni-app 原生 webview：直接關閉當前頁，回到 App
        try {
            if (window.plus && plus.webview && plus.webview.currentWebview) {
                plus.webview.currentWebview().close();
            }
        } catch (e) {}

        // 2) uni-app 內嵌 webview：通知 App 端關閉本頁
        try {
            if (bridgeReady && window.uni && typeof uni.postMessage === 'function') {
                uni.postMessage({ data: { payStatus: 'cancel' } });
            }
        } catch (e) {}

        // 3) 兜底：1 秒後頁面仍未被關閉（普通瀏覽器 / App 未響應）→ 跳轉官網
        setTimeout(function () {
            try { window.location.href = RETURN_URL; } catch (e) {}
        }, 1000);
    };

    // 倒計時：頁面加載即開始（不依賴 App 橋接，瀏覽器同樣生效）
    var left = 3;
    var timer = setInterval(function () {
        left--;
        if (left > 0) {
            if (tip) tip.innerText = left + '秒後自動返回，若沒反應，請點擊按鈕返回';
            return;
        }
        clearInterval(timer);
        window.returnApp();
    }, 1000);
})();
</script>
</body>
</html>
