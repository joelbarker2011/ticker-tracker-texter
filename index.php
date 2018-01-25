<?php
$stocks = ['BND', 'VTI', 'VXUS', 'VCR', 'VFH', 'VGT', 'VHT'];
$stock = $stocks[array_rand($stocks)];

list($low, $high) = getBBands($stock);
$current = getCurrent($stock);

if ($current > $high) {
    $alert = "Sell $stock ($current > $high)";
} elseif ($current < $low) {
    $alert = "Buy $stock ($current < $low)";
} else {
    $alert = false;
}

if ($alert) {
    $response = sendAlert($alert);
    header('Content-Type: text/plain');
    echo $response;
}

// helper functions

function getBBands($stock) {
    $query = http_build_query([
        'function'      => 'BBANDS',
        'symbol'        => $stock,
        'interval'      => 'daily',
        'time_period'   => 21,
        'series_type'   => 'close',
        'nbdevup'       => 2,
        'nbdevdn'       => 1,
        'matype'        => 1,   // EMA
        'apikey'        => $_ENV['ALPHA_VANTAGE_API_KEY'],
    ]);

    $ch = curl_init('https://www.alphavantage.co/query?' . $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    $latest = reset($json['Technical Analysis: BBANDS']);   // get first value, efficiently

    return [$latest['Real Lower Band'], $latest['Real Upper Band']];
}

function getCurrent($stock) {
    $interval = '15min';
    $query = http_build_query([
        'function'      => 'TIME_SERIES_INTRADAY',
        'symbol'        => $stock,
        'interval'      => $interval,
        'apikey'        => $_ENV['ALPHA_VANTAGE_API_KEY'],
    ]);

    $ch = curl_init('https://www.alphavantage.co/query?' . $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    $latest = reset($json["Time Series ($interval)"]);  // get first value, efficiently

    return $latest['4. close'];
}

function sendAlert($alert) {
    $ch = curl_init($_ENV['TILL_URL']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'phone' => [$_ENV['RECIPIENT_PHONE']],
        'text'  => $alert,
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

