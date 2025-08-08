<?php
/*
Plugin Name: Local Tunnel Client
Description: Tunnels your local WordPress site via a WebSocket-based server.
Version: 1.0
Auther: Vineet Tiwari
*/

add_action('init', 'local_tunnel_start_client');

function local_tunnel_start_client() {
    if (php_sapi_name() !== 'cli' && !is_admin()) {
        return;
    }

    $clientId = 'myapp'; // Your tunnel ID used in the URL
    $server = 'ws://localhost:8080'; // Tunnel server address
    $url = parse_url(home_url());

    if (!function_exists('pcntl_fork')) return; // Ensure fork is available

    $pid = pcntl_fork();
    if ($pid === -1) return;
    if ($pid) return; // Parent process exits

    // CHILD PROCESS
    require_once ABSPATH . 'wp-load.php';

    $ws = new WebSocketClient($server);
    $ws->connect();
    $ws->send(json_encode(['type' => 'register', 'clientId' => $clientId]));

    while (true) {
        $msg = $ws->receive();
        if (!$msg) continue;

        $data = json_decode($msg, true);
        if ($data['type'] === 'http-request') {
            $path = $data['path'];
            $_SERVER['REQUEST_METHOD'] = $data['method'];
            $_SERVER['REQUEST_URI'] = $path;
            $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = $url['host'];
            $_SERVER['CONTENT_TYPE'] = $data['headers']['content-type'] ?? 'text/html';

            ob_start();
            require ABSPATH . 'index.php';
            $body = ob_get_clean();

            $response = [
                'requestId' => $data['requestId'],
                'status' => 200,
                'headers' => ["Content-Type" => "text/html"],
                'body' => $body
            ];
            $ws->send(json_encode($response));
        }
    }
}

// === Basic WebSocket client class ===
class WebSocketClient {
    private $url;
    private $socket;

    public function __construct($url) {
        $this->url = $url;
    }

    public function connect() {
        $parts = parse_url($this->url);
        $host = $parts['host'];
        $port = $parts['port'] ?? 80;

        $this->socket = fsockopen($host, $port, $errno, $errstr, 2);
        if (!$this->socket) {
            die("WebSocket connect error: $errstr");
        }

        $key = base64_encode(random_bytes(16));
        $header = "GET / HTTP/1.1\r\n" .
                  "Host: $host\r\n" .
                  "Upgrade: websocket\r\n" .
                  "Connection: Upgrade\r\n" .
                  "Sec-WebSocket-Key: $key\r\n" .
                  "Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($this->socket, $header);
        fread($this->socket, 1500); // ignore handshake response
    }

    public function send($data) {
        $frame = chr(0x81) . chr(strlen($data)) . $data;
        fwrite($this->socket, $frame);
    }

    public function receive() {
        $data = fread($this->socket, 4096);
        if (!$data) return false;
        return substr($data, 2); // strip 2-byte header
    }
}
