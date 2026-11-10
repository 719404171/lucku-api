<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/17
 * Time: 14:35
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $Role;

    public function __construct()
    {
        $this->Role = new Role();
    }

    /**
     * 获取角色列表
     * @param Request $request
     * @return array
     */
    public function getRoleListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Role->getRoleListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 获取角色權限
     * @param Request $request
     * @return array
     */
    public function getRolePowerWeb(Request $request){
        $role_id = $request->post('role_id');
        $language = self::getLanguageWeb();
        $data = $this->Role->getRolePowerWeb($role_id,$language);
        return $this->success(__('web.success'),$data);
    }

    /**
     * 新增角色權限
     * @param Request $request
     * @return array
     */
    public function addRolePower(Request $request){
        $menu = $request->post('menu');
        $power = $request->post('power');
        $role_name = $request->except(['menu','power']);
        $data = $this->Role->addRolePower($role_name,$menu,$power);
        return $data;
    }

    /**
     * 编辑角色權限
     * @param Request $request
     * @return array
     */
    public function editRolePower(Request $request){
        $menu = $request->post('menu');
        $power = $request->post('power');
        $role = $request->post('role');
        $data = $this->Role->editRolePower($role,$menu,$power);
        return $data;
    }

    /**
     * 删除角色
     * @param Request $request
     * @return array
     */
    public function deleteRolePower(Request $request){
        $role_id = $request->post('roleID');
        $data = $this->Role->deleteRolePower($role_id);
        return $data;
    }

    /**
     * 获取所有角色
     * @return array
     */
    public function getAllRoleWeb(){
        $language = self::getLanguageWeb();
        $data = $this->Role->where('role_id','<>',1)->select('role_id',$language.'role_name')->get()->toArray();
        return $this->success(__('web.success'),$data);
    }

}
