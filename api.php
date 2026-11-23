<?php
// JSON proxy: browser -> plugin.php?plugin=fpp-fm30-controler&page=api.php&nopage=1&p=<endpoint>
//          -> fm30d on 127.0.0.1 (never exposed on the network itself).
header('Content-Type: application/json');
header('Cache-Control: no-store');

$allowed = array('status', 'settings', 'pieces', 'piece', 'preview', 'cmd', 'log');
$p = isset($_GET['p']) ? $_GET['p'] : '';
if (!in_array($p, $allowed, true)) {
    http_response_code(404);
    echo json_encode(array('ok' => false, 'error' => 'unknown endpoint'));
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, array('GET', 'PUT', 'POST'), true)) {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'method not allowed'));
    exit;
}

$cfgDir = (isset($settings) && isset($settings['mediaDirectory'])) ? $settings['mediaDirectory'] . '/config' : '/home/fpp/media/config';
$port = 5440;
$cfg = @json_decode(@file_get_contents($cfgDir . '/fpp-fm30-settings.json'), true);
if (is_array($cfg) && isset($cfg['http_port'])) {
    $port = intval($cfg['http_port']);
}

$body = ($method === 'GET') ? '' : file_get_contents('php://input');
$ctx = stream_context_create(array('http' => array(
    'method' => $method,
    'header' => "Content-Type: application/json\r\n",
    'content' => $body,
    'timeout' => 10,
    'ignore_errors' => true,
)));
$r = @file_get_contents('http://127.0.0.1:' . $port . '/api/' . $p, false, $ctx);
if ($r === false) {
    http_response_code(502);
    echo json_encode(array('ok' => false, 'error' => 'FM30 service (fpp-fm30) is not running'));
    exit;
}
if (isset($http_response_header[0]) && preg_match('#^HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
    http_response_code(intval($m[1]));
}
echo $r;
