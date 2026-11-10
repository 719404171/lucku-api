<?php
/*
 * File: UserMessage.php
 * Project: NewDemo
 * Created Time: 2024-07-03 05:02:02 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:31:56 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
/**
 * App\Models\UserMessage
 *
 */
class UserMessage extends Base
{
    use Notifiable;
    protected $table = 'user_message';
    protected $primaryKey = 'message_id';
    public $timestamps = true;
    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }



    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }
/*********************************************************全局作用域注册**********************************************************************/

    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('user_cart', function (Builder $builder) {
		  return $builder->where('user_cart.cart_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 获取留言列表
     * @param $data
     * @return array
     */
    public function getMessageListWeb($data){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('user_message.message_status','=',1);
        if (!empty($data['user_name'])) {
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        $message['data']= $this->join('users','users.user_id','=','user_message.user_id')->where($where)
            ->select('users.user_name','user_message.message_id','user_message.message_content','user_message.created_at')
            ->offset($page)->limit($limit)
            ->orderBy('user_message.created_at', 'desc')
            ->get()->toArray();
        $message['count'] = $this->join('users','users.user_id','=','user_message.user_id')
            ->where($where)
            ->count();
        return $this->success(__('web.success'),$message);
    }

    /**
     * 刪除留言信息
     * @param $messageID
     * @return array
     */
    public function deleteMessageWeb($messageID){
        $data = self::softDelete('user_message','message_id',$messageID,'message_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }



/**********************************************************api**********************************************************************/

    /**
     * @param $token
     * @param $data
     * @return mixed
     * 添加用戶反饋
     */
    public function addUserMessage($data){
        DB::beginTransaction();
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->insert($data);
            if(!empty($effects)){
                // $this->LogInfo('添加收貨地址' , $effects , 'addUserAddress' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.add_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.add_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.add_fail') , $e->getMessage());
        }
    }





/**************************************************common(api和admin共用)***********************************************************/

}
