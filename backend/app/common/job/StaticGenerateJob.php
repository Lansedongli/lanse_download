<?php
declare(strict_types=1);

namespace app\common\job;

use app\common\service\StaticGenerateService;
use think\facade\Log;
use think\queue\Job;

/**
 * 静态化异步生成任务
 * 通过 Redis 队列异步生成静态页面
 *
 * 用法:
 *   Queue::push(StaticGenerateJob::class, ['type' => 'homepage', 'id' => 0], 'static');
 *   Queue::push(StaticGenerateJob::class, ['type' => 'category', 'id' => 1], 'static');
 *   Queue::push(StaticGenerateJob::class, ['type' => 'detail', 'id' => 100], 'static');
 */
class StaticGenerateJob
{
    /**
     * @param Job    $job  队列任务实例
     * @param array  $data ['type' => 'homepage|category|detail|batch', 'id' => int]
     */
    public function fire(Job $job, array $data): void
    {
        $type = $data['type'] ?? '';
        $id   = (int) ($data['id'] ?? 0);

        try {
            $service = app(StaticGenerateService::class);
            $result = $service->generateByType($type, $id);

            if ($result['success'] ?? false) {
                Log::info("静态化任务完成 [{$type}:{$id}]");
                $job->delete();
            } else {
                Log::warning("静态化任务失败 [{$type}:{$id}]: " . ($result['message'] ?? '未知错误'));
                // 失败重试最多3次
                if ($job->attempts() >= 3) {
                    Log::error("静态化任务已达最大重试次数 [{$type}:{$id}]，移至失败队列");
                    $job->delete();
                    // 可选：记录到 failed_jobs 表
                } else {
                    $job->release(10); // 10秒后重试
                }
            }
        } catch (\Throwable $e) {
            Log::error("静态化任务异常 [{$type}:{$id}]: " . $e->getMessage());
            if ($job->attempts() >= 3) {
                $job->delete();
            } else {
                $job->release(30);
            }
        }
    }
}
