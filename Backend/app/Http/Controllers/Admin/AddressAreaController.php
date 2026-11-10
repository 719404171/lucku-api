<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/29
 * Time: 14:29
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddressArea;
use Illuminate\Http\Request;

class AddressAreaController  extends Controller
{

    protected $AddressArea;

    public function __construct()
    {
        $this->AddressArea = new AddressArea();
    }

    public function getAddressAreaWeb(){
        $results = $this->AddressArea->getAddressAreaApi();
        return $results;
    }

}
