<?php
$cacheFile = 'cache_data.json';

$blockCacheTime = 300;     // 5 minutes cache for block count
$dbCacheTime = 600;        // 10 minutes cache for database size
$syncCacheTime = 600;      // 10 minutes cache for sync status

$cachedData = [
    'blockCount' => null,
    'databaseSize' => null,
    'synchronized' => null,
    'timestamps' => [
        'blockCount' => 0,
        'databaseSize' => 0,
        'synchronized' => 0
    ]
];

if (file_exists($cacheFile)) {
    $decoded = json_decode(file_get_contents($cacheFile), true);
    if (is_array($decoded)) {
        $cachedData = array_merge($cachedData, $decoded);
    }
}

$now = time();
$refreshBlock = ($now - $cachedData['timestamps']['blockCount']) > $blockCacheTime;
$refreshDatabase = ($now - $cachedData['timestamps']['databaseSize']) > $dbCacheTime;
$refreshSync = ($now - $cachedData['timestamps']['synchronized']) > $syncCacheTime;

if ($refreshBlock || $refreshDatabase || $refreshSync) {
    $daemonInfo = getMoneroDaemonInfo();

    if ($daemonInfo) {
        $cachedData['blockCount'] = $daemonInfo['blockCount'];
        $cachedData['databaseSize'] = $daemonInfo['databaseSize'];
        $cachedData['synchronized'] = $daemonInfo['synchronized'];
        $cachedData['timestamps']['blockCount'] = $now;
        $cachedData['timestamps']['databaseSize'] = $now;
        $cachedData['timestamps']['synchronized'] = $now;

        file_put_contents($cacheFile, json_encode($cachedData));
    }
}

$blockCount = $cachedData['blockCount'];
$databaseSize = $cachedData['databaseSize'];
$synchronized = $cachedData['synchronized'];

//echo "Block Count: " . $blockCount . "\n";
//echo "Database Size: " . $databaseSize . " GB\n";
//echo "Synced: " . $synchronized . "\n";

function getMoneroDaemonInfo() {
    $url = 'http://xmr.librenode.com:18089/json_rpc';
    $data = [
        'jsonrpc' => '2.0',
        'id' => '0',
        'method' => 'get_info',
        'params' => []
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        return false;
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['result'])) {
        $info = $result['result'];
    
        $blockCount = isset($info['height']) ? $info['height'] : 'Unknown';
        $databaseSize = isset($info['database_size']) ? round($info['database_size'] / (1024 * 1024 * 1024), 2) : 'Unknown';
        $synchronized = isset($info['synchronized']) ? ($info['synchronized'] ? 'Yes' : 'No') : 'Unknown';
    
        return [
            'blockCount' => $blockCount,
            'databaseSize' => $databaseSize,
            'synchronized' => $synchronized
        ];
    } else {
        return [
            'blockCount' => "Error: failed to retrieve block count.",
            'databaseSize' => "Error: failed to retrieve database size.",
            'synchronized' => "Error: failed to retrieve synchronization state."
        ];
    }
}
?>

<!DOCTYPE html>
	<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#5271ff">
        <link rel="canonical" href="https://xmr.librenode.com"/>
        <meta name="robots" content="index, nofollow">
        <meta name="author" content="Tech1k">
        <title>LibreNode - XMR Node</title>
        <link rel="shortcut icon" href="assets/favicon.png?v=2"/>
		<link rel="stylesheet" href="assets/style.css?v=2">
	</head>
	<body>
    <div id="main">
            <center>
				<img src="assets/librexmr.png" style="max-width: 100%;">
            </center>
            <h2>LibreNode Monero Node</h2>
            <p>
                This Monero full node is hosted by <a href="https://librenode.com">LibreNode</a> and is offered freely to the community. You can connect via TCP/IP or via Tor — ideally Tor for enhanced privacy.
                <br/><br/>
                This service relies on donations to help offset hosting costs. If you've found it useful, please consider <a href="https://librenode.com/donate">supporting us</a>.
                <br/><br/>
                While we are committed to not logging your activity, we strongly encourage you to run your own Monero node whenever possible to enhance your privacy.
            </p>
            <h3>Connection Info</h3>
            <ul><img src="assets/web.png" width="25px" style="margin-right: 3px; vertical-align: middle;"><b>Clearnet:</b> <code>xmr.librenode.com:18089</code></ul>
            <ul><img src="assets/tor.png" width="24px" style="margin-right: 3px; vertical-align: middle;"><b>Tor:</b> <code style="word-break: break-word;">gw3ennwsaonltfox7z3rhhof6mxcq2fnwhcj2qyp3kxsfldnxix5b4yd.onion:18089</code></ul>
            <h3>Node Info</h3>
            <ul><b>Block height:</b> <code><?php echo $blockCount; ?></code></ul>
            <ul><b>Synchronized:</b> <code><?php echo $synchronized; ?></code></ul>
            <ul><b>DB Size:</b> <code><?php echo $databaseSize . " GB"; ?></code></ul>
            <br/>
		</div>
	</body>
</html>
