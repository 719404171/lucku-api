<?php

/*
 * File: InvitationCodeController.php
 * Project: NewDemo
 * Created Time: 2026-06-25 05:16:33 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-26 5:05:13 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvitationCode;
use App\Models\InvitationCodeUsageLog;
use Illuminate\Http\Request;

class InvitationCodeController extends Controller
{
    private $InvitationCode;
    private $InvitationCodeUsageLog;

    public function __construct()
    {
        $this->InvitationCode = new InvitationCode();
        $this->InvitationCodeUsageLog = new InvitationCodeUsageLog();
    }

    /**
     * 獲取邀請碼使用記錄列表
     */
    public function getUsageLogListWeb(Request $request)
    {
        $data = $request->post();
        $datas = $this->InvitationCodeUsageLog->getListWeb($data);
        return $this->success(__('web.success'), $datas);
    }

    /**
     * 獲取邀請碼列表
     */
    public function getListWeb(Request $request)
    {
        $data = $request->post();
        $limit = $data['limit'] ?? 20;
        $page = ($data['page'] - 1) * $limit;

        $model = $this->InvitationCode->orderBy('invitation_id', 'desc');

        if (!empty($data['code'])) {
            $model = $model->where('code', 'like', '%' . $data['code'] . '%');
        }

        $count = $model->count();
        $list = $model->offset($page)->limit($limit)->get()->toArray();

        return $this->success(__('web.success'), [
            'data' => $list,
            'count' => $count
        ]);
    }

    /**
     * 新增單個邀請碼
     */
    public function addWeb(Request $request)
    {
        $data = $request->post();

        if (empty($data['code'])) {
            $data['code'] = InvitationCode::generateCode();
        } elseif ($this->InvitationCode->where('code', $data['code'])->exists()) {
            return $this->error(__('web.name_exist'));
        }

        $insert = [
            'code' => $data['code'],
            'use_count' => 0,
            'max_uses' => intval($data['max_uses'] ?? 1),
            'reward_coin' => intval($data['reward_coin'] ?? 0),
            'reward_diamond' => intval($data['reward_diamond'] ?? 0),
            'status' => $data['status'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = $this->InvitationCode->insertGetId($insert);

        if ($id) {
            return $this->success(__('web.success'));
        }
        return $this->error(__('web.network_error'));
    }

    /**
     * 批量生成邀請碼
     */
    public function batchGenerateWeb(Request $request)
    {
        $data = $request->post();
        $count = intval($data['count'] ?? 10);
        $maxUses = intval($data['max_uses'] ?? 1);
        $rewardCoin = intval($data['reward_coin'] ?? 0);
        $rewardIntegral = intval($data['reward_integral'] ?? 0);
        $rewardDiamond = intval($data['reward_diamond'] ?? 0);

        if ($count < 1 || $count > 500) {
            return $this->error(__('web.param_error'));
        }

        $inserts = [];
        $now = date('Y-m-d H:i:s');

        for ($i = 0; $i < $count; $i++) {
            $inserts[] = [
                'code' => InvitationCode::generateCode(),
                'use_count' => 0,
                'max_uses' => $maxUses,
                'reward_coin' => $rewardCoin,
                'reward_diamond' => $rewardDiamond,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->InvitationCode->insert($inserts);

        return $this->success(__('web.success'), ['count' => $count]);
    }

    /**
     * 編輯邀請碼
     */
    public function editWeb(Request $request)
    {
        $data = $request->post();
        $id = $data['invitation_id'] ?? 0;
        if (empty($id)) {
            return $this->error(__('web.param_error'));
        }

        $update = [
            'max_uses' => intval($data['max_uses'] ?? 1),
            'reward_coin' => intval($data['reward_coin'] ?? 0),
            'reward_diamond' => intval($data['reward_diamond'] ?? 0),
            'status' => $data['status'] ?? 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $res = $this->InvitationCode->where('invitation_id', $id)->update($update);

        if ($res !== false) {
            return $this->success(__('web.success'));
        }
        return $this->error(__('web.network_error'));
    }

    /**
     * 刪除邀請碼
     */
    public function deleteWeb(Request $request)
    {
        $id = $request->post('invitation_id');
        if (empty($id)) {
            return $this->error(__('web.param_error'));
        }

        $this->InvitationCode->where('invitation_id', $id)->delete();

        return $this->success(__('web.success'));
    }
}
