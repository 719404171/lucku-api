<?php
/*
 * 郵件發送日誌後台
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailLogController extends \App\Http\Controllers\Controller
{
    /**
     * 獲取郵件發送日誌列表（JOIN users 顯示用戶信息）
     */
    public function getEmailSendLogListWeb(Request $request)
    {
        $data = $request->post();
        $limit = (int)($data['limit'] ?? 10);
        $page  = max(1, (int)($data['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $query = DB::table('email_send_log as l')
            ->leftJoin('users', 'users.user_email', '=', 'l.to_email')
            ->select(
                'l.log_id',
                'l.to_email',
                'l.subject',
                'l.mailer',
                'l.attempt',
                'l.status',
                'l.error_msg',
                'l.duration_ms',
                'l.created_at',
                'users.user_id',
                'users.user_name',
                'users.user_phone',
                'users.user_status'
            );

        // 搜索：郵箱 / 用戶名 / 用戶ID
        if (!empty($data['multi'])) {
            $kw = $data['multi'];
            $query->where(function ($q) use ($kw) {
                $q->where('l.to_email', 'like', "%{$kw}%")
                  ->orWhere('users.user_name', 'like', "%{$kw}%")
                  ->orWhere('users.user_id', '=', (int)$kw);
            });
        }

        // 狀態篩選
        if (isset($data['status']) && $data['status'] !== '' && $data['status'] !== null) {
            $query->where('l.status', (int)$data['status']);
        }

        // mailer 篩選
        if (!empty($data['mailer'])) {
            $query->where('l.mailer', $data['mailer']);
        }

        $total = $query->count();

        $list = $query->orderByDesc('l.log_id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'log_id'          => $row->log_id,
                    'to_email'        => $row->to_email,
                    'subject'         => $row->subject,
                    'mailer'          => $row->mailer,
                    'attempt'         => $row->attempt,
                    'status'          => $row->status,
                    'error_msg'       => $row->error_msg,
                    'duration_ms'     => $row->duration_ms,
                    'created_at'      => $row->created_at,
                    'user_id'         => $row->user_id,
                    'user_name'       => $row->user_name,
                    'user_phone'      => $row->user_phone,
                    'user_status'     => $row->user_status,
                ];
            })
            ->toArray();

        return $this->success('ok', [
            'data'  => $list,
            'count' => $total,
        ]);
    }
}
