<?php
// 修复 ThinkPHP 8.x 在 PHP 8.3 上的兼容性问题
// htmlentities(null) 在 PHP 8.1+ 触发 E_DEPRECATED
// ThinkPHP 的错误处理器把 E_DEPRECATED 转成 ErrorException
// 导致异常页面渲染时二次崩溃，看不到真正的错误

$tpl = 'vendor/topthink/framework/src/tpl/think_exception.tpl';
if (!file_exists($tpl)) {
    echo "❌ 未找到: $tpl\n";
    exit(1);
}

$content = file_get_contents($tpl);
$count = 0;
$content = str_replace(
    'htmlentities($message)',
    'htmlentities($message ?? \'\')',
    $content,
    $count
);

if ($count > 0) {
    file_put_contents($tpl, $content);
    echo "✅ 已修复: $tpl ($count 处)\n";
} else {
    echo "⚠️ 未找到需要修复的代码\n";
}
