<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .success-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            border-radius: 50%;
            background: #4CAF50;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        .success-icon::before {
            content: '✓';
            color: white;
            font-size: 60px;
            font-weight: bold;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
            }
        }

        .success-title {
            font-size: 36px;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .success-message {
            font-size: 18px;
            color: #666;
            line-height: 1.6;
        }

        .back-btn {
            display: block;
            width: 100%;
            margin-top: 40px;
            padding: 18px 0;
            font-size: 20px;
            font-family: inherit;
            color: #ffffff;
            background: linear-gradient(135deg, #4CAF50 0%, #43a047 100%);
            border: none;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
            cursor: pointer;
        }

        .back-btn:active {
            transform: scale(0.98);
        }

        .countdown {
            margin-top: 18px;
            font-size: 15px;
            color: #999;
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
                margin: 20px;
            }

            .success-icon {
                width: 100px;
                height: 100px;
            }

            .success-icon::before {
                font-size: 50px;
            }

            .success-title {
                font-size: 28px;
            }

            .success-message {
                font-size: 16px;
            }

            .back-btn {
                font-size: 18px;
                padding: 16px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon"></div>
        <h1 class="success-title">Payment Success</h1>
        <p class="success-message">Your payment has been processed successfully. Thank you for your purchase!</p>
        <button type="button" id="btnReturn" class="back-btn">Return</button>
        <p id="countdown" class="countdown">Auto return in 3s</p>
    </div>

    <!-- uni SDK -->
    <script type="text/javascript" src="https://js.cdn.aliyun.dcloud.net.cn/dev/uni-app/uni.webview.1.5.2.js"></script>
    <script>
    (function () {
        var RETURN_URL = 'https://www.luckutcg.com/';   // website url for normal browsers
        var bridgeReady = false;
        var returned = false;
        var tip = document.getElementById('countdown');
        var btn = document.getElementById('btnReturn');

        document.addEventListener('UniAppJSBridgeReady', function () { bridgeReady = true; });

        // Close the page inside the App webview, otherwise go to the website
        function doReturn() {
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
                    uni.postMessage({ data: { payStatus: 'success' } });
                }
            } catch (e) {}

            // 3) Fallback: if the page is still alive after 1s (normal browser / App did not react), go to the website
            setTimeout(function () {
                try { window.location.href = RETURN_URL; } catch (e) {}
            }, 1000);
        }

        var left = 3;
        var timer = setInterval(function () {
            left--;
            if (left > 0) {
                if (tip) tip.innerText = 'Auto return in ' + left + 's';
                return;
            }
            clearInterval(timer);
            doReturn();
        }, 1000);

        if (btn) {
            btn.addEventListener('click', function () {
                clearInterval(timer);
                doReturn();
            });
        }
    })();
    </script>
</body>
</html>
