<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/2
 * Time: 10:47
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChatGPTController extends Controller
{
    protected $apiUrl;
    protected $apiKey;
    protected $BaiDuApiKey;
    protected $BaiDuApiSecret;
    protected $BaiDuApiHost;

    public function __construct()
    {
        $this->apiUrl = 'https://api.openai.com/v1/engines/davinci-codex/completions';
        $this->apiKey = '';
        $this->BaiDuApiKey = config('services.BaiDu.baidu_ai_key');
        $this->BaiDuApiSecret = config('services.BaiDu.baidu_ai_secret');
        $this->BaiDuApiHost = config('services.BaiDu.baidu_ai_url');
    }

    /**
     * 發送chatCPT請求
     * @param Request $request
     * @return array
     */
    public function sendingGPTWeb(Request $request){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            'prompt' => $request->post('message'), // 用户输入的消息
            'max_tokens' => 100, // 生成响应的最大令牌数
            'temperature' => 0.7, // 生成文本的随机性
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        // 执行cURL会话
        $response = curl_exec($curl);
        // 关闭cURL会话
        curl_close($curl);
        // 解析API响应
        $data = json_decode($response, true);
        // 获取ChatGPT的响应
        $message = $data['choices'][0]['text'];
        // 输出ChatGPT的响应到Web页面
        return $this->success(__('web.success'),$message);
    }

    /**
     * 發送文言一心ai請求
     * @param Request $request
     * @return mixed
     */
    public function sendingBaiDuGPTWeb(Request $request){
        $message = [
            'messages' =>[
                [
                    'role' => 'user',
                    'content' =>$request->post('message')
                ]
            ]
        ];
        $postData = json_encode($message);

        $curl = curl_init($this->BaiDuApiHost . 'access_token=' . $this->getBaiDuAccessToken());
        // 设置 cURL 选项
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);

        // 执行请求并获取响应
        $response = curl_exec($curl);
        // 关闭 cURL 资源
        curl_close($curl);

        // 解码JSON响应
        $rtn = json_decode($response, true);
        return $this->success(__('web.success'),$rtn);
    }

    /**
     * 获取百度文言一心access_token
     * @return mixed
     */
    public function getBaiDuAccessToken(){
        $curl = curl_init();
        $postData = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->BaiDuApiKey,
            'client_secret' => $this->BaiDuApiSecret
        ];
        curl_setopt_array($curl,array(
            CURLOPT_URL => 'https://aip.baidubce.com/oauth/2.0/token',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        // 执行cURL会话
        $response = curl_exec($curl);
        // 关闭cURL会话
        curl_close($curl);
        $rtn = json_decode($response);
        return $rtn->access_token;
    }

}
