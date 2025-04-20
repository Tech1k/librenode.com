<?php
$cacheFile = 'cache_data.json';

$blockCacheTime = 300;     // 5 minutes
$peerCacheTime = 600;      // 10 minutes
$versionCacheTime = 3600;  // 1 hour

$needSocket = false;

// Load existing cache if it exists
$cachedData = [
    'serverVersion' => null,
    'blockCount' => null,
    'peerCount' => null,
    'timestamps' => [
        'serverVersion' => 0,
        'blockCount' => 0,
        'peerCount' => 0
    ]
];

if (file_exists($cacheFile)) {
    $decoded = json_decode(file_get_contents($cacheFile), true);
    if (is_array($decoded)) {
        $cachedData = array_merge($cachedData, $decoded);
    }
}

$now = time();
$refreshVersion = ($now - $cachedData['timestamps']['serverVersion']) > $versionCacheTime;
$refreshBlock   = ($now - $cachedData['timestamps']['blockCount']) > $blockCacheTime;
$refreshPeer    = ($now - $cachedData['timestamps']['peerCount']) > $peerCacheTime;

if ($refreshVersion || $refreshBlock || $refreshPeer) {
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_connect($socket, "localhost", 50001);
}

// Server version
if ($refreshVersion) {
    $query = '{"id": "version", "method": "server.version", "params": []}';
    socket_write($socket, $query . "\n");
    $response = '';
    while (($chunk = socket_read($socket, 2048, PHP_NORMAL_READ)) !== false) {
        $response .= $chunk;
        if (strpos($chunk, "\n") !== false) break;
    }
    $result = json_decode($response, true);
    if (isset($result['result'][0])) {
        $cachedData['serverVersion'] = $result['result'][0];
        $cachedData['timestamps']['serverVersion'] = $now;
    } else {
        $cachedData['serverVersion'] = "Error, failed to get server version";
    }
}

// Block count
if ($refreshBlock) {
    $query = '{"id": "blk", "method": "blockchain.headers.subscribe", "params": []}';
    socket_write($socket, $query . "\n");
    $response = '';
    while (($chunk = socket_read($socket, 2048, PHP_NORMAL_READ)) !== false) {
        $response .= $chunk;
        if (strpos($chunk, "\n") !== false) break;
    }
    $result = json_decode($response, true);
    if (isset($result['result']['height'])) {
        $cachedData['blockCount'] = $result['result']['height'];
        $cachedData['timestamps']['blockCount'] = $now;
    } else {
        $cachedData['blockCount'] = "Error, failed to get block count";
    }
}

// Peer count
if ($refreshPeer) {
    $query = '{"id": "peers", "method": "server.peers.subscribe", "params": []}';
    socket_write($socket, $query . "\n");
    $response = '';
    while (($chunk = socket_read($socket, 2048, PHP_NORMAL_READ)) !== false) {
        $response .= $chunk;
        if (strpos($chunk, "\n") !== false) break;
    }
    $result = json_decode($response, true);
    if (isset($result['result']) && is_array($result['result'])) {
        $cachedData['peerCount'] = count($result['result']);
        $cachedData['timestamps']['peerCount'] = $now;
    } else {
        $cachedData['peerCount'] = "Error, failed to get peer count";
    }
}

if ($refreshVersion || $refreshBlock || $refreshPeer) {
    file_put_contents($cacheFile, json_encode($cachedData));
    if (isset($socket)) socket_close($socket);
    //echo "Updated data:\n";
} else {
    //echo "Using cached data:\n";
}

//echo "Server version: " . $cachedData['serverVersion'] . "\n";
//echo "Block count: " . $cachedData['blockCount'] . "\n";
//echo "Peer count: " . $cachedData['peerCount'] . "\n";

$serverVersion = $cachedData['serverVersion'];
$blockCount = $cachedData['blockCount'];
$peerCount = $cachedData['peerCount'];
?>


<!DOCTYPE html>
	<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#5271ff">
        <link rel="canonical" href="https://btc.librenode.com"/>
        <meta name="robots" content="index, nofollow">
        <meta name="author" content="Tech1k">
        <title>LibreNode - BTC Electrum Server</title>
        <link rel="shortcut icon" href="assets/favicon.png?v=2"/>
		<link rel="stylesheet" href="assets/style.css?v=2">
	</head>
	<body>
		<div id="main">
            <center>
				<img src="assets/librebtc.png" style="max-width: 100%;">
            </center>
            <h2>LibreNode Bitcoin Electrum Server</h2>
            <p>
                This Bitcoin Fulcrum Electrum Server is hosted by <a href="https://librenode.com">LibreNode</a> and is offered freely to the community. You can connect via SSL, TCP, WSS, or Tor — SSL is the recommended connection method for most wallets, ideally used over Tor for enhanced privacy.
                <br/><br/>
                This service relies on donations to help offset hosting costs. If you've found it useful, please consider <a href="https://librenode.com/donate">supporting us</a>.
                <br/><br/>
                While we are committed to not logging your activity, we strongly encourage you to run your own Electrum server whenever possible to enhance your privacy.
            </p>
            <h3>Connection Info</h3>
            <ul><b>Ports:</b> <code>50002 (SSL), 50001 (TCP), 50004 (WSS)</code></ul>
            <ul><img src="assets/web.png" width="25px" style="margin-right: 3px; vertical-align: middle;"><b>Clearnet:</b> <code>btc.librenode.com</code></ul>
            <ul><img src="assets/tor.png" width="24px" style="margin-right: 3px; vertical-align: middle;"><b>Tor:</b> <code style="word-break: break-word;">gw3ennwsaonltfox7z3rhhof6mxcq2fnwhcj2qyp3kxsfldnxix5b4yd.onion</code></ul>
            <h3>Server Info</h3>
            <ul><b>Server version:</b> <code><?php echo $serverVersion; ?></code></ul>
            <ul><b>Block height:</b> <code><?php echo $blockCount; ?></code></ul>
            <ul><b>Server peers:</b> <code><?php echo $peerCount; ?></code></ul>
            <br/>
		</div>
	</body>
</html>
