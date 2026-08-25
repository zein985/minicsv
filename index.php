<?php
/**
 * 通过GET参数id查询CSV文件中的记录
 * 只输出CSV格式数据（表头+匹配行）
 * 
 * 使用方法: script.php?id=123
 * CSV文件格式: 第一行为表头
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSV文件路径（可修改为您的实际路径）
$csvFile = '/1.58.2/utf8/itemdef.csv';

// 检查是否提供了id参数
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("错误: 请提供id参数, 例如: ?id=123");
}

$searchId = trim($_GET['id']);

// 检查CSV文件是否存在
if (!file_exists($csvFile)) {
    die("错误: CSV文件不存在: " . $csvFile);
}

// 检查文件是否可读
if (!is_readable($csvFile)) {
    die("错误: CSV文件不可读");
}

/**
 * 在CSV文件中查找指定id的记录
 * 
 * @param string $csvFile CSV文件路径
 * @param string $searchId 要搜索的id
 * @return array|null 找到的记录数组, 未找到返回null
 */
function findRecordById($csvFile, $searchId) {
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        return null;
    }

    // 读取表头
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return null;
    }

    // 检查是否有id列 (不区分大小写)
    $idIndex = -1;
    foreach ($headers as $index => $header) {
        if (strcasecmp(trim($header), 'id') === 0) {
            $idIndex = $index;
            break;
        }
    }
    
    if ($idIndex === -1) {
        fclose($handle);
        return null;
    }

    // 逐行搜索
    while (($row = fgetcsv($handle)) !== false) {
        // 确保行有足够的列
        if (count($row) <= $idIndex) {
            continue;
        }

        // 比较id (忽略大小写和前后空格)
        if (strcasecmp(trim($row[$idIndex]), trim($searchId)) === 0) {
            fclose($handle);
            return [
                'headers' => $headers,
                'row' => $row
            ];
        }
    }

    fclose($handle);
    return null;
}

// 执行搜索
$result = findRecordById($csvFile, $searchId);

// 设置纯文本输出
header('Content-Type: text/plain; charset=utf-8');

// 只输出CSV格式数据
if ($result === null) {
    // 未找到记录，只输出空行
    echo "";
} else {
    $headers = $result['headers'];
    $row = $result['row'];
    $colCount = max(count($headers), count($row));
    
    // 输出表头
    $outputHeaders = [];
    for ($i = 0; $i < $colCount; $i++) {
        $header = isset($headers[$i]) ? $headers[$i] : '列' . ($i + 1);
        $outputHeaders[] = '"' . str_replace('"', '""', $header) . '"';
    }
    echo implode(',', $outputHeaders) . "\n";
    
    // 输出数据行
    $outputRow = [];
    for ($i = 0; $i < $colCount; $i++) {
        $value = isset($row[$i]) ? $row[$i] : '';
        $outputRow[] = '"' . str_replace('"', '""', $value) . '"';
    }
    echo implode(',', $outputRow) . "\n";
}
