<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/24
 * Time: 10:34
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;

class SpecsValue extends Base
{
    use Notifiable;
    protected $table = 'specs_value';
    protected $primaryKey = 'specs_value_id';
    public $timestamps = false;


}
