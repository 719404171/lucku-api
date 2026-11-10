<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardDrawTask;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 查賬（對賬）
 * 抽卡對賬：每次抽卡一行，校驗扣款流水、金額與建單狀態
 * 充值對賬：複用 getRechargeLogListWeb（同表同口徑）
 */
class AuditController extends Controller
{
    protected $CardDrawTask;

    public function __construct()
    {
        $this->CardDrawTask = new CardDrawTask();
    }

    /**
     * 獲取抽卡對賬列表
     * @param Request $request
     * @return array
     */
    public function getDrawAuditListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardDrawTask->getDrawAuditListWeb($data, $language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 獲取單個抽卡批次的對賬單（付款/發卡/賬戶資金追溯）
     * @param Request $request
     * @return array
     */
    public function getDrawBillWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardDrawTask->getDrawBillWeb($data, $language);
        if (empty($datas)) {
            return $this->parametersError(__('api.param_error'));
        }
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 對賬-臨時封禁賬號（賬單異常時使用）
     * @param Request $request
     * @return array
     */
    public function banUserWeb(Request $request){
        $userId = (int)$request->post('user_id');
        $days = (int)$request->post('ban_days');
        $reason = trim((string)$request->post('ban_reason'));
        if ($userId <= 0) {
            return $this->parametersError(__('api.param_error'));
        }

        $ok = (new User())->banUserWeb($userId, $days, $reason);
        if (!$ok) {
            return $this->error(__('web.set_fail'));
        }
        return $this->success(__('web.operation_success'), (new User())->getBanInfoWeb($userId));
    }

    /**
     * 對賬-解除封禁
     * @param Request $request
     * @return array
     */
    public function unbanUserWeb(Request $request){
        $userId = (int)$request->post('user_id');
        if ($userId <= 0) {
            return $this->parametersError(__('api.param_error'));
        }

        $ok = (new User())->unbanUserWeb($userId);
        if (!$ok) {
            return $this->error(__('web.set_fail'));
        }
        return $this->success(__('web.operation_success'), (new User())->getBanInfoWeb($userId));
    }
}
