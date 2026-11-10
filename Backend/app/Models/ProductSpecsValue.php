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
class ProductSpecsValue extends Base
{
    use Notifiable;
    protected $table = 'product_specs_value';
    protected $primaryKey = 'product_specs_value_id';
    public $timestamps = false;

}
