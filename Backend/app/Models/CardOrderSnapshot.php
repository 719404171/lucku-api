<?php

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CardOrderSnapshot extends Base{

    protected $table = 'order_snapshot';
    protected $primaryKey = 'snapshot_id';
    use Notifiable;
    public $timestamps = true;
    public static $modify = false;

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }

    public function getUserSnapshotAttribute($data){
        $UserSnapshot = json_decode($data,true);
        $UserSnapshot = $UserSnapshot['user_name'] ?? '';
        return self::$modify ? $UserSnapshot : $data;
    }

    public function getCouponSnapshotAttribute($data){
        $this->language = app('global_language') == "en" ? "en_" : "";
        $CouponSnapshot = json_decode($data,true);
        if(!empty($CouponSnapshot)){
            $CouponSnapshot = $CouponSnapshot[$this->language . 'coupon_name'] ?? $CouponSnapshot;
        }
        return self::$modify ? $CouponSnapshot : $data;
    }

    public function getCardSnapshotAttribute($data){
        $this->language = app('global_language') == "en" ? "en_" : "";
        $CardSnapshot = json_decode($data,true);
        $CardSnapshot = $CardSnapshot[$this->language . 'Card_name'] ?? $data;
        return self::$modify ? $CardSnapshot : $data;
    }

    protected static function boot(){
       parent::boot();
       static::addGlobalScope('order_snapshot', function (Builder $builder) {
          return $builder->where('order_snapshot.snapshot_status', '<>', -1);
       });
    }

    public function addOrderSnapshot($data){
        DB::beginTransaction();
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->insert($data);
            if(!empty($effects)){
                DB::commit();   return $effects;
            }else{
                DB::rollBack();   return false;
            }
        }catch (\Exception $e) {
            DB::rollBack();   return  $e->getMessage();
        }
    }
}
