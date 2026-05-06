<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * 静态化生成服务
 * 将动态页面渲染为静态HTML文件，提升访问速度、减轻服务器压力
 *
 * 输出路径: public/html/
 *   - 首页:        index.html
 *   - 分类页:      category/{id}.html
 *   - 详情页:      detail/{id}.html
 */
class StaticGenerateService
{
    /** @var string 静态文件根目录 */
    private string $htmlDir;

    /** @var string Redis状态Key */
    private const STATUS_KEY = 'static:generate:status';

    public function __construct()
    {
        $this->htmlDir = public_path('html');
        // 确保输出目录存在
        $this->ensureDir($this->htmlDir);
    }

    /**
     * 生成首页HTML
     * 内容: 站点信息 + 热门推荐软件 + 最新公告 + 分类树
     */
    public function generateHomepage(): array
    {
        $result = ['type' => 'homepage', 'success' => true, 'file' => '', 'message' => ''];

        try {
            // 1. 读取站点配置 (system_config表, 无status字段)
            $configs = Db::table('system_config')
                ->column('value', 'key');

            // 2. 最新软件: 热门 + 推荐
            $hotSoftware = Db::table('software')
                ->where('status', 1)
                ->where('is_hot', 1)
                ->order('download_count desc')
                ->limit(10)
                ->select()
                ->toArray();

            $recommendSoftware = Db::table('software')
                ->where('status', 1)
                ->where('is_recommend', 1)
                ->order('sort asc')
                ->limit(10)
                ->select()
                ->toArray();

            // 3. 最新公告
            $announcements = Db::table('announcement')
                ->where('status', 1)
                ->order('is_sticky desc, sort asc, created_at desc')
                ->limit(5)
                ->select()
                ->toArray();

            // 4. 分类树 (仅启用的顶级分类及子分类)
            $categories = Db::table('category')
                ->where('status', 1)
                ->order('sort asc, id asc')
                ->select()
                ->toArray();
            $categoryTree = $this->buildTree($categories, 0);

            // 5. 模板变量
            $templateVars = Db::table('template_var')
                ->column('value', 'name');

            // 加载模板变量到配置中
            foreach ($templateVars as $name => $value) {
                if (!isset($configs[$name])) {
                    $configs[$name] = $value;
                }
            }

            // 渲染HTML
            $html = $this->renderHomepageHtml($configs, $hotSoftware, $recommendSoftware, $announcements, $categoryTree);

            // 写入文件
            $file = $this->htmlDir . '/index.html';
            file_put_contents($file, $html);

            $result['file'] = $file;
            $result['message'] = '首页生成成功';
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['message'] = '首页生成失败: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * 生成分类页HTML
     * 显示该分类下软件分页列表 (每页20条)
     */
    public function generateCategory(int $catId): array
    {
        $result = ['type' => 'category', 'id' => $catId, 'success' => true, 'file' => '', 'message' => ''];

        try {
            // 获取分类信息
            $category = Db::table('category')->find($catId);
            if (!$category) {
                $result['success'] = false;
                $result['message'] = "分类不存在: id={$catId}";
                return $result;
            }

            // 获取该分类及所有子分类ID
            $childIds = $this->getChildCategoryIds($catId);

            $page  = 1;
            $limit = 20;
            $total = Db::table('software')
                ->whereIn('category_id', $childIds)
                ->where('status', 1)
                ->count();

            $totalPages = (int) ceil($total / $limit);

            // 按分页生成多页
            $dir = $this->htmlDir . '/category';
            $this->ensureDir($dir);

            $pagesGenerated = 0;
            for ($p = 1; $p <= $totalPages; $p++) {
                $softwareList = Db::table('software')
                    ->whereIn('category_id', $childIds)
                    ->where('status', 1)
                    ->order('sort asc, id desc')
                    ->page($p, $limit)
                    ->select()
                    ->toArray();

                $html = $this->renderCategoryHtml($category, $softwareList, $p, $totalPages, $total);
                $file = $dir . '/' . ($p === 1 ? "{$catId}.html" : "{$catId}_{$p}.html");
                file_put_contents($file, $html);
                $pagesGenerated++;
            }

            $result['file'] = $dir . "/{$catId}.html";
            $result['message'] = "分类页生成成功，共 {$pagesGenerated} 页";
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['message'] = '分类页生成失败: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * 生成软件详情页HTML
     * 内容: 软件详情 + 评论列表
     */
    public function generateDetail(int $softId): array
    {
        $result = ['type' => 'detail', 'id' => $softId, 'success' => true, 'file' => '', 'message' => ''];

        try {
            // 获取软件详情
            $software = Db::table('software')->find($softId);
            if (!$software) {
                $result['success'] = false;
                $result['message'] = "软件不存在: id={$softId}";
                return $result;
            }

            // 获取所属分类
            $category = Db::table('category')->find($software['category_id']);

            // 获取附件列表
            $attachments = Db::table('attachment')
                ->where('software_id', $softId)
                ->order('sort asc')
                ->select()
                ->toArray();

            // 获取评论 (已通过审核的)
            $comments = Db::table('comment')
                ->where('software_id', $softId)
                ->where('status', 1)
                ->order('id desc')
                ->limit(50)
                ->select()
                ->toArray();

            // 获取标签
            $tagIds = Db::table('software_tag')
                ->where('software_id', $softId)
                ->column('tag_id');
            $tags = [];
            if (!empty($tagIds)) {
                $tags = Db::table('tag')
                    ->whereIn('id', $tagIds)
                    ->select()
                    ->toArray();
            }

            // 获取相关推荐 (同分类下的其他软件)
            $relatedSoftware = Db::table('software')
                ->where('category_id', $software['category_id'])
                ->where('status', 1)
                ->where('id', '<>', $softId)
                ->order('download_count desc')
                ->limit(10)
                ->select()
                ->toArray();

            // 渲染HTML
            $html = $this->renderDetailHtml($software, $category, $attachments, $comments, $tags, $relatedSoftware);

            // 写入文件
            $dir = $this->htmlDir . '/detail';
            $this->ensureDir($dir);
            $file = $dir . "/{$softId}.html";
            file_put_contents($file, $html);

            $result['file'] = $file;
            $result['message'] = '详情页生成成功';
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['message'] = '详情页生成失败: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * 全站批量生成
     * 顺序: 首页 → 所有分类 → 所有软件详情
     */
    public function generateBatch(): array
    {
        $result = [
            'type'    => 'batch',
            'success' => true,
            'summary' => [],
            'message' => '',
        ];

        // 设置运行状态
        $this->setStatus('running');

        try {
            // 1. 生成首页
            $homeResult = $this->generateHomepage();
            $result['summary'][] = $homeResult;

            // 2. 生成所有分类页
            $categories = Db::table('category')
                ->where('status', 1)
                ->column('id');
            foreach ($categories as $catId) {
                $catResult = $this->generateCategory((int) $catId);
                $result['summary'][] = $catResult;
            }

            // 3. 生成所有已发布软件详情页
            $softwareIds = Db::table('software')
                ->where('status', 1)
                ->column('id');
            foreach ($softwareIds as $softId) {
                $detailResult = $this->generateDetail((int) $softId);
                $result['summary'][] = $detailResult;
            }

            $totalCount = count($result['summary']);
            $successCount = count(array_filter($result['summary'], fn($r) => $r['success']));
            $result['message'] = "全站生成完成: 共 {$totalCount} 项, 成功 {$successCount} 项";

            $this->setStatus('idle');
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['message'] = '批量生成异常: ' . $e->getMessage();
            $this->setStatus('error');
        }

        return $result;
    }

    /**
     * 按类型分发生成
     */
    public function generateByType(string $type, int $id = 0): array
    {
        return match ($type) {
            'homepage' => $this->generateHomepage(),
            'category' => $id > 0 ? $this->generateCategory($id) : ['success' => false, 'message' => '分类ID不能为空'],
            'detail'   => $id > 0 ? $this->generateDetail($id)   : ['success' => false, 'message' => '软件ID不能为空'],
            'batch'    => $this->generateBatch(),
            default    => ['success' => false, 'message' => "未知生成类型: {$type}"],
        };
    }

    /**
     * 获取当前生成状态
     */
    public function getStatus(): string
    {
        try {
            $status = Cache::store('redis')->get(self::STATUS_KEY);
            return $status ?: 'idle';
        } catch (\Throwable $e) {
            return 'idle';
        }
    }

    /**
     * 设置生成状态
     */
    private function setStatus(string $status): void
    {
        try {
            Cache::store('redis')->set(self::STATUS_KEY, $status, 3600);
        } catch (\Throwable $e) {
            // Redis不可用时忽略
        }
    }

    // ========================================
    // HTML渲染方法 (heredoc/拼接)
    // ========================================

    /**
     * 渲染公共Header
     */
    private function renderHeader(array $configs, string $title = ''): string
    {
        $siteName = htmlspecialchars($configs['site_name'] ?? '帝国下载系统');
        $pageTitle = $title ? htmlspecialchars($title) . ' - ' . $siteName : $siteName;
        $keywords = htmlspecialchars($configs['site_keywords'] ?? '');
        $description = htmlspecialchars($configs['site_description'] ?? '');
        $baseUrl = $configs['site_url'] ?? '/';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="{$keywords}">
    <meta name="description" content="{$description}">
    <title>{$pageTitle}</title>
    <link rel="stylesheet" href="{$baseUrl}static/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="{$baseUrl}" class="logo">{$siteName}</a>
        <nav class="site-nav">
            <a href="{$baseUrl}">首页</a>
            <a href="{$baseUrl}category.html">分类</a>
        </nav>
    </div>
</header>
<main class="site-main container">
HTML;
    }

    /**
     * 渲染公共Footer
     */
    private function renderFooter(array $configs): string
    {
        $copyright = $configs['copyright'] ?? '&copy; ' . date('Y') . ' 帝国下载系统';
        $baseUrl = $configs['site_url'] ?? '/';

        return <<<HTML
</main>
<footer class="site-footer">
    <div class="container">
        <p>{$copyright}</p>
    </div>
</footer>
<script src="{$baseUrl}static/js/app.js"></script>
</body>
</html>
HTML;
    }

    /**
     * 渲染首页HTML
     */
    private function renderHomepageHtml(array $configs, array $hotSoftware, array $recommendSoftware, array $announcements, array $categoryTree): string
    {
        $header = $this->renderHeader($configs);

        // 热门软件列表
        $hotHtml = '';
        foreach ($hotSoftware as $sw) {
            $title = htmlspecialchars($sw['title'] ?? '');
            $desc  = htmlspecialchars(mb_substr($sw['description'] ?? '', 0, 100));
            $hots  = $this->renderStarRating((float) ($sw['rating'] ?? 0));
            $url   = "/detail/{$sw['id']}.html";
            $hotHtml .= <<<HTML
            <li class="software-item">
                <a href="{$url}" class="software-title">{$title}</a>
                <span class="software-rating">{$hots}</span>
                <span class="software-downloads">下载: {$sw['download_count']}</span>
                <p class="software-desc">{$desc}</p>
            </li>
HTML;
        }

        // 推荐软件列表
        $recHtml = '';
        foreach ($recommendSoftware as $sw) {
            $title = htmlspecialchars($sw['title'] ?? '');
            $desc  = htmlspecialchars(mb_substr($sw['description'] ?? '', 0, 100));
            $url   = "/detail/{$sw['id']}.html";
            $recHtml .= <<<HTML
            <li class="software-item">
                <a href="{$url}" class="software-title">{$title}</a>
                <p class="software-desc">{$desc}</p>
            </li>
HTML;
        }

        // 公告列表
        $annHtml = '';
        foreach ($announcements as $ann) {
            $atitle = htmlspecialchars($ann['title'] ?? '');
            $acontent = htmlspecialchars(mb_substr($ann['content'] ?? '', 0, 200));
            $sticky = !empty($ann['is_sticky']) ? ' <span class="badge-sticky">置顶</span>' : '';
            $annHtml .= <<<HTML
            <li class="announcement-item">
                <h4>{$atitle}{$sticky}</h4>
                <p>{$acontent}</p>
            </li>
HTML;
        }

        // 分类树
        $catHtml = $this->renderCategoryTree($categoryTree, 0);

        $footer = $this->renderFooter($configs);

        return <<<HTML
{$header}
<section class="hero">
    <h1>欢迎来到帝国下载系统</h1>
    <p>海量软件资源，安全高速下载</p>
</section>

<section class="hot-software">
    <h2>热门软件</h2>
    <ul class="software-list">{$hotHtml}</ul>
</section>

<section class="recommend-software">
    <h2>编辑推荐</h2>
    <ul class="software-list">{$recHtml}</ul>
</section>

<section class="announcements">
    <h2>最新公告</h2>
    <ul class="announcement-list">{$annHtml}</ul>
</section>

<section class="category-tree">
    <h2>软件分类</h2>
    <ul class="category-list">{$catHtml}</ul>
</section>
{$footer}
HTML;
    }

    /**
     * 渲染分类页HTML
     */
    private function renderCategoryHtml(array $category, array $softwareList, int $page, int $totalPages, int $total): string
    {
        $configs = $this->getSiteConfigs();
        $catName = htmlspecialchars($category['name'] ?? '分类');
        $header = $this->renderHeader($configs, $catName);

        $listHtml = '';
        foreach ($softwareList as $sw) {
            $title = htmlspecialchars($sw['title'] ?? '');
            $desc  = htmlspecialchars(mb_substr($sw['description'] ?? '', 0, 150));
            $url   = "/detail/{$sw['id']}.html";
            $thumb = !empty($sw['thumbnail']) ? htmlspecialchars($sw['thumbnail']) : '';
            $thumbImg = $thumb ? "<img src=\"{$thumb}\" alt=\"{$title}\" class=\"software-thumb\">" : '';
            $listHtml .= <<<HTML
            <li class="software-item">
                {$thumbImg}
                <div class="software-info">
                    <a href="{$url}" class="software-title">{$title}</a>
                    <span class="software-meta">版本: {$sw['version']} | 下载: {$sw['download_count']}</span>
                    <p class="software-desc">{$desc}</p>
                </div>
            </li>
HTML;
        }

        // 分页导航
        $pagination = $this->renderPagination($page, $totalPages, "/category/{$category['id']}");

        $footer = $this->renderFooter($configs);

        return <<<HTML
{$header}
<section class="category-header">
    <h1>{$catName}</h1>
    <p class="category-desc">{$category['description']}</p>
    <span class="category-total">共 {$total} 款软件</span>
</section>

<section class="software-list-section">
    <ul class="software-list">{$listHtml}</ul>
</section>

<div class="pagination">{$pagination}</div>
{$footer}
HTML;
    }

    /**
     * 渲染详情页HTML
     */
    private function renderDetailHtml(array $software, ?array $category, array $attachments, array $comments, array $tags, array $relatedSoftware): string
    {
        $configs = $this->getSiteConfigs();
        $swTitle = htmlspecialchars($software['title'] ?? '软件详情');
        $header = $this->renderHeader($configs, $swTitle);

        $content = $software['content'] ?? $software['description'] ?? '';
        $version = htmlspecialchars($software['version'] ?? '');
        $author  = htmlspecialchars($software['author'] ?? '');
        $platform = htmlspecialchars($software['platform'] ?? '');
        $license = htmlspecialchars($software['license'] ?? '');
        $fileSize = htmlspecialchars($software['file_size'] ?? '');
        $downloadCount = (int) ($software['download_count'] ?? 0);
        $viewCount = (int) ($software['view_count'] ?? 0);
        $rating = $this->renderStarRating((float) ($software['rating'] ?? 0));
        $points = (int) ($software['require_points'] ?? 0);
        $catName = $category ? htmlspecialchars($category['name']) : '未分类';
        $homepage = htmlspecialchars($software['homepage'] ?? '');

        // 截图
        $images = [];
        if (!empty($software['images'])) {
            $decoded = is_string($software['images']) ? json_decode($software['images'], true) : $software['images'];
            if (is_array($decoded)) {
                $images = $decoded;
            }
        }
        $imagesHtml = '';
        foreach ($images as $img) {
            $imgUrl = htmlspecialchars($img);
            $imagesHtml .= "<img src=\"{$imgUrl}\" alt=\"截图\" class=\"screenshot\">";
        }

        // 附件/下载链接
        $attachHtml = '';
        foreach ($attachments as $att) {
            $attName = htmlspecialchars($att['name'] ?? '下载');
            $attSize = $att['file_size'] ?? 0;
            $attSizeStr = $attSize > 0 ? number_format($attSize / 1048576, 1) . ' MB' : '未知大小';
            $attachHtml .= <<<HTML
            <li class="attachment-item">
                <span class="att-name">{$attName}</span>
                <span class="att-size">{$attSizeStr}</span>
                <span class="att-points">需要 {$points} 点数</span>
            </li>
HTML;
        }

        // 标签
        $tagsHtml = '';
        foreach ($tags as $tag) {
            $tagName = htmlspecialchars($tag['name'] ?? '');
            $tagsHtml .= "<span class=\"tag\">{$tagName}</span>";
        }

        // 评论列表
        $commentHtml = '';
        foreach ($comments as $comment) {
            $cContent = htmlspecialchars($comment['content'] ?? '');
            $cTime = $comment['created_at'] ?? '';
            $cRatingStars = $this->renderStarRating((float) ($comment['rating'] ?? 0));
            $commentHtml .= <<<HTML
            <div class="comment-item">
                <div class="comment-rating">{$cRatingStars}</div>
                <p class="comment-content">{$cContent}</p>
                <span class="comment-time">{$cTime}</span>
            </div>
HTML;
        }

        // 相关推荐
        $relatedHtml = '';
        foreach ($relatedSoftware as $rsw) {
            $rtitle = htmlspecialchars($rsw['title'] ?? '');
            $rurl = "/detail/{$rsw['id']}.html";
            $relatedHtml .= <<<HTML
            <li><a href="{$rurl}">{$rtitle}</a></li>
HTML;
        }

        $footer = $this->renderFooter($configs);

        // 预计算条件块(heredoc中不支持复杂表达式)
        $homepageBlock = $homepage ? "<p>官方主页: <a href=\"{$homepage}\" target=\"_blank\">{$homepage}</a></p>" : '';
        $screenshotsBlock = !empty($imagesHtml) ? "<div class=\"screenshots\">{$imagesHtml}</div>" : '';
        $attachmentsBlock = !empty($attachHtml) ? "<section class=\"attachments\"><h2>下载地址</h2><ul class=\"attachment-list\">{$attachHtml}</ul></section>" : '';
        $relatedBlock = !empty($relatedHtml) ? "<section class=\"related-software\"><h2>相关推荐</h2><ul class=\"related-list\">{$relatedHtml}</ul></section>" : '';
        $commentCount = count($comments);

        return <<<HTML
{$header}
<article class="software-detail">
    <h1>{$swTitle}</h1>
    <div class="software-meta">
        <span>分类: {$catName}</span>
        <span>版本: {$version}</span>
        <span>作者: {$author}</span>
        <span>平台: {$platform}</span>
        <span>授权: {$license}</span>
        <span>大小: {$fileSize}</span>
        <span>下载: {$downloadCount}</span>
        <span>浏览: {$viewCount}</span>
        <span>评分: {$rating}</span>
    </div>
    {$homepageBlock}

    {$screenshotsBlock}

    <div class="tags">{$tagsHtml}</div>

    <div class="software-content">
        {$content}
    </div>

    {$attachmentsBlock}

    <section class="comments">
        <h2>用户评论 ({$commentCount}条)</h2>
        {$commentHtml}
    </section>

    {$relatedBlock}
</article>
{$footer}
HTML;
    }

    /**
     * 渲染分页导航
     */
    private function renderPagination(int $current, int $total, string $baseUrl): string
    {
        if ($total <= 1) {
            return '';
        }

        $html = '<div class="pagination-links">';
        if ($current > 1) {
            $prev = $current - 1;
            $prevUrl = $baseUrl . ($prev === 1 ? '.html' : "_{$prev}.html");
            $html .= "<a href=\"{$prevUrl}\">上一页</a>";
        }
        for ($i = 1; $i <= $total; $i++) {
            $url = $baseUrl . ($i === 1 ? '.html' : "_{$i}.html");
            $active = $i === $current ? ' class="active"' : '';
            $html .= "<a href=\"{$url}\"{$active}>{$i}</a>";
        }
        if ($current < $total) {
            $next = $current + 1;
            $nextUrl = $baseUrl . "_{$next}.html";
            $html .= "<a href=\"{$nextUrl}\">下一页</a>";
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * 渲染星级评分
     */
    private function renderStarRating(float $rating): string
    {
        $full = (int) floor($rating);
        $half = ($rating - $full >= 0.5) ? 1 : 0;
        $empty = 5 - $full - $half;
        return str_repeat('★', $full) . str_repeat('☆', $half) . str_repeat('☆', $empty);
    }

    /**
     * 渲染分类树
     */
    private function renderCategoryTree(array $tree, int $depth): string
    {
        $html = '';
        $indent = str_repeat('  ', $depth);
        foreach ($tree as $item) {
            $name = htmlspecialchars($item['name'] ?? '');
            $url = "/category/{$item['id']}.html";
            $html .= "{$indent}<li><a href=\"{$url}\">{$name}</a>";
            if (!empty($item['children'])) {
                $html .= "\n{$indent}<ul>\n";
                $html .= $this->renderCategoryTree($item['children'], $depth + 1);
                $html .= "{$indent}</ul>";
            }
            $html .= "</li>\n";
        }
        return $html;
    }

    // ========================================
    // 辅助方法
    // ========================================

    /**
     * 获取站点配置（缓存读取）
     */
    private function getSiteConfigs(): array
    {
        $cacheKey = 'static:site_configs';
        try {
            $configs = Cache::store('redis')->get($cacheKey);
            if ($configs) {
                return $configs;
            }
        } catch (\Throwable $e) {
            // Redis不可用，从DB读取
        }

        $configs = Db::table('system_config')
            ->column('value', 'key');

        // 补充模板变量
        $templateVars = Db::table('template_var')
            ->column('value', 'name');
        foreach ($templateVars as $name => $value) {
            if (!isset($configs[$name])) {
                $configs[$name] = $value;
            }
        }

        try {
            Cache::store('redis')->set($cacheKey, $configs, 600);
        } catch (\Throwable $e) {
            // ignore
        }

        return $configs;
    }

    /**
     * 构建分类树
     */
    private function buildTree(array $list, int $parentId = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if (($item['parent_id'] ?? 0) == $parentId) {
                $item['children'] = $this->buildTree($list, (int) $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 获取某个分类的所有子分类ID（含自身）
     */
    private function getChildCategoryIds(int $id): array
    {
        $ids = [$id];
        $children = Db::table('category')
            ->where('parent_id', $id)
            ->where('status', 1)
            ->column('id');
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getChildCategoryIds((int) $childId));
        }
        return $ids;
    }

    /**
     * 确保目录存在
     */
    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
