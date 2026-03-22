<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\validate\NoticeValidate;
use app\common\model\notice\NoticeRecord;
use think\response\Json;

class NoticeController extends BaseApiController
{
    public function lists(): Json
    {
        $params = (new NoticeValidate())->get()->goCheck('lists');
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(50, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = NoticeRecord::where([
            'user_id' => $this->userId,
        ])->whereNull('delete_time');

        $count = (clone $query)->count();
        $lists = $query->field([
                'id',
                'title',
                'content',
                'scene_id',
                'read',
                'extra',
                'create_time',
            ])
            ->order(['id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $extra = json_decode((string)($item['extra'] ?? ''), true);
            $item['extra_data'] = is_array($extra) ? $extra : [];
        }

        $unreadCount = NoticeRecord::where([
            'user_id' => $this->userId,
            'read' => 0,
        ])->whereNull('delete_time')->count();

        return $this->data([
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'unread_count' => (int)$unreadCount,
        ]);
    }

    public function read(): Json
    {
        $params = (new NoticeValidate())->post()->goCheck('read');
        NoticeRecord::update([
            'id' => (int)$params['id'],
            'read' => 1,
            'update_time' => time(),
        ], [
            'id' => (int)$params['id'],
            'user_id' => $this->userId,
        ]);

        return $this->success('已标记为已读', [], 1, 1);
    }

    public function readAll(): Json
    {
        NoticeRecord::where([
            'user_id' => $this->userId,
            'read' => 0,
        ])->whereNull('delete_time')->update([
            'read' => 1,
            'update_time' => time(),
        ]);

        return $this->success('已全部标记为已读', [], 1, 1);
    }
}
