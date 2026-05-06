<?php
declare(strict_types=1);

namespace app\common\model;

class Tag extends BaseModel
{
    protected $name = 'tag';

    public function updateCount(int $tagId): void
    {
        $count = \think\facade\Db::table('software_tag')->where('tag_id', $tagId)->count();
        $this->where('id', $tagId)->update(['count' => $count]);
    }
}
