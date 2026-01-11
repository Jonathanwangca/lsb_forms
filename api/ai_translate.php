<?php
/**
 * AI Translation API
 * 使用 OpenAI GPT-4o-mini 进行智能翻译和代码生成
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$sourceType = $input['source_type'] ?? '';
$value = trim($input['value'] ?? '');
$category = $input['category'] ?? '';

if (empty($value)) {
    jsonError('Value is required');
}

// 获取 OpenAI 配置
$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
$model = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';

if (empty($apiKey) || $apiKey === 'your-api-key-here') {
    // 如果没有配置 API Key，使用本地词典
    $result = localTranslate($sourceType, $value, $category);
    jsonSuccess($result);
    exit;
}

try {
    $result = openaiTranslate($apiKey, $model, $sourceType, $value, $category);
    jsonSuccess($result);
} catch (Exception $e) {
    // 如果 OpenAI 调用失败，回退到本地词典
    error_log('OpenAI Error: ' . $e->getMessage());
    $result = localTranslate($sourceType, $value, $category);
    $result['_fallback'] = true;
    jsonSuccess($result);
}

/**
 * 使用 OpenAI 进行翻译
 */
function openaiTranslate($apiKey, $model, $sourceType, $value, $category) {
    $categoryContext = $category ? "这是建筑行业RFQ系统中「{$category}」分类的参数选项。" : "这是建筑行业RFQ系统的参数选项。";

    if ($sourceType === 'cn') {
        $prompt = <<<PROMPT
{$categoryContext}

请根据以下中文值生成：
1. 对应的英文翻译（专业、简洁）
2. 系统代码（全大写英文，下划线连接，适合做数据库字段值）

中文值：{$value}

请严格按以下JSON格式返回，不要添加其他内容：
{"value_en": "英文翻译", "code": "CODE_VALUE"}
PROMPT;
    } else {
        $prompt = <<<PROMPT
{$categoryContext}

请根据以下英文值生成：
1. 对应的中文翻译（专业、简洁）
2. 系统代码（全大写英文，下划线连接，适合做数据库字段值）

英文值：{$value}

请严格按以下JSON格式返回，不要添加其他内容：
{"value_cn": "中文翻译", "code": "CODE_VALUE"}
PROMPT;
    }

    $response = callOpenAI($apiKey, $model, $prompt);

    // 解析返回的 JSON
    $data = json_decode($response, true);
    if (!$data) {
        // 尝试从返回内容中提取 JSON
        if (preg_match('/\{[^}]+\}/', $response, $matches)) {
            $data = json_decode($matches[0], true);
        }
    }

    if (!$data) {
        throw new Exception('Invalid response format');
    }

    return $data;
}

/**
 * 调用 OpenAI API
 */
function callOpenAI($apiKey, $model, $prompt) {
    $url = 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => '你是一个专业的建筑行业翻译助手，精通中英文建筑术语。你的任务是提供准确、专业的翻译和系统代码。只返回JSON格式的结果，不要有任何其他文字。'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.3,
        'max_tokens' => 200
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'HTTP ' . $httpCode;
        throw new Exception('OpenAI API Error: ' . $errorMsg);
    }

    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response');
    }

    return trim($result['choices'][0]['message']['content']);
}

/**
 * 本地词典翻译（备用方案）
 */
function localTranslate($sourceType, $value, $category) {
    // 建筑行业常用词汇词典
    $cnToEn = [
        '钢结构' => 'Steel Structure',
        '钢材' => 'Steel',
        '不锈钢' => 'Stainless Steel',
        '镀锌' => 'Galvanized',
        '热镀锌' => 'Hot-dip Galvanized',
        '铝' => 'Aluminum',
        '铝合金' => 'Aluminum Alloy',
        '铝镁锰' => 'Al-Mg-Mn',
        '彩钢' => 'Color Steel',
        '彩钢板' => 'Color Steel Sheet',
        '夹芯板' => 'Sandwich Panel',
        '岩棉' => 'Rock Wool',
        '玻璃棉' => 'Glass Wool',
        '聚氨酯' => 'Polyurethane',
        '屋面' => 'Roof',
        '屋面板' => 'Roof Panel',
        '墙面' => 'Wall',
        '墙面板' => 'Wall Panel',
        '外板' => 'Outer Panel',
        '内衬板' => 'Liner Panel',
        '涂层' => 'Coating',
        '保温' => 'Insulation',
        '防水' => 'Waterproof',
        '排水' => 'Drainage',
        '天沟' => 'Gutter',
        '落水管' => 'Downspout',
        '采光' => 'Skylight',
        '天窗' => 'Skylight',
        '檩条' => 'Purlin',
        '门' => 'Door',
        '窗' => 'Window',
        '雨蓬' => 'Canopy',
        '标准' => 'Standard',
        '定制' => 'Custom',
        '是' => 'Yes',
        '否' => 'No',
        '无' => 'None',
        '其他' => 'Other',
    ];

    $result = [];

    if ($sourceType === 'cn') {
        // 中文 -> 英文
        $english = $cnToEn[$value] ?? null;
        if (!$english) {
            // 尝试部分匹配
            foreach ($cnToEn as $cn => $en) {
                if (mb_strpos($value, $cn) !== false) {
                    $english = str_replace($cn, $en, $value);
                    break;
                }
            }
        }
        $result['value_en'] = $english ?: ucwords($value);
        $result['code'] = generateCode($value, $result['value_en']);
    } else {
        // 英文 -> 中文
        $enToCn = array_flip($cnToEn);
        $chinese = null;
        foreach ($enToCn as $en => $cn) {
            if (strcasecmp($value, $en) === 0) {
                $chinese = $cn;
                break;
            }
        }
        $result['value_cn'] = $chinese ?: $value;
        $result['code'] = generateCode($result['value_cn'], $value);
    }

    return $result;
}

/**
 * 生成代码
 */
function generateCode($chinese, $english) {
    if ($english && preg_match('/^[a-zA-Z]/', $english)) {
        $words = preg_split('/[\s\-\/]+/', $english);
        $code = '';
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z0-9]/', '', $word);
            if (!empty($word)) {
                $code .= strtoupper($word) . '_';
            }
        }
        $code = rtrim($code, '_');

        if (strlen($code) > 30) {
            $code = '';
            foreach ($words as $word) {
                $word = preg_replace('/[^a-zA-Z0-9]/', '', $word);
                if (!empty($word)) {
                    $code .= strtoupper(substr($word, 0, 1));
                }
            }
        }
        return $code;
    }
    return strtoupper(uniqid('OPT_'));
}
