<?php
declare(strict_types=1);

namespace app\common\model;

class Attachment extends BaseModel
{
    protected $name = 'attachment';
    protected $updateTime = false;

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id', 'id');
    }
}
