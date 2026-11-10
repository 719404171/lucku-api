<?php
/*
 * File: Role.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-24 9:35:31 am
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

class Role extends Base{

    use Notifiable;

    protected $table = 'role';
    protected $primaryKey = 'role_id';
    public $timestamps = false;
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
     * 获取角色列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getRoleListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('role_id','<>',1);
        $datas['data'] = $this->where(function ($query) use ($data,$language){
            if(!empty($data['role_name'])){
                $query->where($language.'role_name','like','%' . $data['role_name'] . '%');
            }
        })
            ->where($where)
            ->select('role_id','role_name','en_role_name')
            ->offset($page)->limit($limit)
            ->orderBy('role_id','desc')
            ->get()->toArray();
        $datas['count'] = $this->where(function ($query) use ($data,$language){
            if(!empty($data['role_name'])){
                $query->where($language.'role_name','like','%' . $data['role_name'] . '%');
            }
        })->count();
        return $datas;
    }

    /**
     * 获取角色權限
     * @param $roleId
     * @param $language
     * @return array
     */
    public function getRolePowerWeb($roleId,$language){
        $menu = Menu::join('role_menu','menu.menu_id','=','role_menu.menu_id')
            ->where('role_menu.role_id',$roleId)
            ->where('menu.status',1)
            ->select('menu.menu_id','menu.'.$language.'menu_name as menu_name','menu.parent','role_menu.status')
            ->get()->toArray();
        $menuList = Base::getTree($menu);
        for($i=0;$i<count($menuList);$i++){
            for($j=0;$j<count($menuList[$i]['children']);$j++){
                $menuList[$i]['children'][$j]['children'] = PowerMenu::join('power','power_menu.power_id','=','power.power_id')
                    ->where('power_menu.menu_id',$menuList[$i]['children'][$j]['menu_id'])
                    ->where('power_menu.role_id',$roleId)
                    ->select('power.type','power_menu.status',DB::raw('CONCAT('.$menuList[$i]['children'][$j]['menu_id'].',"-",power.power_id) as menu_id'))
                    ->get()->toArray();
            }
        }
        return $menuList;
    }

    /**
     * 新增角色權限
     * @param $role_name
     * @param $menu
     * @param $power
     * @return array
     */
    public function addRolePower($role_name,$menu,$power){
        DB::beginTransaction();
        $hasName = $this->where('role_name',$role_name['role_name'])->orWhere('en_role_name',$role_name['en_role_name'])->first();
        if(empty($hasName)){
            $roleID = $this->insertGetId(['role_name'=>$role_name['role_name'],'en_role_name'=> $role_name['en_role_name']]);
            if(!empty($roleID)){
                for($i=0;$i<count($menu);$i++){
                    $menuData[$i]['menu_id'] = $menu[$i]['menu_id'];
                    $menuData[$i]['status'] = $menu[$i]['status'];
                    $menuData[$i]['role_id'] = $roleID;
                    $menuData[$i]['created_at'] = self::getDate();
                }
                $roleMenu = RoleMenu::insert($menuData);
                for($j=0;$j<count($power);$j++){
                    $powerData[$j]['power_id'] = $power[$j]['power_id'];
                    $powerData[$j]['menu_id'] = $power[$j]['menu_id'];
                    $powerData[$j]['status'] = $power[$j]['status'];
                    $powerData[$j]['role_id'] = $roleID;
                    $powerData[$j]['created_at'] = self::getDate();
                }
                $powerMenu = PowerMenu::insert($powerData);
                if($roleMenu && $powerMenu){
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
            return $this->error(__('web.user_exist'));
        }
    }

    /**
     * 编辑角色權限
     * @param $role
     * @param $menu
     * @param $power
     * @return array
     */
    public function editRolePower($role,$menu,$power){
        DB::beginTransaction();
        $role_name = $this->where(function ($query) use ($role){
            $query->where('role_name',$role['role_name'])->where('role_id','<>',$role['role_id']);
        })->orWhere(function ($q) use ($role){
            $q->where('en_role_name',$role['en_role_name'])->where('role_id','<>',$role['role_id']);
        })->first();
        if(empty($role_name)){
            $roleName = $this->where('role_id',$role['role_id'])->first();
            if($roleName['role_name'] == $role['role_name'] && $roleName['en_role_name'] == $role['en_role_name']){
                $roleUpdate = true;
            }else{
                $roleUpdate = $this->where('role_id',$role['role_id'])->update(['role_name'=>$role['role_name'],'en_role_name'=>$role['en_role_name']]);
            }
            $roleMenuUpdate = true;
            $powerMenuUpdate = true;
            for($i=0;$i<count($menu);$i++){
                $menuData['status'] = $menu[$i]['status'];
                $roleMenu = RoleMenu::where('role_id',$role['role_id'])->where('menu_id',$menu[$i]['menu_id'])->update($menuData);
                if(!$roleMenu){
                    $roleMenuUpdate = false;
                    break;
                }
            }
            for($j=0;$j<count($power);$j++){
                $powerData['status'] = $power[$j]['status'];
                $powerMenu = PowerMenu::where('role_id',$role['role_id'])->where('menu_id',$power[$j]['menu_id'])->where('power_id',$power[$j]['power_id'])->update($powerData);
                if(!$powerMenu){
                    $powerMenuUpdate = false;
                    break;
                }
            }
            if($roleUpdate && $roleMenuUpdate && $powerMenuUpdate){
                DB::commit();
                return $this->success(__('web.edit_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }else{
            return $this->error(__('web.user_exist'));
        }
    }

    /**
     * 删除角色
     * @param $role_id
     * @return array
     */
    public function deleteRolePower($role_id){
        DB::beginTransaction();
        $roleDelete = true;
        $roleMenuDelete = true;
        $powerMenuDelete = true;
        for($i=0;$i<count($role_id);$i++){
            $hasAdmin = AdminRole::where('role_id',$role_id[$i])->select('admin_role_id')->first();
            if(empty($hasAdmin)){
                $deleteRole = $this->where('role_id',$role_id[$i])->delete();//刪除角色
                if(!$deleteRole){
                   $roleDelete = false;
                   break;
                }
                $deleteRoleMenu = RoleMenu::where('role_id',$role_id[$i])->delete();//刪除角色對應菜單
                if(!$deleteRoleMenu){
                    $roleMenuDelete = false;
                    break;
                }
                $deletePowerMenu = PowerMenu::where('role_id',$role_id[$i])->delete();//刪除角色對應權限
                if(!$deletePowerMenu){
                    $powerMenuDelete = false;
                    break;
                }
            }else{
                return $this->error(__('web.existingUsers'));
            }
        }
        if($roleDelete && $roleMenuDelete && $powerMenuDelete){
            DB::commit();
            return $this->success(__('web.delete_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.delete_fail'));
        }
    }



}
