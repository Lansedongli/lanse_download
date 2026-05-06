<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Db;

/**
 * 全站搜索服务
 * 基于 MySQL FULLTEXT 索引 + Redis 热门搜索词
 */
class SearchService
{
    /**
     * 全文搜索
     *
     * @param string $keyword  搜索关键词
     * @param string $type     搜索类型: all(全部) / software(软件) / category(分类)
     * @param int    $page     页码
     * @param int    $pageSize 每页条数
     * @return array {total, page, pageSize, data: [{type, id, title, content_snippet, url}]}
     */
    public function search(string $keyword, string $type = 'all', int $page = 1, int $pageSize = 20): array
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return ['total' => 0, 'page' => $page, 'pageSize' => $pageSize, 'data' => []];
        }

        $page     = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset   = ($page - 1) * $pageSize;

        // 准备 BOOLEAN MODE 搜索词（每个词加通配符前缀以支持部分匹配）
        $booleanKeyword = $this->buildBooleanKeyword($keyword);

        $results = [];
        $total   = 0;

        // 搜索软件
        if ($type === 'all' || $type === 'software') {
            [$swResults, $swTotal] = $this->searchSoftware($booleanKeyword, $keyword, $offset, $pageSize);
            $results = array_merge($results, $swResults);
            $total  += $swTotal;
        }

        // 搜索分类
        if ($type === 'all' || $type === 'category') {
            [$catResults, $catTotal] = $this->searchCategory($booleanKeyword, $keyword, $offset, $pageSize);
            $results = array_merge($results, $catResults);
            $total  += $catTotal;
        }

        // 按相关度排序（MATCH score 降序），混合结果时分页在子查询中各自处理
        if ($type === 'all') {
            // all 模式：各自取前 pageSize 条，合并后截取
            $results = array_slice($results, 0, $pageSize);
        }

        return [
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'data'     => $results,
        ];
    }

    /**
     * 搜索软件表
     *
     * @return array [results, total]
     */
    private function searchSoftware(string $booleanKeyword, string $rawKeyword, int $offset, int $limit): array
    {
        try {
            // 使用 MATCH...AGAINST IN BOOLEAN MODE 进行全文搜索
            $countSql = "SELECT COUNT(*) AS cnt FROM software 
                         WHERE MATCH(title, content) AGAINST (:kw IN BOOLEAN MODE) 
                         AND status = 1 AND deleted_at IS NULL";
            $totalRow = Db::query($countSql, ['kw' => $booleanKeyword]);
            $total    = (int) ($totalRow[0]['cnt'] ?? 0);

            $dataSql = "SELECT id, title, description, content, 
                               MATCH(title, content) AGAINST (:kw IN BOOLEAN MODE) AS relevance
                        FROM software 
                        WHERE MATCH(title, content) AGAINST (:kw IN BOOLEAN MODE) 
                        AND status = 1 AND deleted_at IS NULL
                        ORDER BY relevance DESC
                        LIMIT :offset, :limit";
            $rows = Db::query($dataSql, [
                'kw'     => $booleanKeyword,
                'offset' => $offset,
                'limit'  => $limit,
            ]);

            $results = [];
            foreach ($rows as $row) {
                $snippet = $this->extractSnippet($row['description'] ?? '' . ' ' . ($row['content'] ?? ''), $rawKeyword);
                $results[] = [
                    'type'            => 'software',
                    'id'              => (int) $row['id'],
                    'title'           => $row['title'] ?? '',
                    'content_snippet' => $snippet,
                    'url'             => '/software/' . $row['id'],
                ];
            }

            return [$results, $total];
        } catch (\Throwable $e) {
            // FULLTEXT 索引不存在或其他数据库错误时降级返回空
            return [[], 0];
        }
    }

    /**
     * 搜索分类表
     *
     * @return array [results, total]
     */
    private function searchCategory(string $booleanKeyword, string $rawKeyword, int $offset, int $limit): array
    {
        try {
            $countSql = "SELECT COUNT(*) AS cnt FROM category 
                         WHERE MATCH(name, description) AGAINST (:kw IN BOOLEAN MODE) 
                         AND status = 1";
            $totalRow = Db::query($countSql, ['kw' => $booleanKeyword]);
            $total    = (int) ($totalRow[0]['cnt'] ?? 0);

            $dataSql = "SELECT id, name, description,
                               MATCH(name, description) AGAINST (:kw IN BOOLEAN MODE) AS relevance
                        FROM category 
                        WHERE MATCH(name, description) AGAINST (:kw IN BOOLEAN MODE) 
                        AND status = 1
                        ORDER BY relevance DESC
                        LIMIT :offset, :limit";
            $rows = Db::query($dataSql, [
                'kw'     => $booleanKeyword,
                'offset' => $offset,
                'limit'  => $limit,
            ]);

            $results = [];
            foreach ($rows as $row) {
                $snippet = $this->extractSnippet($row['description'] ?? '', $rawKeyword);
                $results[] = [
                    'type'            => 'category',
                    'id'              => (int) $row['id'],
                    'title'           => $row['name'] ?? '',
                    'content_snippet' => $snippet,
                    'url'             => '/category/' . $row['id'],
                ];
            }

            return [$results, $total];
        } catch (\Throwable $e) {
            // FULLTEXT 索引不存在或其他数据库错误时降级返回空
            return [[], 0];
        }
    }

    /**
     * 构建 BOOLEAN MODE 搜索关键词
     * 将用户输入的词拆分，每个词添加 + 前缀（必须包含）和 * 通配符（前缀匹配）
     */
    private function buildBooleanKeyword(string $keyword): string
    {
        // 分割为单个词（按空格、中文也按字符）
        $words = preg_split('/[\s,，。；;]+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            // 单个词/短语直接加通配符
            return '+' . $keyword . '*';
        }

        // 每个词加 + 前缀和 * 通配符，支持部分匹配
        $parts = array_map(function ($word) {
            // 过滤太短的词（单字符中文保留）
            if (mb_strlen($word) < 1) {
                return '';
            }
            return '+' . $word . '*';
        }, $words);

        return implode(' ', array_filter($parts));
    }

    /**
     * 从文本中提取包含关键词的摘要片段
     */
    private function extractSnippet(string $text, string $keyword, int $maxLen = 200): string
    {
        // 去除 HTML 标签
        $text = strip_tags($text);
        // 去除多余空白
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        // 查找关键词位置
        $pos = mb_stripos($text, $keyword);
        if ($pos === false) {
            // 关键词不在文本中，返回开头部分
            return mb_substr($text, 0, $maxLen) . '...';
        }

        // 从关键词前一些字符开始截取
        $start = max(0, $pos - (int)($maxLen / 3));
        $snippet = mb_substr($text, $start, $maxLen);

        // 添加省略号
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if (($start + $maxLen) < mb_strlen($text)) {
            $snippet .= '...';
        }

        return $snippet;
    }

    /**
     * 记录搜索关键词到 Redis（热门搜索统计）
     * Redis 不可用时自动降级，不抛出异常
     */
    public function logSearch(string $keyword): void
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return;
        }

        try {
            // 通过 Cache 获取原生 Redis 实例操作 sorted set
            $redis = Cache::store('redis')->handler();
            if ($redis && method_exists($redis, 'zIncrBy')) {
                $redis->zIncrBy('search:hot', 1, $keyword);
            }
        } catch (\Throwable $e) {
            // Redis 不可用时降级，不报错
        }
    }

    /**
     * 获取热门搜索关键词
     *
     * @param int $limit 返回条数
     * @return array 关键词数组
     */
    public function hotKeywords(int $limit = 10): array
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis && method_exists($redis, 'zRevRange')) {
                // 按分数从高到低取前 N 个
                $items = $redis->zRevRange('search:hot', 0, $limit - 1, true);
                if (!empty($items) && is_array($items)) {
                    return array_map(function ($keyword, $count) {
                        return [
                            'keyword' => $keyword,
                            'count'   => (int) $count,
                        ];
                    }, array_keys($items), $items);
                }
            }
        } catch (\Throwable $e) {
            // Redis 不可用时降级返回空数组
        }

        return [];
    }
}
