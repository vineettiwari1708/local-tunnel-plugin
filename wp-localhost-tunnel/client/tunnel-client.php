<?php
$server = 'ws://127.0.0.1:8080';
$clientId = 'myapp';

$ws = stream_socket_client("tcp://" . parse_url($server, PHP_URL_HOST) . ":" . parse_url($server, PHP_URL_PORT), $errno, $errstr, 30);
if (!$ws) {
    file_put_contents("php://stderr", "WebSocket connection failed: $errstr ($errno)\n");
    exit(1);
}

fwrite($ws, encodeWS("register", ['clientId' => $clientId]));

while (!feof($ws)) {
    $data = decodeWS($ws);
    if (!$data) continue;

    if ($data['type'] === 'http-request') {
        $url = 'http://localhost' . $data['path'];
        $opts = [
            'http' => [
                'method' => $data['method'],
                'header' => buildHeaderString($data['headers']),
                'content' => $data['body'],
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($opts);
        $responseBody = @file_get_contents($url, false, $context);
        $headers = parse_headers($http_response_header ?? []);
        $status = explode(' ', $http_response_header[0])[1] ?? 200;

        fwrite($ws, encodeWS("http-response", [
            'requestId' => $data['requestId'],
            'status' => (int)$status,
            'headers' => $headers,
            'body' => $responseBody
        ]));
    }
}

function buildHeaderString($headers) {
    $out = '';
    foreach ($headers as $key => $value) {
        $out .= "$key: $value\r\n";
    }
    return $out;
}

function parse_headers($headers) {
    $parsed = [];
    foreach ($headers as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $value] = explode(': ', $line, 2);
            $parsed[$key] = $value;
        }
    }
    return $parsed;
}

function encodeWS($type, $data) {
    return json_encode(array_merge(['type' => $type], $data)) . "\n";
}

function decodeWS($ws) {
    return json_decode(fgets($ws), true);
}
