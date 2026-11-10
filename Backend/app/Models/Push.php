<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/3
 * Time: 14:40
 */

namespace App\Models;

use App\Http\Controllers\Common\GeTuiController;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Push extends Base
{
    use Notifiable;
    protected $table = 'push';
    protected $primaryKey = 'push_id';
    public $timestamps = false;

    /**
     * 推送消息
     * @param $data
     * @return array
     */
    public function pushMessageWeb($data){
        DB::beginTransaction();
        if($data['push_type'] == 1){
            $userInfo = User::where('user_id',$data['user_id'])->select('user_id')->pluck('user_id')->toArray();//web版本去除cid验证->whereNotNull('client_id')
            if(empty($userInfo)){
                return $this->error(__('web.push_not_user'));//错误信息改为没有用户
            }
        }else{
            $userInfo = User::where('user_status','=',1)->select('user_id')->pluck('user_id')->toArray();
            if(empty($userInfo)){
                return $this->error(__('web.push_not_user'));
            }
        }
        $pushInfo = [
            'admin_id' => $data['admin_id'],
            'user_id' => $data['user_id'],
            'push_type' => $data['push_type'],
            'push_title' => $data['push_title'],
            'push_content' => $data['push_content'],
//            'push_platform' => $data['push_platform'],web不再需要區分操作系統
        ];
        $addPush = $this->insert($pushInfo);
        if($addPush){
            for($i=0;$i<count($userInfo);$i++){
                $systemInfo[$i] = [
                    'user_id' => $userInfo[$i],
                    'system_type' => 3,
                    'system_content' => $data['push_content'],
                    'en_system_content' => $data['push_content'],
                    'created_at' => self::getDate(),
                    'updated_at' => self::getDate()
                ];
            }
            $addSystem = SystemInfo::insert($systemInfo);
            if($addSystem){
                DB::commit();
                return $this->success(__('web.push_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.push_fail'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.push_fail'));
        }
    }

    /**
     * 獲取推送消息列表
     * @param $data
     * @return array
     */
    public function getPushMessageListWeb($data){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('push.push_status','=',1);
        $datas['data'] = $this->join('admins','admins.admin_id','=','push.admin_id')
            ->leftJoin('users','users.user_id','=','push.user_id')
            ->where($where)
            ->where(function ($query) use ($data){
                if(!empty($data['username'])){
                    $query->whereRaw('(concat(admins.username,users.user_name) like "%' . $data['username'] . '%")');
                }
            })
            ->select('push.*','admins.username','users.user_name')
            ->offset($page)->limit($limit)
            ->orderBy('push_id','desc')
            ->get()->toArray();
        $datas['count'] = $this->join('admins','admins.admin_id','=','push.admin_id')
            ->leftJoin('users','users.user_id','=','push.user_id')
            ->where($where)
            ->where(function ($query) use ($data){
                if(!empty($data['username'])){
                    $query->whereRaw('(concat(admins.username,users.user_name) like "%' . $data['username'] . '%")');
                }
            })->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 刪除推送消息
     * @param $pushID
     * @return array
     */
    public function deletePushMessageWeb($pushID){
        $data = self::softDelete('push','push_id',$pushID,'push_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

}
