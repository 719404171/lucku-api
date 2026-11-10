<?php
/*
 * File: UserMemberController.php
 * Project: NewDemo
 * Created Time: 2024-07-02 09:17:20 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-24 4:16:19 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserMember;
use Illuminate\Http\Request;

class UserMemberController
 extends Controller
{
    protected $UserMember;

    public function __construct()
    {
        $this->UserMember = new UserMember();
    }

    /**
     * 獲取會員等級列表
     * @param Request $request
     * @return array
     */
    public function getMemberListWeb(Request $request){
        $datas = $request->post();
        $limit = empty($datas['limit']) ? 10 : $datas['limit'];
        $page = empty($datas['page']) ? 1 : $datas['page'];
        // 按檔位區間升序（member_id 順序 ≠ 等級順序，初心是後加檔位但區間最低）
        $paginator = UserMember::where('member_status', '!=', -1)
            ->orderBy('member_end', 'asc')
            ->orderBy('member_id', 'asc')
            ->paginate($limit, ['*'], 'page', $page);
        $results = [
            'count' => $paginator->total(),
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage()
        ];
        if(empty($data)){
            $data = [];
        }
        return $this->success(__('web.success'), $results);
    }

    /**
     * 添加會員等級
     * @param Request $request
     * @return array
     */
    public function addMemberListWeb(Request $request){
        $data = $request->post();
        $state = $this->UserMember->addMemberListWeb($data);
        return $state;
    }

    /**
     * 修改會員等級
     * @param Request $request
     * @return array
     */
    public function editMemberListWeb(Request $request){
        $data = $request->post();
        $state = $this->UserMember->editMemberListWeb($data);
        return $state;
    }

    /**
     * 删除會員等級
     * @param Request $request
     * @return array
     */
    public function deleteMemberListWeb(Request $request){
        $data = $request->post();
        $state = $this->UserMember->deleteMemberListWeb($data);
        return $state;
    }
}
