<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/26
 * Time: 15:46
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
class ProductAlbum extends Base
{
    use Notifiable;
    protected $table = 'product_album';
    protected $primaryKey = 'product_album_id';
    public $timestamps = false;

}
