<?php
/**
 * 通过GET参数id查询同目录CSV文件中的记录
 * 支持多种输出格式: csv, json, txt
 * 
 * 使用方法: 
 *   script.php?id=123              (默认输出CSV)
 *   script.php?id=123&format=csv   (输出CSV)
 *   script.php?id=123&format=json  (输出JSON)
 *   script.php?id=123&format=txt   (输出纯文本表格)
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSV文件路径（相对于当前PHP文件的目录）
$csvFile = __DIR__ . '/1.58.2/utf8/itemdef.csv';

// 检查是否提供了id参数
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("错误: 请提供id参数, 例如: ?id=123");
}

$searchId = trim($_GET['id']);

// 获取输出格式 (默认csv)
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'csv';
$allowedFormats = ['csv', 'json', 'txt'];
if (!in_array($format, $allowedFormats)) {
    $format = 'csv';
}

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

/**
 * 输出CSV格式
 */
function outputCsv($headers, $row) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: inline; filename="result.csv"');
    
    $colCount = max(count($headers), count($row));
    
    $outputHeaders = [];
    for ($i = 0; $i < $colCount; $i++) {
        $header = isset($headers[$i]) ? $headers[$i] : '列' . ($i + 1);
        $outputHeaders[] = '"' . str_replace('"', '""', $header) . '"';
    }
    echo implode(',', $outputHeaders) . "\n";
    
    $outputRow = [];
    for ($i = 0; $i < $colCount; $i++) {
        $value = isset($row[$i]) ? $row[$i] : '';
        $outputRow[] = '"' . str_replace('"', '""', $value) . '"';
    }
    echo implode(',', $outputRow) . "\n";
}

/**
 * 输出JSON格式
 */
function outputJson($headers, $row) {
    header('Content-Type: application/json; charset=utf-8');
    
    $colCount = max(count($headers), count($row));
    $data = [];
    
    for ($i = 0; $i < $colCount; $i++) {
        $header = isset($headers[$i]) ? $headers[$i] : '列' . ($i + 1);
        $value = isset($row[$i]) ? $row[$i] : '';
        $data[$header] = $value;
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

/**
 * 输出TXT格式 (纯文本表格)
 */
function outputTxt($headers, $row) {
    header('Content-Type: text/plain; charset=utf-8');
    
    $colCount = max(count($headers), count($row));
    
    // 计算每列最大宽度
    $colWidths = [];
    for ($i = 0; $i < $colCount; $i++) {
        $header = isset($headers[$i]) ? $headers[$i] : '列' . ($i + 1);
        $value = isset($row[$i]) ? $row[$i] : '';
        $colWidths[$i] = max(mb_strlen($header, 'UTF-8'), mb_strlen($value, 'UTF-8'), 4);
    }
    
    // 绘制表头分隔线
    $line = "+";
    foreach ($colWidths as $width) {
        $line .= str_repeat("-", $width + 2) . "+";
    }
    echo $line . "\n";
    
    // 输出表头
    echo "|";
    foreach ($headers as $i => $header) {
        $display = isset($headers[$i]) ? $headers[$i] : '列' . ($i + 1);
        $padding = $colWidths[$i] - mb_strlen($display, 'UTF-8');
        echo " " . $display . str_repeat(" ", $padding) . " |";
    }
    echo "\n" . $line . "\n";
    
    // 输出数据行
    echo "|";
    foreach ($row as $i => $value) {
        $display = $value;
        $padding = $colWidths[$i] - mb_strlen($display, 'UTF-8');
        echo " " . $display . str_repeat(" ", $padding) . " |";
    }
    echo "\n" . $line . "\n";
}

// 根据格式输出
if ($result === null) {
    // 未找到记录
    header('Content-Type: text/plain; charset=utf-8');
    echo "";
} else {
    $headers = $result['headers'];
    $row = $result['row'];
    
    switch ($format) {
        case 'json':
            outputJson($headers, $row);
            break;
        case 'txt':
            outputTxt($headers, $row);
            break;
        case 'csv':
        default:
            outputCsv($headers, $row);
            break;
    }
}
