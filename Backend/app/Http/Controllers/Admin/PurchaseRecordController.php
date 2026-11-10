<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/28
 * Time: 9:21
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\PurchaseRecord;
use App\Models\WastageRecord;
use Illuminate\Http\Request;

class PurchaseRecordController extends Controller
{
    protected $PurchaseRecord;
    protected $WastageRecord;

    public function __construct()
    {
        $this->PurchaseRecord = new PurchaseRecord();
        $this->WastageRecord = new WastageRecord();
    }

    /**
     * 獲取損耗記錄
     * @param Request $request
     * @return array
     */
    public function getWastageRecordListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->WastageRecord->getWastageRecordListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加損耗記錄
     * @param Request $request
     * @return array
     */
    public function addWastageRecordListWeb(Request $request){
        $wastageRecord = $request->post();
        $data = $this->WastageRecord->addWastageRecordListWeb($wastageRecord);
        return $data;
    }

    /**
     * 编辑損耗記錄
     * @param Request $request
     * @return array
     */
    public function editWastageRecordListWeb(Request $request){
        $wastageRecord = $request->post();
        $data = $this->WastageRecord->editWastageRecordListWeb($wastageRecord);
        return $data;
    }

    /**
     * 删除損耗記錄
     * @param Request $request
     * @return array
     */
    public function deleteWastageRecordListWeb(Request $request){
        $wastageID = $request->post('wastage_id');
        $data = $this->WastageRecord->deleteWastageRecordListWeb($wastageID);
        return $data;
    }

    /**
     * 获取采购记录
     * @param Request $request
     * @return array
     */
    public function getPurchaseRecordListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->PurchaseRecord->getPurchaseRecordListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/编辑采购记录
     * @param Request $request
     * @return array
     */
    public function handlePurchaseRecordListWeb(Request $request){
        $purchaseRecord = $request->post();
        $data = $this->PurchaseRecord->handlePurchaseRecordListWeb($purchaseRecord);
        return $data;
    }

    /**
     * 删除采购记录
     * @param Request $request
     * @return array
     */
    public function deletePurchaseRecordListWeb(Request $request){
        $purchaseRecordID = $request->post('purchase_id');
        $data = $this->PurchaseRecord->deletePurchaseRecordListWeb($purchaseRecordID);
        return $data;
    }

    /**
     * 采购邮箱发送
     * @param Request $request
     * @return array
     */
    public function purchaseRecordEmailWeb(Request $request){
        $purchaseID = $request->post('purchase_id');
        $language = self::getLanguageWeb();
        $datas = $this->PurchaseRecord->purchaseRecordEmailWeb($purchaseID,$language);
        return $datas;
    }

    /**
     * 采购進倉/退倉
     * @param Request $request
     * @return array
     */
    public function purchaseWarehouseWeb(Request $request){
        $token = $request->header('TOKEN');
        $purchaseID = $request->post('purchase_id');
        $purchaseType = $request->post('purchase_type');
        $data = $this->PurchaseRecord->purchaseWarehouseWeb($purchaseID,$purchaseType,$token);
        return $data;
    }

}
