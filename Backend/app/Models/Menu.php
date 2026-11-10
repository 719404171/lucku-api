<?php
/*
 * File: Menu.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-24 9:35:04 am
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


class Menu extends Base{

    use Notifiable;
    protected $table = 'menu';
    protected $primaryKey = 'menu_id';
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
     * 获取所有菜单和權限
     * @param $language
     * @return array
     */
    public function getAllMenuWeb($language){
        $data = Menu::where('menu.status',1)
            ->select('menu.menu_id','menu.'.$language.'menu_name as menu_name','menu.parent','menu.status','menu.icon','menu.path','menu.redirect')
            ->get()->toArray();
        $menuList = Base::getTree($data);
        for($i=0;$i<count($menuList);$i++){
            for($j=0;$j<count($menuList[$i]['children']);$j++){
//                $menuList[$i]['children'][$j]['children'] = Power::select('power.type',DB::raw('1 as status'), DB::raw('CONCAT('.$menuList[$i]['children'][$j]['menu_id'].',"-",power.power_id) as menu_id'))
//                    ->get()->toArray();
                $menuList[$i]['children'][$j]['children'] = PowerMenu::join('power','power_menu.power_id','=','power.power_id')
                    ->where('power_menu.menu_id',$menuList[$i]['children'][$j]['menu_id'])
                    ->where('power_menu.status',1)
                    ->where('power_menu.role_id',1)
                    ->select('power.type',DB::raw('1 as status'),DB::raw('CONCAT('.$menuList[$i]['children'][$j]['menu_id'].',"-",power.power_id) as menu_id'))
                    ->get()->toArray();
            }
        }
        return $menuList;
    }




}
