<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 下载对象（软件）模型
 */
class Software extends BaseModel
{
    protected $name = 'software';
    protected $deleteTime = 'deleted_at';

    /** @var array JSON字段 */
    protected $json = ['images'];

    /** @var array 追加属性 */
    protected $append = ['category_name', 'type_name'];

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 关联附件
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'software_id', 'id');
    }

    /**
     * 关联标签
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'software_tag', 'tag_id', 'software_id');
    }

    public function getCategoryNameAttr($value, $data): string
    {
        return Category::where('id', $data['category_id'] ?? 0)->value('name') ?? '';
    }

    public function getTypeNameAttr($value, $data): string
    {
        return SoftwareType::where('id', $data['type_id'] ?? 0)->value('name') ?? '';
    }

    /**
     * 增加下载计数
     */
    public static function incDownloadCount(int $id): void
    {
        self::where('id', $id)->inc('download_count')->update();
    }

    /**
     * 热门列表
     */
    public function scopeHot($query, int $limit = 10)
    {
        return $query->where('status', 1)->order('download_count desc')->limit($limit);
    }

    /**
     * 推荐列表
     */
    public function scopeRecommend($query, int $limit = 10)
    {
        return $query->where('is_recommend', 1)->where('status', 1)->order('sort asc')->limit($limit);
    }
}
