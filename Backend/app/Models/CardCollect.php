<?php
/*
 * File: UserCollect.php
 * Project: NewDemo
 * Created Time: 2024-07-02 03:31:49 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-04 3:58:14 pm
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
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\CardCollect
 *
 */
class CardCollect extends Base{

    
    use Notifiable;
    protected $table = 'card_collect';
    protected $primaryKey = 'collect_id';
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

/*********************************************************关联**********************************************************************/ 


/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/
    /**
     * @param $data
     * @return mixed
     * 收藏/取消收藏卡包
     */
    public function Collectcard($data){
        DB::beginTransaction();
        try {
            $collect_effects=null;
            $Uncollect_effects=null;
            $where[] = array('users.user_id','=',$data['user_id']);
            $where[] = array('collect.card_sort_id','=',$data['card_sort_id']);
            $datas = $this->from('users as users')->join('card_collect as collect','collect.user_id','=','users.user_id')->where($where)->first();
            if(empty($datas)){
                $info = [
                    'user_id' => $data['user_id'],
                    'card_sort_id' => $data['card_sort_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $collect_effects= $this->insert($info);
            }else{
                $Uncollect_effects = $this->where('collect_id',$datas['collect_id'])->delete();
            }
            if(!empty($collect_effects)){
                DB::commit();
                return $this->success( __('api.collect_success'));
            }
            if(!empty($Uncollect_effects)){
                DB::commit();
                return $this->success( __('api.cancel_collect_success'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.collect_fail') , $e->getMessage());
        }
    }

    /**
     * 獲取用戶收藏列表（卡包）
     * @param $data
     * @return mixed
     */
    public function getUsersCollect($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $results = $this->from('users as u')
            ->join('card_collect as cc', 'cc.user_id', '=', 'u.user_id')
            ->join('card_sort as cs', 'cs.card_sort_id', '=', 'cc.card_sort_id')
            ->where('u.user_id', $data['user_id'])
            ->where('cs.status', 1)
            ->where('cs.draw_enable', '<>', 0)
            ->offset($page)->limit($limit)
            ->orderBy('cc.created_at', 'desc')
            ->select(
                'u.user_id', 'u.user_name', 'u.user_phone', 'u.user_avatar', 'u.user_balance',
                'cc.*',
                'cs.'.$this->language.'card_sort_name as card_sort_name',
                'cs.card_sort_img',
                'cs.card_draw_price'
            )
            ->selectRaw('(SELECT COUNT(*) FROM card WHERE card.card_sort_id = cs.card_sort_id AND card.card_status <> -1 AND card.card_stock = 0) as drawn_count')
            ->selectRaw('(SELECT COUNT(*) FROM card WHERE card.card_sort_id = cs.card_sort_id AND card.card_status <> -1 AND card.card_stock > 0) as undrawn_count')
            ->selectRaw('(SELECT COUNT(*) FROM card WHERE card.card_sort_id = cs.card_sort_id AND card.card_status <> -1) as total_cards')
            ->get();

        $num = count($results);
        if($num > 0){
            $output = [
                'user_id' => $results[0]->user_id,
                'user_name' => $results[0]->user_name,
                'user_phone' => $results[0]->user_phone,
                'user_avatar' => $results[0]->user_avatar,
                'user_balance' => $results[0]->user_balance,
                'get_card_collect_num' => $num,
                'getCardCollect' => $results->toArray()
            ];
            return $this->success(__('api.list_success'), $output);
        }else{
            return $this->success(__('api.list_null'), $results);
        }
    }

/**************************************************common(api和admin共用)***********************************************************/


}
