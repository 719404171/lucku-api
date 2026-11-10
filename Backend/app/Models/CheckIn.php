<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/11/10
 * Time: 10:09
 */

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
class CheckIn extends Base
{
    use Notifiable;
    protected $table = 'check_in';
    protected $primaryKey = 'check_id';

}
