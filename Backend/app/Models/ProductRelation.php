<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/26
 * Time: 14:04
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
class ProductRelation extends Base
{
    use Notifiable;
    protected $table = 'product_relation';
    protected $primaryKey = 'product_relation_id';
    public $timestamps = false;

}
