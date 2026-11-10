<!DOCTYPE html>
<html lang="zh">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Account deletion</title>
</head>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<style>
  * {
    box-sizing: border-box;
    -webkit-user-select: none;
    outline: none;
    margin: 0;
    padding: 0;
  }

  .loader {
    border: 8px solid #f3f3f3;
    border-top: 8px solid #3498db;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    animation: spin 1s linear infinite;
    margin: 200px auto;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  .form {
    position: relative;
    width: 85%;
    height: 600px;
    margin: 50px auto 0;
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 20px;
  }

  .form-title {
    font-weight: 700;
    font-size: 20px;
    color: #5f5f5f;
    text-align: center;
    letter-spacing: 5px;
    padding: 0;
    margin-bottom: 50px;
  }

  .form-item {
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
  }

  .form-item__label {
    display: inline-block;
    height: 40px;
    line-height: 40px;
    font-size: 14px;
    color: #5f5f5f;
  }


  .form-button {
    position: absolute;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    display: block;
    width: 200px;
    height: 40px;
    line-height: 40px;
    border-radius: 8px;
    font-size: 14px;
    text-align: center;
    color: #fff;
    background-color: rgb(255, 166, 0);
    border: none;
    box-shadow: 0px 0px 8px 10px rgb(255, 166, 0, .1);
  }

  .form-button:active {
    background-color: rgb(255, 188, 65);
  }

  .input-item {
    height: 40px;
    line-height: 40px;
    padding-left: 10px;
    border-radius: 8px;
    border: 2px solid #eee;
    font-size: 14px;
    color: #7a7a7a;
  }

  .input-item:focus {
    border-color: #000;
  }

  .tips {
    font-size: 12px;
    color: rgb(255, 77, 77);
  }

  .mask {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, .7);
    z-index: 999;
  }

  .hide {
    display: none;
  }

  .delete-tips {
    display: none;
    font-size: 20px;
    color: #000;
    text-align: center;
    margin-top: 200px;
    padding: 0;
  }

  .area-code {
    margin-right: 5px;
    height: 40px;
    line-height: 40px;
    border-radius: 8px;
    border: 2px solid #eee;
    background-color: #fff;
  }

  label {
    margin-right: 10px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #7a7a7a;
  }

  option {
    height: 40px;
    line-height: 40px;
  }

  .flex {
    display: flex;
  }

  .flex-1 {
    flex: 1;
  }

  .logo {
    display: block;
    width: 80px;
    height: 80px;
    border-radius: 100%;
    margin: 30px auto 30px;
    object-fit: cover;
  }
</style>

<body>
  <!-- <image src="https://img2.baidu.com/it/u=2343179780,3964624973&fm=253&fmt=auto&app=120&f=JPEG?w=1044&h=696"
         class="logo"></image> -->
  <p class="delete-tips">successfully delete!</p>

  <div class="form">
    <p class="form-title">Account deletion</p>

    <div class="form-item">
      <span class="form-item__label">Log in to your phone number or email address:</span>
      <div class="flex">
        <label onchange="handleChangeType()">
          <input checked type="radio" name="loginType" value="phone">
          phone
        </label>
        <label onchange="handleChangeType()">
          <input type="radio" name="loginType" value="email">
          mailbox
        </label>
      </div>
      <div class="flex">
        <select class="area-code" name="areaCode">
          <option value="+852">+852</option>
          <option value="+86">+86</option>
        </select>
        <input class="input-item flex-1" placeholder="please enter"></input>
      </div>
    </div>

    <div class="form-item">
      <span class="form-item__label">password:</span>
      <input class="input-item" placeholder="please enter" type="password"></input>
    </div>

    <p class="tips">Note: After confirming the deletion of the account, all account information will be deleted simultaneously. 
                    Please pay attention to the saving of personal important information！</p>

    <button class="form-button" onclick="onSubmit()">Confirm deletion</button>

    <div class="mask hide">
      <div class="loader"></div>
    </div>
  </div>
</body>
<script>
  var loginType = 'phone'

  // 提交刪除
  function onSubmit() {
    const _this = this
    const username = document.querySelectorAll('.input-item')[0].value //帳號
    const password = document.querySelectorAll('.input-item')[1].value //密碼
    let areaCode = null //區號
    if (loginType === 'phone') {
      areaCode = document.querySelector('select[name="areaCode"]').value
      let has = areaCode === '+852' ? isHongKongPhoneNumber(`${areaCode}${username}`) : isChinaPhoneNumber(`${areaCode}${username}`)
      if (!has) return alert('The phone number is incorrect')
    } else {
      if (!isEmail(username)) return alert('Mailbox format error')
    }

    loading()
    axios.post('{{$base_uri}}/api/BannedUser', {username:username,password:password,areaCode:areaCode})
      .then((response) => {
        console.log(response.data)
        if (response.data.status==200) {
          loading()
          success()
        }else{
          loading()
          alert(response.data.msg)
        }
      })
      .catch((error) => {
        console.log(error)
        loading()
        alert(response.data.msg)
      })
  }

  // 判断香港手机号码
  function isHongKongPhoneNumber(phoneNumber) {
    // 香港手机号码正则表达式
    var regex = /^(?:\+?852\-?)?[123456789]\d{3}\-?\d{4}$/;
    return regex.test(phoneNumber);
  }

  // 判断中国大陆手机号码
  function isChinaPhoneNumber(phoneNumber) {
    // 中国大陆手机号码正则表达式
    var regex = /^(?:\+?86\-?)?1[3-9]\d{9}$/;
    return regex.test(phoneNumber);
  }

  // 判断邮箱
  function isEmail(email) {
    // 邮箱正则表达式
    var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
  }

  function handleChangeType() {
    var selectedValue = document.querySelector('input[name="loginType"]:checked').value
    var areaCodeDom = document.querySelector('.area-code')
    loginType = selectedValue

    if (selectedValue === 'email') {
      areaCodeDom.style.display = 'none'
    } else {
      areaCodeDom.style.display = 'block'
    }
  }

  // 刪除成功調用動畫
  function success() {
    document.querySelector('.form').style.display = 'none'
    document.querySelector('.delete-tips').style.display = 'block'
  }

  // 頁面加載動畫
  function loading() {
    var loaderElement = document.querySelector('.mask');
    if (loaderElement.classList.contains('hide')) {
      loaderElement.classList.remove('hide');
    } else {
      loaderElement.classList.add('hide');
    }
  }


</script>

</html>