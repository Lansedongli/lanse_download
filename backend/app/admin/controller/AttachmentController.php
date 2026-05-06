<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\model\Attachment as AttachmentModel;
use think\App;
use think\facade\Validate;

/**
 * 附件管理控制器
 * 上传、删除、列表
 */
class AttachmentController extends BaseController
{
    private AttachmentModel $attachmentModel;
    private string $uploadRoot;

    public function __construct(App $app, AttachmentModel $attachmentModel)
    {
        parent::__construct($app);
        $this->attachmentModel = $attachmentModel;
        $this->uploadRoot = public_path('uploads');
    }

    /**
     * 附件列表（按软件ID筛选）
     * GET /admin/api/attachments?software_id=1
     */
    public function index(): \think\Response
    {
        $softwareId = (int) $this->request->get('software_id', 0);

        $attachments = $this->attachmentModel
            ->when($softwareId > 0, fn($q) => $q->where('software_id', $softwareId))
            ->order('sort', 'asc')
            ->select();

        return $this->success($attachments->toArray());
    }

    /**
     * 上传附件
     * POST /admin/api/attachments/upload
     * @body software_id int    软件ID
     * @body file        file   上传文件
     */
    public function upload(): \think\Response
    {
        $softwareId = (int) $this->request->post('software_id', 0);
        $file = $this->request->file('file');

        if (!$file) {
            return $this->error('请选择要上传的文件', 422);
        }
        if ($softwareId <= 0) {
            return $this->error('软件ID不能为空', 422);
        }

        // 验证文件
        $validate = Validate::rule([
            'file' => 'fileSize:104857600|fileExt:zip,rar,7z,tar,gz,exe,msi,apk,iso,pdf,doc,docx,ppt,pptx,txt',
        ])->message([
            'file.fileSize' => '文件大小不能超过100MB',
            'file.fileExt'  => '不支持的文件类型',
        ]);

        if (!$validate->check(['file' => $file])) {
            return $this->error($validate->getError(), 422);
        }

        // 按日期分目录
        $dateDir = date('Y/m/d');
        $savePath = $this->uploadRoot . '/' . $dateDir;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        // 生成唯一文件名
        $originalName = $file->getOriginalName();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = md5(uniqid('edown_', true)) . '.' . $ext;

        // 移动文件
        $fileInfo = $file->move($savePath, $safeName);

        if (!$fileInfo) {
            return $this->error('文件上传失败');
        }

        $relativePath = 'uploads/' . $dateDir . '/' . $safeName;
        $absolutePath = $savePath . '/' . $safeName;

        // 写入数据库
        $attachment = new AttachmentModel();
        $attachment->software_id   = $softwareId;
        $attachment->name          = $originalName;
        $attachment->file_path     = $absolutePath;
        $attachment->file_url      = '/' . $relativePath;
        $attachment->file_size     = filesize($absolutePath);
        $attachment->file_md5      = md5_file($absolutePath);
        $attachment->file_ext      = $ext;
        $attachment->mime_type     = mime_content_type($absolutePath) ?: 'application/octet-stream';
        $attachment->sort          = (int) $this->request->post('sort', 0);
        $attachment->save();

        return $this->success([
            'id'        => $attachment->id,
            'name'      => $attachment->name,
            'file_url'  => $attachment->file_url,
            'file_size' => $attachment->file_size,
            'file_ext'  => $attachment->file_ext,
        ], '上传成功');
    }

    /**
     * 删除附件
     * DELETE /admin/api/attachments/<id>
     */
    public function delete(int $id): \think\Response
    {
        $attachment = $this->attachmentModel->find($id);
        if (!$attachment) {
            return $this->error('附件不存在', 404);
        }

        // 尝试删除物理文件
        if (file_exists($attachment->file_path)) {
            @unlink($attachment->file_path);
        }

        $attachment->delete();

        return $this->success([], '附件已删除');
    }

    /**
     * 更新附件排序
     * PUT /admin/api/attachments/<id>/sort
     */
    public function updateSort(int $id): \think\Response
    {
        $attachment = $this->attachmentModel->find($id);
        if (!$attachment) {
            return $this->error('附件不存在', 404);
        }

        $sort = (int) $this->request->put('sort', 0);
        $attachment->sort = $sort;
        $attachment->save();

        return $this->success($attachment->toArray(), '排序更新成功');
    }
}
