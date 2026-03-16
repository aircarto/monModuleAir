<?php
/**
 * Proxy pour l'API LLM locale
 * Relaie les requêtes du frontend vers le serveur LLM interne (192.168.1.120)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read and validate input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request: messages array required']);
    exit;
}

// Build request for local LLM
$llmPayload = json_encode([
    'model' => $data['model'] ?? 'qwen2.5-coder:7b',
    'messages' => $data['messages']
]);

// Measure time
$startTime = microtime(true);

// Call local LLM server
$ch = curl_init('http://192.168.1.120/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $llmPayload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$elapsed = round(microtime(true) - $startTime, 1);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'LLM server unreachable: ' . $curlError]);
    exit;
}

// Parse response and inject timing + token estimation
$responseData = json_decode($response, true);

if ($responseData && isset($responseData['choices'][0]['message']['content'])) {
    // Estimate tokens (~4 chars per token for French text)
    $promptText = '';
    foreach ($data['messages'] as $msg) {
        $promptText .= $msg['content'] ?? '';
    }
    $promptTokens = (int) ceil(mb_strlen($promptText) / 4);
    $completionText = $responseData['choices'][0]['message']['content'];
    $completionTokens = (int) ceil(mb_strlen($completionText) / 4);

    // Add usage and timing info
    $responseData['usage'] = [
        'prompt_tokens' => $promptTokens,
        'completion_tokens' => $completionTokens,
        'total_tokens' => $promptTokens + $completionTokens
    ];
    $responseData['timing'] = [
        'elapsed_seconds' => $elapsed,
        'tokens_per_second' => $elapsed > 0 ? round($completionTokens / $elapsed, 1) : 0
    ];

    http_response_code($httpCode);
    echo json_encode($responseData);
} else {
    // Pass through as-is if we can't parse
    http_response_code($httpCode);
    echo $response;
}
