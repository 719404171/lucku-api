<?php
/*
 * File: UserMember.php
 * Project: NewDemo
 * Created Time: 2024-06-21 02:45:57 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-25 4:07:24 pm
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
 * App\Models\UserMember
 *
 */
class UserMember extends base
{
    use Notifiable;
    protected $table = 'user_member';
    protected $primaryKey = 'member_id';
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
        $this->AppDetail = ['member_id',$this->language.'member_name as member_name','member_start','member_end','member_img','member_status','login_bonus_coin','login_bonus_diamond','created_at','updated_at'];
    }


/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();

	   static::addGlobalScope('user_member', function (Builder $builder) {
		  return $builder->where('user_member.member_status', '<>', -1);
	   });
    }



/*********************************************************admin**********************************************************************/

    /**
     * 按積分計算用戶所處會員檔位的 member_end（檔位比較基準）
     * 口徑與 User::levelMember / addLoginBonus 一致：
     * - 積分低於最低檔起點 = 未定級，回傳 0
     * - 積分高於最高檔 end = 最高檔
     * - 其餘取「member_end 升序中第一個 end >= 積分」的檔位
     * @param int|float $score 用戶 user_integral_all
     * @return int
     */
    public static function memberEndByScore($score){
        $members = self::query()->get()->toArray();
        if (empty($members)) {
            return 0;
        }
        $ends = array_map(function ($m) { return (int)$m['member_end']; }, $members);
        $starts = array_map(function ($m) { return (int)$m['member_start']; }, $members);
        $score = (int)$score;
        if ($score < min($starts)) {
            return 0;
        }
        if ($score > max($ends)) {
            return (int)max($ends);
        }
        sort($ends);
        foreach ($ends as $end) {
            if ($score <= $end) {
                return (int)$end;
            }
        }
        return (int)max($ends);
    }

    /**
     * 按會員等級 ID 取該檔位的 member_end（檔位比較基準），不存在回傳 0
     * @param int $memberId
     * @return int
     */
    public static function memberEndById($memberId){
        if ((int)$memberId <= 0) {
            return 0;
        }
        return (int)(self::query()->where('member_id', (int)$memberId)->value('member_end') ?? 0);
    }

    /**
     * 批量按會員等級 ID 取檔位資料（名稱依當前語言）
     * @param array $memberIds
     * @param string $language '' 或 'en_'
     * @return array member_id => ['name'=>string,'member_start'=>int,'member_end'=>int]
     */
    public static function memberInfoByIds(array $memberIds, $language = ''){
        $ids = array_values(array_unique(array_filter(array_map('intval', $memberIds))));
        if (empty($ids)) {
            return [];
        }
        $nameField = $language === 'en_' ? 'en_member_name' : 'member_name';
        return self::query()
            ->whereIn('member_id', $ids)
            ->get(['member_id', $nameField, 'member_start', 'member_end'])
            ->keyBy('member_id')
            ->map(function ($m) use ($nameField) {
                return [
                    'name'         => (string)($m->$nameField ?? ''),
                    'member_start' => (int)($m->member_start ?? 0),
                    'member_end'   => (int)($m->member_end ?? 0),
                ];
            })
            ->toArray();
    }

    /**
     * 添加會員等級
     * @param $data
     * @return array
     */
    public function addMemberListWeb($data)
    {
        if(empty($data['member_start']) || empty($data['member_end']) || empty($data['member_img']) || empty($data['member_name']) || empty($data['en_member_name'])){
            return $this->error(__('web.param_missing'));
        }
        // 检查等级
        if ($data['member_start'] >= $data['member_end']) {
            return $this->error(__('web.param_error'));
        }
        // 检查名称是否重复
        if ($this->where('member_name', $data['member_name'])->exists()) {
            return $this->error(__('web.name_duplication'));
        }
        if ($this->where('en_member_name', $data['en_member_name'])->exists()) {
            return $this->error(__('web.name_duplication'));
        }
        DB::beginTransaction();
        $data['created_at'] = self::getDate();
        $data['updated_at'] = self::getDate();
        $insertmember = $this->insert($data);
        if($insertmember){
            DB::commit();
            return $this->success(__('web.add_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.add_fail'));
        }
    }
    /**
     * 修改會員等級
     * @param $data
     * @return array
     */
    public function editMemberListWeb($data){
        DB::beginTransaction();
        $status = true;
//        for($i=0;$i<count($data);$i++){
            if(!empty($data['member_id'])){
                $data['updated_at'] = self::getDate();
            }
            $datas = $this->updateOrInsert(['member_id'=>$data['member_id']], $data);
            if(!$datas){
                $status = false;
            }
//        }

        if($status){
            DB::commit();
            return $this->success(__('web.set_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 删除會員等級
     * @param $data
     * @return array
     */
    public function deleteMemberListWeb($data){
        if (empty($data['member_id'])) {
            return $this->error(__('web.param_missing'));
        }
        $memberId = $data['member_id'];
        DB::beginTransaction();
        try {
            $deleted = $this->where('member_id', $memberId)
                ->update([
                    'member_status' => -1,
                    'updated_at' => self::getDate()
                ]);

            if ($deleted) {
                DB::commit();
                return $this->success(__('web.delete_success'));
            } else {
                DB::rollBack();
                return $this->error(__('web.delete_fail'));
            }

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error(__('web.delete_fail'));
        }
    }

/**********************************************************api**********************************************************************/
    /**
     * @return mixed
     * 獲取會員等級信息
     */
    public function getUserMemberrApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('member_status','<>',-1);
        $effects=array();
        // 按檔位區間升序返回：member_id 順序 ≠ 等級順序（初心是後加檔位但區間最低），後台也可能調整區間
        $info = $this ->where($where)->select($this->AppDetail)->orderBy('member_end','asc')->orderBy('member_id','asc')->paginate($limit , $page);
        $effects=$info->items();
        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }

    /**
     * 獲取所有會員等級（不分頁，API用）
     */
    public function getMemberApi()
    {
        $list = $this->where('member_status', 1)
            ->select($this->AppDetail)
            ->orderBy('member_end', 'asc')
            ->orderBy('member_id', 'asc')
            ->get();
        return $this->success(__('api.list_success'), $list);
    }

/**************************************************common(api和admin共用)***********************************************************/

}
