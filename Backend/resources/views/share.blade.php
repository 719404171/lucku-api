<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>分享</title>
  </head>
  <body>
    <img
      src=<?php echo $app_logo; ?> 
      style="width: 150px; height: 150px; margin-top: 80px;border-radius: 20px;"
    />
    <div class="content">
        <button id="openapp">打開 APP</button>
    </div>
    <div style="color: #fff;margin-bottom: 80px;">請打开瀏覽器訪問該頁面</div>
    <div class="down">
      <button id="downios">
        <img
          src="{{ asset('./image/e1f2a1bdcf2da9f45e8f2edfb1d778d.png') }}"
          alt=""
          style="width: 24px; height: 24px; vertical-align: bottom"
        />
        iOS 下載
      </button>
      <button id="downgoogle">
        <img
          src="{{ asset('./image/31b884242417e44c8670d5817237e7e.png') }}"
          alt=""
          style="width: 24px; height: 24px; vertical-align: bottom"
        />
        Google 下載
      </button>
    </div>
  </body>
</html>

<style>
  * {
    margin: 0;
    padding: 0;
  }
  body {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    background-color: #d5cfcf;
  }
  .content {
    padding: 50px 20px 20px;
    width: 100%;
    display: flex;
    justify-content: space-around;
    box-sizing: border-box;
  }
  #openapp {
    width: 70%;
    height: 40px;
    border-radius: 40px;
    border: none;
    background-color: #5e3d89;
    color: #fff;
    font-weight: 800;
    font-size: 14px;
  }
  .down {
    width: 100%;
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
  }
  #downios {
    width: 70%;
    height: 40px;
    /* border: 1px solid #000; */
    border-radius: 40px;
    background-color: #000;
    color: #fff;
    border: none;
    font-weight: 800;
    font-size: 14px;
  }
  #downgoogle {
    width: 70%;
    height: 40px;
    border-radius: 40px;
    border: none;
    background-color: #fff;
    margin-top: 20px;
    font-weight: 800;
    font-size: 14px;
  }
</style>
<script>
  const openapp = document.getElementById("openapp");
  const downios = document.getElementById("downios");
  const downgoogle = document.getElementById("downgoogle");
  openapp.addEventListener("click", function () {
    window.location.href = '{{$app_name}}';
  });
  downios.addEventListener("click", function () {
    window.location.href = '{{$apple_download}}';
  });
  downgoogle.addEventListener("click", function () {
    window.location.href = '{{$google_download}}';
  });
</script>