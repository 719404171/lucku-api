<?php
namespace App\Extend;
use App\Models\PospalSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
/**
 * Class pospal
 * @package App\Extend
 * 银豹相关配置,數據處理
 */
class pospal extends Controller
{

    private $Pospal;
    private $pospal_key;


    public function __construct()
    {
        $this->PospalSetting = new PospalSetting();
        $info=$this->PospalSetting->getPospalList();
        $this->pospal_key =$info['pospal_key'];


    }




	function doApiRequest($url,$sendData){
		$jsondata = json_encode($sendData);
		$signature = strtoupper(md5($this->pospal_key.$jsondata));
		return $this -> httpsRequest($url,$jsondata,$signature);
	}


	// 模拟提交数据函数
	function httpsRequest($url, $data, $signature){
		$time = time();
		$curl = curl_init();// 启动一个CURL会话
		// 设置HTTP头
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			"User-Agent: openApi",
			"Content-Type: application/json; charset=utf-8",
			"accept-encoding: gzip,deflate",
			"time-stamp: ".$time,
			"data-signature: ".$signature
		));
        curl_setopt($curl, CURLOPT_ENCODING, ''); // 解决数据乱码
		curl_setopt($curl, CURLOPT_URL, $url);         // 要访问的地址
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);		// Post提交的数据包
		// curl_setopt($curl, CURLOPT_PROXY,'127.0.0.1:8888');//设置代理服务器,此处用的是fiddler，可以抓包分析发送与接收的数据
		curl_setopt($curl, CURLOPT_POST, 1);		// 发送一个常规的Post请求
	
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 获取的信息以文件流的形式返回
		$output = curl_exec($curl); // 执行操作
		if (curl_errno($curl)) {
		   echo 'Errno'.curl_error($curl);//捕抓异常
		}
		curl_close($curl); // 关闭CURL会话
		
		return json_decode($output, true); // 返回数据

	}



    //處理返回數據
    public function Processing($msg)
    {
        $totalAmount=0;
        foreach ($msg['data']['result'] as $key=>$val)
        {
            $discounts=0;
            $quantity=0;
            $sellPrice=0;
            if($val['invalid'] != 1){
                $totalAmount +=$val['totalAmount'];
            }
            $res = DB::table('paycode')->get();
            $res =get_object_vars($res);// 对象转数组   toArray（）无效
            foreach ($val['payments'] as $k=>$v){
                 foreach ($res as $ke => $va){
                     if($v['code'] == $va['code']){
                         $msg['data']['result'][$key]['code'] = $va['name']; //循環支付方式
                     }
                 }
            }
            foreach ($val['items'] as $kk=>$vv){
                $discounts += $discount =$vv['sellPrice']*$vv['quantity']-$vv['totalAmount'];//商品原價*商品數量-實收金額
                $quantity +=$vv['quantity'];//商品數量
                $msg['data']['result'][$key]['items'][$kk]['discount'] =sprintf("%.2f", $discount); //每條商品折扣
                $sellPrice += $vv['sellPrice']*$vv['quantity']; //商品總原價
            }
            $msg['data']['result'][$key]['discount'] = sprintf("%.2f", $discounts);  //總折扣價
            $msg['data']['result'][$key]['quantity'] = $quantity;  //商品總數量
            $msg['data']['result'][$key]['sellPrice'] = sprintf("%.2f", $sellPrice);  //商品總原價
            if($val['invalid']==1){
                $msg['data']['result'][$key]['invalid']='作廢單據';
            }else{
                $msg['data']['result'][$key]['invalid']='有效單據';
            }
        }
        $msg['data']['totalAmount']=sprintf("%.2f", $totalAmount); //銷售總金額
        return $msg;
    }

    //根据单据序列号查询
    public function TicketBySn($msg)
    {
        $totalAmount=0;
        foreach ($msg['data']['items'] as $key=>$val)
        {
           $sellPrice=0;
           $totalAmount +=$val['totalAmount'];
            $sellPrice += $val['sellPrice']*$val['quantity']; //商品每條原價
            $msg['data']['items'][$key]['sellPrice'] =sprintf("%.2f", $sellPrice); //商品每條原價
           $discount =$val['sellPrice']*$val['quantity']-$val['totalAmount'];//每條商品折扣
           $msg['data']['items'][$key]['discount'] =sprintf("%.2f", $discount); //每條商品折扣
            $msg['data']['items'][$key]['totalAmount'] =sprintf("%.2f", $val['totalAmount']) ;
            $msg['data']['items'][$key]['totalProfit'] = sprintf("%.2f", $sellPrice-$discount);//每條利潤  原價-折扣
        }
        return $msg;

    }
    //處理返回狀態
    public function enable($msg)
    {
        if(!empty($msg['data']['result'])){
            foreach ($msg['data']['result'] as $key=>$val){
                if($val['enable']==1){
                    $msg['data']['result'][$key]['enable']='可用';
                }else if ($val['enable']==-1){
                    $msg['data']['result'][$key]['enable']='刪除';
                }else{
                    $msg['data']['result'][$key]['enable']='禁用';
                }
                $msg['data']['result'][$key]['discount'] = $val['discount'].'%';//折扣百分比
            }
        }
        return $msg;
    }

}
