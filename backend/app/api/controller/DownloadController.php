<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\service\DownloadService;
use think\App;
use think\facade\Validate;

/**
 * 下载核心控制器
 * 处理获取下载链接、文件流输出
 * 部分接口需认证 (AuthMiddleware)
 */
class DownloadController extends BaseController
{
    private readonly DownloadService $downloadService;

    public function __construct(App $app, DownloadService $downloadService)
    {
        parent::__construct($app);
        $this->downloadService = $downloadService;

        // 注入 AuthMiddleware 设置的用户信息
        if (isset($this->request->user)) {
            $this->currentUser = $this->request->user;
        }
    }

    /**
     * 获取下载链接（需登录AuthMiddleware）
     * POST /api/v1/download/url
     *
     * @body software_id   int 软件ID
     * @body attachment_id int 附件ID（可选，默认取第一个）
     */
    public function getUrl(): \think\Response
    {
        $data = $this->request->post();

        $validate = Validate::rule([
            'software_id'   => 'require|integer|>:0',
            'attachment_id' => 'integer|>=:0',
        ])->message([
            'software_id.require'  => '软件ID不能为空',
            'software_id.integer'  => '软件ID必须为整数',
            'attachment_id.integer' => '附件ID必须为整数',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError(), 422);
        }

        $softwareId   = (int) $data['software_id'];
        $attachmentId = (int) ($data['attachment_id'] ?? 0);
        $userId       = $this->userId();
        $ip           = $this->request->ip();

        $result = $this->downloadService->getDownloadUrl(
            $softwareId,
            $attachmentId,
            $userId,
            $ip
        );

        if (!$result['success']) {
            return $this->error($result['message'], 403);
        }

        return $this->success([
            'download_url' => $result['download_url'],
            'file_name'    => $result['file_name'],
            'file_size'    => $result['file_size'],
        ]);
    }

    /**
     * 文件下载输出（验证token后stream文件）
     * GET /api/v1/download/file?token=xxx
     */
    public function download(): \think\Response
    {
        $token = $this->request->get('token', '');

        if (empty($token)) {
            return $this->error('下载令牌不能为空', 422);
        }

        $fileInfo = $this->downloadService->serveFile($token);

        if ($fileInfo === null) {
            return $this->error('下载链接无效或已过期', 403);
        }

        // 检查文件是否存在
        if (!file_exists($fileInfo['path'])) {
            return $this->error('文件不存在', 404);
        }

        // 流式输出文件
        return download(
            $fileInfo['path'],
            $fileInfo['name']
        )->mimeType($fileInfo['mime'] ?? 'application/octet-stream');
    }
}
