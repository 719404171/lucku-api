<?php
/*
 * File: Base.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-17 4:10:42 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use App\Extend;
use App\Http\Controllers\Common\GeTuiController;
use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Http\Controllers\Common\MonologsController as Logs;//日志类
use App\Http\Controllers\Common\GoogleTranslateController as GoogleTranslate;//谷歌翻译
/**
 * App\Models\Base
 */
class Base extends Authenticatable
{

    use Notifiable;
    public $timestamps = true;
    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date) {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }


/*********************************************************return common**********************************************************************/

    /**
    * 成功返回数据
    * @param $message 信息
    */
    protected function success($msg = "Success" , $results=''){

        // $results=GoogleTranslate::translate($results, 'en');

        return ["status" => 200, "msg" => $msg, "results" => $results];
    }

    /**
    * 失败返回数据
    * @param $message 信息
    */
    protected function error($msg = "Network Error" , $results=''){

        if(empty($results)){
            return ["status" => 500, "msg" => $msg];
        }else{
            return ["status" => 500, "msg" => $msg ,"results" => $results];
        }


    }


    /**
    * 参数错误返回数据
    * @param $message 信息
    */
    protected function parametersError($msg = "No Parameters" , $results=''){

        if(empty($results)){
            return ["status" => 501, "msg" => $msg];
        }else{
            return ["status" => 501, "msg" => $msg ,"results" => $results];
        }

    }

    /**
    * 输出日志信息
    * @param $message 信息
    * @param array $data 数据
    * @param string $filename 文件名称  默认：info
    * @param string $isDate 是否按月份分文件夹 默认：否
    */
    protected function LogInfo($message, $data, $filename, $isDate){

        Logs::info($message, $data, $filename, $isDate);
    }


    /**
    * 输出Mysql操作日志信息
    * @param $message 信息
    * @param array $data 数据
    * @param string $filename 文件名称  默认：info
    * @param string $isDate 是否按月份分文件夹 默认：否
    */
    protected function LogSql($filename = 'info', $isDate = true){

        Logs::sql($filename, $isDate);
    }


    /**
    * 循環節點
    * @param $message 信息
    */

    static public function getTree($menu, $pid = 0)
    {
        $tree = [];
        if (empty($menu)) {
            return $tree;
        } else {
            $newData = [];
            //循环重新排列
            foreach ($menu as $datum) {
                $newData[$datum['menu_id']] = $datum;
                $newData[$datum['menu_id']]['children'] = [];
            }
            foreach ($newData as $key => $datum) {
                if ($datum['parent'] > 0) {
                    //不是根节点的将自己的地址放到父级的child节点
                    $newData[$datum['parent']]['children'][] = &$newData[$key];
                } else {
                    //根节点直接把地址放到新数组中
                    $tree[] = &$newData[$datum['menu_id']];
                }
            }
            return $tree;
        }
    }

    static public function getDate(){
        return date('Y-m-d H:i:s');
    }

/*********************************************************schema 緩存**********************************************************************/

    /**
     * 數據表欄位緩存（同一請求內只查一次 information_schema）
     * @var array
     */
    protected static $schemaColumnsCache = [];

    /**
     * 判斷數據表是否存在（帶緩存）
     * @param string $table
     * @return bool
     */
    public static function hasTableCached($table){
        return self::getTableColumns($table) !== null;
    }

    /**
     * 判斷欄位是否存在（帶緩存）
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function hasColumnCached($table, $column){
        $columns = self::getTableColumns($table);
        return $columns !== null && in_array($column, $columns, true);
    }

    /**
     * 獲取數據表欄位列表（帶緩存，表不存在返回 null）
     * @param string $table
     * @return array|null
     */
    protected static function getTableColumns($table){
        if (!array_key_exists($table, self::$schemaColumnsCache)) {
            try {
                $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
                self::$schemaColumnsCache[$table] = empty($columns) ? null : $columns;
            } catch (\Throwable $e) {
                self::$schemaColumnsCache[$table] = null;
            }
        }
        return self::$schemaColumnsCache[$table];
    }

    /**
     * 批量修改
     * @param $table
     * @param $idField
     * @param $id
     * @param $field
     * @param $status
     * @return bool
     */
    static public function softDelete($table,$idField,$id,$field,$status){
        DB::beginTransaction();
        $update = true;
        for($i=0;$i<count($id);$i++){
            $data = DB::table($table)->where($idField,$id[$i])->update([$field => $status]);
            if(!$data){
                $update = false;
                break;
            }
        }
        if($update){
            DB::commit();
            return true;
        }else{
            DB::rollBack();
            return false;
        }
    }

    /**判斷兩個多維鍵值數組是否相等，返回他們不等的數據
     * @param $arr1
     * @param $arr2
     * @return array
     */
    static public function isSameArray($arr1,$arr2){
        if(count($arr1) != count($arr2)){
            return [count($arr1),count($arr2)];
        }
        $arr3=array();
        foreach ($arr1 as $key => $value) {
            if(!in_array($value,$arr2)){
                $arr3[]=$value;
            }
        }
        return $arr3;
    }

    static public function isEqualArray($arr1,$arr2){
        if (array_diff($arr1, $arr2) == array_diff($arr2, $arr1)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 訂單消息推送
     * @param $orderID  //訂單id
     * @param $cn_content //中文推送內容
     * @param $en_content //英文推送內容
     * @param $languagenow //使用的語言
     */
    static public function pushOrderMessage($orderID,$cn_content,$en_content,$languagenow){
        $clientID = Order::join('users','order.user_id','=','users.user_id')
            ->where('order.order_id',$orderID)
            ->whereNotNull('users.client_id')
            ->first()->client_id;
        $user = Order::where('order_id', $orderID)//簡化id獲取
                ->value('user_id');
        $content = ($languagenow == 'en') ? $en_content : $cn_content;//推送内容判斷中英文
        if(!empty($clientID)){
            $GeTui = new GeTuiController();
            $GeTui->pushToSingleByCid($clientID,'Order',$content,1);
        }
            $systemInfo = [
                'user_id' => $user,
                'system_type' => 3,
                'system_content' => $cn_content,
                'en_system_content' => $en_content,
                'created_at' => self::getDate()
            ];
            SystemInfo::insert($systemInfo);
    }

/*********************************************************return admin**********************************************************************/


/*********************************************************return api**********************************************************************/


    /**
     * @param $value
     * @return array
     * 獲取二維碼
     */
    public function getQRCode($value){
        $img = new \QRcode();
        $values = $value; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6; // 生成图片大小
        $filename = rand(10000, 99999) . time() . '.jpg';
        $img->png($values, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $uploadQRcode = $this->uploadQRcode($filename);
        if (!empty($uploadQRcode)) {
            @unlink($filename);//刪除生產二維碼
            return $uploadQRcode;
        } else {
            $file_path='';
            return $file_path;
        }

    }


    /**
     * @param $filename
     * @return array
     * 上傳二維碼
     */
    public function uploadQRcode($filename){
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        $object = 'ewm/' . $filename;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filename);
            $file_path=config('services.aliyun_oss.url') . '/' . $object;
            return $file_path;
        } catch (OssException $e) {
            return '';
        }
    }






/*********************************************************END************************************************************************/

}
