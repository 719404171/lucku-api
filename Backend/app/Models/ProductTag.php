<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/26
 * Time: 16:51
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
class ProductTag extends Base
{
    use Notifiable;
    protected $table = 'product_tag';
    protected $primaryKey = 'product_tag_id';
    public $timestamps = false;

}
