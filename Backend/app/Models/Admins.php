<?php
/*
 * File: Admins.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-24 9:34:09 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Models;


use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admins extends Base{

    use Notifiable;
    protected $table = 'admins';
    protected $primaryKey = 'admin_id';
    protected $hidden = ['password','token'];
    public $timestamps = true;

    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }

/*********************************************************admin**********************************************************************/

    /**
     * 获取管理员权限及菜单
     * @param $token
     * @param $language
     * @return array
     */
    public function getAdminMenuWeb($token,$language){
        $menu = Menu::join('role_menu','menu.menu_id','=','role_menu.menu_id')
            ->join('role','role_menu.role_id','=','role.role_id')
            ->join('admin_role','role.role_id','=','admin_role.role_id')
            ->join('admins','admins.admin_id','=','admin_role.admin_id')
            ->where('admins.token',$token)
            ->where('menu.status',1)
            ->where('role_menu.status',1)
            ->select('menu.menu_id','menu.'.$language.'menu_name as menu_name','menu.parent','menu.icon','menu.path','menu.redirect','role.role_id','role.role_name')
            ->get()->toArray();
        $menuList = Base::getTree($menu);
        for($i=0;$i<count($menuList);$i++){
            for($j=0;$j<count($menuList[$i]['children']);$j++){
                $menuList[$i]['children'][$j]['power'] = PowerMenu::join('power','power_menu.power_id','=','power.power_id')
                    ->where('power_menu.menu_id',$menuList[$i]['children'][$j]['menu_id'])
                    ->where('power_menu.role_id',$menuList[$i]['children'][$j]['role_id'])
                    ->where('power_menu.status',1)
                    ->select('power.power_id','power.type')
                    ->get()->toArray();
            }
        }
        return $menuList;
    }

    /**
     * 获取管理員列表
     * @param $data
     * @param $token
     * @param $language
     * @return mixed
     */
    public function getAdminListWeb($data,$token,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $adminID = $this->where('token',$token)->first()->admin_id;
        $where[] = array('admins.admin_id','<>',$adminID);
        $where[] = array('admins.status','<>',-1);
        $where[] = array('admin_role.role_id','<>',1);
        if(!empty($data['username'])){
            $where[] = array('admins.username','like','%'.$data['username'].'%');
        }
        if(!empty($data['role_id'])){
            $where[] = array('admin_role.role_id',$data['role_id']);
        }
        if(isset($data['status']) && (!empty($data['status']) || is_numeric($data['status']))){
            $where['admins.status'] = $data['status'];
        }
        $adminList['data'] = $this->join('admin_role','admins.admin_id','=','admin_role.admin_id')
            ->join('role','admin_role.role_id','=','role.role_id')
            ->where($where)
            ->select('admins.admin_id','admins.username','admins.avatar','admins.email','admins.phone','admins.status','role.'.$language.'role_name as role_name','role.role_id')
            ->offset($page)->limit($limit)
            ->orderBy('admin_id','desc')
            ->get()->toArray();
        $adminList['count'] = $this->join('admin_role','admins.admin_id','=','admin_role.admin_id')
            ->join('role','admin_role.role_id','=','role.role_id')
            ->where($where)
            ->count();
        return $adminList;
    }

    /**
     * 新增管理員
     * @param $adminInfo
     * @param $role_id
     * @return array
     */
    public function addAdminWeb($adminInfo,$role_id){
        DB::beginTransaction();
        $hasAdmin = $this->where(function ($query) use ($adminInfo){
            $query->where('username',$adminInfo['username'])->where('status','<>',-1);
        })->orWhere(function ($q) use ($adminInfo){
            $q->where('email',$adminInfo['email'])->where('status','<>',-1);
        })->orWhere(function ($q) use ($adminInfo){
            $q->where('phone',$adminInfo['phone'])->where('status','<>',-1);
        })->first();
        if(empty($hasAdmin)){
            $adminInfo['created_at'] = self::getDate();
            $adminInfo['password'] = md5($adminInfo['password']);
            $adminID = $this->insertGetId($adminInfo);
            if(!empty($adminID)){
                $addAdminRole = AdminRole::insert(['admin_id'=>$adminID,'role_id'=>$role_id,'created_at'=>self::getDate()]);
                if($addAdminRole){
                    DB::commit();
                    return $this->success(__('web.add_success'));
                }else{
                    DB::rollBack();
                    return $this->error(__('web.add_fail'));
                }
            }else{
                DB::rollBack();
                return $this->error(__('web.add_fail'));
            }
        }else{
            if(!empty($hasAdmin['username']) && !empty($adminInfo['username']) && ($hasAdmin['phone'] == $adminInfo["username"])){
                return $this->error(__('web.user_exist'));
            }else if(!empty($hasAdmin['email']) && !empty($adminInfo['email']) && ($hasAdmin['email'] == $adminInfo["email"])){
                return $this->error(__('web.email_exist'));
            }else if(!empty($hasAdmin['phone']) && !empty($adminInfo['phone']) && ($hasAdmin['phone'] == $adminInfo["phone"])){
                return $this->error(__('web.phone_exist'));
            }
        }
    }

    /**
     * 編輯管理員
     * @param $adminInfo
     * @param $role_id
     * @return array
     */
    public function editAdminWeb($adminInfo,$role_id){
        DB::beginTransaction();
        $hasAdmin = $this->where(function ($query) use ($adminInfo){
            $query->where('username',$adminInfo['username'])->where('status','<>',-1)->where('admin_id','<>',$adminInfo['admin_id']);
        })->orWhere(function ($q) use ($adminInfo){
            $q->where('email',$adminInfo['email'])->where('status','<>',-1)->where('admin_id','<>',$adminInfo['admin_id']);
        })->orWhere(function ($q) use ($adminInfo){
            $q->where('phone',$adminInfo['phone'])->where('status','<>',-1)->where('admin_id','<>',$adminInfo['admin_id']);
        })->first();
        if(empty($hasAdmin)){
            $adminID = $this->where('admin_id',$adminInfo['admin_id'])->update($adminInfo);
            if($adminID){
                $adminRoleID = AdminRole::where('admin_id',$adminInfo['admin_id'])->first()->role_id;
                if($role_id != $adminRoleID){
                    $editAdminRole = AdminRole::where('admin_id',$adminInfo['admin_id'])->update(['role_id'=>$role_id]);
                    if($editAdminRole){
                        DB::commit();
                        return $this->success(__('web.edit_success'));
                    }else{
                        DB::rollBack();
                        return $this->error(__('web.edit_fail'));
                    }
                }else{
                    DB::commit();
                    return $this->success(__('web.edit_success'));
                }
            }else{
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }else{
            if(!empty($hasAdmin['username']) && !empty($adminInfo['username']) && ($hasAdmin['phone'] == $adminInfo["username"])){
                return $this->error(__('web.user_exist'));
            }else if(!empty($hasAdmin['email']) && !empty($adminInfo['email']) && ($hasAdmin['email'] == $adminInfo["email"])){
                return $this->error(__('web.email_exist'));
            }else if(!empty($hasAdmin['phone']) && !empty($adminInfo['phone']) && ($hasAdmin['phone'] == $adminInfo["phone"])){
                return $this->error(__('web.phone_exist'));
            }
        }
    }

    /**
     * 刪除管理員
     * @param $adminID
     * @return array
     */
    public function deleteAdminWeb($adminID){
        $update = self::softDelete('admins','admin_id',$adminID,'status',-1);
        if($update){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

}
