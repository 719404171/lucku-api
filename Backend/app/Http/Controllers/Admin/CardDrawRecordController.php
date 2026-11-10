<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardDrawTask;
use App\Models\CardOrderDetails;
use Illuminate\Http\Request;

/**
 * 抽卡記錄：批次（card_draw_tasks）+ 明細（抽卡訂單逐張卡）
 * 以及「卡牌多用戶抽取檢測」（同一張卡被多個用戶抽到的實時監測與歷史回溯）
 */
class CardDrawRecordController extends Controller
{
    protected $CardDrawTask;
    protected $CardOrderDetails;

    public function __construct()
    {
        $this->CardDrawTask = new CardDrawTask();
        $this->CardOrderDetails = new CardOrderDetails();
    }

    /**
     * 獲取抽卡批次列表
     * @param Request $request
     * @return array
     */
    public function getDrawTaskListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardDrawTask->getDrawTaskListWeb($data, $language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 獲取抽卡明細列表（每張卡一行）
     * @param Request $request
     * @return array
     */
    public function getDrawDetailListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardOrderDetails->getDrawDetailListWeb($data, $language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 卡牌多用戶抽取檢測-列表（同一張卡被多個用戶抽到）
     * 支持卡牌ID/名稱、用戶ID/暱稱/郵箱、時間範圍檢索；
     * log_action 非空時寫入操作日誌（查詢審計）；is_export=1 時記為導出。
     * @param Request $request
     * @return array
     */
    public function getMultiDrawCheckListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardOrderDetails->getMultiDrawCheckListWeb($data, $language);

        if (!empty($data['is_export'])) {
            $this->CardOrderDetails->writeMultiDrawAuditLog($request->header('TOKEN'), '導出', $this->buildMultiDrawFilterText($data));
        } elseif (!empty($data['log_action'])) {
            $this->CardOrderDetails->writeMultiDrawAuditLog($request->header('TOKEN'), $data['log_action'], $this->buildMultiDrawFilterText($data));
        }
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 卡牌多用戶抽取檢測-卡片詳情（完整抽取鏈路，精確到秒）
     * @param Request $request
     * @return array
     */
    public function getMultiDrawCheckDetailWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardOrderDetails->getMultiDrawCheckDetailWeb($data, $language);
        if (empty($datas)) {
            return $this->error(__('web.param_error'));
        }
        $cardName = $datas['card']['card_name'] ?? '';
        $this->CardOrderDetails->writeMultiDrawAuditLog(
            $request->header('TOKEN'),
            '查看詳情',
            '卡牌ID=' . (int)($data['card_id'] ?? 0) . ($cardName !== '' ? '（' . $cardName . '）' : '')
        );
        return $this->success(__('web.success'), $datas);
    }

    /**
     * 拼接篩選條件文案（寫入操作日誌，便於審計回溯）
     * @param array $data
     * @return string
     */
    private function buildMultiDrawFilterText($data){
        $parts = [];
        if (!empty($data['card_id'])) {
            $parts[] = '卡牌ID=' . (int)$data['card_id'];
        }
        if (!empty($data['card_name'])) {
            $parts[] = '卡牌名稱=' . $data['card_name'];
        }
        if (!empty($data['user_id'])) {
            $parts[] = '用戶ID=' . (int)$data['user_id'];
        }
        if (!empty($data['user_name'])) {
            $parts[] = '用戶暱稱=' . $data['user_name'];
        }
        if (!empty($data['user_email'])) {
            $parts[] = '用戶郵箱=' . $data['user_email'];
        }
        if (!empty($data['time']) && !empty($data['time'][0])) {
            $parts[] = '時間=' . $data['time'][0] . '~' . ($data['time'][1] ?? '');
        }
        return $parts ? implode('，', $parts) : '全部';
    }
}
